<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

use elfuvo\import\assets\ImportSetupAsset;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var array $extensions */
/** @var string $progressAction */
/** @var \elfuvo\import\forms\UploadForm $uploadForm */

ImportSetupAsset::register($this);

$this->title = Yii::t('import-wizard', 'Choose file for import');

?>

<div class="card">

    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title) ?></h4>
    </div>

    <div class="import-progress-container" data-url="<?= Url::to([$progressAction]) ?>"></div>

    <div class="card-content">
        <?php
        $form = ActiveForm::begin([
            'options' => [
                'enctype' => 'multipart/form-data',
                'class' => 'import-form',
            ]
        ]); ?>

        <div class="form-group">
            <label for="importFile">
                <?= Yii::t('import-wizard', 'Choose file for import'); ?>
                (<?= implode(', ', $extensions); ?>)
            </label>
            <?= $form->field($uploadForm, 'file')
                ->fileInput(
                    [
                        'class' => 'form-control',
                        'id' => 'importFile',
                        'accept' => implode(', ', $extensions),
                    ]
                ); ?>
        </div>

        <div class="form-group">
            <?= Html::submitButton('Загрузить файл',
                ['class' => 'btn btn-success']) ?>
        </div>

        <?php
        ActiveForm::end(); ?>
    </div>
</div>
