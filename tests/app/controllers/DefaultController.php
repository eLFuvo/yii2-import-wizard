<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:33
 */

namespace elfuvo\import\tests\app\controllers;

use elfuvo\import\actions\ProgressAction;
use elfuvo\import\actions\SetupAction;
use elfuvo\import\actions\UploadFileAction;
use elfuvo\import\tests\app\models\Review;
use yii\web\Controller;
use yii\web\ErrorAction;

/**
 * Class DefaultController
 * @package elfuvo\import\tests\controllers
 */
class DefaultController extends Controller
{
    public $layout = '@app/views/layouts/main';

    /**
     * @return array
     */
    public function actions()
    {
        return [
            'upload-file-import' => [
                'class' => UploadFileAction::class,
                'model' => new Review(),
                'view' => '@root/src/views/upload-file',
                'nextAction' => 'setup-import',
                'progressAction' => 'progress',
            ],
            'setup-import' => [
                'class' => SetupAction::class,
                'view' => '@root/src/views/setup',
                'model' => new Review([
                    'hidden' => Review::HIDDEN_YES,
                ]),
                'scenario' => Review::SCENARIO_DEFAULT,
                'previousAction' => 'upload-file-import',
                'excludeAttributes' => [
                    'id',
                    'language',
                    'createdAt',
                    'updatedAt'
                ]
            ],
            'progress' => [
                'class' => ProgressAction::class,
                'model' => new Review(),
                'view' => '@root/src/views/progress',
            ],
            'error' => [
                'class' => ErrorAction::class,
                'view' => '@app/views/default/error',
            ]
        ];
    }
}
