Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist elfuvo/yii2-import "~0.0.1"
```

or add

```
"elfuvo/yii2-import-wizard": "~0.0.1"
```

to the require section of your `composer.json` file.

Configure
---------

Configure the desired storage option for the import result and the available import adapters

```php
'definitions' => [
...
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
        ];
    }
```

Add the import link button to the view:

```php
    <?= Html::a('Upload Excel file for import', ['upload-file-import'], [
         'class' => 'btn btn-primary'
    ]) ?>
```


It is necessary to carefully consider the validation of the model, 
as bad validation may lead to incorrect data insertion 
(for example: a date from Excel cannot be inserted as a date in MySql) and errors when inserting data into the database.
Also, the validation rules set automatically the type of conversion of import data to the value of the model attribute.

Yii2 queue component must be configured for executing ImportJob.

