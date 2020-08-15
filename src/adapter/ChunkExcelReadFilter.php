<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

/**
 * Created by PhpStorm.
 * User: petr
 * Date: 01.12.2016
 * Time: 15:53
 */

namespace elfuvo\import\adapter;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Class ChunkExcelReadFilter
 * @package common\components\excel
 */
class ChunkExcelReadFilter implements IReadFilter
{
    /**
     * @var int
     */
    protected $startRow = 0;

    /**
     * @var int
     */
    protected $endRow = 0;

    /**
     * @param $startRow
     * @param $chunkSize
     */
    public function setRows($startRow, $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize;
    }

    /**
     * @param string $column
     * @param int $row
     * @param string $worksheetName
     * @return bool
     */
    public function readCell($column, $row, $worksheetName = '')
    {
        if (($row == 1) || ($row >= $this->startRow && $row < $this->endRow)) {
            return true;
        }
        return false;
    }
}
