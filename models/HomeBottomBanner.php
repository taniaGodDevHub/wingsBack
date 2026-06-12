<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Url;
use yii\web\UploadedFile;

/**
 * @property int $id
 * @property string $image_url
 * @property string $button_text
 * @property string|null $button_url
 * @property int $updated_at
 */
class HomeBottomBanner extends ActiveRecord
{
    public const DEFAULT_BUTTON_TEXT = 'Перейти в каталог';

    public ?UploadedFile $imageFile = null;

    public static function tableName(): string
    {
        return '{{%home_bottom_banner}}';
    }

    public static function singleton(): self
    {
        $model = static::findOne(1);
        if ($model !== null) {
            return $model;
        }

        return new self([
            'id' => 1,
            'image_url' => '',
            'button_text' => self::DEFAULT_BUTTON_TEXT,
            'button_url' => null,
            'updated_at' => time(),
        ]);
    }

    public function rules(): array
    {
        return [
            [['button_text'], 'required'],
            [['button_text'], 'string', 'max' => 128],
            [['button_url'], 'string', 'max' => 512],
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
            'image_url' => Yii::t('app', 'Image'),
            'imageFile' => Yii::t('app', 'Image'),
            'button_text' => Yii::t('app', 'Button text'),
            'button_url' => Yii::t('app', 'Button link'),
        ];
    }

    public function init(): void
    {
        parent::init();
        if ($this->button_text === '') {
            $this->button_text = self::DEFAULT_BUTTON_TEXT;
        }
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
            $this->addError($attribute, Yii::t('app', 'Upload a bottom banner image.'));
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

        return true;
    }

    /** @return array<string, mixed> */
    public function toApiArray(): array
    {
        return [
            'image_url' => $this->getImagePublicUrl() ?? '',
            'button_text' => $this->button_text,
            'button_url' => $this->button_url,
        ];
    }
}
