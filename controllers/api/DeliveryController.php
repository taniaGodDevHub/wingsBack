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
 *     description="Checkout: способы доставки СДЭК, расчёт, ПВЗ и подсказки адреса через DaData. До подключения ЛК СДЭК работает mock-режим (cdekMockMode)."
 * )
 *
 * @OA\Post(
 *     path="/api/delivery/suggest-address",
 *     summary="Подсказки полного адреса (DaData)",
 *     description="Основной эндпоинт подсказок адреса: поиск по одной строке (город, улица, дом). Данные из DaData (ключ dadataApiKey). Авторизация не требуется. Для списка ПВЗ после выбора города используйте GET /api/delivery/pvz с city_fias_id из поля data.city_fias_id.",
 *     operationId="DeliveryController.actionSuggestAddress",
 *     tags={"Доставка"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 allOf={
 *                     @OA\Schema(ref="#/components/schemas/DaDataSuggestRequest"),
 *                     @OA\Schema(@OA\Property(property="delivery_method_id", type="integer", default=2, description="1 — СДЭК до ПВЗ, 2 — курьер СДЭК (по умолчанию). Влияет только на проверку способа доставки."))
 *                 }
 *             ),
 *             @OA\Examples(example="address", ref="#/components/examples/dadata-suggest-address-request")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Список подсказок адреса",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(property="status", type="string", example="success"),
 *                 @OA\Property(
 *                     property="data",
 *                     type="array",
 *                     @OA\Items(ref="#/components/schemas/DeliveryAddressSuggestion")
 *                 )
 *             ),
 *             @OA\Examples(example="dadata", ref="#/components/examples/dadata-suggest-address-response")
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/delivery/pvz",
 *     summary="Список пунктов выдачи СДЭК",
 *     description="Возвращает ПВЗ в городе по city_fias_id. В mock-режиме — тестовые пункты Москвы.",
 *     operationId="DeliveryController.actionPvz",
 *     tags={"Доставка"},
 *     @OA\Parameter(name="city_fias_id", in="query", required=true, @OA\Schema(type="string")),
 *     @OA\Parameter(name="delivery_method_id", in="query", @OA\Schema(type="integer", default=1, description="1 — СДЭК до ПВЗ")),
 *     @OA\Response(
 *         response=200,
 *         description="Список ПВЗ",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 @OA\Property(property="status", type="string", example="success"),
 *                 @OA\Property(
 *                     property="data",
 *                     type="array",
 *                     @OA\Items(ref="#/components/schemas/CdekPvzPoint")
 *                 )
 *             ),
 *             @OA\Examples(example="mock", ref="#/components/examples/cdek-pvz-mock")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/delivery/calculate-delivery",
 *     summary="Рассчитать доставку для заказа",
 *     description="Рассчитывает стоимость и сроки СДЭК, сохраняет delivery_cost в заказ. Обязателен перед confirm.",
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
 *             @OA\Schema(ref="#/components/schemas/DeliveryCalculateResponse"),
 *             @OA\Examples(example="mock", ref="#/components/examples/cdek-calculate-mock")
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
            'optional' => ['suggest-address', 'pvz'],
        ];
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'suggest-address' => ['POST'],
                'pvz' => ['GET'],
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
        $deliveryMethodId = (int) ($body['delivery_method_id'] ?? DeliveryService::METHOD_CDEK_COURIER_ID);
        if ($query === '') {
            throw new \InvalidArgumentException('query is required.');
        }

        return [
            'status' => 'success',
            'data' => $this->delivery->suggestAddressForCheckout($query, $deliveryMethodId, $count),
        ];
    }

    public function actionPvz(): array
    {
        $cityFiasId = (string) Yii::$app->request->get('city_fias_id', '');
        $deliveryMethodId = (int) Yii::$app->request->get('delivery_method_id', DeliveryService::METHOD_CDEK_PVZ_ID);
        if ($cityFiasId === '') {
            throw new \InvalidArgumentException('city_fias_id is required.');
        }

        return [
            'status' => 'success',
            'data' => $this->delivery->listPvzPoints($cityFiasId, $deliveryMethodId),
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
        $deliveryMethodId = (int) ($body['delivery_method_id'] ?? DeliveryService::METHOD_CDEK_PVZ_ID);
        if ($orderId <= 0 || $cityFiasId === '') {
            throw new \InvalidArgumentException('order_id and city_fias_id are required.');
        }

        return $this->delivery->calculateDelivery($orderId, (int) $user->id, $cityFiasId, $deliveryMethodId);
    }
}
