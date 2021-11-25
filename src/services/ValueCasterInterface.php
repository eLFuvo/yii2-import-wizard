<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-10-29
 * Time: 11:43
 */

namespace elfuvo\import\services;

use yii\base\Model;

/**
 *
 */
interface ValueCasterInterface
{
    /**
     * set column label from header
     *
     * @param string $label
     * @return void
     */
    public function setHeaderLabel(string $label);

    /**
     * @param \yii\base\Model $model
     * @param string $attribute
     * @param $value
     * @return void
     */
    public function cast(Model $model, string $attribute, $value);

    /**
     * @return string
     */
    public function getName(): string;
}
