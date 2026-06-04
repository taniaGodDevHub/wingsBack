<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name_ru
 */
class CatalogFeature extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%catalog_feature}}';
    }

    public function rules(): array
    {
        return [
            [['name_ru'], 'required'],
            [['name_ru'], 'string', 'max' => 255],
        ];
    }

    public function getValues(): \yii\db\ActiveQuery
    {
        return $this->hasMany(CatalogFeatureValue::class, ['feature_id' => 'id'])
            ->orderBy(['name' => SORT_ASC]);
    }

    /** @return static[] */
    public static function findAllForAdminForm(): array
    {
        return static::find()
            ->with('values')
            ->orderBy(['name_ru' => SORT_ASC, 'id' => SORT_ASC])
            ->all();
    }
}
