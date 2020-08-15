<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

namespace elfuvo\import\result;

use elfuvo\import\adapter\ExcelProgress;
use elfuvo\import\MapAttribute;

/**
 * Interface ResultImportInterface
 * @package elfuvo\import\result
 */
interface ResultImportInterface
{
    const UPDATE_COUNTER = 'update_counter';
    const ADD_COUNTER = 'add_counter';
    const DELETE_COUNTER = 'delete_counter';
    const SKIP_COUNTER = 'skip_counter';

    /**
     * @param string $key
     * @return void
     */
    public function setKey(string $key);

    /**
     * @param string $error
     */
    public function addError(string $error): void;

    /**
     * @return array|null
     */
    public function getErrors(): array;

    /**
     * @return bool
     */
    public function hasErrors(): bool;

    /**
     * @param string $counter
     * @param int $count
     * @return bool
     */
    public function addCount($counter = ResultImportInterface::UPDATE_COUNTER, int $count = 1): bool;

    /**
     * @param string $counter
     * @return int
     */
    public function getCounter(string $counter): int;

    /**
     * @param int $total
     */
    public function setProgressTotal(int $total): void;

    /**
     * @return int
     */
    public function getProgressTotal(): int;

    /**
     * @return int
     */
    public function increaseProgressDone(): int;

    /**
     * @param int $done
     */
    public function setProgressDone(int $done): void;

    /**
     * @return int
     */
    public function getProgressDone(): int;

    /**
     * @param array|ExcelProgress|null $batch
     */
    public function setBatch($batch): void;

    /**
     * @return array|ExcelProgress|null
     */
    public function getLastBatch();

    /**
     * @return bool
     */
    public function resetBatch(): bool;

    /**
     * @param MapAttribute[] $map
     */
    public function setMap(array $map): void;

    /**
     * @return MapAttribute[]
     */
    public function getMap(): array;
}
