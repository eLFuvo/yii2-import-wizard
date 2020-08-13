<?php
/**
 * Created by PhpStorm.
 * User: elfuvo
 * Date: 30.04.19
 * Time: 10:31
 */

namespace elfuvo\import\adapter;

use yii\base\Model;
use yii\helpers\ArrayHelper;

/**
 * @property int $startRowIndex
 * @property int $lastRowIndex
 * @property int $totalRows
 * @property int $totalSheets
 * @property int $activeSheetIndex
 * @property array $sheetTotalRows
 *
 * Class Progress
 * @package app\extensions\import\src\adapter
 */
class ExcelProgress extends Model
{
    /**
     * @var int
     */
    public $startRowIndex = 1;

    /**
     * @var int
     */
    public $lastRowIndex = 0;

    /**
     * @var int
     */
    public $totalRows = 1;

    /**
     * @var int
     */
    public $totalSheets = 1;

    /**
     * @var int
     */
    public $activeSheetIndex = 0;

    /**
     * @var array
     */
    public $sheetTotalRows = [1];

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['startRowIndex', 'lastRowIndex', 'totalRows', 'totalSheets', 'activeSheetIndex'], 'integer'],
            [['sheetTotalRows'], 'each', 'rule' => ['integer']],
        ];
    }

    /**
     * @return integer
     */
    public function getSheetTotalRows()
    {
        return ArrayHelper::getValue($this->sheetTotalRows, $this->activeSheetIndex, 0);
    }
}
