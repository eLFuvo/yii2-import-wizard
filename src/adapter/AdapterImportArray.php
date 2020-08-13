<?php
/**
 * Created by PhpStorm.
 * User: elfuvo
 * Date: 18.04.19
 * Time: 15:45
 */

namespace elfuvo\import\adapter;

/**
 * Class AdapterImportArray
 * @package elfuvo\import\adapter
 */
class AdapterImportArray extends AbstractImportAdapter
{

    /**
     * @var array
     */
    public $data = [];

    /**
     * @return array
     */
    public function getHeaderData(): array
    {
        return reset($this->data);
    }

    /**
     *
     * @return array|null
     */
    public function getBatchData(): ?array
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getTotalRows(): int
    {
        return count($this->data);
    }

    /**
     * @inheritdoc
     */
    public function isDone(): bool
    {
        return true;
    }
}
