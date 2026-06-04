<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $image_url
 * @property int $sort_order
 * @property bool $is_active
 */
class HomeBanner extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%home_banner}}';
    }

    public function rules(): array
    {
        return [
            [['image_url'], 'required'],
            [['image_url'], 'string', 'max' => 512],
            [['sort_order'], 'integer'],
            [['is_active'], 'boolean'],
        ];
    }
}
