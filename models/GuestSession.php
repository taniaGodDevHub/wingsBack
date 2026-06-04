<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property string $session_id
 * @property int $created_at
 * @property int $updated_at
 */
class GuestSession extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%guest_session}}';
    }

    public static function primaryKey(): array
    {
        return ['session_id'];
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['session_id'], 'required'],
            [['session_id'], 'string', 'max' => 64],
        ];
    }
}
