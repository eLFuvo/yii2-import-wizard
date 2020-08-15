<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

namespace elfuvo\import\adapter;

/**
 * Interface ImportAdapterInterface
 * @package elfuvo\import\adapter
 */
interface AdapterImportInterface
{
    /**
     * @return array
     */
    public static function getFileExtensions(): array;

    /**
     * get several rows from import data or return null if no more
     *
     * @return array|null
     */
    public function getBatchData(): ?array;

    /**
     * get 1st row of import file
     *
     * @return array
     */
    public function getHeaderData(): array;

    /**
     * @return array|ExcelProgress
     */
    public function getProgress();

    /**
     * @param array|null|ExcelProgress $progress
     */
    public function setProgress($progress);

    /**
     * @return int
     */
    public function getTotalRows(): int;

    /**
     * @return int
     */
    public function getStartRowIndex(): int;

    /**
     * @param int $index
     * @return void
     */
    public function setStartRowIndex(int $index);

    /**
     * detecting end of import data
     *
     * @return bool
     */
    public function isDone(): bool;
}
