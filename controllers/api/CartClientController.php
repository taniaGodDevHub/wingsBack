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
 *     description="Одна активная корзина на пользователя (user_id) или гостевую сессию (session_id). Гость: X-Session-ID или session_id в body/query. После входа merge через guest_sync в auth-ответе и/или POST /api/cart-client/sync (идемпотентно)."
 * )
 *
 * @OA\Post(
 *     path="/api/cart-client/add",
 *     summary="Добавить товар в корзину",
 *     description="Добавляет товар выбранного размера или увеличивает количество позиции. Обязательны product_id и size_value из доступных размеров товара. Гость: X-Session-ID или session_id в body. Авторизованный: Bearer.",
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
 *     description="actionUpdate — устанавливает новое количество позиции с указанным size_value; quantity=0 удаляет позицию",
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
 *     description="Удаляет позицию по product_id и size_value. Гость: X-Session-ID или session_id в body.",
 *     operationId="CartClientController.actionRemove",
 *     tags={"Корзина"},
 *     security={{"bearerAuth": {}}, {"sessionId": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/CartProductRequest")
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
 *                 @OA\Property(property="size_value", type="string", example="M"),
 *                 @OA\Property(property="is_in_cart", type="boolean", example=false)
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/cart-client/list",
 *     summary="Получить содержимое корзины",
 *     description="Список позиций корзины текущего пользователя или гостевой сессии. В `summary`: `items_count`, `total_amount`, `blago_total` (сумма благо по всем товарам). У каждой позиции — `blago_amount` (благо товара × quantity).",
 *     operationId="CartClientController.actionList",
 *     tags={"Корзина"},
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
 *         description="Количество позиций на странице (макс. 200)",
 *         required=false,
 *         @OA\Schema(type="integer", default=200)
 *     ),
 *     @OA\Parameter(
 *         name="session_id",
 *         in="query",
 *         description="Для гостя, если не передан X-Session-ID",
 *         required=false,
 *         @OA\Schema(ref="#/components/schemas/GuestSessionId")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Корзина",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/CartListResponse"),
 *             @OA\Examples(example="cart-with-blago", ref="#/components/examples/cart-list-with-blago")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/cart-client/count",
 *     summary="Посчитать выбранные позиции",
 *     description="actionCount — подсчёт количества, суммы и благо для указанных товаров. Ответ: `selected_items_count`, `selected_total_amount`, `selected_blago_total`.",
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
 *                     description="Массив product_id или объектов {product_id, size_value, quantity, unit_price}",
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
 *     description="Ручной merge гостевой корзины в корзину пользователя. Требует Bearer + session_id (body или X-Session-ID). Идемпотентен: безопасно вызывать после auth, даже если guest_sync уже выполнен. Количества одинаковых товаров суммируются.",
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
        $sizeValue = (string) ($body['size_value'] ?? '');
        $quantity = (int) ($body['quantity'] ?? 1);
        $cartId = isset($body['cart_id']) && $body['cart_id'] !== null ? (int) $body['cart_id'] : null;
        if ($productId <= 0) {
            throw new \InvalidArgumentException('product_id is required.');
        }

        return $this->cart->add($owner, $productId, $sizeValue, $quantity, $cartId);
    }

    public function actionUpdate(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $body = Yii::$app->request->bodyParams;
        $productId = (int) ($body['product_id'] ?? 0);
        $sizeValue = (string) ($body['size_value'] ?? '');
        $quantity = (int) ($body['quantity'] ?? 1);
        $cartId = isset($body['cart_id']) ? (int) $body['cart_id'] : null;
        if ($productId <= 0) {
            throw new \InvalidArgumentException('product_id is required.');
        }

        return $this->cart->update($owner, $productId, $sizeValue, $quantity, $cartId);
    }

    public function actionRemove(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $body = Yii::$app->request->bodyParams;
        $productId = (int) ($body['product_id'] ?? 0);
        $sizeValue = (string) ($body['size_value'] ?? '');
        $cartId = isset($body['cart_id']) ? (int) $body['cart_id'] : null;
        if ($productId <= 0) {
            throw new \InvalidArgumentException('product_id is required.');
        }

        return $this->cart->remove($owner, $productId, $sizeValue, $cartId);
    }

    public function actionList(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);

        $page = (int) Yii::$app->request->get('page', 1);
        $pageSize = (int) Yii::$app->request->get('page_size', 200);

        return $this->cart->list($owner, $page, $pageSize);
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
