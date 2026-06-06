<?php

declare(strict_types=1);

namespace app\models;

use app\components\SlugHelper;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $feature_id
 * @property string $name
 * @property string $slug
 * @property string|null $hex
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
            [['slug'], 'string', 'max' => 255],
            [['hex'], 'string', 'max' => 7],
            [['hex'], 'required', 'when' => static function (self $model): bool {
                $feature = $model->feature ?? CatalogFeature::findOne($model->feature_id);

                return $feature !== null && $feature->isColor();
            }],
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->hasAttribute('slug') && ($this->slug === '' || $this->slug === null)) {
            $featureId = (int) $this->feature_id;
            $base = SlugHelper::fromName((string) $this->name, 'value');
            $this->slug = SlugHelper::makeUnique($base, function (string $slug) use ($featureId): bool {
                $query = static::find()->where(['feature_id' => $featureId, 'slug' => $slug]);
                if (!$this->isNewRecord) {
                    $query->andWhere(['<>', 'id', $this->id]);
                }

                return $query->exists();
            });
        }

        return true;
    }

    public static function ensureForColor(Color $color): ?self
    {
        $feature = CatalogFeature::findColorFeature();
        if ($feature === null) {
            return null;
        }

        $featureId = (int) $feature->id;
        $value = static::findOne(['feature_id' => $featureId, 'name' => $color->name]);
        if ($value === null) {
            $value = new self([
                'feature_id' => $featureId,
                'name' => $color->name,
                'hex' => $color->hex,
            ]);
            $value->save(false);

            return $value;
        }

        if ($value->hex !== $color->hex) {
            $value->hex = $color->hex;
            $value->save(false, ['hex']);
        }

        return $value;
    }

    public static function findColorForValue(self $value): ?Color
    {
        $feature = $value->feature ?? CatalogFeature::findOne($value->feature_id);
        if ($feature === null || !$feature->isColor()) {
            return null;
        }

        $query = Color::find()->where(['name' => $value->name]);
        if ($value->hex !== null && $value->hex !== '') {
            $query->andWhere(['hex' => $value->hex]);
        }

        return $query->one();
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
