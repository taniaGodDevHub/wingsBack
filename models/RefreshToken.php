<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property string $token_hash
 * @property int $expires_at
 * @property int $created_at
 */
class RefreshToken extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%refresh_token}}';
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
            [['user_id', 'token_hash', 'expires_at'], 'required'],
            [['user_id', 'expires_at'], 'integer'],
            [['token_hash'], 'string', 'max' => 64],
        ];
    }

    public static function findValidByHash(string $hash): ?static
    {
        return static::find()
            ->where(['token_hash' => $hash])
            ->andWhere(['>', 'expires_at', time()])
            ->one();
    }
}
