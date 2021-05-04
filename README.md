[![Latest Stable Version](https://img.shields.io/github/v/release/elfuvo/yii2-import-wizard.svg)](https://packagist.org/packages/elfuvo/yii2-import-wizard) 
[![Build](https://img.shields.io/github/workflow/status/elfuvo/yii2-import-wizard/Build.svg)](https://github.com/elfuvo/yii2-import-wizard)
[![Total Downloads](https://img.shields.io/github/downloads/elfuvo/yii2-import-wizard/total.svg)](https://packagist.org/packages/elfuvo/yii2-import-wizard)
[![License](https://img.shields.io/github/license/elfuvo/yii2-import-wizard.svg)](https://github.com/elfuvo/yii2-import-wizard/blob/master/LICENSE)
[![Yii2](https://img.shields.io/badge/Powered_by-Yii_Framework-green.svg?style=flat)](http://www.yiiframework.com/)

Requirements
------------

* PHP >=7.1

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist elfuvo/yii2-import-wizard "~0.1.0"
```

or add

```
"elfuvo/yii2-import-wizard": "~0.1.0"
```

to the "require" section of your `composer.json` file.

Configure
---------

Configure desired storage option for the import result and the available import adapters

```php
// in common app config
[
'container'=>[
    'definitions' => [
                \elfuvo\import\result\ResultImportInterface::class =>
                    \elfuvo\import\result\CacheContinuesResultImport::class,
                \elfuvo\import\adapter\AdapterFabricInterface::class => [
                    'class' => \elfuvo\import\adapter\AdapterFabricDefault::class,
                    'adapters' => [
                        \elfuvo\import\adapter\AdapterImportExcel::class,
                        \elfuvo\import\adapter\AdapterImportCsv::class,
                    ]
                ],    
        ],
    ],
];
```

Add translations to i18n yii component:

```php
[
    'components' => [
        'i18n' => [
                'class' => \yii\i18n\I18N::class,
                'translations' => [
                    'import-wizard' => [
                        'class' => \yii\i18n\PhpMessageSource::class,
                        'sourceLanguage' => 'en',
                        'basePath' => '@vendor/elfuvo/yii2-import/src/messages',
                    ],
                ],
        ]
    ]
];
```


Add import steps actions to the controller:

```php
    /**
     * @return array
     */
    public function actions()
    {
        
        return [
            'upload-file-import' => [
                'class' => \elfuvo\import\actions\UploadFileAction::class,
                'model' => new Review(), // model instance for import
                'nextAction' => 'setup-import', // next action name
                'progressAction' => 'progress', // name of progress action
            ],
            'setup-import' => [
                'class' => \elfuvo\import\actions\SetupAction::class,
                // model instance with predefined attribute values. It will be cloned in import service.
                /*'model' => new Review([ 
                      'hidden' => Nko::HIDDEN_NO,
                      'language' => Yii::$app->language,
                      'createdBy' => Yii::$app->user->getId(),
                ])*/
                // can be callable function
                'model' => function(){ 
                    $importModel = new Review([
                        'hidden' => Review::HIDDEN_NO,
                        'language' => Yii::$app->language,
                        'createdBy' => Yii::$app->user->getId(),
                    ]);
                    // some behaviors does not works in console app
                    // there we can disable them 
                    $importModel->detachBehavior('LanguageBehavior');
                    $importModel->detachBehavior('CreatedByBehavior');
                    
                    return $importModel;
                },                     
                'scenario' => Review::SCENARIO_DEFAULT, // scenario of model validation when saving model from import
                'previousAction' => 'upload-file-import', // previous action name
                'excludeAttributes' => [ // exclude model attributes for building import map
                    'id',
                    'language',
                    'createdBy',
                    'createdAt',
                    'updatedAt'
                ]
            ],
            'progress' => [ // action for showing current import progress/statistic and errors after import is done
                'class' => \elfuvo\import\actions\ProgressAction::class,
                'model' => new Review(),
            ],
        ];
    }
```

Add import link button into the view:

```php
    <?= Html::a(Yii::t('import-wizard', 'Import models from Excel file'), ['upload-file-import'], [
         'class' => 'btn btn-primary'
    ]) ?>
```


It is necessary to carefully consider validation of the model, 
as bad validation may lead to incorrect data insertion 
(for example: a date from Excel cannot be inserted as a date in MySql) and errors when inserting data into the database.
Also, the validation rules set automatically type of conversion of import data to the value of the model attribute.

```php
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
                [['rating'], 'double', 'min' => 1, 'max' => 5], // will add float converter in import wizard
                [['publishAt'], 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'], // will add date converter in import wizard
        ];
    }

```

Important! Import file must have column(s) with unique (identity) values for updating existing models.

Yii2 queue component must be configured for executing ImportJob.

Screenshots
------------
![Step 1](resources/upload-file.png "step 1 - upload file for import")
![Step 2](resources/import-setup.png "step 2 - setup import map")
![Step 3](resources/progress.png "step 3 - wait until import done")
