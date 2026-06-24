<?php

declare(strict_types=1);

namespace app\models;

use app\components\SlugHelper;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $created_at
 * @property int $updated_at
 */
class ProductGroup extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%product_group}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['slug'], 'string', 'max' => 255],
            [['slug'], 'unique'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Product group name'),
            'slug' => Yii::t('app', 'Slug'),
            'created_at' => Yii::t('app', 'Created at'),
            'updated_at' => Yii::t('app', 'Updated at'),
        ];
    }

    public function getProducts(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Product::class, ['product_group_id' => 'id'])
            ->orderBy(['id' => SORT_ASC]);
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        if ($this->slug === '' || $this->slug === null) {
            $base = 'group-' . SlugHelper::fromName((string) $this->name, 'group');
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
        $options = ['' => Yii::t('app', 'No product group')];
        foreach (static::find()->orderBy(['name' => SORT_ASC])->all() as $group) {
            $options[(int) $group->id] = $group->name;
        }

        return $options;
    }
}
