<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Url;
use yii\web\UploadedFile;

/**
 * @property int $id
 * @property string $title
 * @property string|null $subtitle
 * @property string $image_url
 * @property int $updated_at
 */
class HomeAbout extends ActiveRecord
{
    public ?UploadedFile $imageFile = null;

    public static function tableName(): string
    {
        return '{{%home_about}}';
    }

    public static function singleton(): self
    {
        $model = static::findOne(1);
        if ($model !== null) {
            return $model;
        }

        return new self([
            'id' => 1,
            'title' => '',
            'subtitle' => null,
            'image_url' => '',
            'updated_at' => time(),
        ]);
    }

    public function rules(): array
    {
        return [
            [['title'], 'required'],
            [['title', 'subtitle'], 'string', 'max' => 255],
            [['image_url'], 'string', 'max' => 512],
            [['updated_at'], 'integer'],
            [
                'imageFile',
                'file',
                'skipOnEmpty' => true,
                'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/x-webp'],
                'checkExtensionByMimeType' => false,
                'maxSize' => 5_242_880,
            ],
            ['imageFile', 'validateImageRequired'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'title' => Yii::t('app', 'Title'),
            'subtitle' => Yii::t('app', 'Subtitle'),
            'image_url' => Yii::t('app', 'Image'),
            'imageFile' => Yii::t('app', 'Image'),
        ];
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if (is_array($values)) {
            unset($values['imageFile']);
        }

        parent::setAttributes($values, $safeOnly);
    }

    public function validateImageRequired(string $attribute): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        if ($this->image_url === '' && $this->imageFile === null) {
            $this->addError($attribute, Yii::t('app', 'Upload an about block image.'));
        }
    }

    public function hasLocalImage(): bool
    {
        return str_starts_with((string) $this->image_url, 'uploads/');
    }

    public function getImagePublicUrl(): ?string
    {
        if ($this->image_url === '' || !$this->hasLocalImage()) {
            return null;
        }

        return Url::to('@web/' . ltrim($this->image_url, '/'), true);
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->updated_at = time();
        $this->subtitle = trim((string) ($this->subtitle ?? ''));
        if ($this->subtitle === '') {
            $this->subtitle = null;
        }

        return true;
    }

    /** @return array<string, mixed> */
    public function toApiArray(): array
    {
        return [
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'image_url' => $this->getImagePublicUrl() ?? '',
        ];
    }
}
