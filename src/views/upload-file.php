<?php
/**
 * Created by PhpStorm.
 * User: elfuvo
 * Date: 26.04.19
 * Time: 14:05
 */

use elfuvo\import\assets\ImportSetupAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var \elfuvo\import\result\ResultImportInterface $result */
/** @var \elfuvo\import\adapter\AdapterFabricInterface $fabric */
/** @var string $action */

ImportSetupAsset::register($this);

$this->title = 'Загрузить файл импорта';

?>

<div class="card">

    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title) ?></h4>
    </div>

    <div class="import-progress-container" data-url="<?= Url::to(['/' . $action]) ?>">
        <?= $this->render(
            '_import_stat',
            [
                'result' => $result,
            ]
        ); ?>
    </div>
    <?php if (!$result->getProgressTotal() || $result->getProgressDone() == $result->getProgressTotal()): ?>
        <div class="card-content">
            <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

            <div class="form-group">
                <label for="importFile">Загрузите файл импорта
                    (<?= implode(', ', $fabric->getFileImportExtensions()); ?>)
                </label>
                <?= Html::fileInput(
                    'importFile',
                    null,
                    [
                        'class' => 'form-control',
                        'id' => 'importFile',
                        'accept' => implode(', ', $fabric->getFileImportExtensions()),
                    ]
                ); ?>
            </div>

            <div class="form-group">
                <?= Html::submitButton('Загрузить файл',
                    ['class' => 'btn btn-success']) ?>
            </div>

            <?php ActiveForm::end(); ?>
        </div>
    <?php endif; ?>
</div>
