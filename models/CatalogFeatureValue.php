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
            [['hex'], 'required',
                'when' => static function (self $model): bool {
                    return $model->isColorFeatureSelected();
                },
                // Без whenClient ActiveForm всегда требует hex, даже если поле скрыто — форма «ничего не делает».
                'whenClient' => 'function (attribute, value) {'
                    . 'var select = document.querySelector(\'#feature-value-form select[name="CatalogFeatureValue[feature_id]"]\');'
                    . 'if (!select) { return false; }'
                    . 'var colorFeatureId = String(select.getAttribute("data-color-feature-id") || "");'
                    . 'return colorFeatureId !== "" && String(select.value) === colorFeatureId;'
                    . '}',
            ],
        ];
    }

    public function beforeValidate(): bool
    {
        if (!parent::beforeValidate()) {
            return false;
        }

        if (!$this->isColorFeatureSelected()) {
            $this->hex = null;
        }

        return true;
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if (!$this->isColorFeatureSelected()) {
            $this->hex = null;
        }

        if ($this->hasAttribute('slug') && ($this->slug === '' || $this->slug === null)) {
            $featureId = (int) $this->feature_id;
            SlugHelper::assignUniqueSlug($this, 'name', 'slug', 'value', function ($query) use ($featureId): void {
                $query->andWhere(['feature_id' => $featureId]);
            });
        }

        return true;
    }

    public function isColorFeatureSelected(): bool
    {
        $feature = $this->feature;
        if ($feature === null && $this->feature_id !== null && (int) $this->feature_id > 0) {
            $feature = CatalogFeature::findOne((int) $this->feature_id);
        }

        return $feature !== null && $feature->isColor();
    }

    public static function ensureForColor(Color $color): ?self
    {
        $feature = CatalogFeature::findColorFeature();
        if ($feature === null) {
            return null;
        }

        $featureId = (int) $feature->id;
        $value = null;
        if ($color->slug !== '' && $color->slug !== null) {
            $value = static::findOne(['feature_id' => $featureId, 'slug' => $color->slug]);
        }
        if ($value === null) {
            $value = static::findOne(['feature_id' => $featureId, 'name' => $color->name]);
        }
        if ($value === null) {
            $value = new self([
                'feature_id' => $featureId,
                'name' => $color->name,
                'hex' => $color->hex,
            ]);
            if ($color->slug !== '' && $color->slug !== null) {
                $value->slug = $color->slug;
            }
            $value->save(false);

            return $value;
        }

        $dirty = [];
        if ($value->name !== $color->name) {
            $value->name = $color->name;
            $dirty[] = 'name';
        }
        if ($value->hex !== $color->hex) {
            $value->hex = $color->hex;
            $dirty[] = 'hex';
        }
        if ($color->slug !== '' && $color->slug !== null && $value->slug !== $color->slug) {
            $value->slug = $color->slug;
            $dirty[] = 'slug';
        }
        if ($dirty !== []) {
            $value->save(false, $dirty);
        }

        return $value;
    }

    public static function findColorForValue(self $value): ?Color
    {
        $feature = $value->feature ?? CatalogFeature::findOne($value->feature_id);
        if ($feature === null || !$feature->isColor()) {
            return null;
        }

        if ($value->slug !== null && $value->slug !== '') {
            $color = Color::findOne(['slug' => $value->slug]);
            if ($color !== null) {
                return $color;
            }
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
