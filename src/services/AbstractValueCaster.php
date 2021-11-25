<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-11-25
 * Time: 14:38
 */

namespace elfuvo\import\services;

use ReflectionClass;

/**
 *
 */
abstract class AbstractValueCaster implements ValueCasterInterface
{
    /**
     * @var string|null
     */
    protected $label;

    /**
     * @param string $label
     */
    public function setHeaderLabel(string $label)
    {
        $this->label = $label;
    }

    /**
     * @inheritDoc
     */
    abstract public function cast(string $attribute, $value);

    /**
     * @return string
     */
    public function getName(): string
    {
        return (new ReflectionClass($this))->getShortName();
    }
}
