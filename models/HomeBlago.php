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
 * @property int $collection_start_at
 * @property int $collection_end_at
 * @property float $amount
 * @property string $image_url
 * @property int $updated_at
 */
class HomeBlago extends ActiveRecord
{
    public ?UploadedFile $imageFile = null;
    public string $collectionStartInput = '';
    public string $collectionEndInput = '';

    public static function tableName(): string
    {
        return '{{%home_blago}}';
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
            'collection_start_at' => 0,
            'collection_end_at' => 0,
            'amount' => 0,
            'image_url' => '',
            'updated_at' => time(),
        ]);
    }

    public function rules(): array
    {
        return [
            [['title', 'collectionStartInput', 'collectionEndInput', 'amount'], 'required'],
            [['title'], 'string', 'max' => 255],
            [['collection_start_at', 'collection_end_at', 'updated_at'], 'integer'],
            [['amount'], 'number', 'min' => 0],
            [['image_url'], 'string', 'max' => 512],
            [['collectionStartInput', 'collectionEndInput'], 'safe'],
            ['collectionEndInput', 'validateCollectionPeriod'],
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
            'collectionStartInput' => Yii::t('app', 'Collection start'),
            'collectionEndInput' => Yii::t('app', 'Collection end'),
            'amount' => Yii::t('app', 'Amount'),
            'image_url' => Yii::t('app', 'Image'),
            'imageFile' => Yii::t('app', 'Image'),
        ];
    }

    public function init(): void
    {
        parent::init();
        $this->syncDateInputsFromTimestamps();
    }

    public function afterFind(): void
    {
        parent::afterFind();
        $this->syncDateInputsFromTimestamps();
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

        $this->title = trim($this->title);
        $this->collection_start_at = $this->parseDateInput($this->collectionStartInput, false);
        $this->collection_end_at = $this->parseDateInput($this->collectionEndInput, true);

        return true;
    }

    public function validateCollectionPeriod(string $attribute): void
    {
        if ($this->hasErrors('collectionStartInput') || $this->hasErrors('collectionEndInput')) {
            return;
        }

        $startInput = trim($this->collectionStartInput);
        $endInput = trim($this->collectionEndInput);
        if ($startInput === '' || $endInput === '') {
            return;
        }

        if ($this->collection_start_at <= 0) {
            $this->addError('collectionStartInput', Yii::t('app', 'Invalid date.'));

            return;
        }

        if ($this->collection_end_at <= 0) {
            $this->addError('collectionEndInput', Yii::t('app', 'Invalid date.'));

            return;
        }

        if ($this->collection_end_at < $this->collection_start_at) {
            $this->addError($attribute, Yii::t('app', 'Collection end must be on or after collection start.'));
        }
    }

    public function validateImageRequired(string $attribute): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        if ($this->image_url === '' && $this->imageFile === null) {
            $this->addError($attribute, Yii::t('app', 'Upload a blago block image.'));
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
        $this->amount = round((float) $this->amount, 2);

        return true;
    }

    /** @return array<string, mixed> */
    public function toApiArray(): array
    {
        return [
            'title' => $this->title,
            'collection_start_at' => (int) $this->collection_start_at,
            'collection_end_at' => (int) $this->collection_end_at,
            'amount' => (float) $this->amount,
            'image_url' => $this->getImagePublicUrl() ?? '',
        ];
    }

    private function syncDateInputsFromTimestamps(): void
    {
        $this->collectionStartInput = $this->collection_start_at > 0
            ? date('Y-m-d', (int) $this->collection_start_at)
            : '';
        $this->collectionEndInput = $this->collection_end_at > 0
            ? date('Y-m-d', (int) $this->collection_end_at)
            : '';
    }

    private function parseDateInput(string $input, bool $endOfDay): int
    {
        $input = trim($input);
        if ($input === '') {
            return 0;
        }

        $time = strtotime($input . ($endOfDay ? ' 23:59:59' : ' 00:00:00'));
        if ($time === false) {
            return 0;
        }

        return $time;
    }
}
