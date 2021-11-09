<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

/**
 * Created by PhpStorm.
 * User: elfuvo
 * Date: 26.04.19
 * Time: 14:04
 */

namespace elfuvo\import\actions;

use Closure;
use elfuvo\import\adapter\AdapterFabricInterface;
use elfuvo\import\forms\UploadForm;
use elfuvo\import\ImportJob;
use elfuvo\import\services\ImportServiceInterface;
use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\validators\FileValidator;
use yii\web\Controller;
use yii\web\UploadedFile;

/**
 * Class UploadFileAction
 * @package elfuvo\import\actions
 */
class UploadFileAction extends Action
{
    /**
     * @var string
     */
    public $view = '@vendor/elfuvo/yii2-import-wizard/src/views/upload-file';

    /**
     * @var string
     */
    public $nextAction = 'setup-import';

    /**
     * @var string
     */
    public $progressAction = 'progress';

    /**
     * @var Model
     */
    public $model;

    /**
     * @var null|array|\Closure
     */
    public $attributeMap = null;

    /**
     * @var int
     */
    public $startRowIndex = 2;

    /**
     * @var ImportServiceInterface
     */
    protected $service;

    /**
     * @var AdapterFabricInterface
     */
    protected $fabric;

    /**
     * UploadFileAction constructor.
     * @param string $id
     * @param Controller $controller
     * @param ImportServiceInterface $service
     * @param AdapterFabricInterface $fabric
     * @param array $config
     */
    public function __construct(
        string $id,
        Controller $controller,
        ImportServiceInterface $service,
        AdapterFabricInterface $fabric,
        array $config = []
    ) {
        $this->service = $service;
        $this->fabric = $fabric;

        parent::__construct($id, $controller, $config);
    }

    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();

        if (!$this->model) {
            throw new InvalidConfigException('Model property must be set');
        }

        if (!$this->model instanceof Model) {
            throw new InvalidConfigException('Model must be instance of ' . Model::class);
        }
    }

    /**
     * @return string|\yii\web\Response
     * @throws \yii\base\Exception
     * @throws \elfuvo\import\exception\AdapterImportException
     */
    public function run()
    {
        $this->service->setModel($this->model)
            ->getResult()->getLastBatch();
        $uploadForm = new UploadForm();

        $extensions = $this->fabric->getFileImportExtensions();
        // add extensions filter
        foreach ($uploadForm->getValidators() as $validator) {
            if ($validator instanceof FileValidator) {
                $validator->extensions = array_map(function ($extension) {
                    return trim($extension, '.');
                }, $extensions);
                $validator->checkExtensionByMimeType = false;
                break;
            }
        }

        if (Yii::$app->request->getIsPost()) {
            $uploadForm->file = UploadedFile::getInstance($uploadForm, 'file');

            if ($uploadForm->validate() && $this->service->uploadImportFile($uploadForm->file, $this->model)) {
                $attributeMap = null;
                if ($this->attributeMap instanceof Closure) {
                    $adapter = $this->fabric->create($this->service->getUploadedImportFile());
                    $attributeMap = call_user_func($this->attributeMap, $adapter->getHeaderData());
                } elseif (is_array($this->attributeMap)) {
                    $attributeMap = $this->attributeMap;
                }

                if (!empty($attributeMap) && $this->startImport($attributeMap)) {
                    return $this->controller->redirect([$this->id]);
                }

                return $this->controller->redirect([$this->nextAction]);
            }
        }

        return $this->controller->render(
            $this->view,
            [
                'model' => $this->model,
                'uploadForm' => $uploadForm,
                'extensions' => $extensions,
                'progressAction' => $this->progressAction,
            ]
        );
    }

    /**
     * @param \elfuvo\import\models\MapAttribute[] $attributeMap
     * @throws \elfuvo\import\exception\AdapterImportException
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\base\Exception
     */
    protected function startImport(array $attributeMap): bool
    {
        $adapter = $this->fabric->create($this->service->getUploadedImportFile());
        $this->service->setMap($attributeMap)->setAdapter($adapter);
        // reset previous result data
        $this->service->getResult()->resetBatch();
        // set total rows
        $this->service->getResult()->setProgressTotal($adapter->getTotalRows());

        $adapter->setStartRowIndex((int)$this->startRowIndex);
        $this->service->getResult()->setProgressDone($adapter->getStartRowIndex());
        // save statistic: total/done rows
        $this->service->getResult()->setBatch(null);

        if (Yii::$app->has('queue')) {
            /** @var \yii\queue\JobInterface $importJob */
            $importJob = Yii::createObject([
                'class' => ImportJob::class,
                'adapter' => $adapter,
                'mapAttribute' => $attributeMap,
                'modelClass' => get_class($this->model),
                'modelAttributes' => $this->model->toArray(),
            ]);
            Yii::$app->get('queue')->push($importJob);
        } else {
            $this->service->import();
        }

        return true;
    }
}
