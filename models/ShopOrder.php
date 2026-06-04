<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property string $status
 * @property string $payment_status
 * @property int|null $expires_at
 * @property float $total_price
 * @property string|null $delivery_provider
 * @property string|null $delivery_method_code
 * @property int|null $delivery_method_id
 * @property string|null $city_fias_id
 * @property string|null $destination_id
 * @property string|null $destination_address
 * @property string|null $delivery_address
 * @property string|null $payment_method
 * @property string|null $payment_url
 * @property string|null $comment
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $completed_at
 */
class ShopOrder extends ActiveRecord
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DELIVERING = 'delivering';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_RETURNED = 'returned';

    public static function tableName(): string
    {
        return '{{%shop_order}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function getItems(): \yii\db\ActiveQuery
    {
        return $this->hasMany(OrderItem::class, ['order_id' => 'id']);
    }

    public function getTracking(): \yii\db\ActiveQuery
    {
        return $this->hasOne(OrderTracking::class, ['order_id' => 'id']);
    }

    public static function findDraftForUser(int $userId): ?static
    {
        return static::find()
            ->where(['user_id' => $userId, 'status' => self::STATUS_DRAFT])
            ->andWhere(['or', ['expires_at' => null], ['>', 'expires_at', time()]])
            ->orderBy(['id' => SORT_DESC])
            ->one();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at < time();
    }

    public function isEditable(): bool
    {
        return $this->status === self::STATUS_DRAFT && !$this->isExpired();
    }
}
