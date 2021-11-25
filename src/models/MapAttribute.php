<?php
/**
 * Created by PhpStorm
 * User: elfuvo
 * Date: 2021-10-29
 * Time: 11:39
 */

namespace elfuvo\import\models;

use DateTime;
use elfuvo\import\services\ValueCasterInterface;
use Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\di\Instance;
use yii\helpers\StringHelper;
use yii\validators\BooleanValidator;
use yii\validators\DateValidator;
use yii\validators\EmailValidator;
use yii\validators\NumberValidator;
use yii\validators\StringValidator;
use yii\validators\UrlValidator;

/**
 * @property string|int $column
 * @property boolean|int $identity
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
     * @return array
     */
    public function rules(): array
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
    public function attributeLabels(): array
    {
        return [
            'attribute' => Yii::t('import-wizard', 'Choose property of item'),
            'castTo' => Yii::t('import-wizard', 'Cast value to'),
            'identity' => Yii::t('import-wizard', 'Item identity'),
        ];
    }

    /**
     * @return array
     */
    public static function getCastList(): array
    {
        return [
            self::TYPE_STRING => Yii::t('import-wizard', 'String'),
            self::TYPE_INTEGER => Yii::t('import-wizard', 'Integer'),
            self::TYPE_FLOAT => Yii::t('import-wizard', 'Number'),
            self::TYPE_DATE => Yii::t('import-wizard', 'Date'),
            self::TYPE_DATETIME => Yii::t('import-wizard', 'Date and time'),
            self::TYPE_EMAIL => Yii::t('import-wizard', 'E-mail'),
            self::TYPE_URL => Yii::t('import-wizard', 'URL'),
        ];
    }

    /**
     * @param $value
     * @return string|null
     */
    protected function castToDate($value): ?string
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
    protected function castToDateTime($value): ?string
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
     * @param \yii\base\Model $model
     * @param $value
     * @param string|null $label
     */
    public function setValue(Model $model, $value, string $label = null)
    {
        if (!in_array($this->castTo, array_keys(self::AUTO_CASTING_VALIDATORS))
            && class_exists($this->castTo)) {
            /** @var ValueCasterInterface $caster */
            $caster = Instance::ensure($this->castTo, ValueCasterInterface::class);
            if ($label) {
                $caster->setHeaderLabel($label);
            }

            $caster->cast($model, $this->attribute, $value);
        } else {
            $model->setAttributes([$this->attribute => $this->typecastValue($value, $this->castTo, $label)]);
        }
    }

    /**
     * detect cast type by attribute active validators
     *
     * @param Model $model
     * @param $attribute
     * @return string|null
     */
    public static function detectCasting(Model $model, $attribute): ?string
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
     * @return bool|float|int|string|null typecast result.
     * @throws InvalidArgumentException
     */
    protected function typecastValue($value, $type, string $label = null)
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
                case self::TYPE_DATETIME:
                    return $this->castToDateTime($value);
                default:
                    throw new InvalidArgumentException('Unsupported type "' . $type . '"');
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isIdentity(): bool
    {
        return (bool)$this->identity;
    }
}
