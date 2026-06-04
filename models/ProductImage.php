<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

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
        $stored = $this->image_url;
        if (preg_match('#^https?://#', $stored)) {
            $path = parse_url($stored, PHP_URL_PATH);
            if (!is_string($path) || !str_contains($path, '/uploads/products/')) {
                return $stored;
            }

            $filename = basename($path);
        } elseif (str_contains($stored, 'uploads/products/')) {
            $filename = basename($stored);
        } else {
            return $stored;
        }

        return rtrim(Yii::getAlias('@httpwebuploads'), '/') . '/products/' . $filename;
    }
}
