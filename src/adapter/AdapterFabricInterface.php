<?php
/**
 * Created by PhpStorm.
 * User: elfuvo
 * Date: 26.04.19
 * Time: 14:40
 */

namespace elfuvo\import\adapter;

use elfuvo\import\exception\AdapterImportException;

/**
 * Interface AdapterFabricInterface
 * @package elfuvo\import\adapter
 */
interface AdapterFabricInterface
{
    /**
     * @return array
     */
    public function getFileImportExtensions(): array;

    /**
     * @param string $filename
     * @return AdapterImportInterface
     * @throws AdapterImportException
     */
    public function create(string $filename): AdapterImportInterface;
}
