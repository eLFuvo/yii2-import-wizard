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
 * Time: 14:05
 */

use elfuvo\import\assets\ImportSetupAsset;
use elfuvo\import\MapAttribute;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var yii\web\View $this */
/** @var array $header */
/** @var array $attributes */
/** @var array $attributeOptions */
/** @var int $startRowIndex */
/** @var \elfuvo\import\MapAttribute[] $mapAttribute */

$this->title = 'Настройка импорта';

ImportSetupAsset::register($this);
?>

<div class="card">

    <div class="card-header">
        <h4 class="card-title"><?= Html::encode($this->title) ?></h4>
    </div>
    <div class="card-content">
        <?php
        $form = ActiveForm::begin(['options' => ['class' => 'setup-import-form']]); ?>

        <table class="table">
            <thead>
            <tr>
                <th>Колонка в файле</th>
                <?php
                foreach ($header as $column => $value): ?>
                    <td><strong><?= $column; ?></strong></td>
                <?php
                endforeach; ?>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>1я строка импорта</td>
                <?php
                foreach ($header as $column => $value): ?>
                    <td><?= Html::encode($value); ?></td>
                <?php
                endforeach; ?>
            </tr>
            <tr>
                <td>Поле модели для заполнения</td>
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
                <td>Преобразовать значение из импорта в</td>
                <?php
                foreach ($header as $column => $value):
                    $model = $mapAttribute[$column] ?? new MapAttribute();
                    ?>
                    <td class="type" data-id="<?= $column; ?>">
                        <?= $form->field(
                            $model,
                            '[' . $column . ']castTo'
                        )->dropDownList(
                            MapAttribute::getCastList(),
                            [
                                'class' => 'form-control cast-to',
                            ]
                        ); ?>
                    </td>
                <?php
                endforeach; ?>
            </tr>
            <tr>
                <td>Поля для идентификации модели</td>
                <?php
                foreach ($header as $column => $value):
                    $model = $mapAttribute[$column] ?? new MapAttribute();
                    ?>
                    <td>
                        <?= $form->field(
                            $model,
                            '[' . $column . ']identity'
                        )->checkbox(); ?>
                    </td>
                <?php
                endforeach; ?>
            </tr>
            </tbody>
        </table>

        <div class="form-group">
            <label for="startRowIndex">Начать импорт со строки</label>
            <?= Html::input(
                'number',
                'startRowIndex',
                $startRowIndex,
                [
                    'id' => 'startRowIndex',
                    'class' => 'form-control',
                    'min' => 1,
                ]
            ); ?>
        </div>
        <div class="form-group">
            <?= Html::submitButton('Импортировать',
                ['class' => 'btn btn-success']) ?>
        </div>

        <?php
        ActiveForm::end(); ?>
    </div>

</div>
