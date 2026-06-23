<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;
use yii\helpers\Url;

/**
 * @property int $id
 * @property int $product_id
 * @property string $image_url
 * @property int $sort_order
 * @property-read string $publicUrl
 */
class ProductImage extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%product_image}}';
    }

    public function rules(): array
    {
        return [
            [['product_id', 'image_url'], 'required'],
            [['product_id', 'sort_order'], 'integer'],
            [['image_url'], 'string', 'max' => 512],
            [['sort_order'], 'default', 'value' => 0],
        ];
    }

    public function getPublicUrl(): string
    {
        $stored = (string) $this->image_url;
        if ($stored === '') {
            return '';
        }

        if (preg_match('#^https?://#', $stored)) {
            $path = parse_url($stored, PHP_URL_PATH);
            if (!is_string($path) || !str_contains($path, '/uploads/products/')) {
                return $stored;
            }

            return Url::to('@web' . $path, true);
        }

        if (str_starts_with($stored, 'uploads/')) {
            return Url::to('@web/' . ltrim($stored, '/'), true);
        }

        return $stored;
    }
}
