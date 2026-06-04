<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $record_id
 * @property string $channel
 * @property string $destination
 * @property string $code_hash
 * @property string $type
 * @property int $expires_at
 * @property int|null $used_at
 * @property int $created_at
 */
class AuthVerificationChallenge extends ActiveRecord
{
    public const CHANNEL_PHONE = 'phone';
    public const CHANNEL_EMAIL = 'email';
    public const TYPE_REGISTRATION = 'registration';
    public const TYPE_LOGIN = 'login';
    public const TYPE_EMAIL_CONFIRM = 'email_confirmation';

    public static function tableName(): string
    {
        return '{{%auth_verification_challenge}}';
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'updatedAtAttribute' => false,
            ],
        ];
    }

    public function rules(): array
    {
        return [
            [['record_id', 'channel', 'destination', 'code_hash', 'type', 'expires_at'], 'required'],
            [['expires_at', 'used_at'], 'integer'],
            [['record_id'], 'string', 'max' => 36],
            [['channel', 'type'], 'string', 'max' => 32],
            [['destination'], 'string', 'max' => 255],
            [['code_hash'], 'string', 'max' => 255],
        ];
    }

    public function isExpired(): bool
    {
        return time() > $this->expires_at;
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public static function findActive(string $recordId): ?static
    {
        $model = static::findOne(['record_id' => $recordId]);
        if ($model === null || $model->isUsed() || $model->isExpired()) {
            return null;
        }

        return $model;
    }
}
