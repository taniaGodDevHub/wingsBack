<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use app\services\ApiOwnerContext;
use app\services\CartService;
use OpenApi\Annotations as OA;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;
use yii\web\UnauthorizedHttpException;

/**
 * @OA\Tag(
 *     name="Корзина",
 *     description="Управление корзиной покупок (гость и авторизованный пользователь)"
 * )
 *
 * @OA\Post(
 *     path="/api/cart-client/add",
 *     summary="Добавить товар в корзину",
 *     description="actionAdd — добавляет товар или увеличивает количество; для гостя нужен заголовок X-Session-ID",
 *     operationId="CartClientController.actionAdd",
 *     tags={"Корзина"},
 *     security={{"bearerAuth": {}}, {"sessionId": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/CartAddRequest")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Состояние позиции в корзине",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/CartItemActionResponse")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/cart-client/update",
 *     summary="Обновить количество товара в корзине",
 *     description="actionUpdate — устанавливает новое количество; quantity=0 удаляет позицию",
 *     operationId="CartClientController.actionUpdate",
 *     tags={"Корзина"},
 *     security={{"bearerAuth": {}}, {"sessionId": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/CartAddRequest")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Обновлённая позиция",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/CartItemActionResponse")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/cart-client/remove",
 *     summary="Удалить товар из корзины",
 *     description="actionRemove — удаляет позицию по product_id",
 *     operationId="CartClientController.actionRemove",
 *     tags={"Корзина"},
 *     security={{"bearerAuth": {}}, {"sessionId": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"product_id"},
 *                 @OA\Property(property="product_id", type="integer"),
 *                 @OA\Property(property="cart_id", type="integer", nullable=true)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Товар удалён из корзины",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(property="cart_id", type="integer"),
 *                 @OA\Property(property="product_id", type="integer"),
 *                 @OA\Property(property="is_in_cart", type="boolean", example=false)
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/cart-client/list",
 *     summary="Получить содержимое корзины",
 *     description="actionList — список позиций с информацией о товарах и итоговой суммой",
 *     operationId="CartClientController.actionList",
 *     tags={"Корзина"},
 *     security={{"bearerAuth": {}}, {"sessionId": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Корзина",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/CartListResponse")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/cart-client/count",
 *     summary="Посчитать выбранные позиции",
 *     description="actionCount — подсчёт количества и суммы для указанных товаров",
 *     operationId="CartClientController.actionCount",
 *     tags={"Корзина"},
 *     security={{"bearerAuth": {}}, {"sessionId": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(property="cart_id", type="integer", nullable=true),
 *                 @OA\Property(
 *                     property="items",
 *                     type="array",
 *                     description="Массив product_id или объектов {product_id, quantity, unit_price}",
 *                     @OA\Items(oneOf={
 *                         @OA\Schema(type="integer"),
 *                         @OA\Schema(type="object")
 *                     })
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Итоги по выбранным позициям",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/CartCountResponse")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/cart-client/sync",
 *     summary="Объединить гостевую корзину с пользовательской",
 *     description="actionSync — после входа переносит товары из гостевой сессии в корзину авторизованного пользователя",
 *     operationId="CartClientController.actionSync",
 *     tags={"Корзина"},
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
 *             @OA\Schema(ref="#/components/schemas/CartSyncResponse")
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 */
class CartClientController extends BaseApiController
{
    private CartService $cart;

    public function init(): void
    {
        parent::init();
        $this->cart = new CartService();
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::class,
            'authMethods' => [HttpBearerAuth::class],
            'optional' => ['add', 'update', 'remove', 'list', 'count'],
        ];
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'add' => ['POST'],
                'update' => ['POST'],
                'remove' => ['POST'],
                'list' => ['GET'],
                'count' => ['POST'],
                'sync' => ['POST'],
            ],
        ];

        return $behaviors;
    }

    public function actionAdd(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $body = Yii::$app->request->bodyParams;
        $productId = (int) ($body['product_id'] ?? 0);
        $quantity = (int) ($body['quantity'] ?? 1);
        $cartId = isset($body['cart_id']) && $body['cart_id'] !== null ? (int) $body['cart_id'] : null;
        if ($productId <= 0) {
            throw new \InvalidArgumentException('product_id is required.');
        }

        return $this->cart->add($owner, $productId, $quantity, $cartId);
    }

    public function actionUpdate(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $body = Yii::$app->request->bodyParams;
        $productId = (int) ($body['product_id'] ?? 0);
        $quantity = (int) ($body['quantity'] ?? 1);
        $cartId = isset($body['cart_id']) ? (int) $body['cart_id'] : null;
        if ($productId <= 0) {
            throw new \InvalidArgumentException('product_id is required.');
        }

        return $this->cart->update($owner, $productId, $quantity, $cartId);
    }

    public function actionRemove(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $body = Yii::$app->request->bodyParams;
        $productId = (int) ($body['product_id'] ?? 0);
        $cartId = isset($body['cart_id']) ? (int) $body['cart_id'] : null;
        if ($productId <= 0) {
            throw new \InvalidArgumentException('product_id is required.');
        }

        return $this->cart->remove($owner, $productId, $cartId);
    }

    public function actionList(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);

        return $this->cart->list($owner, 1, 200);
    }

    public function actionCount(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $body = Yii::$app->request->bodyParams;
        $cartId = isset($body['cart_id']) ? (int) $body['cart_id'] : null;
        $items = $body['items'] ?? [];
        if (!is_array($items)) {
            throw new \InvalidArgumentException('items must be an array.');
        }

        return $this->cart->count($owner, $cartId, $items);
    }

    public function actionSync(): array
    {
        $user = Yii::$app->user->identity;
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        $body = Yii::$app->request->bodyParams;
        $sessionId = (string) ($body['session_id'] ?? Yii::$app->request->headers->get('X-Session-ID', ''));

        return $this->cart->sync($user, $sessionId);
    }
}
