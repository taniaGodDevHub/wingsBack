<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $order_id
 * @property int $product_id
 * @property string $name
 * @property int $quantity
 * @property float $unit_price
 * @property float $total_price
 * @property string|null $delivery_label
 */
class OrderItem extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%order_item}}';
    }

    public function getProduct(): \yii\db\ActiveQuery
    {
        return $this->hasOne(Product::class, ['id' => 'product_id']);
    }
}
