![Build](https://github.com/eLFuvo/yii2-import-wizard/workflows/Build/badge.svg)

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

to the require section of your `composer.json` file.

Configure
---------

Configure the desired storage option for the import result and the available import adapters

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


Add the import steps actions to the controller:

```php
    /**
     * @return array
     */
    public function actions()
    {
        return [
            'upload-file-import' => [
                'class' => UploadFileImportAction::class,
                'model' => new Review(), // model instance for import
                'nextAction' => 'setup-import', // next action name
                'progressAction' => 'progress', // name of progress action
            ],
            'setup-import' => [
                'class' => SetupImportAction::class,
                'model' => new Review([ // model instance with predefined attribute values. It will be cloned in import service.
                    'hidden' => Review::HIDDEN_NO,
                    'createdBy' => Yii::$app->user->getId(),
                ]),
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
                'class' => ProgressAction::class,
                'model' => new Review(),
            ],
        ];
    }
```

Add the import link button into the view:

```php
    <?= Html::a('Upload Excel file for import', ['upload-file-import'], [
         'class' => 'btn btn-primary'
    ]) ?>
```


It is necessary to carefully consider the validation of the model, 
as bad validation may lead to incorrect data insertion 
(for example: a date from Excel cannot be inserted as a date in MySql) and errors when inserting data into the database.
Also, the validation rules set automatically the type of conversion of import data to the value of the model attribute.

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

