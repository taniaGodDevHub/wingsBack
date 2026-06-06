<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use app\services\DeliveryService;
use app\services\OrderService;
use OpenApi\Annotations as OA;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;
use yii\web\UnauthorizedHttpException;

/**
 * @OA\Tag(
 *     name="Заказы",
 *     description="Оформление заказов и история покупок"
 * )
 *
 * @OA\Post(
 *     path="/api/orders/create",
 *     summary="Создать черновик заказа",
 *     description="actionCreate — создаёт новый черновик заказа, предыдущий черновик пользователя удаляется",
 *     operationId="actionCreate",
 *     tags={"Заказы"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/OrderCreateRequest")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Созданный заказ",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/OrderCreateResponse")
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 *
 * @OA\Get(
 *     path="/api/orders/active",
 *     summary="Получить активный черновик заказа",
 *     description="actionActive — возвращает текущий неоформленный заказ пользователя",
 *     operationId="actionActive",
 *     tags={"Заказы"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(
 *         response=200,
 *         description="Активный заказ",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/OrderActiveResponse")
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 *
 * @OA\Get(
 *     path="/api/orders/{order_id}",
 *     summary="Получить заказ по ID",
 *     description="actionView — детальная информация о заказе",
 *     operationId="actionView",
 *     tags={"Заказы"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="order_id",
 *         in="path",
 *         description="ID заказа",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Детали заказа",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/OrderDetailsResponse")
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 *
 * @OA\Post(
 *     path="/api/orders/{order_id}/confirm",
 *     summary="Подтвердить оформление заказа",
 *     description="actionConfirm — переводит черновик в статус ожидания оплаты и возвращает ссылку на оплату",
 *     operationId="actionConfirm",
 *     tags={"Заказы"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="order_id",
 *         in="path",
 *         description="ID заказа",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/OrderConfirmRequest")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Заказ оформлен",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/OrderConfirmResponse")
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 *
 * @OA\Get(
 *     path="/api/orders/{order_id}/delivery-options",
 *     summary="Получить способы доставки для заказа",
 *     description="actionDeliveryOptions — доступные методы доставки с учётом города",
 *     operationId="actionDeliveryOptions",
 *     tags={"Заказы"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *         name="order_id",
 *         in="path",
 *         description="ID заказа",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Parameter(
 *         name="city_fias_id",
 *         in="query",
 *         description="ФИАС ID города для расчёта",
 *         required=false,
 *         @OA\Schema(type="string")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Список способов доставки",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id", type="integer"),
 *                     @OA\Property(property="name", type="string"),
 *                     @OA\Property(property="code", type="string"),
 *                     @OA\Property(property="is_pvz", type="boolean")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 *
 * @OA\Get(
 *     path="/api/orders/purchases",
 *     summary="Получить историю покупок",
 *     description="actionPurchases — список оформленных заказов пользователя",
 *     operationId="actionPurchases",
 *     tags={"Заказы"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="page", in="query", description="Номер страницы", @OA\Schema(type="integer", default=1)),
 *     @OA\Parameter(name="page_size", in="query", description="Записей на странице", @OA\Schema(type="integer", default=999)),
 *     @OA\Response(
 *         response=200,
 *         description="Список заказов",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(property="orders", type="array", @OA\Items(type="object")),
 *                 @OA\Property(property="available_filters", type="object")
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 *
 * @OA\Get(
 *     path="/api/orders/deliveries",
 *     summary="Получить заказы в доставке",
 *     description="actionDeliveries — список заказов со статусами доставки и трекингом",
 *     operationId="actionDeliveries",
 *     tags={"Заказы"},
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(name="page", in="query", description="Номер страницы", @OA\Schema(type="integer", default=1)),
 *     @OA\Parameter(name="page_size", in="query", description="Записей на странице", @OA\Schema(type="integer", default=999)),
 *     @OA\Response(
 *         response=200,
 *         description="Заказы в доставке",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(property="orders", type="array", @OA\Items(type="object"))
 *             )
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 */
class OrdersController extends BaseApiController
{
    private OrderService $orders;
    private DeliveryService $delivery;

    public function init(): void
    {
        parent::init();
        $this->orders = new OrderService();
        $this->delivery = new DeliveryService();
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'create' => ['POST'],
                'active' => ['GET'],
                'view' => ['GET'],
                'confirm' => ['POST'],
                'delivery-options' => ['GET'],
                'purchases' => ['GET'],
                'deliveries' => ['GET'],
            ],
        ];

        return $behaviors;
    }

    private function requireUser(): \app\models\User
    {
        $user = Yii::$app->user->identity;
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        return $user;
    }

    public function actionCreate(): array
    {
        $user = $this->requireUser();
        $body = Yii::$app->request->bodyParams;

        return $this->orders->create($user, $body['items'] ?? [], $body['comment'] ?? null);
    }

    public function actionActive(): array
    {
        $user = $this->requireUser();

        return $this->orders->getActive((int) $user->id);
    }

    public function actionView(int $order_id): array
    {
        $user = $this->requireUser();

        return $this->orders->getDetails($order_id, (int) $user->id);
    }

    public function actionConfirm(int $order_id): array
    {
        $user = $this->requireUser();

        return $this->orders->confirm($order_id, (int) $user->id, Yii::$app->request->bodyParams);
    }

    public function actionDeliveryOptions(int $order_id): array
    {
        $user = $this->requireUser();
        $cityFiasId = Yii::$app->request->get('city_fias_id');

        return $this->delivery->deliveryOptions($order_id, (int) $user->id, $cityFiasId !== null ? (string) $cityFiasId : null);
    }

    public function actionPurchases(): array
    {
        $user = $this->requireUser();
        $page = (int) Yii::$app->request->get('page', 1);
        $pageSize = (int) Yii::$app->request->get('page_size', 999);

        return $this->orders->purchases((int) $user->id, $page, $pageSize);
    }

    public function actionDeliveries(): array
    {
        $user = $this->requireUser();
        $page = (int) Yii::$app->request->get('page', 1);
        $pageSize = (int) Yii::$app->request->get('page_size', 999);

        return $this->orders->deliveries((int) $user->id, $page, $pageSize);
    }
}
