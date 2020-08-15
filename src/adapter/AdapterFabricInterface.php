<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
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
