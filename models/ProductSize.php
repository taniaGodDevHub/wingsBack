<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $product_id
 * @property int $size_id
 * @property string|null $chest_circumference
 * @property bool $is_in_stock
 * @property-read Size|null $size
 */
class ProductSize extends ActiveRecord
{
    /** @var string[] */
    private const SIZE_SORT_ORDER = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

    public static function tableName(): string
    {
        return '{{%product_size}}';
    }

    public function rules(): array
    {
        return [
            [['product_id', 'size_id'], 'required'],
            [['product_id', 'size_id'], 'integer'],
            [['chest_circumference'], 'string', 'max' => 16],
            [['is_in_stock'], 'boolean'],
        ];
    }

    /** @return string[] */
    public static function getStandardSizeValues(): array
    {
        return Size::getStandardSizeValues();
    }

    public function getSize(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Size::class, ['id' => 'size_id']);
    }

    public function getSizeValue(): string
    {
        $size = $this->size;

        return $size !== null ? (string) $size->size_value : '';
    }

    /** @return string[] */
    public static function getDistinctSizeValues(): array
    {
        $values = static::find()
            ->alias('ps')
            ->innerJoin(['s' => Size::tableName()], 's.id = ps.size_id')
            ->select('s.size_value')
            ->distinct()
            ->column();

        $values = array_values(array_unique(array_merge(self::getStandardSizeValues(), $values)));

        usort($values, static function (string $a, string $b): int {
            $leftIndex = array_search($a, self::SIZE_SORT_ORDER, true);
            $rightIndex = array_search($b, self::SIZE_SORT_ORDER, true);

            if ($leftIndex === false && $rightIndex === false) {
                return strnatcasecmp($a, $b);
            }
            if ($leftIndex === false) {
                return 1;
            }
            if ($rightIndex === false) {
                return -1;
            }

            return $leftIndex <=> $rightIndex;
        });

        return array_values($values);
    }
}
