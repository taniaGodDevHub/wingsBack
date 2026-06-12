<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Url;
use yii\web\UploadedFile;

/**
 * @property int $id
 * @property string $gender_code
 * @property string $image_url
 * @property int $updated_at
 */
class HomeGenderBlock extends ActiveRecord
{
    /** @var list<string> */
    public const CODES = ['male', 'female'];

    public ?UploadedFile $imageFile = null;

    public static function tableName(): string
    {
        return '{{%home_gender_block}}';
    }

    public function rules(): array
    {
        return [
            [['gender_code'], 'required'],
            [['gender_code'], 'string', 'max' => 16],
            [['gender_code'], 'in', 'range' => self::CODES],
            [['gender_code'], 'unique'],
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
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'gender_code' => Yii::t('app', 'Gender'),
            'image_url' => Yii::t('app', 'Image'),
            'imageFile' => Yii::t('app', 'Image'),
        ];
    }

    public static function getOrCreate(string $code): self
    {
        $model = static::findOne(['gender_code' => $code]);
        if ($model !== null) {
            return $model;
        }

        return new self([
            'gender_code' => $code,
            'image_url' => '',
            'updated_at' => time(),
        ]);
    }

    /** @return array<string, self> */
    public static function blocksMap(): array
    {
        $map = [];
        foreach (self::CODES as $code) {
            $map[$code] = self::getOrCreate($code);
        }

        return $map;
    }

    public function getDisplayName(): string
    {
        $gender = Gender::findOne(['code' => $this->gender_code]);

        return $gender?->name ?? $this->gender_code;
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if (is_array($values)) {
            unset($values['imageFile']);
        }

        parent::setAttributes($values, $safeOnly);
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
            'gender' => $this->gender_code,
            'name' => $this->getDisplayName(),
            'image_url' => $this->getImagePublicUrl() ?? '',
        ];
    }
}
