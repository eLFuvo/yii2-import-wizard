<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-10-29
 * Time: 11:36
 */

namespace elfuvo\import\services;

use elfuvo\import\adapter\AdapterImportExcel;
use elfuvo\import\adapter\AdapterImportInterface;
use elfuvo\import\exception\AdapterImportException;
use elfuvo\import\result\ResultImportInterface;
use Yii;
use yii\base\BaseObject;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;
use elfuvo\import\models\MapAttribute;

/**
 * Class ImportService
 * @package elfuvo\import
 *
 * @property-read string[] $customCasters
 * @property-read string $uploadedImportFile
 */
class ImportService extends BaseObject implements ImportServiceInterface
{
    /**
     * @var string
     */
    public $tmpPath = '@runtime/import';

    /**
     * @var string[] - list of custom value casters
     */
    public $casters = [];

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
    public function setAdapter(AdapterImportInterface $adapter): ImportServiceInterface
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * @param MapAttribute[] $map
     * @return ImportService
     * @throws \yii\base\InvalidConfigException
     */
    public function setMap(array $map): ImportServiceInterface
    {
        $this->map = $this->normalizeAttributeMap($map);
        $this->getResult()->setMap($map);

        return $this;
    }

    /**
     * @param array $attributeMap
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    protected function normalizeAttributeMap(array $attributeMap): array
    {
        $list = [];
        foreach ($attributeMap as $index => $item) {
            if (!$item instanceof MapAttribute) {
                throw new InvalidConfigException('Element of attribute map must be instance of ' . MapAttribute::class);
            }
            if ($item->column) {
                $list[$item->column] = $item;
            } else {
                $list[$index] = $item;
            }
        }

        return $list;
    }

    /**
     * @param Model $model
     * @return $this
     * @throws \yii\base\InvalidConfigException
     */
    public function setModel(Model $model): ImportServiceInterface
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
    public function setValidationScenario(string $scenario): ImportServiceInterface
    {
        $this->validationScenario = $scenario;

        return $this;
    }

    /**
     * @param \yii\web\UploadedFile $file
     * @param \yii\base\Model $model
     * @return bool
     * @throws \yii\base\Exception
     */
    public function uploadImportFile(UploadedFile $file, Model $model): bool
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
    public function getUploadedImportFile(): string
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
     * @inheritDoc
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
     * @inheritDoc
     * @throws \yii\db\Exception
     */
    public function importBatch(): bool
    {
        if (!$this->adapter) {
            throw new InvalidConfigException('Adapter must be set');
        }
        if (!$this->model instanceof ActiveRecord && !YII_ENV_TEST) {
            throw new InvalidConfigException('Model must be instance of ' . ActiveRecord::class);
        }

        // set progress data from last result batch
        if ($progress = $this->getResult()->getLastBatch()) {
            $this->adapter->setProgress($progress);
        }
        $behaviors = array_keys($this->model->getBehaviors());
        if ($rows = $this->adapter->getBatchData()) {
            $transaction = null;
            if (!YII_ENV_TEST) {
                $transaction = Yii::$app->db->beginTransaction();
            }
            foreach ($rows as $row) {
                if (empty($row)) {
                    $this->getResult()->addCount(ResultImportInterface::SKIP_COUNTER);
                    $this->getResult()->increaseProgressDone();
                    continue;
                }

                $existsModelConditions = [];
                /** @var Model|ActiveRecord $model */
                $model = clone $this->model;
                // some behaviors can be detached for the original model
                // so detach it for cloned model
                foreach ($model->getBehaviors() as $behavior => $config) {
                    if (!in_array($behavior, $behaviors)) {
                        $model->detachBehavior($behavior);
                    }
                }
                $model->setScenario($this->validationScenario);
                foreach ($row as $column => $value) {
                    $mapAttributeModel = $this->map[$column] ?? new MapAttribute();

                    if ($mapAttributeModel->attribute) {
                        $mapAttributeModel->setValue($model, $value); // set model attributes with value casting
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
            if ($transaction) {
                $transaction->commit();
            }
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
            // all batches done, but not all rows is processed
            // this happens when additional styles (validators) are assigned to cells, but there is no data in them
            if ($this->getResult()->getProgressDone() < $this->getResult()->getProgressTotal()) {
                $skipped = $this->getResult()->getProgressTotal() - $this->getResult()->getProgressDone();
                $this->getResult()->addCount(ResultImportInterface::SKIP_COUNTER, $skipped);
                $this->getResult()->setProgressDone($this->getResult()->getProgressTotal());
                $this->getResult()->setBatch($this->adapter->getProgress());
            }
        }

        return false;
    }

    /**
     * @return \elfuvo\import\result\ResultImportInterface
     * @throws \yii\base\InvalidConfigException
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

    /**
     * @return array
     */
    public function getCustomCasters(): array
    {
        return $this->casters;
    }
}
