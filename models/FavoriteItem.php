<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property int $product_id
 * @property int $created_at
 */
class FavoriteItem extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%favorite_item}}';
    }

    public function rules(): array
    {
        return [
            [['product_id'], 'required'],
            [['user_id'], 'integer'],
            [['session_id'], 'string', 'max' => 64],
            [['product_id'], 'integer'],
            [['created_at'], 'integer'],
        ];
    }

    public function beforeValidate(): bool
    {
        if ($this->isNewRecord && empty($this->created_at)) {
            $this->created_at = time();
        }

        return parent::beforeValidate();
    }
}
