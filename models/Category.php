<?php

declare(strict_types=1);

namespace app\models;

use app\components\SlugHelper;
use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property int $sort_order
 * @property bool $is_active
 */
class Category extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%category}}';
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['parent_id', 'sort_order'], 'integer'],
            [['sort_order'], 'default', 'value' => 0],
            [['is_active'], 'boolean'],
            [['slug'], 'string', 'max' => 255],
            [['slug'], 'unique'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Category name'),
            'slug' => Yii::t('app', 'Slug'),
            'parent_id' => Yii::t('app', 'Parent category'),
            'sort_order' => Yii::t('app', 'Sort order'),
            'is_active' => Yii::t('app', 'Is active'),
        ];
    }

    public function getChildren(): \yii\db\ActiveQuery
    {
        return $this->hasMany(self::class, ['parent_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
    }

    public static function findBySlug(string $slug): ?static
    {
        return static::findOne(['slug' => $slug, 'is_active' => true]);
    }

    /** @return array<int|string, string> */
    public static function getDropdownOptions(): array
    {
        $categories = static::find()
            ->orderBy(['sort_order' => SORT_ASC, 'name' => SORT_ASC])
            ->all();

        $byId = [];
        foreach ($categories as $category) {
            $byId[(int) $category->id] = $category;
        }

        $options = ['' => '—'];
        foreach ($categories as $category) {
            $label = $category->name;
            $parentId = $category->parent_id !== null ? (int) $category->parent_id : null;
            if ($parentId !== null && isset($byId[$parentId])) {
                $label = $byId[$parentId]->name . ' → ' . $label;
            }
            $options[(int) $category->id] = $label;
        }

        return $options;
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        if ($this->parent_id === '' || $this->parent_id === 0) {
            $this->parent_id = null;
        }
        if ($this->sort_order === '' || $this->sort_order === null) {
            $this->sort_order = 0;
        }

        SlugHelper::assignUniqueSlug($this, 'name', 'slug', 'category');

        return true;
    }
}
