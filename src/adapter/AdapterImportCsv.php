<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

/**
 * Created by PhpStorm.
 * User: elfuvo
 * Date: 30.04.19
 * Time: 13:31
 */

namespace elfuvo\import\adapter;

use elfuvo\import\exception\AdapterImportException;
use SplFileObject;

/**
 * Class AdapterImportCsv
 * @package elfuvo\import\adapter
 */
class AdapterImportCsv extends AbstractImportAdapter
{
    const CHUNK_SIZE = 100;

    const ALLOWED_FILE_EXTENSIONS = ['csv'];

    /**
     * @var string
     */
    public $filename;

    /**
     * @var int
     */
    protected $fileTotalRows = 0;

    /**
     * @var SplFileObject
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
            throw new AdapterImportException('Only .csv files are allowed');
        }
    }

    /**
     * @return array
     */
    public static function getFileExtensions(): array
    {
        return ['.csv'];
    }

    /**
     * @return array
     */
    public function getHeaderData(): array
    {
        $this->getReader()->seek(0);

        $cols = $this->getReader()->fgetcsv();
        $header = [];
        foreach ($cols as $col => $value) {
            $header[$col] = $this->filter((string)$value);
        }
        $this->reader = null;

        return $header;
    }

    /**
     * @throws AdapterImportException
     */
    public function getBatchData(): ?array
    {
        $lastRowIndex = $this->startRowIndex;

        if ($this->progress) {
            $lastRowIndex = $this->progress->lastRowIndex;
        } else {// get last row index
            $this->setProgress(new ExcelProgress([
                    'startRowIndex' => $this->startRowIndex,
                    'totalRows' => $this->getTotalRows(),
                    'sheetTotalRows' => [$this->getTotalRows()],
                ])
            );
        }

        if ($this->progress->totalRows < 1) {
            throw new AdapterImportException('Nothing to import');
        }

        if ($lastRowIndex >= $this->progress->totalRows) {
            return null;
        }
        $this->getReader()->rewind();
        $this->getReader()->seek($lastRowIndex);
        $list = [];
        // get data
        for ($rowIndex = 0; $rowIndex < self::CHUNK_SIZE; ++$rowIndex) {
            $this->getReader()->next();
            $item = $this->getReader()->fgetcsv();
            if ($item) {
                $row = array_filter($item, 'trim');
                // skip empty rows
                $row = array_filter($row);
                if (empty($row)) {
                    continue;
                }
            } else {
                continue;
            }
            unset($row);

            array_push($list, $item);
        }
        $lastRowIndex += self::CHUNK_SIZE;
        $this->progress->lastRowIndex = $lastRowIndex;
        $this->reader = null;

        return $list ?: null;
    }

    /**
     * @param $value
     * @return string
     */
    protected function filter(string $value)
    {
        return trim($value);
    }

    /**
     * @return SplFileObject
     */
    protected function getReader(): SplFileObject
    {
        if (!$this->reader) {
            $this->reader = new SplFileObject($this->filename);
            $this->reader->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);
            $this->reader->rewind();
        }
        return $this->reader;
    }

    /**
     * @return int
     */
    public function getTotalRows(): int
    {
        // force to seek to last line, won't raise error
        $this->getReader()->seek($this->getReader()->getSize());
        $key = $this->getReader()->key();
        $this->reader = null;

        return $key;
    }

    /**
     * @inheritdoc
     */
    public function isDone(): bool
    {
        return ($this->getProgress()->totalSheets - 1 <= $this->getProgress()->activeSheetIndex) &&
            ($this->progress->sheetTotalRows[$this->getProgress()->activeSheetIndex] <=
                $this->getProgress()->lastRowIndex);
    }
}
