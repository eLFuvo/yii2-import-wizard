<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

namespace elfuvo\import\forms;

use Yii;
use yii\base\Model;

/**
 * Class UploadForm
 * @package elfuvo\import\forms
 */
class UploadForm extends Model
{
    public $file;

    /**
     * @return array|array[]
     */
    public function rules()
    {
        return [
            [['file'], 'file'],
            [['file'], 'required'],
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'file' => Yii::t('import-wizard', 'File for import'),
        ];
    }
}
