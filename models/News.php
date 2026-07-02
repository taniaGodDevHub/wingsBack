<?php

declare(strict_types=1);

namespace app\models;

use app\components\SlugHelper;
use app\services\NewsImageUploadService;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Url;
use yii\web\UploadedFile;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string|null $subtitle
 * @property string|null $text
 * @property string|null $image_url
 * @property int $created_at
 * @property bool $is_published
 */
class News extends ActiveRecord
{
    public ?UploadedFile $imageFile = null;
    public ?string $createdAtInput = '';

    public static function tableName(): string
    {
        return '{{%news}}';
    }

    public function rules(): array
    {
        return [
            [['title', 'slug', 'created_at'], 'required'],
            [['text'], 'string'],
            [['created_at'], 'integer'],
            [['is_published'], 'boolean'],
            [['title', 'slug', 'subtitle'], 'string', 'max' => 255],
            [['image_url'], 'string', 'max' => 512],
            [['slug'], 'unique'],
            [['createdAtInput'], 'safe'],
            [
                'imageFile',
                'file',
                'skipOnEmpty' => true,
                'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                'mimeTypes' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/x-webp'],
                'checkExtensionByMimeType' => false,
                'maxSize' => NewsImageUploadService::MAX_BYTES,
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'title' => Yii::t('app', 'Title'),
            'slug' => Yii::t('app', 'Slug'),
            'subtitle' => Yii::t('app', 'Subtitle'),
            'text' => Yii::t('app', 'Text'),
            'image_url' => Yii::t('app', 'Image'),
            'imageFile' => Yii::t('app', 'Image'),
            'created_at' => Yii::t('app', 'Created at'),
            'createdAtInput' => Yii::t('app', 'Created at'),
            'is_published' => Yii::t('app', 'Display on site'),
        ];
    }

    public function init(): void
    {
        parent::init();
        if ($this->isNewRecord && (int) $this->created_at <= 0) {
            $this->created_at = time();
        }
        if ((string) $this->createdAtInput === '') {
            $this->createdAtInput = (string) date('Y-m-d\TH:i', (int) $this->created_at);
        }
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if (is_array($values)) {
            unset($values['imageFile']);
        }

        parent::setAttributes($values, $safeOnly);
    }

    public function beforeValidate(): bool
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        $createdAtInput = trim((string) $this->createdAtInput);
        if ($createdAtInput !== '') {
            $timestamp = strtotime($createdAtInput);
            if ($timestamp === false) {
                $this->addError('createdAtInput', Yii::t('app', 'Invalid date.'));
            } else {
                $this->created_at = $timestamp;
            }
        }

        $this->slug = trim((string) $this->slug);
        $this->title = trim((string) $this->title);
        $this->subtitle = trim((string) ($this->subtitle ?? ''));
        if ($this->slug === '' && $this->title !== '') {
            $baseSlug = SlugHelper::fromName($this->title, 'news');
            $this->slug = SlugHelper::makeUnique(
                $baseSlug,
                fn (string $slug): bool => static::find()
                    ->andWhere(['slug' => $slug])
                    ->andFilterCompare('id', (int) $this->id, '<>')
                    ->exists(),
            );
        }

        return true;
    }

    public function afterFind(): void
    {
        parent::afterFind();
        $this->createdAtInput = (string) date('Y-m-d\TH:i', (int) $this->created_at);
    }

    public function hasLocalImage(): bool
    {
        return str_starts_with((string) $this->image_url, 'uploads/news/');
    }

    public function getImagePublicUrl(): ?string
    {
        if ($this->image_url === '' || $this->image_url === null) {
            return null;
        }

        if ($this->hasLocalImage()) {
            return Url::to('@web/' . ltrim($this->image_url, '/'), true);
        }

        if (str_starts_with((string) $this->image_url, 'http')) {
            return $this->image_url;
        }

        return null;
    }

    /** @return array{id:int,title:string,slug:string,image_url:string} */
    public function toApiCard(): array
    {
        return [
            'id' => (int) $this->id,
            'title' => (string) $this->title,
            'slug' => (string) $this->slug,
            'image_url' => (string) ($this->getImagePublicUrl() ?? ''),
        ];
    }

    /** @return array<string,mixed> */
    public function toApiDetail(): array
    {
        return [
            'id' => (int) $this->id,
            'title' => (string) $this->title,
            'slug' => (string) $this->slug,
            'subtitle' => $this->subtitle,
            'text' => $this->text,
            'image_url' => (string) ($this->getImagePublicUrl() ?? ''),
            'created_at' => (int) $this->created_at,
        ];
    }
}
