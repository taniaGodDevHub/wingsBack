<?php

declare(strict_types=1);

namespace app\models;

use app\components\SlugHelper;
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
        ];
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->hasAttribute('slug') && ($this->slug === '' || $this->slug === null)) {
            $base = SlugHelper::fromName((string) $this->name, 'color');
            $this->slug = SlugHelper::makeUnique($base, function (string $slug): bool {
                $query = static::find()->where(['slug' => $slug]);
                if (!$this->isNewRecord) {
                    $query->andWhere(['<>', 'id', $this->id]);
                }

                return $query->exists();
            });
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
