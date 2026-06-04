<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $feature_id
 * @property string $name
 */
class CatalogFeatureValue extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%catalog_feature_value}}';
    }

    public function rules(): array
    {
        return [
            [['feature_id', 'name'], 'required'],
            [['feature_id'], 'integer'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    public function getFeature(): \yii\db\ActiveQuery
    {
        return $this->hasOne(CatalogFeature::class, ['id' => 'feature_id']);
    }

    /** @return array<int|string, string> */
    public static function getDropdownOptionsForFeature(int $featureId): array
    {
        $options = ['' => '—'];
        $values = static::find()
            ->where(['feature_id' => $featureId])
            ->orderBy(['name' => SORT_ASC])
            ->all();

        foreach ($values as $value) {
            $options[(int) $value->id] = $value->name;
        }

        return $options;
    }
}
