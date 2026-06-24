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
 * @property string|null $description
 * @property int|null $product_group_id
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
 * @property array<int, array{chest_circumference: string, is_in_stock: bool}> $sizeChartBySizeId
 * @property-read ProductGroup|null $productGroup
 */
class Product extends ActiveRecord
{
    public const BLAGO_UNIT_RUB = 'rub';
    public const BLAGO_UNIT_PERCENT = 'percent';

    public ?int $categoryId = null;

    public string $blago_unit = self::BLAGO_UNIT_RUB;

    public string $blago_input = '';

    /** @var array<int, int|string> */
    public array $featureValueByFeatureId = [];

    /** @var array<int, array{chest_circumference: string, is_in_stock: bool}> */
    public array $sizeChartBySizeId = [];

    public string $newProductGroupName = '';
    public static function tableName(): string
    {
        return '{{%product}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function setAttributes($values, $safeOnly = true): void
    {
        if (is_array($values) && array_key_exists('categoryId', $values)) {
            $categoryId = $values['categoryId'];
            $values['categoryId'] = $categoryId === '' || $categoryId === null ? null : (int) $categoryId;
        }

        if (is_array($values) && array_key_exists('product_group_id', $values)) {
            $groupId = $values['product_group_id'];
            $values['product_group_id'] = $groupId === '' || $groupId === null ? null : (int) $groupId;
        }

        parent::setAttributes($values, $safeOnly);
    }

    public function rules(): array
    {
        return [
            [['slug', 'name', 'price'], 'required'],
            [['price', 'old_price', 'blago'], 'number', 'min' => 0],
            [['blago_input'], 'number', 'min' => 0, 'skipOnEmpty' => true],
            [['blago_input'], 'number', 'max' => 100, 'when' => static fn (self $model): bool => $model->blago_unit === self::BLAGO_UNIT_PERCENT],
            [['blago_unit'], 'in', 'range' => [self::BLAGO_UNIT_RUB, self::BLAGO_UNIT_PERCENT]],
            [['blago_unit', 'blago_input'], 'safe'],
            [['is_available', 'is_bestseller', 'is_featured_home'], 'boolean'],
            [['featured_sort', 'bestseller_rank'], 'default', 'value' => 0],
            [['featured_sort', 'bestseller_rank'], 'integer', 'min' => 0],
            [['gender'], 'string', 'max' => 16],
            [['gender'], 'in', 'range' => static fn (): array => Gender::getActiveCodes(), 'skipOnEmpty' => true],
            [['brand', 'product_code'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['slug'], 'unique'],
            [['categoryId', 'product_group_id'], 'integer'],
            [['featureValueByFeatureId', 'sizeChartBySizeId', 'newProductGroupName'], 'safe'],
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
        if ($this->isRelationPopulated('sizes')) {
            $this->syncSizeChartFromRelation();
        }

        $this->blago_unit = self::BLAGO_UNIT_RUB;
        $this->blago_input = (float) $this->blago > 0 ? (string) (float) $this->blago : '';
    }

    public function attributeLabels(): array
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'slug' => Yii::t('app', 'Slug'),
            'name' => Yii::t('app', 'Product name'),
            'description' => Yii::t('app', 'Description'),
            'brand' => Yii::t('app', 'Brand'),
            'product_code' => Yii::t('app', 'Product code'),
            'price' => Yii::t('app', 'Price'),
            'old_price' => Yii::t('app', 'Old price'),
            'blago' => Yii::t('app', 'Blago'),
            'blago_unit' => Yii::t('app', 'Blago unit'),
            'blago_input' => Yii::t('app', 'Blago'),
            'is_available' => Yii::t('app', 'Available'),
            'is_bestseller' => Yii::t('app', 'Bestseller'),
            'is_featured_home' => Yii::t('app', 'Featured on home'),
            'featured_sort' => Yii::t('app', 'Featured sort order'),
            'bestseller_rank' => Yii::t('app', 'Bestseller rank'),
            'gender' => Yii::t('app', 'Gender'),
            'categoryId' => Yii::t('app', 'Category'),
            'product_group_id' => Yii::t('app', 'Product group'),
            'newProductGroupName' => Yii::t('app', 'Create new product group'),
            'sizeChartBySizeId' => Yii::t('app', 'Size chart'),
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

    public function getSizes(): \yii\db\ActiveQuery
    {
        return $this->hasMany(ProductSize::class, ['product_id' => 'id'])
            ->joinWith('size')
            ->orderBy(['{{%size}}.sort_order' => SORT_ASC, '{{%size}}.id' => SORT_ASC]);
    }

    public function getFeatureValues(): \yii\db\ActiveQuery
    {
        return $this->hasMany(CatalogFeatureValue::class, ['id' => 'feature_value_id'])
            ->viaTable('{{%product_feature_value}}', ['product_id' => 'id']);
    }

    public function getProductGroup(): \yii\db\ActiveQuery
    {
        return $this->hasOne(ProductGroup::class, ['id' => 'product_group_id']);
    }

    public function syncFeatureValueSelectionsFromRelation(): void
    {
        $this->featureValueByFeatureId = [];
        foreach ($this->featureValues as $value) {
            $featureId = (int) $value->feature_id;
            if (isset($this->featureValueByFeatureId[$featureId])) {
                continue;
            }

            $feature = $value->feature ?? CatalogFeature::findOne($featureId);
            if ($feature !== null && $feature->isColor()) {
                $color = CatalogFeatureValue::findColorForValue($value);
                if ($color !== null) {
                    $this->featureValueByFeatureId[$featureId] = (int) $color->id;
                    continue;
                }
            }

            $this->featureValueByFeatureId[$featureId] = (int) $value->id;
        }
    }

    public static function findAvailable(int $id): ?static
    {
        return static::find()
            ->where(['id' => $id, 'is_available' => true])
            ->one();
    }

    public function syncSizeChartFromRelation(): void
    {
        $this->sizeChartBySizeId = [];
        foreach ($this->sizes as $productSize) {
            $sizeId = (int) $productSize->size_id;
            if ($sizeId <= 0) {
                continue;
            }

            $this->sizeChartBySizeId[$sizeId] = [
                'chest_circumference' => (string) ($productSize->chest_circumference ?? ''),
                'is_in_stock' => (bool) $productSize->is_in_stock,
            ];
        }
    }

    public function initSizeChartFromDefaults(): void
    {
        $this->sizeChartBySizeId = [];
        foreach (Size::findAllOrdered() as $size) {
            $this->sizeChartBySizeId[(int) $size->id] = [
                'chest_circumference' => (string) $size->default_chest_circumference,
                'is_in_stock' => false,
            ];
        }
    }

    public function ensureSizeChartInitialized(): void
    {
        if ($this->sizeChartBySizeId !== []) {
            return;
        }

        if ($this->isRelationPopulated('sizes') && $this->sizes !== []) {
            $this->syncSizeChartFromRelation();

            return;
        }

        $this->initSizeChartFromDefaults();
    }

    /** @return string[] */
    public function getSizeValues(): array
    {
        $values = [];
        foreach ($this->sizes as $productSize) {
            if (!(bool) $productSize->is_in_stock) {
                continue;
            }

            $sizeValue = $productSize->getSizeValue();
            if ($sizeValue !== '') {
                $values[] = $sizeValue;
            }
        }

        return $values;
    }

    /**
     * @return array<int, array{rus_label: string, size_value: string, chest_circumference: string, is_in_stock: bool}>
     */
    public function getSizeChartData(): array
    {
        $rows = [];
        foreach ($this->sizes as $productSize) {
            $size = $productSize->size;
            if ($size === null) {
                continue;
            }

            $chest = trim((string) ($productSize->chest_circumference ?? ''));
            if ($chest === '') {
                $chest = (string) $size->default_chest_circumference;
            }

            $rows[] = [
                'rus_label' => (string) $size->rus_label,
                'size_value' => (string) $size->size_value,
                'chest_circumference' => $chest,
                'is_in_stock' => (bool) $productSize->is_in_stock,
            ];
        }

        return $rows;
    }

    /** @return Product[] */
    public function getGroupVariantProducts(): array
    {
        if ($this->product_group_id === null || (int) $this->product_group_id <= 0) {
            return [];
        }

        if ($this->isRelationPopulated('productGroup') && $this->productGroup !== null && $this->productGroup->isRelationPopulated('products')) {
            $products = [];
            foreach ($this->productGroup->products as $product) {
                if (!(bool) $product->is_available) {
                    continue;
                }

                $products[] = $product;
            }

            return $products;
        }

        return static::find()
            ->where([
                'product_group_id' => (int) $this->product_group_id,
                'is_available' => true,
            ])
            ->with(['featureValues.feature'])
            ->orderBy(['id' => SORT_ASC])
            ->all();
    }

    /** @return array{id: int, slug: string, name: string, hex: string}|null */
    public function getColorData(): ?array
    {
        if (!$this->isRelationPopulated('featureValues')) {
            return null;
        }

        foreach ($this->featureValues as $value) {
            $feature = $value->feature;
            if ($feature === null || !$feature->isColor()) {
                continue;
            }

            $color = CatalogFeatureValue::findColorForValue($value);
            if ($color !== null) {
                return [
                    'id' => (int) $color->id,
                    'slug' => (string) $color->slug,
                    'name' => $color->name,
                    'hex' => $color->hex,
                ];
            }

            return [
                'id' => (int) $value->id,
                'slug' => (string) ($value->slug ?? ''),
                'name' => $value->name,
                'hex' => $value->hex ?? '',
            ];
        }

        return null;
    }

    /** @return array<string, string> */
    public static function getBlagoUnitOptions(): array
    {
        return [
            self::BLAGO_UNIT_RUB => '₽',
            self::BLAGO_UNIT_PERCENT => '%',
        ];
    }

    public function resolveBlagoAmount(): float
    {
        if ($this->blago_input === '' || $this->blago_input === null) {
            return 0.0;
        }

        $input = (float) $this->blago_input;
        if ($this->blago_unit === self::BLAGO_UNIT_PERCENT) {
            return round((float) $this->price * $input / 100, 2);
        }

        return round($input, 2);
    }

    public function beforeSave($insert): bool
    {
        if (!parent::beforeSave($insert)) {
            return false;
        }

        $this->blago = $this->resolveBlagoAmount();
        $this->search_text = mb_strtolower(trim($this->name . ' ' . $this->slug . ' ' . (string) ($this->description ?? '')));
        $this->featured_sort = (int) ($this->featured_sort ?? 0);
        $this->bestseller_rank = (int) ($this->bestseller_rank ?? 0);

        return true;
    }
}
