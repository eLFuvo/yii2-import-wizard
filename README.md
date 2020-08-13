Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist elfuvo/yii2-import "~0.0.2"
```

or add

```
"elfuvo/yii2-import": "~0.0.2"
```

to the require section of your `composer.json` file.

Configure
---------

Настройте желаемый вариант хранения результата импорта и доступные адаптеры импорта в
common.php

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

Настройте actions в контролере бэкенда:

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
                    'serviceId',
                    'stationId',
                    'authorPhoto',
                    'language',
                    'createdBy',
                    'createdAt',
                    'updatedAt'
                ]
            ],
        ];
    }
```

Необходимо тщательно проработать валидацию модели, т.к. плохая валидация 
может привести к вставке неправильных данных (например дата из Excel не может 
быть вставлена как дата в MySql) и ошибкам вставки данных в БД.
Также правила валидации устанавливают автоматически тип конвертации данных 
импорта в значение атрибута модели. 

Добавьте права в менджер доступов console.php

```php
                [
                    'name' => 'review',
                    'controllers' => [
                        'default' => [
                            'index',
                            'create',
                            'update',
                            'delete',
                            'view',
                            'upload-file-import',
                            'setup-import',
                        ],
                    ],
                ],
```

Добавьте кнопку-ссылку импорта в index вид:

```html
    <div class="card-header">
        <p>
            <?= Html::a(Yii::t('system', 'Create'), ['create'], [
                'class' => 'btn btn-success'
            ]) ?>
            <?= Html::a('Импортировать из файла', ['upload-file-import'], [
                'class' => 'btn btn-primary'
            ]) ?>
        </p>
    </div>
```

Yii2 queue must be configured for pushing ImportJob.

