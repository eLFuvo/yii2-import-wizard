<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:33
 */

return [
    'id' => 'tests',
    'controllerNamespace' => 'elfuvo\\import\\tests\\app\\controllers',
    'viewPath' => '@app/views',
    'defaultRoute' => 'default/upload-file-import',
    'name' => 'Import wizard',
    'timeZone' => 'UTC',
    'language' => 'ru',
    'basePath' => dirname(dirname(__DIR__)),
    'aliases' => [
        '@root' => dirname(dirname(dirname(__DIR__))),
        '@vendor' => '@root/vendor',
        '@bower' => '@vendor/bower-asset',
        '@app' => '@root/tests/app',
        '@runtime' => '@app/runtime',
    ],
    'container' => [
        'singletons' => [],
        'definitions' => [
            \elfuvo\import\result\ResultImportInterface::class =>
                \elfuvo\import\result\FileContinuesResultImport::class,
            \elfuvo\import\adapter\AdapterFabricInterface::class => [
                'class' => \elfuvo\import\adapter\AdapterFabricDefault::class,
                'adapters' => [
                    \elfuvo\import\adapter\AdapterImportExcel::class,
                    \elfuvo\import\adapter\AdapterImportCsv::class,
                ]
            ],
            \yii\web\Request::class => [
                'class' => \yii\web\Request::class,
                'enableCookieValidation' => false,
                'enableCsrfValidation' => false,
            ]
        ],
    ],
    'modules' => [],
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
            'keyPrefix' => 'import-wizard',
        ],
        'queue' => [
            'class' => \yii\queue\file\Queue::class,
            'path' => '@runtime/queue',
        ],
        'i18n' => [
            'class' => \yii\i18n\I18N::class,
            'translations' => [
                'import-wizard' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'sourceLanguage' => 'en',
                    'basePath' => '@app/messages',
                ],
            ],
        ],
    ]
];
