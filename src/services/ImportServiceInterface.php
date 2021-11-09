<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-10-29
 * Time: 11:37
 */

namespace elfuvo\import\services;

use elfuvo\import\adapter\AdapterImportInterface;
use elfuvo\import\result\ResultImportInterface;
use yii\base\Model;
use yii\web\UploadedFile;

/**
 *
 */
interface ImportServiceInterface
{
    /**
     * @param \elfuvo\import\adapter\AdapterImportInterface $adapter
     * @return $this
     */
    public function setAdapter(AdapterImportInterface $adapter): ImportServiceInterface;

    /**
     * @param array $map
     * @return $this
     */
    public function setMap(array $map): ImportServiceInterface;

    /**
     * @param \yii\base\Model $model
     * @return $this
     */
    public function setModel(Model $model): ImportServiceInterface;

    /**
     * @param string $scenario
     * @return $this
     */
    public function setValidationScenario(string $scenario): ImportServiceInterface;

    /**
     * @param \yii\web\UploadedFile $file
     * @param \yii\base\Model $model
     * @return bool
     * @throws \yii\base\Exception
     */
    public function uploadImportFile(UploadedFile $file, Model $model): bool;

    /**
     * @return string
     */
    public function getUploadedImportFile(): string;

    /**
     * @return \elfuvo\import\result\ResultImportInterface
     * @throws \elfuvo\import\exception\AdapterImportException
     * @throws \yii\base\InvalidConfigException
     */
    public function import(): ResultImportInterface;

    /**
     * @return bool
     * @throws \yii\base\InvalidConfigException
     * @throws \elfuvo\import\exception\AdapterImportException
     */
    public function importBatch(): bool;

    /**
     * @return \elfuvo\import\result\ResultImportInterface
     * @throws \yii\base\InvalidConfigException
     */
    public function getResult(): ResultImportInterface;

    /**
     * @return array
     */
    public function getCustomCasters(): array;
}
