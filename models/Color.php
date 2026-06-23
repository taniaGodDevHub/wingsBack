<?php

declare(strict_types=1);

namespace app\models;

use app\components\SlugHelper;
use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $hex
 */
class Color extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%color}}';
    }

    public function rules(): array
    {
        return [
            [['name', 'hex'], 'required'],
            [['name'], 'string', 'max' => 100],
            [['hex'], 'string', 'max' => 7],
            [['slug'], 'string', 'max' => 255],
            [['slug'], 'unique'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Color name'),
            'slug' => Yii::t('app', 'Slug'),
            'hex' => Yii::t('app', 'Hex code'),
        ];
    }

    public static function findByIdOrSlug(int|string $idOrSlug): ?self
    {
        if (self::isNumericId($idOrSlug)) {
            return static::findOne((int) $idOrSlug);
        }

        if (is_string($idOrSlug) && $idOrSlug !== '') {
            return static::findOne(['slug' => $idOrSlug]);
        }

        return null;
    }

    public static function isNumericId(mixed $value): bool
    {
        if (is_int($value)) {
            return $value > 0;
        }

        if (!is_string($value) || $value === '') {
            return false;
        }

        return ctype_digit($value) && (int) $value > 0;
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->hasAttribute('slug') && ($this->slug === '' || $this->slug === null)) {
            SlugHelper::assignUniqueSlug($this, 'name', 'slug', 'color');
        }

        return true;
    }

    /** @return array<int|string, string> */
    public static function getDropdownOptions(): array
    {
        $options = ['' => '—'];
        foreach (static::find()->orderBy(['name' => SORT_ASC])->all() as $color) {
            $options[(int) $color->id] = $color->name;
        }

        return $options;
    }

    public function afterSave($insert, $changedAttributes): void
    {
        parent::afterSave($insert, $changedAttributes);
        CatalogFeatureValue::ensureForColor($this);
    }
}
