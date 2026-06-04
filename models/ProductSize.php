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
    public static function tableName(): string
    {
        return '{{%product_size}}';
    }
}
