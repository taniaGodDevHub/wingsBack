<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $email
 * @property int $created_at
 * @property int $updated_at
 */
class NewsSubscriptionEmail extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%news_subscription_email}}';
    }

    public function rules(): array
    {
        return [
            [['email'], 'required'],
            [['email'], 'string', 'max' => 255],
            [['email'], 'email'],
            [['created_at', 'updated_at'], 'integer'],
        ];
    }

    public function beforeValidate(): bool
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        $this->email = mb_strtolower(trim((string) $this->email));

        return true;
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $now = time();
        if ($insert) {
            $this->created_at = $now;
        }
        $this->updated_at = $now;

        return true;
    }
}
