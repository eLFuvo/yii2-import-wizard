<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2020-08-14
 * Time: 21:32
 */

namespace elfuvo\import;

use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\helpers\StringHelper;
use yii\validators\BooleanValidator;
use yii\validators\DateValidator;
use yii\validators\EmailValidator;
use yii\validators\NumberValidator;
use yii\validators\StringValidator;
use yii\validators\UrlValidator;

/**
 * @property string|int $column
 * @property boolean $identity
 * @property string $attribute
 * @property string $castTo
 *
 * Class MapAttribute
 * @package elfuvo\import
 */
class MapAttribute extends Model
{
    // @see yii\behaviors\AttributeTypecastBehavior
    public const TYPE_INTEGER = 'integer';
    public const TYPE_FLOAT = 'float';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_STRING = 'string';
    // other cast types
    public const TYPE_EMAIL = 'email';
    public const TYPE_DATE = 'date';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_URL = 'url';

    protected const AUTO_CASTING_VALIDATORS = [
        self::TYPE_STRING => StringValidator::class,
        self::TYPE_FLOAT => NumberValidator::class,
        self::TYPE_DATETIME => DateValidator::class,
        self::TYPE_EMAIL => EmailValidator::class,
        self::TYPE_BOOLEAN => BooleanValidator::class,
        self::TYPE_URL => UrlValidator::class,
    ];

    public const IGNORE_COLUMN = 'ignore-value';

    /**
     * @var int
     */
    public $column;

    /**
     * @var int
     */
    public $identity;

    /**
     * @var string
     */
    public $attribute;

    /**
     * @var string
     */
    public $castTo = self::TYPE_STRING;

    /**
     * setModel?
     *
     * @var Model
     */
    public $model;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['attribute', 'castTo'], 'required'],
            [['attribute', 'castTo'], 'string'],
            [['column'], 'string'],
            [['identity'], 'boolean'], // 1|0
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'attribute' => 'Выберите св-во элемента',
            'castTo' => 'Преобразовать значение',
            'identity' => 'Идентификатор элемента',
        ];
    }

    /**
     * @return array
     */
    public static function getCastList()
    {
        return [
            self::TYPE_STRING => 'Строка',
            self::TYPE_INTEGER => 'Целое число',
            self::TYPE_FLOAT => 'Дробное число',
            self::TYPE_DATE => 'Дата',
            self::TYPE_DATETIME => 'Дата и время',
            self::TYPE_EMAIL => 'E-mail',
            self::TYPE_URL => 'Ссылка',
        ];
    }

    /**
     * @param $value
     * @return string|null
     */
    protected function castToDate($value)
    {
        try {
            $dt = new DateTime($value);
            return $dt->format('Y-m-d');
        } catch (Exception $e) {
            // date is PhpExcel date?
            try {
                $value = Date::excelToTimestamp($value);
                $dt = (new DateTime())->setTimestamp($value);
                return $dt->format('Y-m-d');
            } catch (Exception $e) {
                // do nothing
            }
        }

        return null;
    }

    /**
     * @param $value
     * @return string|null
     */
    protected function castToDateTime($value)
    {
        try {
            $dt = new DateTime($value);
            return $dt->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            // date is PhpExcel date?
            try {
                $value = Date::excelToTimestamp($value);
                $dt = (new DateTime())->setTimestamp($value);
                return $dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                // do nothing
            }
        }

        return null;
    }

    /**
     * @param Model $model
     * @param $value
     */
    public function setValue(Model $model, $value)
    {
        $model->setAttributes([$this->attribute => $this->typecastValue($value, $this->castTo)]);
    }

    /**
     * detect cast type by attribute active validators
     *
     * @param Model $model
     * @param $attribute
     * @return int|null|string
     */
    public static function detectCasting(Model $model, $attribute)
    {
        $validators = $model->getActiveValidators($attribute);
        foreach ($validators as $validator) {
            foreach (self::AUTO_CASTING_VALIDATORS as $castType => $allowedValidator) {
                if ($validator instanceof $allowedValidator) {
                    if ($validator instanceof NumberValidator && $validator->integerOnly) {
                        return self::TYPE_INTEGER;
                    } elseif ($validator instanceof DateValidator
                        && strlen($validator->format) <= 10 // YYYY-MM-dd, dd/MM/YYYY
                        && preg_match('#Y#i', $validator->format)) {
                        return self::TYPE_DATE;
                    }

                    return $castType;
                }
            }
        }

        return null;
    }

    /**
     * Casts the given value to the specified type.
     * @param mixed $value value to be type-casted.
     * @param string|callable $type type name or typecast callable.
     * @return mixed typecast result.
     */
    protected function typecastValue($value, $type)
    {
        if (is_scalar($type)) {
            if (is_object($value) && method_exists($value, '__toString')) {
                $value = $value->__toString();
            }

            switch ($type) {
                case self::TYPE_INTEGER:
                    return (int)$value;
                case self::TYPE_FLOAT:
                    return (float)$value;
                case self::TYPE_BOOLEAN:
                    return (bool)$value;
                case self::TYPE_STRING:
                case self::TYPE_EMAIL:
                    if (is_float($value)) {
                        return StringHelper::floatToString($value);
                    }
                    return (string)$value;
                case self::TYPE_DATE:
                    return $this->castToDate($value);
                    break;
                case self::TYPE_DATETIME:
                    return $this->castToDateTime($value);
                    break;
                default:
                    throw new InvalidArgumentException("Unsupported type '{$type}'");
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isIdentity()
    {
        return $this->identity ? true : false;
    }
}
