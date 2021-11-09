<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

use elfuvo\import\assets\ImportSetupAsset;
use elfuvo\import\models\MapAttribute;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var \yii\base\Model $model */
/** @var array $header */
/** @var array $attributes */
/** @var array $attributeOptions */
/** @var int $startRowIndex */
/** @var MapAttribute[] $mapAttribute */
/** @var string[] $casterList */

$this->title = Yii::t('import-wizard', 'Import settings');

ImportSetupAsset::register($this);
?>
<div class="card">
    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title) ?></h4>
    </div>
    <div class="card-content">
        <?php
        $form = ActiveForm::begin(
            [
                'options' => [
                    'class' => 'setup-import-form',
                    'data-model' => $model->formName(),
                ]
            ]
        ); ?>

        <table class="table">
            <thead>
            <tr>
                <th><?= Yii::t('import-wizard', 'Column in a file'); ?></th>
                <?php
                foreach ($header as $column => $value): ?>
                    <td><strong><?= $column; ?></strong></td>
                <?php
                endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td><?= Yii::t('import-wizard', '1st row'); ?></td>
                <?php
                foreach ($header as $column => $value): ?>
                    <td><?= Html::encode($value); ?></td>
                <?php
                endforeach; ?>
            </tr>
            <tr>
                <td><?= Yii::t('import-wizard', 'Model field to fill'); ?></td>
                <?php
                foreach ($header as $column => $value):
                    $model = $mapAttribute[$column] ?? new MapAttribute();
                    ?>
                    <td class="attribute" data-id="<?= $column; ?>">
                        <?= $form->field(
                            $model,
                            '[' . $column . ']attribute'
                        )->dropDownList(
                            $attributes,
                            [
                                'class' => 'form-control map-attribute',
                                'options' => $attributeOptions,
                                'data-live-search' => 'true',
                            ]
                        ); ?>
                    </td>
                <?php
                endforeach; ?>
            </tr>
            <tr>
                <td><?= Yii::t('import-wizard', 'Cast value from import to'); ?></td>
                <?php
                foreach ($header as $column => $value):
                    $model = $mapAttribute[$column] ?? new MapAttribute();
                    ?>
                    <td class="type" data-id="<?= $column; ?>">
                        <?= $form->field(
                            $model,
                            '[' . $column . ']castTo'
                        )->dropDownList(
                            $casterList,
                            [
                                'class' => 'form-control cast-to',
                            ]
                        ); ?>
                    </td>
                <?php
                endforeach; ?>
            </tr>
            <tr>
                <td><?= Yii::t('import-wizard', 'Model identification fields'); ?></td>
                <?php
                foreach ($header as $column => $value):
                    $model = $mapAttribute[$column] ?? new MapAttribute();
                    ?>
                    <td class="identity">
                        <?= $form->field(
                            $model,
                            '[' . $column . ']identity'
                        )->checkbox(
                            [
                                'class' => 'identity',
                                'data-id' => $column,
                            ]
                        ); ?>
                    </td>
                <?php
                endforeach; ?>
            </tr>
            </tbody>
        </table>

        <div class="form-group">
            <label for="startRowIndex"><?= Yii::t('import-wizard', 'Start import from row'); ?></label>
            <?= Html::input(
                'number',
                'startRowIndex',
                $startRowIndex,
                [
                    'id' => 'startRowIndex',
                    'class' => 'form-control start-row-index',
                    'min' => 1,
                ]
            ); ?>
        </div>
        <div class="form-group">
            <?= Html::submitButton(
                Yii::t('import-wizard', 'Start import'),
                ['class' => 'btn btn-success']
            ); ?>
            <?= Html::button(
                Yii::t('import-wizard', 'Reset'),
                [
                    'type'=>'reset',
                    'class' => 'btn btn-default'
                ]
            ); ?>
        </div>

        <?php
        ActiveForm::end(); ?>
    </div>

</div>
