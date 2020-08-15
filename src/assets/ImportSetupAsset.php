<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

namespace elfuvo\import\assets;

use yii\web\AssetBundle;
use yii\web\JqueryAsset;

/**
 * Class ImportSetupAsset
 * @package app\extensions\import\src\assets
 */
class ImportSetupAsset extends AssetBundle
{
    /**
     *
     */
    public function init()
    {
        $this->sourcePath = dirname(__FILE__) . '/dist/';
        parent::init();
    }

    /**
     * @var array
     */
    public $js = [
        'import-setup.js',
    ];

    public $depends = [
        JqueryAsset::class,
    ];
}
