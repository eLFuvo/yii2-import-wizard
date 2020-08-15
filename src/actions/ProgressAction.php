<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

namespace elfuvo\import\actions;

use elfuvo\import\ImportService;
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
    public $view = '@elfuvo/yii2-import-wizard/src/views/progress';

    /**
     * @var Model
     */
    public $model;

    /**
     * @var ImportService
     */
    protected $service;

    /**
     * ProgressAction constructor.
     * @param string $id
     * @param Controller $controller
     * @param ImportService $service
     * @param array $config
     */
    public function __construct(
        string $id,
        Controller $controller,
        ImportService $service,
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
