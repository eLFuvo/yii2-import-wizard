<?php

namespace elfuvo\import\result;

use Yii;
use yii\helpers\FileHelper;

/**
 * Class ResultFileContinuesImport
 * @package app\modules\auto\components\import
 */
class FileContinuesResultImport extends AbstractResultImport
{
    const FILE_PREFIX = 'result_';

    /**
     * @var string
     */
    public $pointerPath = '@runtime/import';

    /**
     * @param array $list
     */
    public function setBatch($list): void
    {
        $this->batch = $list;
        $path = Yii::getAlias($this->pointerPath);
        if (!is_dir($path)) {
            FileHelper::createDirectory($path);
        }

        $fh = fopen($this->getLogName(), 'wb');
        fwrite(
            $fh,
            serialize(get_object_vars($this))
        );
        fclose($fh);
    }

    /**
     * @return array|\elfuvo\import\adapter\ExcelProgress|null
     */
    public function getLastBatch()
    {
        if (file_exists($this->getLogName())) {
            $contents = file_get_contents($this->getLogName());
            $stat = unserialize($contents);
            foreach ($stat as $field => $value) {
                if (property_exists($this, $field)) {
                    $this->{$field} = $value;
                }
            }
            // free some memory
            $contents = $stat = null;
        }

        return $this->batch;
    }

    /**
     * @return bool
     */
    public function resetBatch(): bool
    {
        parent::resetBatch();

        if (file_exists($this->getLogName())) {
            @unlink($this->getLogName());
        }

        return true;
    }

    /**
     * @return string
     */
    protected function getLogName()
    {
        $path = Yii::getAlias($this->pointerPath);

        return $path . '/' . self::FILE_PREFIX . $this->key . '.log';
    }
}
