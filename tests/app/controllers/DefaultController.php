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
use elfuvo\import\models\MapAttribute;
use elfuvo\import\services\BracketValueCaster;
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
            // action with predefined map
            'upload-file-import-map' => [
                'class' => UploadFileAction::class,
                'model' => new Review([
                    'language' => 'ru',
                    'hidden' => Review::HIDDEN_NO,
                ]),
                'view' => '@root/src/views/upload-file',
                'startRowIndex' => 2,
                'attributeMap' => [
                    new MapAttribute([
                        'column' => 'A',
                        'attribute' => 'b24StationId',
                        'castTo' => BracketValueCaster::class,
                    ]),
                    new MapAttribute([
                        'column' => 'B',
                        'attribute' => 'title',
                        'castTo' => MapAttribute::TYPE_STRING,
                        'identity' => 1,
                    ]),
                    new MapAttribute([
                        'column' => 'C',
                        'attribute' => 'author',
                        'castTo' => MapAttribute::TYPE_STRING,
                    ]),
                    new MapAttribute([
                        'column' => 'D',
                        'attribute' => 'text',
                        'castTo' => MapAttribute::TYPE_STRING,
                    ]),
                    new MapAttribute([
                        'column' => 'E',
                        'attribute' => 'rating',
                        'castTo' => MapAttribute::TYPE_FLOAT,
                    ]),
                    new MapAttribute([
                        'column' => 'F',
                        'attribute' => 'publishAt',
                        'castTo' => MapAttribute::TYPE_DATETIME,
                    ]),
                ],
                'nextAction' => 'setup-import',
                'progressAction' => 'progress',
            ],
            'setup-import' => [
                'class' => SetupAction::class,
                'view' => '@root/src/views/setup',
                'model' => new Review([
                    'language' => 'ru',
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
