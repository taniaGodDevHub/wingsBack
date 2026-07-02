<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\ApiHttpException;
use app\components\api\BaseApiController;
use app\models\News;
use app\services\NewsService;
use OpenApi\Annotations as OA;
use Yii;
use yii\filters\VerbFilter;

/**
 * @OA\Tag(
 *     name="Новости",
 *     description="Публичные статьи и новости"
 * )
 *
 * @OA\Get(
 *     path="/api/news/{slug}",
 *     summary="Статья по slug",
 *     description="Возвращает опубликованную статью (`is_published=true`) и до 3 последних опубликованных статей, кроме текущей.

Поля `article`: id, title, slug, subtitle, text, image_url, created_at.
Поля каждого элемента `latest`: id, title, slug, image_url.

Авторизация не требуется. Неопубликованные и несуществующие статьи — 404.",
 *     operationId="newsBySlug",
 *     tags={"Новости"},
 *     @OA\Parameter(
 *         name="slug",
 *         in="path",
 *         required=true,
 *         description="Slug статьи",
 *         @OA\Schema(type="string", example="otkrytie-novogo-magazina")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Статья и связанные последние публикации",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/NewsArticleResponse")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Статья не найдена или скрыта",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(@OA\Property(property="detail", type="string", example="News not found"))
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/news",
 *     summary="Список статей",
 *     description="Постраничный список опубликованных статей (`is_published=true`), отсортированных по дате создания (новые первыми).

Каждый элемент `items`: id, title, slug, image_url.
Авторизация не требуется.",
 *     operationId="newsList",
 *     tags={"Новости"},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Номер страницы",
 *         required=false,
 *         @OA\Schema(type="integer", default=1, minimum=1)
 *     ),
 *     @OA\Parameter(
 *         name="page_size",
 *         in="query",
 *         description="Статей на странице (макс. 100)",
 *         required=false,
 *         @OA\Schema(type="integer", default=20, minimum=1, maximum=100)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Список статей",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/NewsArticleListResponse")
 *         )
 *     )
 * )
 */
class NewsController extends BaseApiController
{
    private NewsService $news;

    public function init(): void
    {
        parent::init();
        $this->news = new NewsService();
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'index' => ['GET'],
                'view' => ['GET'],
            ],
        ];

        return $behaviors;
    }

    /** @return array{page:int,pages:int,total:int,items:list<array{id:int,title:string,slug:string,image_url:string}>} */
    public function actionIndex(): array
    {
        $page = (int) Yii::$app->request->get('page', 1);
        $pageSize = (int) Yii::$app->request->get('page_size', 20);

        return $this->news->list($page, $pageSize);
    }

    /** @return array{article: array<string,mixed>, latest: list<array{id:int,title:string,slug:string,image_url:string}>} */
    public function actionView(string $slug): array
    {
        $slug = trim($slug);
        if ($slug === '') {
            throw ApiHttpException::notFound('News not found');
        }

        $article = News::find()
            ->where(['slug' => $slug, 'is_published' => true])
            ->one();

        if ($article === null) {
            throw ApiHttpException::notFound('News not found');
        }

        $latest = News::find()
            ->where(['is_published' => true])
            ->andWhere(['<>', 'id', (int) $article->id])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC])
            ->limit(3)
            ->all();

        return [
            'article' => $article->toApiDetail(),
            'latest' => array_map(static fn (News $news): array => $news->toApiCard(), $latest),
        ];
    }
}
