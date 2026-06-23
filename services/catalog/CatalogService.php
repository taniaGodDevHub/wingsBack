<?php

declare(strict_types=1);

namespace app\services\catalog;

use app\components\api\ApiHttpException;
use app\models\CatalogFeature;
use app\models\CatalogFeatureValue;
use app\models\Category;
use app\models\Color;
use app\models\HomeAbout;
use app\models\HomeBanner;
use app\models\HomeBottomBanner;
use app\models\HomeGenderBlock;
use app\models\Product;
use Yii;
use yii\db\ActiveQuery;
use yii\db\Expression;
use yii\db\Query;

class CatalogService
{
    public function showcase(int $page, int $pageSize): array
    {
        $page = max(1, $page);
        $pageSize = min(100, max(1, $pageSize));

        $query = $this->baseProductQuery()
            ->orderBy([
                'p.is_featured_home' => SORT_DESC,
                'p.featured_sort' => SORT_ASC,
                'p.is_bestseller' => SORT_DESC,
                'p.bestseller_rank' => SORT_ASC,
                'p.created_at' => SORT_DESC,
            ]);

        return $this->paginateProducts($query, $page, $pageSize, true);
    }

    public function universalSearch(string $search, int $limitProducts, int $limitCategories): array
    {
        $search = trim($search);
        if (mb_strlen($search) < 2) {
            throw new \InvalidArgumentException('search must be at least 2 characters.');
        }
        if (mb_strlen($search) > 100) {
            $search = mb_substr($search, 0, 100);
        }

        $limitProducts = min(20, max(1, $limitProducts));
        $limitCategories = min(20, max(1, $limitCategories));
        $term = mb_strtolower($search);
        $like = '%' . $term . '%';
        $prefix = $term . '%';

        $searchCondition = [
            'or',
            ['like', 'p.search_text', $like, false],
            ['like', 'p.slug', $like, false],
        ];
        if (mb_strlen($term) >= 3) {
            $searchCondition[] = new Expression(
                'SOUNDEX(SUBSTRING(p.name, 1, 100)) = SOUNDEX(:snd)',
                [':snd' => $term],
            );
        }

        $productQuery = $this->baseProductQuery()
            ->andWhere($searchCondition)
            ->orderBy([
                new Expression('CASE WHEN p.search_text LIKE :prefix OR p.slug LIKE :prefix THEN 0 ELSE 1 END', [
                    ':prefix' => $prefix,
                ]),
                'p.is_bestseller' => SORT_DESC,
                'p.name' => SORT_ASC,
            ])
            ->limit($limitProducts);

        $products = $productQuery->all();
        if (count($products) < $limitProducts) {
            $products = $this->appendFuzzyProducts($products, $term, $limitProducts);
        }

        $productTotal = (int) $this->baseProductQuery()->andWhere($searchCondition)->count();

        $categoryQuery = Category::find()
            ->where(['is_active' => true])
            ->andWhere(['or', ['like', 'name', $like, false], ['like', 'slug', $like, false]])
            ->orderBy([
                new Expression('CASE WHEN name LIKE :cprefix OR slug LIKE :cprefix THEN 0 ELSE 1 END', [
                    ':cprefix' => $prefix,
                ]),
                'sort_order' => SORT_ASC,
            ])
            ->limit($limitCategories);
        $categories = $categoryQuery->all();
        $categoryTotal = (int) Category::find()
            ->where(['is_active' => true])
            ->andWhere(['or', ['like', 'name', $like, false], ['like', 'slug', $like, false]])
            ->count();

        return [
            'query' => $search,
            'products' => [
                'total' => $productTotal,
                'data' => array_map([ProductPresenter::class, 'universalProduct'], $products),
            ],
            'categories' => [
                'total' => $categoryTotal,
                'data' => array_map(static fn (Category $c): array => [
                    'id' => (int) $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                ], $categories),
            ],
        ];
    }

    public function categoryTree(?string $contextSlug = null): array
    {
        $categories = Category::find()
            ->where(['is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC])
            ->all();

        $byParent = [];
        foreach ($categories as $category) {
            $byParent[$category->parent_id ?? 0][] = $category;
        }

        $relatedIds = null;
        $contextCategoryId = null;
        if ($contextSlug !== null && $contextSlug !== '') {
            $context = Category::findBySlug($contextSlug);
            if ($context !== null) {
                $relatedIds = $this->collectCategoryPathIds($context);
                $contextCategoryId = (int) $context->id;
            }
        }

        return $this->buildTreeNodes($byParent, 0, $relatedIds, $contextCategoryId);
    }

    /** @param array<string, mixed> $params */
    public function search(array $params, ?Category $category = null): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $pageSize = min(100, max(1, (int) ($params['page_size'] ?? 60)));

        $query = $this->baseProductQuery();
        if ($category !== null) {
            $query->innerJoin('{{%product_category}} pc_filter', 'pc_filter.product_id = p.id')
                ->andWhere(['pc_filter.category_id' => $category->id]);
        }

        $this->applySearchFilters($query, $params);
        $this->applySort($query, $params);

        $result = $this->paginateProducts($query, $page, $pageSize, false);
        $result['page_size'] = $pageSize;
        $result['available_filters'] = $this->buildAvailableFilters($params, $category);
        $result['category'] = $category === null ? null : [
            'id' => (int) $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ];

        return $result;
    }

    public function searchByCategorySlug(string $slug, array $params): array
    {
        $category = Category::findBySlug($slug);
        if ($category === null) {
            throw ApiHttpException::notFound('Category not found');
        }

        return $this->search($params, $category);
    }

    /** @param int[]|null $relatedIds */
    private function buildTreeNodes(
        array $byParent,
        int $parentKey,
        ?array $relatedIds,
        ?int $contextCategoryId = null,
    ): array {
        $nodes = [];
        foreach ($byParent[$parentKey] ?? [] as $category) {
            $categoryId = (int) $category->id;
            $children = $this->buildTreeNodes($byParent, $categoryId, $relatedIds, $contextCategoryId);
            $nodes[] = [
                'id' => $categoryId,
                'name' => $category->name,
                'slug' => $category->slug,
                'related' => $relatedIds === null
                    || (in_array($categoryId, $relatedIds, true) && $categoryId !== $contextCategoryId),
                'children' => $children,
            ];
        }

        return $nodes;
    }

    /** @return int[] */
    private function collectCategoryPathIds(Category $category): array
    {
        $ids = [(int) $category->id];
        $parentId = $category->parent_id;
        while ($parentId !== null) {
            $parent = Category::findOne($parentId);
            if ($parent === null) {
                break;
            }
            $ids[] = (int) $parent->id;
            $parentId = $parent->parent_id;
        }

        return $ids;
    }

    private function baseProductQuery(): ActiveQuery
    {
        return Product::find()
            ->alias('p')
            ->where(['p.is_available' => true])
            ->with(['images', 'categories', 'sizes', 'featureValues.feature']);
    }

    /** @param array<string, mixed> $params */
    private function applySearchFilters(ActiveQuery $query, array $params): void
    {
        $categoryIds = $this->parseCsvInts((string) ($params['category_ids'] ?? ''));
        if ($categoryIds !== []) {
            $query->innerJoin('{{%product_category}} pc_cat', 'pc_cat.product_id = p.id')
                ->andWhere(['pc_cat.category_id' => $categoryIds]);
        }

        $sizes = $this->parseCsvStrings((string) ($params['size_values'] ?? ''));
        if ($sizes !== []) {
            $query->innerJoin('{{%product_size}} psz', 'psz.product_id = p.id')
                ->andWhere(['psz.size_value' => $sizes]);
        }

        $gender = (string) ($params['gender'] ?? '');
        if ($gender !== '' && in_array($gender, ['male', 'female', 'unisex'], true)) {
            $query->andWhere(['p.gender' => $gender]);
        }

        if (isset($params['price_min']) && $params['price_min'] !== '') {
            $query->andWhere(['>=', 'p.price', (float) $params['price_min']]);
        }
        if (isset($params['price_max']) && $params['price_max'] !== '') {
            $query->andWhere(['<=', 'p.price', (float) $params['price_max']]);
        }

        $this->applyFeatureFilters($query, $params);
        // legacy params — safely ignored
        unset($params['brand_ids'], $params['country_ids'], $params['color_ids']);

        $query->groupBy('p.id');
    }

    /** @param array<string, mixed> $params */
    private function applyFeatureFilters(ActiveQuery $query, array $params): void
    {
        $raw = $params['feature_filters'] ?? '';
        if ($raw === '' || $raw === null) {
            return;
        }

        $decoded = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($decoded)) {
            return;
        }

        $index = 0;
        foreach ($decoded as $featureKey => $rawValues) {
            if (!is_array($rawValues) || $rawValues === []) {
                continue;
            }

            if ((string) $featureKey === 'color') {
                $valueIds = $this->resolveColorFilterValueIds($rawValues);
                if ($valueIds === []) {
                    continue;
                }
            } else {
                $valueIds = array_values(array_filter(array_map('intval', $rawValues)));
                if ($valueIds === []) {
                    continue;
                }
            }

            $alias = 'pfv' . $index++;
            $query->innerJoin("{{%product_feature_value}} {$alias}", "{$alias}.product_id = p.id")
                ->andWhere(['in', "{$alias}.feature_value_id", $valueIds]);
        }
    }

    /** @param array<int|string> $colorKeys @return int[] */
    private function resolveColorFilterValueIds(array $colorKeys): array
    {
        $valueIds = [];
        foreach ($colorKeys as $colorKey) {
            if (!is_int($colorKey) && !is_string($colorKey)) {
                continue;
            }
            if (is_string($colorKey) && trim($colorKey) === '') {
                continue;
            }

            $color = Color::findByIdOrSlug($colorKey);
            if ($color === null) {
                continue;
            }

            $featureValue = CatalogFeatureValue::ensureForColor($color);
            if ($featureValue !== null) {
                $valueIds[] = (int) $featureValue->id;
            }
        }

        return array_values(array_unique($valueIds));
    }

    /**
     * @param Product[] $existing
     * @return Product[]
     */
    private function appendFuzzyProducts(array $existing, string $term, int $limit): array
    {
        $existingIds = array_map(static fn (Product $p): int => (int) $p->id, $existing);
        $candidates = $this->baseProductQuery()->limit(80)->all();
        $scored = [];
        foreach ($candidates as $product) {
            if (in_array((int) $product->id, $existingIds, true)) {
                continue;
            }
            $haystack = mb_strtolower($product->name . ' ' . $product->slug);
            $distance = $this->textDistance($haystack, $term);
            if ($distance <= 3 || str_contains($haystack, $term)) {
                $scored[] = ['product' => $product, 'distance' => $distance];
            }
        }
        usort($scored, static fn (array $a, array $b): int => $a['distance'] <=> $b['distance']);
        foreach ($scored as $row) {
            if (count($existing) >= $limit) {
                break;
            }
            $existing[] = $row['product'];
        }

        return $existing;
    }

    private function textDistance(string $a, string $b): int
    {
        if (preg_match('/^[\x00-\x7F]*$/', $a) && preg_match('/^[\x00-\x7F]*$/', $b)) {
            return levenshtein(substr($a, 0, 255), substr($b, 0, 255));
        }

        similar_text($a, $b, $percent);

        return (int) round(100 - $percent);
    }

    /** @param array<string, mixed> $params */
    private function applySort(ActiveQuery $query, array $params): void
    {
        $sort = $this->resolveSort($params);

        switch ($sort['by']) {
            case 'price':
                $query->orderBy(['p.price' => $sort['order'], 'p.id' => SORT_ASC]);
                return;
            case 'created_at':
                $query->orderBy(['p.created_at' => $sort['order'], 'p.id' => SORT_ASC]);
                return;
            case 'blago':
                $query->orderBy(['p.blago' => SORT_DESC, 'p.id' => SORT_ASC]);
                return;
            case 'popular':
            default:
                $query->orderBy([
                    'p.is_bestseller' => SORT_DESC,
                    'p.bestseller_rank' => SORT_ASC,
                    'p.id' => SORT_ASC,
                ]);
        }
    }

    /**
     * @param array<string, mixed> $params
     * @return array{by: string, order: int}
     */
    private function resolveSort(array $params): array
    {
        $sort = strtolower(trim((string) ($params['sort'] ?? '')));
        if ($sort !== '') {
            return match ($sort) {
                'price_asc' => ['by' => 'price', 'order' => SORT_ASC],
                'price_desc' => ['by' => 'price', 'order' => SORT_DESC],
                'blago' => ['by' => 'blago', 'order' => SORT_DESC],
                'popular', 'popularity' => ['by' => 'popular', 'order' => SORT_DESC],
                default => ['by' => 'popular', 'order' => SORT_DESC],
            };
        }

        $sortBy = strtolower(trim((string) ($params['sort_by'] ?? '')));
        if ($sortBy === '') {
            return ['by' => 'popular', 'order' => SORT_DESC];
        }

        if ($sortBy === 'blago') {
            return ['by' => 'blago', 'order' => SORT_DESC];
        }

        if ($sortBy === 'popular') {
            return ['by' => 'popular', 'order' => SORT_DESC];
        }

        $sortOrder = strtolower((string) ($params['sort_order'] ?? 'desc')) === 'asc' ? SORT_ASC : SORT_DESC;

        return ['by' => $sortBy, 'order' => $sortOrder];
    }

    private function paginateProducts(ActiveQuery $query, int $page, int $pageSize, bool $showcase): array
    {
        $countQuery = clone $query;
        $countQuery->orderBy([]);
        $total = (int) $countQuery->select('p.id')->distinct()->count();
        $pages = max(1, (int) ceil($total / $pageSize));

        if ($page > $pages) {
            $result = [
                'page' => $page,
                'pages' => $pages,
                'total' => $total,
                'items' => [],
            ];
            if ($showcase) {
                $this->appendShowcaseBlocks($result);
            }

            return $result;
        }

        $products = $query
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->all();

        $items = $showcase
            ? ProductPresenter::showcaseItems($products)
            : ProductPresenter::searchItems($products);

        $result = [
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'items' => $items,
        ];

        if ($showcase) {
            $this->appendShowcaseBlocks($result);
        }

        return $result;
    }

    /**
     * @return array{
     *     banners: list<array<string, mixed>>,
     *     about: array<string, mixed>|null,
     *     categories: list<array<string, mixed>>,
     *     bottom_banner: array<string, mixed>|null
     * }
     */
    public function homePageContent(): array
    {
        return [
            'banners' => $this->activeBannersForApi(),
            'about' => $this->homeAboutForApi(),
            'categories' => $this->homeGenderBlocksForApi(),
            'bottom_banner' => $this->homeBottomBannerForApi(),
        ];
    }

    /** @param array<string, mixed> $result */
    private function appendShowcaseBlocks(array &$result): void
    {
        $content = $this->homePageContent();
        $result['banners'] = $content['banners'];
        if ($content['about'] !== null) {
            $result['about'] = $content['about'];
        }
        if ($content['categories'] !== []) {
            $result['categories'] = $content['categories'];
        }
        if ($content['bottom_banner'] !== null) {
            $result['bottom_banner'] = $content['bottom_banner'];
        }
    }

    /** @return array{image_url: string, button_text: string, button_url: string|null}|null */
    private function homeBottomBannerForApi(): ?array
    {
        $banner = HomeBottomBanner::findOne(1);
        if ($banner === null || $banner->getImagePublicUrl() === null || $banner->button_text === '') {
            return null;
        }

        return $banner->toApiArray();
    }

    /** @return array{title: string, image_url: string}|null */
    private function homeAboutForApi(): ?array
    {
        $about = HomeAbout::findOne(1);
        if ($about === null || $about->title === '' || $about->getImagePublicUrl() === null) {
            return null;
        }

        return $about->toApiArray();
    }

    /** @return list<array<string, mixed>> */
    private function homeGenderBlocksForApi(): array
    {
        $blocks = HomeGenderBlock::find()
            ->orderBy(['id' => SORT_ASC])
            ->all();

        $result = [];
        foreach ($blocks as $block) {
            if ($block->getImagePublicUrl() === null) {
                continue;
            }

            $result[] = $block->toApiArray();
        }

        return $result;
    }

    /** @return list<array<string, mixed>> */
    private function activeBannersForApi(): array
    {
        $banners = HomeBanner::find()
            ->where(['is_active' => true])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->limit(10)
            ->all();

        return array_map(
            static fn (HomeBanner $banner): array => $banner->toApiArray(),
            $banners,
        );
    }

    /** @param array<string, mixed> $params */
    private function buildAvailableFilters(array $params, ?Category $category): array
    {
        $base = $this->baseProductQuery();
        if ($category !== null) {
            $base->innerJoin('{{%product_category}} pc0', 'pc0.product_id = p.id')
                ->andWhere(['pc0.category_id' => $category->id]);
        }
        $this->applySearchFilters($base, $params);

        $priceRow = (new Query())
            ->from(['sub' => (clone $base)->select(['p.price'])->groupBy('p.id')])
            ->select(['min' => 'MIN(sub.price)', 'max' => 'MAX(sub.price)'])
            ->one();

        $featureBlocks = $this->filterBlocksFeatures($base);
        $colorBlock = null;
        $otherFeatureBlocks = [];
        foreach ($featureBlocks as $block) {
            if ($block['id'] === 'color') {
                $colorBlock = $block;
                continue;
            }
            $otherFeatureBlocks[] = $block;
        }

        $filters = [
            $this->filterBlockCategory($base, $params),
        ];
        if ($colorBlock !== null) {
            $filters[] = $colorBlock;
        }
        $filters[] = $this->filterBlockSize($base, $params);
        $filters[] = $this->filterBlockGender($base, $params);

        return [
            'filters' => array_merge($filters, $otherFeatureBlocks),
            'price' => [
                'min' => (float) ($priceRow['min'] ?? 0),
                'max' => (float) ($priceRow['max'] ?? 0),
            ],
        ];
    }

    /** @param array<string, mixed> $params */
    private function filterBlockCategory(ActiveQuery $base, array $params): array
    {
        $rows = (new Query())
            ->select(['c.id', 'c.name', 'cnt' => 'COUNT(DISTINCT p.id)'])
            ->from(['p' => (clone $base)->select('p.id')->groupBy('p.id')])
            ->innerJoin('{{%product_category}} pc', 'pc.product_id = p.id')
            ->innerJoin('{{%category}} c', 'c.id = pc.category_id')
            ->groupBy(['c.id', 'c.name'])
            ->orderBy(['c.name' => SORT_ASC])
            ->all();

        return [
            'id' => 'category',
            'type' => 'list',
            'name_ru' => 'Категория',
            'values' => array_map(static fn (array $r): array => [
                'id' => (int) $r['id'],
                'name' => $r['name'],
                'count' => (int) $r['cnt'],
            ], $rows),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function filterBlocksFeatures(ActiveQuery $base): array
    {
        $blocks = [];
        $features = CatalogFeature::find()->orderBy(['name_ru' => SORT_ASC, 'id' => SORT_ASC])->all();

        foreach ($features as $feature) {
            if ($feature->isDuplicateColorFeature()) {
                continue;
            }

            $featureId = (int) $feature->id;
            $isColor = $feature->isColor();
            if ($isColor) {
                $colorBlock = $this->filterBlockColor($base, $feature);
                if ($colorBlock !== null) {
                    $blocks[] = $colorBlock;
                }
                continue;
            }

            $rows = (new Query())
                ->select(['fv.id', 'fv.name', 'fv.hex', 'cnt' => 'COUNT(DISTINCT p.id)'])
                ->from(['p' => (clone $base)->select('p.id')->groupBy('p.id')])
                ->innerJoin('{{%product_feature_value}} pfv', 'pfv.product_id = p.id')
                ->innerJoin('{{%catalog_feature_value}} fv', 'fv.id = pfv.feature_value_id')
                ->andWhere(['fv.feature_id' => $featureId])
                ->groupBy(['fv.id', 'fv.name', 'fv.hex'])
                ->orderBy(['fv.name' => SORT_ASC])
                ->all();

            if ($rows === []) {
                continue;
            }

            $blocks[] = [
                'id' => 'feature_' . $featureId,
                'type' => 'list',
                'name_ru' => $feature->name_ru,
                'values' => array_map(static fn (array $row): array => [
                    'id' => (int) $row['id'],
                    'name' => $row['name'],
                    'count' => (int) $row['cnt'],
                ], $rows),
            ];
        }

        return $blocks;
    }

    private function filterBlockColor(ActiveQuery $base, CatalogFeature $feature): ?array
    {
        $values = [];
        foreach (Color::find()->orderBy(['name' => SORT_ASC])->all() as $color) {
            $featureValue = CatalogFeatureValue::ensureForColor($color);
            if ($featureValue === null) {
                continue;
            }

            $count = (int) (new Query())
                ->from(['p' => (clone $base)->select('p.id')->groupBy('p.id')])
                ->innerJoin('{{%product_feature_value}} pfv', 'pfv.product_id = p.id')
                ->where(['pfv.feature_value_id' => (int) $featureValue->id])
                ->count('*', Yii::$app->db);

            if ($count <= 0) {
                continue;
            }

            $values[] = [
                'id' => (int) $color->id,
                'slug' => $color->slug,
                'name' => $color->name,
                'hex' => $color->hex,
                'count' => $count,
            ];
        }

        if ($values === []) {
            return null;
        }

        return [
            'id' => 'color',
            'type' => 'list',
            'name_ru' => $feature->name_ru,
            'values' => $values,
        ];
    }

    private function filterBlockSize(ActiveQuery $base, array $params): array
    {
        $rows = (new Query())
            ->select(['psz.size_value', 'cnt' => 'COUNT(DISTINCT p.id)'])
            ->from(['p' => (clone $base)->select('p.id')->groupBy('p.id')])
            ->innerJoin('{{%product_size}} psz', 'psz.product_id = p.id')
            ->groupBy(['psz.size_value'])
            ->all();

        return [
            'id' => 'size',
            'type' => 'list',
            'name_ru' => 'Размер',
            'values' => array_map(static fn (array $r): array => [
                'id' => $r['size_value'],
                'name' => $r['size_value'],
                'count' => (int) $r['cnt'],
            ], $rows),
        ];
    }

    private function filterBlockGender(ActiveQuery $base, array $params): array
    {
        $rows = (new Query())
            ->select(['prod.gender', 'cnt' => 'COUNT(DISTINCT sub.id)'])
            ->from(['sub' => (clone $base)->select('p.id')->groupBy('p.id')])
            ->innerJoin('{{%product}} prod', 'prod.id = sub.id')
            ->andWhere(['not', ['prod.gender' => null]])
            ->groupBy(['prod.gender'])
            ->all();

        $labels = ['male' => 'Мужской', 'female' => 'Женский', 'unisex' => 'Унисекс'];

        return [
            'id' => 'gender',
            'type' => 'list',
            'name_ru' => 'Пол',
            'values' => array_map(static fn (array $r): array => [
                'id' => $r['gender'],
                'name' => $labels[$r['gender']] ?? $r['gender'],
                'count' => (int) $r['cnt'],
            ], $rows),
        ];
    }

    /** @return int[] */
    private function parseCsvInts(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', $value))));
    }

    /** @return string[] */
    private function parseCsvStrings(string $value): array
    {
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
