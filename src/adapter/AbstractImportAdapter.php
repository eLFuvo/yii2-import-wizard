<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

namespace elfuvo\import\adapter;

use yii\base\BaseObject;

/**
 * Class AbstractImportAdapter
 * @package elfuvo\import\adapter
 */
abstract class AbstractImportAdapter extends BaseObject implements AdapterImportInterface
{
    /**
     * @var array|ExcelProgress
     */
    protected $progress;

    /**
     * @var int
     */
    protected $startRowIndex = 2;

    /**
     * @return array
     */
    public static function getFileExtensions(): array
    {
        return [];
    }

    /**
     * @return array
     */
    abstract public function getHeaderData(): array;

    /**
     * @return array
     */
    abstract public function getBatchData(): ?array;

    /**
     * @inheritdoc
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * @param array|ExcelProgress $progress
     */
    public function setProgress($progress): void
    {
        $this->progress = $progress;
    }

    abstract public function getTotalRows(): int;

    /**
     * @return int
     */
    public function getStartRowIndex(): int
    {
        return $this->startRowIndex;
    }

    /**
     * @param int $startRowIndex
     */
    public function setStartRowIndex(int $startRowIndex): void
    {
        $this->startRowIndex = $startRowIndex;
    }

    /**
     * @inheritdoc
     */
    abstract public function isDone(): bool;
}
