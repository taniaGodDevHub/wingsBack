<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $cart_id
 * @property int $product_id
 * @property int $quantity
 */
class CartItem extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%cart_item}}';
    }

    public function rules(): array
    {
        return [
            [['cart_id', 'product_id'], 'required'],
            [['cart_id', 'quantity'], 'integer'],
            [['product_id'], 'integer'],
            ['quantity', 'default', 'value' => 1],
        ];
    }
}
