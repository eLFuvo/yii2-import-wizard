<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-10-29
 * Time: 11:53
 */

namespace elfuvo\import\services;

use Yii;

/**
 *
 */
class BracketValueCaster implements ValueCasterInterface
{
    /**
     * @param string $attribute
     * @param bool|int|string $value
     * @return bool|int|string|null
     */
    public function cast(string $attribute, $value)
    {
        if (is_string($value) && preg_match('#\[(.+)\]#', $value, $matches)) {
            return $matches[1];
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return Yii::t('import-wizard', 'Extract value from brackets');
    }
}
