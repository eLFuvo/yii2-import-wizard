<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

namespace elfuvo\import\adapter;

use elfuvo\import\exception\AdapterImportException;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\BaseReader;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * Class ExcelMappedAdapter
 *
 * @package app\i18n\adapters
 */
class AdapterImportExcel extends AbstractImportAdapter
{
    const CHUNK_SIZE = 100;

    const ALLOWED_FILE_EXTENSIONS = ['xlsx', 'xls'];

    /**
     * @var string
     */
    public $filename;

    /**
     * @var int
     */
    protected $fileTotalRows = 0;

    /**
     * @var BaseReader|Xlsx|Xls
     */
    private $reader;

    /**
     * @throws AdapterImportException
     */
    public function init()
    {
        if (empty($this->filename)) {
            throw new AdapterImportException('Path is not defined');
        }
        if (!file_exists($this->filename)) {
            throw new AdapterImportException('Can\'t read the file: ' . $this->filename);
        }
        if (!in_array(pathinfo($this->filename, PATHINFO_EXTENSION), self::ALLOWED_FILE_EXTENSIONS)) {
            throw new AdapterImportException('Only .xls, .xlsx files are allowed');
        }
    }

    /**
     * @return array
     */
    public static function getFileExtensions(): array
    {
        return ['.xlsx', '.xls'];
    }

    /**
     * @return array
     */
    public function getHeaderData(): array
    {
        $this->getReader()->getReadFilter()
            ->setRows(0, 1);

        $spreadsheet = $this->getReader()->load($this->filename);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
        $highestColumn = Coordinate::columnIndexFromString($highestColumn);

        //get headers (fields)
        $header = [];
        for ($col = 1; $col <= $highestColumn; $col++) {
            $columnName = Coordinate::stringFromColumnIndex($col);
            $header[$columnName] = $worksheet->getCellByColumnAndRow($col, '1')->getValue();
        }
        $this->reader = null;

        return $header;
    }

    /**
     * @throws AdapterImportException|\PhpOffice\PhpSpreadsheet\Exception
     * @throws \Exception
     */
    public function getBatchData(): ?array
    {
        if (!$this->getProgress()) {// get last row index
            $this->setProgress(
                new ExcelProgress([
                    'activeSheetIndex' => 0,
                    'startRowIndex' => $this->getStartRowIndex(),
                    'lastRowIndex' => $this->getStartRowIndex()
                ])
            );
            $totalRows = 0;
            $sheetTotalRows = 0;
            $objWorksheets = $this->getReader()->listWorksheetInfo($this->filename);
            foreach ($objWorksheets as $index => $sheet) {
                $totalRows += (int)$sheet['totalRows'];
                if (!$sheetTotalRows) {
                    $sheetTotalRows = (int)$sheet['totalRows'];
                }
                $this->getProgress()->sheetTotalRows[$index] = (int)$sheet['totalRows'];
                $this->getProgress()->totalRows = $totalRows;
            }
            $this->getProgress()->totalSheets = $totalSheets = count($objWorksheets);
        }

        $lastRowIndex = $this->getProgress()->lastRowIndex;
        $activeSheetIndex = $this->getProgress()->activeSheetIndex;
        $totalRows = $this->getProgress()->totalRows;
        $totalSheets = $this->getProgress()->totalSheets;
        $sheetTotalRows = $this->getProgress()->getSheetTotalRows();

        if ($totalRows < 1) {
            throw new AdapterImportException('Nothing to import');
        }
        // no datasheet for import
        if ($activeSheetIndex >= $totalSheets) {
            return null;
        }

        $this->getReader()->getReadFilter()
            ->setRows($lastRowIndex, self::CHUNK_SIZE);

        $spreadsheet = $this->getReader()->load($this->filename);
        $spreadsheet->setActiveSheetIndex($activeSheetIndex);
        $worksheet = $spreadsheet->getActiveSheet();
        $highestColumn = $worksheet->getHighestColumn(); // e.g 'F'
        $highestColumn = Coordinate::columnIndexFromString($highestColumn);

        // get headers (fields)
        $list = [];
        $endRowIndex = ($lastRowIndex + self::CHUNK_SIZE > $sheetTotalRows) ? $sheetTotalRows :
            $lastRowIndex + self::CHUNK_SIZE;
        // get data
        for ($rowIndex = $lastRowIndex; $rowIndex <= $endRowIndex; ++$rowIndex) {
            $item = [];

            for ($col = 1; $col <= $highestColumn; $col++) {
                $cell = $worksheet->getCellByColumnAndRow($col, $rowIndex);
                if (Date::isDateTime($cell)) {
                    $value = Date::excelToTimestamp($cell->getValue());
                } else {
                    $value = $this->filter((string)$cell->getFormattedValue());
                }
                $columnName = Coordinate::stringFromColumnIndex($col);
                $item[$columnName] = $value;
            }
            // skip empty rows
            $row = array_filter($item);
            if (empty($row)) {
                continue;
            }
            unset($row);

            array_push($list, $item);
        }
        $lastRowIndex += self::CHUNK_SIZE;
        $this->progress->lastRowIndex = $lastRowIndex;

        if ($lastRowIndex >= $sheetTotalRows) {
            $activeSheetIndex++;

            $this->getProgress()->lastRowIndex = 1;
            $this->getProgress()->activeSheetIndex = $activeSheetIndex;
        }

        $this->reader = null;

        return $list ?: null;
    }

    /**
     * @param string $value
     * @return string
     */
    protected function filter(string $value): string
    {
        return trim($value);
    }

    /**
     * @return BaseReader|Xls|Xlsx
     * @throws AdapterImportException|\PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    protected function getReader(): BaseReader
    {
        if (!$this->reader) {
            // detect import file format [xlsx, xls]
            $format = IOFactory::identify($this->filename);
            /**  Create a new Reader of the type defined in $inputFileType  **/
            /** @var IReader|BaseReader $reader */
            $this->reader = IOFactory::createReader($format);

            if (!($this->reader instanceof Xlsx || $this->reader instanceof Xls)) {
                throw new AdapterImportException('File for import is not for this adapter. Actual format: ' . $format);
            }
            $this->reader->setReadDataOnly(true);

            // @see https://phpspreadsheet.readthedocs.io/en/latest/topics/reading-files/#combining-read-filters-with-the-setsheetindex-method-to-split-a-large-csv-file-across-multiple-worksheets
            $chunkFilter = new ChunkExcelReadFilter();
            // Tell the Reader that we want to use the Read Filter that we've Instantiated
            // and that we want to store it in contiguous rows/columns

            $this->reader->setReadFilter($chunkFilter);
        }

        return $this->reader;
    }

    /**
     * @return int
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \elfuvo\import\exception\AdapterImportException
     */
    public function getTotalRows(): int
    {
        if (empty($this->fileTotalRows)) {
            $objWorksheets = $this->getReader()->listWorksheetInfo($this->filename);
            foreach ($objWorksheets as $sheet) {
                $this->fileTotalRows += (int)$sheet['totalRows'];
            }
        }

        return $this->fileTotalRows;
    }

    /**
     * @inheritdoc
     */
    public function isDone(): bool
    {
        return ($this->getProgress()->totalSheets - 1 <= $this->getProgress()->activeSheetIndex)
            && ($this->getProgress()->getSheetTotalRows() <= $this->getProgress()->lastRowIndex);
    }
}
