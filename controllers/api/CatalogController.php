<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use app\services\catalog\CatalogService;
use OpenApi\Annotations as OA;
use Yii;
use yii\filters\VerbFilter;

/**
 * @OA\Tag(
 *     name="Каталог",
 *     description="Витрина, поиск и категории товаров"
 * )
 *
 * @OA\Tag(
 *     name="Главная страница",
 *     description="Баннеры, блок «О нас», категории по полу и нижний баннер"
 * )
 *
 * @OA\Get(
 *     path="/api/catalog/home",
 *     summary="Контент главной страницы",
 *     description="Возвращает баннеры слайд-шоу, блок «О нас», изображения категорий (мужское/женское) и нижний баннер. Авторизация не требуется. Для загрузки только визуального контента главной предпочтительнее этот эндпоинт; тот же набор полей также дублируется в ответе `/api/catalog/showcase`.",
 *     operationId="catalogHomeContent",
 *     tags={"Главная страница"},
 *     @OA\Response(
 *         response=200,
 *         description="Баннеры, блок «О нас», категории и нижний баннер главной",
 *         @OA\JsonContent(ref="#/components/schemas/HomePageContentResponse")
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/catalog/showcase",
 *     summary="Получить витрину главной страницы",
 *     description="Постраничный список товаров для главной. В ответе также могут присутствовать `banners`, `about` и `categories` — см. схему `ShowcaseResponse`. Только визуальный контент главной: `GET /api/catalog/home`.",
 *     operationId="actionShowcase",
 *     tags={"Каталог"},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Номер страницы",
 *         required=false,
 *         @OA\Schema(type="integer", default=1)
 *     ),
 *     @OA\Parameter(
 *         name="page_size",
 *         in="query",
 *         description="Количество товаров на странице (макс. 100)",
 *         required=false,
 *         @OA\Schema(type="integer", default=36)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Витрина товаров",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/ShowcaseResponse")
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/catalog/search/universal",
 *     summary="Универсальный поиск",
 *     description="actionUniversal — поиск товаров и категорий по строке (минимум 2 символа)",
 *     operationId="actionUniversal",
 *     tags={"Каталог"},
 *     @OA\Parameter(
 *         name="search",
 *         in="query",
 *         description="Поисковый запрос",
 *         required=true,
 *         @OA\Schema(type="string", example="куртка")
 *     ),
 *     @OA\Parameter(
 *         name="limit_products",
 *         in="query",
 *         description="Максимум товаров в ответе",
 *         required=false,
 *         @OA\Schema(type="integer", default=8)
 *     ),
 *     @OA\Parameter(
 *         name="limit_categories",
 *         in="query",
 *         description="Максимум категорий в ответе",
 *         required=false,
 *         @OA\Schema(type="integer", default=6)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Результаты поиска",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(property="query", type="string"),
 *                 @OA\Property(
 *                     property="products",
 *                     type="object",
 *                     @OA\Property(property="total", type="integer"),
 *                     @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *                 ),
 *                 @OA\Property(
 *                     property="categories",
 *                     type="object",
 *                     @OA\Property(property="total", type="integer"),
 *                     @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *                 )
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/catalog/categories/simple-tree",
 *     summary="Дерево категорий",
 *     description="actionSimpleTree — иерархический список активных категорий",
 *     operationId="actionSimpleTree",
 *     tags={"Каталог"},
 *     @OA\Parameter(
 *         name="category_slug",
 *         in="query",
 *         description="Slug категории для подсветки связанных веток",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Дерево категорий",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/CategoryTreeNode"),
 *                 example={{
 *                     "id": 1,
 *                     "name": "Women",
 *                     "slug": "women",
 *                     "related": true,
 *                     "children": {{
 *                         "id": 11,
 *                         "name": "Dresses",
 *                         "slug": "dresses",
 *                         "related": true,
 *                         "children": {{
 *                             "id": 111,
 *                             "name": "Evening Dresses",
 *                             "slug": "evening-dresses",
 *                             "related": false,
 *                             "children": {}
 *                         }}
 *                     }}
 *                 }}
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/catalog/search",
 *     summary="Каталог с фильтрами",
 *     description="actionSearch — поиск товаров с фильтрами, сортировкой и доступными значениями фильтров",
 *     operationId="actionSearch",
 *     tags={"Каталог"},
 *     @OA\Parameter(name="page", in="query", description="Номер страницы", @OA\Schema(type="integer", default=1)),
 *     @OA\Parameter(name="page_size", in="query", description="Товаров на странице (макс. 100)", @OA\Schema(type="integer", default=60)),
 *     @OA\Parameter(name="category_ids", in="query", description="ID категорий через запятую", @OA\Schema(type="string", example="1,2,3")),
 *     @OA\Parameter(name="size_values", in="query", description="Размеры через запятую", @OA\Schema(type="string", example="S,M,L")),
 *     @OA\Parameter(name="gender", in="query", description="Пол: male, female, unisex", @OA\Schema(type="string", enum={"male","female","unisex"})),
 *     @OA\Parameter(name="price_min", in="query", description="Минимальная цена", @OA\Schema(type="number")),
 *     @OA\Parameter(name="price_max", in="query", description="Максимальная цена", @OA\Schema(type="number")),
 *     @OA\Parameter(
 *         name="sort",
 *         in="query",
 *         description="Сортировка (приоритетнее sort_by): popular — по популярности; price_asc — по возрастанию цены; price_desc — по убыванию цены; blago — больше блага (product.blago DESC)",
 *         @OA\Schema(type="string", default="popular", enum={"popular","price_asc","price_desc","blago"})
 *     ),
 *     @OA\Parameter(name="sort_by", in="query", description="Устаревший вариант: price, popular, blago, created_at", @OA\Schema(type="string", enum={"price","popular","blago","created_at"})),
 *     @OA\Parameter(name="sort_order", in="query", description="Направление для sort_by=price или created_at", @OA\Schema(type="string", default="desc", enum={"asc","desc"})),
 *     @OA\Parameter(
 *         name="feature_filters",
 *         in="query",
 *         description="JSON-строка объекта CatalogFeatureFilters: фильтр color принимает массив ID цвета (integer) и/или slug (string)",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Список товаров с фильтрами",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/CatalogSearchResponse")
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/catalog/search/category/{slug}",
 *     summary="Товары категории",
 *     description="actionSearchCategory — поиск товаров внутри категории по slug с фильтрами",
 *     operationId="actionSearchCategory",
 *     tags={"Каталог"},
 *     @OA\Parameter(
 *         name="slug",
 *         in="path",
 *         description="Slug категории",
 *         required=true,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Parameter(name="page", in="query", description="Номер страницы", @OA\Schema(type="integer", default=1)),
 *     @OA\Parameter(name="page_size", in="query", description="Товаров на странице (макс. 100)", @OA\Schema(type="integer", default=60)),
 *     @OA\Parameter(name="category_ids", in="query", description="ID категорий через запятую", @OA\Schema(type="string", example="1,2,3")),
 *     @OA\Parameter(name="size_values", in="query", description="Размеры через запятую", @OA\Schema(type="string", example="S,M,L")),
 *     @OA\Parameter(name="gender", in="query", description="Пол: male, female, unisex", @OA\Schema(type="string", enum={"male","female","unisex"})),
 *     @OA\Parameter(name="price_min", in="query", description="Минимальная цена", @OA\Schema(type="number")),
 *     @OA\Parameter(name="price_max", in="query", description="Максимальная цена", @OA\Schema(type="number")),
 *     @OA\Parameter(
 *         name="sort",
 *         in="query",
 *         description="Сортировка (приоритетнее sort_by): popular — по популярности; price_asc — по возрастанию цены; price_desc — по убыванию цены; blago — больше блага (product.blago DESC)",
 *         @OA\Schema(type="string", default="popular", enum={"popular","price_asc","price_desc","blago"})
 *     ),
 *     @OA\Parameter(name="sort_by", in="query", description="Устаревший вариант: price, popular, blago, created_at", @OA\Schema(type="string", enum={"price","popular","blago","created_at"})),
 *     @OA\Parameter(name="sort_order", in="query", description="Направление для sort_by=price или created_at", @OA\Schema(type="string", default="desc", enum={"asc","desc"})),
 *     @OA\Parameter(
 *         name="feature_filters",
 *         in="query",
 *         description="JSON-строка объекта CatalogFeatureFilters: фильтр color принимает массив ID цвета (integer) и/или slug (string)",
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Список товаров категории",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/CatalogSearchResponse")
 *         )
 *     )
 * )
 */
class CatalogController extends BaseApiController
{
    private CatalogService $catalog;

    public function init(): void
    {
        parent::init();
        $this->catalog = new CatalogService();
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'home' => ['GET'],
                'showcase' => ['GET'],
                'universal' => ['GET'],
                'simple-tree' => ['GET'],
                'search' => ['GET'],
                'search-category' => ['GET'],
            ],
        ];

        return $behaviors;
    }

    public function actionHome(): array
    {
        return $this->catalog->homePageContent();
    }

    public function actionShowcase(): array
    {
        $page = (int) Yii::$app->request->get('page', 1);
        $pageSize = (int) Yii::$app->request->get('page_size', 36);

        return $this->catalog->showcase($page, $pageSize);
    }

    public function actionUniversal(): array
    {
        $search = (string) Yii::$app->request->get('search', '');
        $limitProducts = (int) Yii::$app->request->get('limit_products', 8);
        $limitCategories = (int) Yii::$app->request->get('limit_categories', 6);

        return $this->catalog->universalSearch($search, $limitProducts, $limitCategories);
    }

    public function actionSimpleTree(): array
    {
        $contextSlug = (string) Yii::$app->request->get('category_slug', '');

        return $this->catalog->categoryTree($contextSlug !== '' ? $contextSlug : null);
    }

    public function actionSearch(): array
    {
        return $this->catalog->search(Yii::$app->request->get());
    }

    public function actionSearchCategory(string $slug): array
    {
        return $this->catalog->searchByCategorySlug($slug, Yii::$app->request->get());
    }
}
