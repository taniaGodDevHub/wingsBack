<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string|null $brand
 * @property string|null $product_code
 * @property float $price
 * @property float|null $old_price
 * @property float $blago
 * @property bool $is_available
 * @property bool $is_bestseller
 * @property bool $is_featured_home
 * @property int $featured_sort
 * @property int $bestseller_rank
 * @property string|null $gender
 * @property string|null $search_text
 * @property int $created_at
 * @property int $updated_at
 * @property int|null $categoryId
 * @property array<int, int|string> $featureValueByFeatureId feature_id => feature_value_id
 */
class Product extends ActiveRecord
{
    public ?int $categoryId = null;

    /** @var array<int, int|string> */
    public array $featureValueByFeatureId = [];
    public static function tableName(): string
    {
        return '{{%product}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['slug', 'name', 'price'], 'required'],
            [['price', 'old_price', 'blago'], 'number', 'min' => 0],
            [['is_available', 'is_bestseller', 'is_featured_home'], 'boolean'],
            [['featured_sort', 'bestseller_rank'], 'integer'],
            [['gender'], 'string', 'max' => 16],
            [['gender'], 'in', 'range' => static fn (): array => Gender::getActiveCodes(), 'skipOnEmpty' => true],
            [['brand', 'product_code'], 'string', 'max' => 255],
            [['slug'], 'unique'],
            [['categoryId'], 'integer'],
            [['featureValueByFeatureId'], 'safe'],
        ];
    }

    public function afterFind(): void
    {
        parent::afterFind();
        if ($this->categoryId === null && $this->isRelationPopulated('categories')) {
            $category = $this->categories[0] ?? null;
            $this->categoryId = $category !== null ? (int) $category->id : null;
        }
        if ($this->isRelationPopulated('featureValues')) {
            $this->syncFeatureValueSelectionsFromRelation();
        }
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'slug' => Yii::t('app', 'Slug'),
            'name' => Yii::t('app', 'Product name'),
            'brand' => Yii::t('app', 'Brand'),
            'product_code' => Yii::t('app', 'Product code'),
            'price' => Yii::t('app', 'Price'),
            'old_price' => Yii::t('app', 'Old price'),
            'blago' => Yii::t('app', 'Blago'),
            'is_available' => Yii::t('app', 'Available'),
            'is_bestseller' => Yii::t('app', 'Bestseller'),
            'is_featured_home' => Yii::t('app', 'Featured on home'),
            'featured_sort' => Yii::t('app', 'Featured sort order'),
            'bestseller_rank' => Yii::t('app', 'Bestseller rank'),
            'gender' => Yii::t('app', 'Gender'),
            'categoryId' => Yii::t('app', 'Category'),
            'search_text' => Yii::t('app', 'Search text'),
            'created_at' => Yii::t('app', 'Created at'),
            'updated_at' => Yii::t('app', 'Updated at'),
        ];
    }

    public function getImages(): \yii\db\ActiveQuery
    {
        return $this->hasMany(ProductImage::class, ['product_id' => 'id'])->orderBy(['sort_order' => SORT_ASC]);
    }

    public function getCategories(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Category::class, ['id' => 'category_id'])
            ->viaTable('{{%product_category}}', ['product_id' => 'id']);
    }

    public function getColors(): \yii\db\ActiveQuery
    {
        return $this->hasMany(Color::class, ['id' => 'color_id'])
            ->viaTable('{{%product_color}}', ['product_id' => 'id']);
    }

    public function getSizes(): \yii\db\ActiveQuery
    {
        return $this->hasMany(ProductSize::class, ['product_id' => 'id']);
    }

    public function getFeatureValues(): \yii\db\ActiveQuery
    {
        return $this->hasMany(CatalogFeatureValue::class, ['id' => 'feature_value_id'])
            ->viaTable('{{%product_feature_value}}', ['product_id' => 'id']);
    }

    public function syncFeatureValueSelectionsFromRelation(): void
    {
        $this->featureValueByFeatureId = [];
        foreach ($this->featureValues as $value) {
            $featureId = (int) $value->feature_id;
            if (!isset($this->featureValueByFeatureId[$featureId])) {
                $this->featureValueByFeatureId[$featureId] = (int) $value->id;
            }
        }
    }

    public static function findAvailable(int $id): ?static
    {
        return static::find()
            ->where(['id' => $id, 'is_available' => true])
            ->one();
    }

    /** @return string[] */
    public function getSizeValues(): array
    {
        $values = [];
        foreach ($this->sizes as $size) {
            $values[] = $size->size_value;
        }

        return $values;
    }

    /** @return array<int, array<string, mixed>> */
    public function getColorsData(): array
    {
        $result = [];
        foreach ($this->colors as $color) {
            $result[] = [
                'id' => (int) $color->id,
                'name' => $color->name,
                'hex' => $color->hex,
            ];
        }

        return $result;
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }
        $this->search_text = mb_strtolower($this->name . ' ' . $this->slug);

        return true;
    }
}
