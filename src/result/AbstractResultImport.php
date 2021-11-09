<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

namespace elfuvo\import\result;

use elfuvo\import\adapter\ExcelProgress;
use elfuvo\import\models\MapAttribute;

/**
 * Class AbstractResultImport
 * @package elfuvo\import\result
 */
abstract class AbstractResultImport implements ResultImportInterface
{
    const ERRORS_LIMIT = 100;

    /**
     * @var string
     */
    protected $key = 'import-result';

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @var int
     */
    protected $progressDone = 0;

    /**
     * @var int
     */
    protected $progressTotal = 0;

    /**
     * @var array|ExcelProgress|null
     */
    protected $batch;

    /**
     * @var \elfuvo\import\models\MapAttribute[]
     */
    protected $map = [];

    /**
     * @var array
     */
    protected $data = [
        ResultImportInterface::UPDATE_COUNTER => 0,
        ResultImportInterface::ADD_COUNTER => 0,
        ResultImportInterface::DELETE_COUNTER => 0,
        ResultImportInterface::SKIP_COUNTER => 0,
    ];

    /**
     * @param string $key
     */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

    /**
     * @param string $counter
     * @param int $count
     * @return bool
     */
    public function addCount($counter = ResultImportInterface::UPDATE_COUNTER, int $count = 1): bool
    {
        if (!in_array($counter, [
            ResultImportInterface::UPDATE_COUNTER,
            ResultImportInterface::ADD_COUNTER,
            ResultImportInterface::DELETE_COUNTER,
            ResultImportInterface::SKIP_COUNTER,
        ])) {
            return false;
        }

        $this->data[$counter] += $count;

        return true;
    }

    /**
     * @param string $counter
     * @return int
     */
    public function getCounter(string $counter): int
    {
        return $this->data[$counter] ?? 0;
    }

    /**
     * @param string $error
     */
    public function addError(string $error): void
    {
        array_push($this->errors, $error);
        if (count($this->errors) > static::ERRORS_LIMIT) {
            array_shift($this->errors); // there is too many errors
        }
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * @param int $total
     */
    public function setProgressTotal(int $total): void
    {
        $this->progressTotal = $total;
    }

    /**
     * @return int
     */
    public function getProgressTotal(): int
    {
        return $this->progressTotal;
    }

    /**
     * @return int
     */
    public function increaseProgressDone(): int
    {
        $this->progressDone++;
        if ($this->progressDone > $this->progressTotal) {
            $this->progressDone = $this->progressTotal;
        }
        return $this->progressDone;
    }

    /**
     * @param int $done
     */
    public function setProgressDone(int $done): void
    {
        $this->progressDone = $done;
    }

    /**
     * @return int
     */
    public function getProgressDone(): int
    {
        return $this->progressDone;
    }

    /**
     * @param array|ExcelProgress $batch
     */
    public function setBatch($batch): void
    {
        $this->batch = $batch;
    }

    /**
     * @return array|ExcelProgress|null
     */
    public function getLastBatch()
    {
        return $this->batch;
    }

    /**
     * @return bool
     */
    public function resetBatch(): bool
    {
        // reset all result data
        $this->batch = null;
        $this->data = [
            ResultImportInterface::UPDATE_COUNTER => 0,
            ResultImportInterface::ADD_COUNTER => 0,
            ResultImportInterface::DELETE_COUNTER => 0,
            ResultImportInterface::SKIP_COUNTER => 0,
        ];
        $this->map = [];
        $this->errors = [];
        $this->progressTotal = 0;
        $this->progressDone = 0;

        return true;
    }

    /**
     * @return MapAttribute[]
     */
    public function getMap(): array
    {
        return $this->map;
    }

    /**
     * @param MapAttribute[] $map
     */
    public function setMap(array $map): void
    {
        $this->map = $map;
    }
}
