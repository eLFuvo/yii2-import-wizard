<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-10-29
 * Time: 11:43
 */

namespace elfuvo\import\services;

/**
 *
 */
interface ValueCasterInterface
{
    /**
     * @param string $attribute
     * @param $value
     * @return string|int|bool|null
     */
    public function cast(string $attribute, $value);

    /**
     * @return string
     */
    public function getName(): string;
}
