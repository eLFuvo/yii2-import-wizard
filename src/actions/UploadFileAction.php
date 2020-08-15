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

use elfuvo\import\adapter\AdapterFabricInterface;
use elfuvo\import\forms\UploadForm;
use elfuvo\import\ImportService;
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
    public $view = '@elfuvo/yii2-import-wizard/views/upload-file';

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
     * @var ImportService
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
     * @param ImportService $service
     * @param AdapterFabricInterface $fabric
     * @param array $config
     */
    public function __construct(
        string $id,
        Controller $controller,
        ImportService $service,
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

            if ($uploadForm->validate()) {
                if ($this->service->uploadImportFile($uploadForm->file, $this->model)) {
                    return $this->controller->redirect([$this->nextAction]);
                }
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
}
