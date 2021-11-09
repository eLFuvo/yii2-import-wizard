<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

namespace elfuvo\import\result;

use elfuvo\import\adapter\ExcelProgress;
use Yii;
use yii\caching\TagDependency;

/**
 * Class ResultFileContinuesImport
 * @package app\modules\auto\components\import
 */
class CacheContinuesResultImport extends AbstractResultImport
{
    const CACHE_DURATION = 3600;
    const CACHE_KEY = 'import';

    /**
     * @param array|ExcelProgress $batch
     */
    public function setBatch($batch): void
    {
        $key = [
            self::CACHE_KEY,
            $this->key,
        ];

        $dependency = new TagDependency([
            'tags' => [
                self::CACHE_KEY,
                $this->key,
            ]
        ]);

        Yii::$app->cache->set(
            $key,
            get_object_vars($this),
            self::CACHE_DURATION,
            $dependency
        );
    }

    /**
     * @return array|\elfuvo\import\adapter\ExcelProgress|null
     */
    public function getLastBatch()
    {
        $key = [
            self::CACHE_KEY,
            $this->key,
        ];

        if (($stat = Yii::$app->cache->get($key)) !== false) {
            foreach ($stat as $field => $value) {
                if (property_exists($this, $field)) {
                    $this->{$field} = $value;
                }
            }
            // free some memory
            $stat = null;
        }

        return $this->batch;
    }

    /**
     * @return bool
     */
    public function resetBatch(): bool
    {
        parent::resetBatch();

        TagDependency::invalidate(Yii::$app->cache, [$this->key]);

        return true;
    }
}
