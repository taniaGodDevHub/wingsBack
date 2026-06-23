<?php

declare(strict_types=1);

namespace app\models;

use app\components\SlugHelper;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name_ru
 * @property string $slug
 * @property string|null $code
 */
class CatalogFeature extends ActiveRecord
{
    public const CODE_COLOR = 'color';

    public static function tableName(): string
    {
        return '{{%catalog_feature}}';
    }

    public function rules(): array
    {
        return [
            [['name_ru'], 'required'],
            [['name_ru'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 255],
            [['code'], 'string', 'max' => 32],
            [['code'], 'unique'],
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->hasAttribute('slug') && ($this->slug === '' || $this->slug === null)) {
            SlugHelper::assignUniqueSlug($this, 'name_ru', 'slug', 'feature');
        }

        return true;
    }

    public function isColor(): bool
    {
        return $this->code === self::CODE_COLOR;
    }

    public function isDuplicateColorFeature(): bool
    {
        $canonical = static::findColorFeature();
        if ($canonical === null || (int) $this->id === (int) $canonical->id) {
            return false;
        }

        return $this->name_ru === $canonical->name_ru;
    }

    public static function findColorFeature(): ?self
    {
        return static::findOne(['code' => self::CODE_COLOR]);
    }

    public function getValues(): \yii\db\ActiveQuery
    {
        return $this->hasMany(CatalogFeatureValue::class, ['feature_id' => 'id'])
            ->orderBy(['name' => SORT_ASC]);
    }

    /** @return static[] */
    public static function findAllForAdminForm(): array
    {
        $features = static::find()
            ->with('values')
            ->orderBy(['name_ru' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        return array_values(array_filter(
            $features,
            static fn (self $feature): bool => !$feature->isDuplicateColorFeature(),
        ));
    }
}
