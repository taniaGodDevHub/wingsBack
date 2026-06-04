<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $order_id
 * @property string $provider
 * @property string|null $track_number
 * @property string|null $current_status
 * @property string|null $description
 * @property string|null $current_city
 * @property int|null $updated_at
 * @property string|null $expected_delivery
 */
class OrderTracking extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%order_tracking}}';
    }

    public static function primaryKey(): array
    {
        return ['order_id'];
    }
}
