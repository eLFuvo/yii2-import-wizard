<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

namespace elfuvo\import\actions;

use elfuvo\import\services\ImportServiceInterface;
use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\web\Controller;

/**
 * Class ProgressAction
 * @package elfuvo\import\actions
 */
class ProgressAction extends Action
{
    /**
     * @var string
     */
    public $view = '@vendor/elfuvo/yii2-import-wizard/src/views/progress';

    /**
     * @var Model
     */
    public $model;

    /**
     * @var ImportServiceInterface
     */
    protected $service;

    /**
     * ProgressAction constructor.
     * @param string $id
     * @param Controller $controller
     * @param ImportServiceInterface $service
     * @param array $config
     */
    public function __construct(
        string $id,
        Controller $controller,
        ImportServiceInterface $service,
        array $config = []
    ) {
        $this->service = $service;

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
     * @throws \yii\base\InvalidConfigException
     */
    public function run()
    {
        $this->service->setModel($this->model)
            ->getResult()->getLastBatch();

        if (Yii::$app->request->getIsAjax()) {
            return $this->controller->renderPartial(
                $this->view,
                [
                    'result' => $this->service->getResult(),
                ]
            );
        }

        return $this->controller->render(
            $this->view,
            [
                'result' => $this->service->getResult(),
            ]
        );
    }
}
