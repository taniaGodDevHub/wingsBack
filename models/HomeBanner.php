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
 * @property string|null $title
 * @property string|null $text
 * @property string $button_text
 * @property string|null $button_url
 * @property int $sort_order
 * @property bool $is_active
 */
class HomeBanner extends ActiveRecord
{
    public const DEFAULT_BUTTON_TEXT = 'Перейти в каталог';

    public ?UploadedFile $imageFile = null;

    public static function tableName(): string
    {
        return '{{%home_banner}}';
    }

    public function rules(): array
    {
        return [
            [['image_url'], 'required', 'when' => static fn (self $model): bool => $model->imageFile === null],
            [['image_url'], 'string', 'max' => 512],
            [['title'], 'string', 'max' => 255],
            [['text'], 'string'],
            [['button_text'], 'required'],
            [['button_text'], 'string', 'max' => 128],
            [['button_url'], 'string', 'max' => 512],
            [['sort_order'], 'integer'],
            [['is_active'], 'boolean'],
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
            'title' => Yii::t('app', 'Title'),
            'text' => Yii::t('app', 'Text'),
            'button_text' => Yii::t('app', 'Button text'),
            'button_url' => Yii::t('app', 'Button link'),
            'sort_order' => Yii::t('app', 'Sort order'),
            'is_active' => Yii::t('app', 'Display on homepage'),
        ];
    }

    public function init(): void
    {
        parent::init();
        if ($this->isNewRecord && $this->button_text === '') {
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

        if ($this->isNewRecord && $this->imageFile === null) {
            $this->addError($attribute, Yii::t('app', 'Upload a banner image.'));
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

    /** @return array<string, mixed> */
    public function toApiArray(): array
    {
        $imageUrl = $this->getImagePublicUrl();
        if ($imageUrl === null && str_starts_with((string) $this->image_url, 'http')) {
            $imageUrl = $this->image_url;
        }

        return [
            'id' => (int) $this->id,
            'image_url' => $imageUrl ?? '',
            'title' => $this->title,
            'text' => $this->text,
            'button_text' => $this->button_text,
            'button_url' => $this->button_url,
            'sort_order' => (int) $this->sort_order,
        ];
    }
}
