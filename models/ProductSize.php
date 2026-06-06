<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $product_id
 * @property string $size_value
 */
class ProductSize extends ActiveRecord
{
    /** @var string[] */
    public const STANDARD_SIZES = ['S', 'M', 'L', 'XL'];

    /** @var string[] */
    private const SIZE_SORT_ORDER = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL'];

    public static function tableName(): string
    {
        return '{{%product_size}}';
    }

    /** @return string[] */
    public static function getStandardSizeValues(): array
    {
        return self::STANDARD_SIZES;
    }

    /** @return string[] */
    public static function getDistinctSizeValues(): array
    {
        $values = static::find()
            ->select('size_value')
            ->distinct()
            ->column();

        $values = array_values(array_unique(array_merge(self::STANDARD_SIZES, $values)));

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
