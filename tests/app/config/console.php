<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:33
 */

return \yii\helpers\ArrayHelper::merge(
    require('common.php'),
    [
        'id' => 'console',
        'bootstrap' => [
            'queue',
        ],
    ]
);
