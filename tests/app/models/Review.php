<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:33
 */

namespace elfuvo\import\app\models;

use Yii;
use yii\base\Model;


/**
 *
 * Class Review
 * @package elfuvo\import\app\models
 */
class Review extends Model
{
    public const HIDDEN_NO = 0;
    public const HIDDEN_YES = 1;

    /**
     * @var array|null
     */
    protected static $existsModelList;

    /**
     * @var yii\caching\FileCache
     */
    protected static $cache;

    protected const CACHE_KEY = 'import-reviews';

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $title;

    /**
     * @var string
     */
    public $author;

    /**
     * @var string
     */
    public $text;

    /**
     * @var float
     */
    public $rating;

    /**
     * @var int
     */
    public $hidden;

    /**
     * @var string
     */
    public $language;

    /**
     * @var string
     */
    public $createdAt;

    /**
     * @var string
     */
    public $publishAt;
    /**
     * @var
     */
    public $updatedAt;

    /**
     * @return array|array[]
     */
    public function rules()
    {
        return [
            [['hidden'], 'integer'],
            [['author', 'title', 'text', 'rating'], 'required'],
            [['text'], 'string'],
            [['rating',], 'double'],
            [['publishAt', 'createdAt', 'updatedAt'], 'safe'],
            [['author', 'title'], 'string', 'max' => 255],
            [['language'], 'string', 'max' => 8],
            [['publishAt'], 'date', 'format' => 'yyyy-MM-dd HH:mm:ss'],
        ];
    }

    /**
     * @param bool $runValidation
     * @param null $attributeNames
     * @return bool
     */
    public function save($runValidation = true, $attributeNames = null)
    {
        if ($this->validate()) {
            self::getList();
            $this->id = count(self::$existsModelList) + 1;
            array_push(self::$existsModelList, $this);
            Yii::$app->cache->set(self::CACHE_KEY, self::$existsModelList);
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public static function getList()
    {
        if (is_null(self::$existsModelList)) {
            self::$existsModelList = Yii::$app->cache->get(self::CACHE_KEY);
            if (!self::$existsModelList) {
                self::$existsModelList = [];
            }
        }
        return self::$existsModelList;
    }

    /**
     * @param array $conditions
     * @return Review|null
     */
    public static function findOne($conditions)
    {
        $models = array_filter(self::getList(), function (Model $model) use ($conditions) {
            return $model->getAttributes(array_keys($conditions)) == $conditions;
        });
        if (count($models)) {
            return current($models);
        }

        return null;
    }
}
