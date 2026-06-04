<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use app\services\catalog\CatalogService;
use Yii;
use yii\filters\VerbFilter;

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
                'showcase' => ['GET'],
                'universal' => ['GET'],
                'simple-tree' => ['GET'],
                'search' => ['GET'],
                'search-category' => ['GET'],
            ],
        ];

        return $behaviors;
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
