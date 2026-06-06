<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use app\services\ApiOwnerContext;
use app\services\FavoritesService;
use OpenApi\Annotations as OA;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;
use yii\web\UnauthorizedHttpException;

/**
 * @OA\Tag(
 *     name="Избранное",
 *     description="Список избранных товаров (гость и авторизованный пользователь)"
 * )
 *
 * @OA\Post(
 *     path="/api/favorites/add",
 *     summary="Добавить товар в избранное",
 *     description="actionAdd — добавляет товар в избранное; для гостя нужен заголовок X-Session-ID",
 *     operationId="FavoritesController.actionAdd",
 *     tags={"Избранное"},
 *     security={{"bearerAuth": {}}, {"sessionId": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"product_id"},
 *                 @OA\Property(property="product_id", type="integer")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Товар добавлен",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/FavoriteActionResponse")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/favorites/remove",
 *     summary="Удалить товар из избранного",
 *     description="actionRemove — удаляет товар из списка избранного",
 *     operationId="FavoritesController.actionRemove",
 *     tags={"Избранное"},
 *     security={{"bearerAuth": {}}, {"sessionId": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"product_id"},
 *                 @OA\Property(property="product_id", type="integer")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Товар удалён",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/FavoriteActionResponse")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/favorites/check",
 *     summary="Проверить наличие товаров в избранном",
 *     description="actionCheck — возвращает map product_id → is_favorite для переданного списка",
 *     operationId="FavoritesController.actionCheck",
 *     tags={"Избранное"},
 *     security={{"bearerAuth": {}}, {"sessionId": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"product_ids"},
 *                 @OA\Property(property="product_ids", type="array", @OA\Items(type="integer")),
 *                 @OA\Property(property="session_id", type="string", nullable=true, description="Для гостя, если не передан X-Session-ID")
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Статусы избранного",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(
 *                     property="favorites",
 *                     type="object",
 *                     additionalProperties=@OA\Schema(type="boolean"),
 *                     example={"123": true, "456": false}
 *                 )
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/favorites/list",
 *     summary="Получить список избранного",
 *     description="actionList — постраничный список избранных товаров",
 *     operationId="FavoritesController.actionList",
 *     tags={"Избранное"},
 *     security={{"bearerAuth": {}}, {"sessionId": {}}},
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
 *         description="Количество записей на странице (макс. 200)",
 *         required=false,
 *         @OA\Schema(type="integer", default=200)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Список избранного",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/FavoritesListResponse")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/favorites/sync",
 *     summary="Объединить гостевое избранное с пользовательским",
 *     description="actionSync — после входа переносит избранное из гостевой сессии в аккаунт пользователя",
 *     operationId="FavoritesController.actionSync",
 *     tags={"Избранное"},
 *     security={{"bearerAuth": {}}, {"sessionId": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/SyncRequest")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Результат объединения",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/FavoritesSyncResponse")
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 */
class FavoritesController extends BaseApiController
{
    private FavoritesService $favorites;

    public function init(): void
    {
        parent::init();
        $this->favorites = new FavoritesService();
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::class,
            'authMethods' => [HttpBearerAuth::class],
            'optional' => ['add', 'remove', 'check', 'list'],
        ];
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'add' => ['POST'],
                'remove' => ['POST'],
                'check' => ['POST'],
                'list' => ['GET'],
                'sync' => ['POST'],
            ],
        ];

        return $behaviors;
    }

    public function actionAdd(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $productId = (int) (Yii::$app->request->bodyParams['product_id'] ?? 0);
        if ($productId <= 0) {
            throw new \InvalidArgumentException('product_id is required.');
        }

        return $this->favorites->add($owner, $productId);
    }

    public function actionRemove(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $productId = (int) (Yii::$app->request->bodyParams['product_id'] ?? 0);
        if ($productId <= 0) {
            throw new \InvalidArgumentException('product_id is required.');
        }

        return $this->favorites->remove($owner, $productId);
    }

    public function actionCheck(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $productIds = Yii::$app->request->bodyParams['product_ids'] ?? [];
        if (!is_array($productIds)) {
            throw new \InvalidArgumentException('product_ids must be an array.');
        }

        return $this->favorites->check($owner, $productIds);
    }

    public function actionList(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $page = (int) Yii::$app->request->get('page', 1);
        $pageSize = (int) Yii::$app->request->get('page_size', 200);

        return $this->favorites->list($owner, $page, $pageSize);
    }

    public function actionSync(): array
    {
        $user = Yii::$app->user->identity;
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        $body = Yii::$app->request->bodyParams;
        $sessionId = (string) ($body['session_id'] ?? Yii::$app->request->headers->get('X-Session-ID', ''));

        return $this->favorites->sync($user, $sessionId);
    }
}
