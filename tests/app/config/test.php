<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:33
 */

return \yii\helpers\ArrayHelper::merge(require('common.php'),
    [
        'id' => 'tests',
        'controllerNamespace' => 'elfuvo\\import\\tests\\app\\controllers',
        'viewPath' => '@app/views',
        'defaultRoute' => 'default/upload-file-import',
        'name' => 'Import wizard',
        'timeZone' => 'UTC',
        'language' => 'ru',
        'basePath' => dirname(__DIR__),
        'aliases' => [
            '@tests' => '@root/tests',
            '@public' => '@app/web',
        ],
        'container' => [
            'singletons' => [],
            'definitions' => [],
        ],
        'modules' => [],
        'components' => [
            'session' => [
                'class' => \yii\web\CacheSession::class,
                'timeout' => 24 * 60 * 60,
                'cache' => [
                    'class' => yii\caching\FileCache::class,
                    'keyPrefix' => hash('crc32', __LINE__),
                ],
            ],
            'urlManager' => [
                'class' => \yii\web\UrlManager::class,
                'enablePrettyUrl' => true,
                'showScriptName' => false,
                'normalizer' => [
                    'class' => \yii\web\UrlNormalizer::class,
                ],
                'rules' => [
                    '<controller:[\w\-]+>/<action:[\w\-]+>' => '<controller>/<action>',
                ],
                'baseUrl' => '/',
            ],
            'errorHandler' => [
                'class' => \yii\web\ErrorHandler::class,
                'errorAction' => 'default/error',
            ],
        ]
    ]
);
