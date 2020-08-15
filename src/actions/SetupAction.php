<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

/**
 * Created by PhpStorm.
 * User: elfuvo
 * Date: 26.04.19
 * Time: 14:04
 */

namespace elfuvo\import\actions;

use elfuvo\import\adapter\AdapterFabricInterface;
use elfuvo\import\exception\AdapterImportException;
use elfuvo\import\ImportJob;
use elfuvo\import\ImportService;
use elfuvo\import\MapAttribute;
use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\base\Model;
use yii\web\Controller;

/**
 * Class SetupImportAction
 * @package elfuvo\import\actions
 */
class SetupAction extends Action
{
    /**
     * @var string
     */
    public $view = '@vendor/elfuvo/yii2-import-wizard/src/views/setup';

    /**
     * @var Model
     */
    public $model;

    /**
     * @var string
     */
    public $scenario = Model::SCENARIO_DEFAULT;

    /**
     * @var array
     */
    public $excludeAttributes = ['id', 'language', 'createdBy', 'createdAt', 'updatedAt'];

    /**
     * @var string
     */
    public $previousAction = 'upload-file-import';

    /**
     * @var ImportService
     */
    protected $service;

    /**
     * @var AdapterFabricInterface
     */
    protected $fabric;

    /**
     * SetupImportAction constructor.
     * @param string $id
     * @param Controller $controller
     * @param ImportService $service
     * @param AdapterFabricInterface $fabric
     * @param array $config
     */
    public function __construct(
        string $id,
        Controller $controller,
        ImportService $service,
        AdapterFabricInterface $fabric,
        array $config = []
    ) {
        $this->service = $service;
        $this->fabric = $fabric;

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
     * @throws AdapterImportException
     */
    public function run()
    {
        $this->service->setModel($this->model)
            ->setValidationScenario($this->scenario)
            ->getResult()->getLastBatch(); // load last result

        $importFile = $this->service->getUploadedImportFile();
        if ($importFile) {
            $adapter = $this->fabric->create($importFile);
        } else {
            throw new AdapterImportException('No file for import');
        }

        $mapAttribute = [];
        $previousMap = $this->service->getResult()->getMap();
        $header = $adapter->getHeaderData();
        if (count($previousMap) != count($header)) {
            $previousMap = [];
        }
        foreach ($header as $column => $value) {
            $mapAttribute[$column] = $previousMap[$column] ?? new MapAttribute();
        }

        if (Yii::$app->request->post()) {
            // reset previous result data
            $this->service->getResult()->resetBatch();
            // set total rows
            $this->service->getResult()->setProgressTotal($adapter->getTotalRows());

            $adapter->setStartRowIndex((int)Yii::$app->request->post('startRowIndex', 2));
            $this->service->getResult()->setProgressDone($adapter->getStartRowIndex());
            if (Model::loadMultiple($mapAttribute, Yii::$app->request->post()) &&
                Model::validateMultiple($mapAttribute)) {
                $mapAttribute = array_filter($mapAttribute, function (MapAttribute $attribute) {
                    return !empty($attribute->attribute) && $attribute->attribute != MapAttribute::IGNORE_COLUMN;
                });

                $this->service->setMap($mapAttribute)
                    ->setAdapter($adapter);
                // save statistic: total/done rows
                $this->service->getResult()->setBatch(null);

                // $this->service->import();

                $importJob = Yii::createObject([
                    'class' => ImportJob::class,
                    'adapter' => $adapter,
                    'mapAttribute' => $mapAttribute,
                    'model' => $this->model,
                ]);
                Yii::$app->queue->push($importJob);

                return $this->controller->redirect([$this->previousAction]);
            }
        }

        $attributes = ['' => '', MapAttribute::IGNORE_COLUMN => Yii::t('import-wizard', 'Ignore column')];
        $attributeOptions = [
            '' => [
                'value' => '',
                'data-type' => '',
            ],
            MapAttribute::IGNORE_COLUMN => [
                'value' => MapAttribute::IGNORE_COLUMN,
                'data-type' => '',
            ],
        ];
        foreach ($this->model->getAttributes(null, $this->excludeAttributes) as $attribute => $value) {
            $attributeOptions[$attribute] = [
                'value' => $attribute,
                'data-type' => MapAttribute::detectCasting($this->model, $attribute)
            ];
            $attributes[$attribute] = $this->model->getAttributeLabel($attribute);
        }

        return $this->controller->render(
            $this->view,
            [
                'model' => $this->model,
                'header' => $header,
                'attributes' => $attributes,
                'attributeOptions' => $attributeOptions,
                'mapAttribute' => $mapAttribute,
                'startRowIndex' => $adapter->getStartRowIndex(),
            ]
        );
    }
}
