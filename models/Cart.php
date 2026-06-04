<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property bool $is_active
 * @property int $created_at
 * @property int $updated_at
 */
class Cart extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%cart}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['is_active'], 'boolean'],
            [['user_id'], 'integer'],
            [['session_id'], 'string', 'max' => 64],
        ];
    }

    public function getItems(): \yii\db\ActiveQuery
    {
        return $this->hasMany(CartItem::class, ['cart_id' => 'id']);
    }

    public static function findActiveForUser(int $userId): ?static
    {
        return static::findOne(['user_id' => $userId, 'is_active' => true]);
    }

    public static function findActiveForSession(string $sessionId): ?static
    {
        return static::findOne(['session_id' => $sessionId, 'is_active' => true]);
    }
}
