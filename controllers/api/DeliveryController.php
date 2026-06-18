<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use app\services\DeliveryService;
use OpenApi\Annotations as OA;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;
use yii\web\UnauthorizedHttpException;

/**
 * @OA\Tag(
 *     name="Доставка",
 *     description="Расчёт и подсказки адресов доставки СДЭК"
 * )
 *
 * @OA\Post(
 *     path="/api/delivery/suggest-address",
 *     summary="Подсказки адресов для оформления",
 *     description="actionSuggestAddress — подсказки полного адреса в одной строке (город, улица, дом). Возвращает full_address с индексом и postal_code отдельным полем.",
 *     operationId="DeliveryController.actionSuggestAddress",
 *     tags={"Доставка"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 allOf={
 *                     @OA\Schema(ref="#/components/schemas/DaDataSuggestRequest"),
 *                     @OA\Schema(@OA\Property(property="delivery_method_id", type="integer", default=1))
 *                 }
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Подсказки адреса",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(property="status", type="string", example="success"),
 *                 @OA\Property(
 *                     property="data",
 *                     type="array",
 *                     @OA\Items(ref="#/components/schemas/DeliveryAddressSuggestion")
 *                 )
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/delivery/calculate-delivery",
 *     summary="Рассчитать доставку для заказа",
 *     description="actionCalculateDelivery — рассчитывает сроки доставки СДЭК и обновляет метки позиций заказа",
 *     operationId="DeliveryController.actionCalculateDelivery",
 *     tags={"Доставка"},
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/DeliveryCalculateRequest")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Результат расчёта",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/DeliveryCalculateResponse")
 *         )
 *     ),
 *     @OA\Response(response=401, ref="#/components/responses/unauthorized")
 * )
 */
class DeliveryController extends BaseApiController
{
    private DeliveryService $delivery;

    public function init(): void
    {
        parent::init();
        $this->delivery = new DeliveryService();
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'optional' => ['suggest-address'],
        ];
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'suggest-address' => ['POST'],
                'calculate-delivery' => ['POST'],
            ],
        ];

        return $behaviors;
    }

    public function actionSuggestAddress(): array
    {
        $body = Yii::$app->request->bodyParams;
        $query = (string) ($body['query'] ?? '');
        $count = min(20, max(1, (int) ($body['count'] ?? 10)));
        $deliveryMethodId = (int) ($body['delivery_method_id'] ?? DeliveryService::METHOD_CDEK_ID);
        if ($query === '') {
            throw new \InvalidArgumentException('query is required.');
        }

        return [
            'status' => 'success',
            'data' => $this->delivery->suggestAddressForCheckout($query, $deliveryMethodId, $count),
        ];
    }

    public function actionCalculateDelivery(): array
    {
        $user = Yii::$app->user->identity;
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        $body = Yii::$app->request->bodyParams;
        $orderId = (int) ($body['order_id'] ?? 0);
        $cityFiasId = (string) ($body['city_fias_id'] ?? '');
        $deliveryMethodId = (int) ($body['delivery_method_id'] ?? DeliveryService::METHOD_CDEK_ID);
        if ($orderId <= 0 || $cityFiasId === '') {
            throw new \InvalidArgumentException('order_id and city_fias_id are required.');
        }

        return $this->delivery->calculateDelivery($orderId, (int) $user->id, $cityFiasId, $deliveryMethodId);
    }
}
