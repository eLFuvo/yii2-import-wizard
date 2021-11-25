<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-10-29
 * Time: 11:53
 */

namespace elfuvo\import\services;

use Yii;
use yii\base\Model;

/**
 *
 */
class BracketValueCaster extends AbstractValueCaster
{
    /**
     * @inheritDoc
     */
    public function cast(Model $model, string $attribute, $value)
    {
        if (is_string($value) && preg_match('#\[(.+)\]#', $value, $matches)) {
            $value = $matches[1];
        }

        $model->setAttributes([$attribute => $value]);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return Yii::t('import-wizard', 'Extract value from brackets');
    }
}
