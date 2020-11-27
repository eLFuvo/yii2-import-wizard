<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

namespace elfuvo\import;

use elfuvo\import\adapter\AdapterImportInterface;
use elfuvo\import\result\ResultImportInterface;
use Yii;
use yii\base\BaseObject;
use yii\base\Exception;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

/**
 * Class ImportService
 * @package elfuvo\import
 */
class ImportService extends BaseObject
{
    /**
     * @var string
     */
    public $tmpPath = '@runtime/import';

    /**
     * @var AdapterImportInterface
     */
    protected $adapter;

    /**
     * @var ResultImportInterface
     */
    protected $result;

    /**
     * @var MapAttribute[]
     */
    protected $map;

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var string
     */
    protected $validationScenario = Model::SCENARIO_DEFAULT;

    /**
     * ImportService constructor.
     * @param ResultImportInterface $result
     * @param array $config
     */
    public function __construct(ResultImportInterface $result, array $config = [])
    {
        $this->result = $result;

        parent::__construct($config);
    }

    /**
     * @param AdapterImportInterface $adapter
     * @return ImportService
     */
    public function setAdapter(AdapterImportInterface $adapter): self
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * @param MapAttribute[] $map
     * @return ImportService
     */
    public function setMap(array $map): self
    {
        $this->map = $map;
        $this->getResult()->setMap($map);

        return $this;
    }

    /**
     * @param Model $model
     * @return ImportService
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;

        // set unique key for result
        $class = explode('\\', get_class($model));
        $key = array_pop($class);
        $this->getResult()->setKey($key);

        return $this;
    }

    /**
     * @param string $scenario
     * @return ImportService
     */
    public function setValidationScenario(string $scenario): self
    {
        $this->validationScenario = $scenario;

        return $this;
    }

    /**
     * @param UploadedFile $file
     * @param Model $model
     * @return bool
     */
    public function uploadImportFile(UploadedFile $file, Model $model)
    {
        $class = explode('\\', get_class($model));
        $path = Yii::getAlias($this->tmpPath);
        if (!is_dir($path)) {
            FileHelper::createDirectory($path);
        }
        $className = array_pop($class);
        // delete previous import files
        $oldFiles = glob($path . '/' . $className . '.*');
        if ($oldFiles) {
            foreach ($oldFiles as $oldFile) {
                @unlink($oldFile);
            }
        }

        return $file->saveAs($path . '/' . $className . '.' . $file->getExtension());
    }

    /**
     * @return string
     */
    public function getUploadedImportFile()
    {
        $class = explode('\\', get_class($this->model));
        $path = Yii::getAlias($this->tmpPath);
        $className = array_pop($class);
        $importFiles = glob($path . '/' . $className . '.*');
        if ($importFiles) {
            return array_shift($importFiles);
        }
        return '';
    }

    /**
     * @return ResultImportInterface
     */
    public function import(): ResultImportInterface
    {
        $counter = 0;
        while ($this->importBatch()) {
            $counter++;
        }

        return $this->result;
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function importBatch(): bool
    {
        if (!$this->adapter) {
            throw new Exception('Adapter must be set');
        }
        if (!$this->model instanceof ActiveRecord && !YII_ENV_TEST) {
            throw new Exception('Model must be instance of ' . ActiveRecord::class);
        }

        // set progress data from last result batch
        if ($progress = $this->getResult()->getLastBatch()) {
            $this->adapter->setProgress($progress);
        }
        if ($rows = $this->adapter->getBatchData()) {
            $transaction = Yii::$app->db->beginTransaction();
            foreach ($rows as $row) {
                $existsModelConditions = [];
                /** @var Model|ActiveRecord $model */
                $model = clone $this->model;
                // some behaviors can be detached for the original model
                $behaviors = $this->model->getBehaviors();
                foreach ($model->getBehaviors() as $behavior => $config) {
                    if (!in_array($behavior, $behaviors)) {
                        $model->detachBehavior($behavior);
                    }
                }
                $model->setScenario($this->validationScenario);
                foreach ($row as $column => $value) {
                    $mapAttributeModel = $this->map[$column] ?? new MapAttribute();

                    if ($mapAttributeModel->attribute) {
                        $mapAttributeModel->setValue($model, $value);
                        // if attribute is identity
                        if ($mapAttributeModel->isIdentity()) {
                            // get attribute after value casting
                            $existsModelConditions[$mapAttributeModel->attribute] =
                                current($model->getAttributes([$mapAttributeModel->attribute]));
                        }
                    }
                }
                $updateModel = false;
                if ($existsModelConditions) {
                    $existModel = $model::findOne($existsModelConditions);
                    if ($existModel) {
                        $updateModel = true;
                        $existModel->setAttributes($model->getAttributes());
                        $model = $existModel;
                        $existModel = $existsModelConditions = null;
                    }
                }
                try {
                    if ($model->save()) {
                        $this->getResult()->addCount($updateModel ?
                            ResultImportInterface::ADD_COUNTER : ResultImportInterface::UPDATE_COUNTER);
                    } else {
                        $this->getResult()->addError(implode('; ', $model->getErrorSummary(true)));
                        $this->getResult()->addCount(ResultImportInterface::SKIP_COUNTER);
                    }
                } catch (\Exception $e) {
                    $this->getResult()->addError($e->getMessage());
                }

                // unset model, free some memory
                $model = $mapAttributeModel = $existsModelConditions = null;
                $this->getResult()->increaseProgressDone();
            }
            $transaction->commit();
            $transaction = null;
            $rows = null;

            if ($this->adapter->isDone()) {
                // we processed all rows in import file
                $this->getResult()->setProgressDone($this->getResult()->getProgressTotal());
            }

            $this->getResult()->setBatch($this->adapter->getProgress());
            $this->result = null;

            return true;
        } else {
            // something is wrong...
            $this->getResult()->setProgressDone($this->getResult()->getProgressTotal());
        }

        return false;
    }

    /**
     * @return ResultImportInterface
     */
    public function getResult(): ResultImportInterface
    {
        if (!$this->result) {
            $this->result = Yii::createObject(ResultImportInterface::class);
            // set unique key for result
            $class = explode('\\', get_class($this->model));
            $key = array_pop($class);
            $this->result->setKey($key);
            $this->result->setMap($this->map);
        }

        return $this->result;
    }
}
