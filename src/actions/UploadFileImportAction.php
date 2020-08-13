<?php
/**
 * Created by PhpStorm.
 * User: elfuvo
 * Date: 26.04.19
 * Time: 14:04
 */

namespace elfuvo\import\actions;

use elfuvo\import\adapter\AdapterFabricInterface;
use elfuvo\import\ImportService;
use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\web\Controller;
use yii\web\UploadedFile;

/**
 * Class UploadFileAction
 * @package elfuvo\import\actions
 */
class UploadFileImportAction extends Action
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

        if (Yii::$app->request->getIsAjax()) {

            $viewPath = preg_replace('#\/([^\/]+)$#', '', $this->view);

            return $this->controller->renderPartial(
                $viewPath . '/_import_stat',
                [
                    'result' => $this->service->getResult(),
                ]
            );
        } elseif (Yii::$app->request->post()) {
            $file = UploadedFile::getInstanceByName('importFile');
            if ($file && $file->tempName) {
                if ($this->service->uploadImportFile($file, $this->model)) {
                    return $this->controller->redirect([$this->nextAction]);
                }
            }
        }

        $action = $this->controller->action->uniqueId;

        return $this->controller->render(
            $this->view,
            [
                'result' => $this->service->getResult(),
                'model' => $this->model,
                'fabric' => $this->fabric,
                'action' => $action,
            ]
        );
    }
}
