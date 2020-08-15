<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

namespace elfuvo\import\adapter;

use elfuvo\import\exception\AdapterImportException;
use Yii;
use yii\base\BaseObject;

/**
 * Class AdapterFabricDefault
 * @package elfuvo\import\adapter
 */
class AdapterFabricDefault extends BaseObject implements AdapterFabricInterface
{
    /**
     * @var array
     */
    public $adapters = [
        AdapterImportArray::class,
        AdapterImportExcel::class,
    ];

    /**
     * @return array
     */
    public function getFileImportExtensions(): array
    {
        $extensions = [];
        foreach ($this->adapters as $adapter) {
            $ext = $adapter::getFileExtensions();
            $extensions = array_merge($extensions, $ext);
        }

        return $extensions;
    }

    /**
     * @param string $filename
     * @return AdapterImportInterface
     * @throws AdapterImportException
     */
    public function create(string $filename): AdapterImportInterface
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        foreach ($this->adapters as $adapterClass) {
            if (in_array('.' . $extension, $adapterClass::getFileExtensions())) {
                /** @var AdapterImportInterface $adapter */
                $adapter = Yii::createObject([
                    'class' => $adapterClass,
                    'filename' => $filename,
                ]);

                return $adapter;
            }
        }
        throw new AdapterImportException('No adapter found for file ' . $filename);
    }
}
