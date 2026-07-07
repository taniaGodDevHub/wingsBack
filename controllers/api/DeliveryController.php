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
 *     description="Checkout: способы доставки СДЭК, расчёт, ПВЗ и подсказки адреса через DaData. При cdekMockMode=true или без ключей СДЭК возвращаются тестовые данные."
 * )
 *
 * @OA\Post(
 *     path="/api/delivery/suggest-address",
 *     summary="Подсказки полного адреса (DaData)",
 *     description="Основной эндпоинт подсказок адреса: поиск по одной строке (город, улица, дом). Данные из DaData (ключ dadataApiKey). Авторизация не требуется. Для списка ПВЗ после выбора города используйте GET /api/delivery/pvz с city_fias_id из поля data.city_fias_id; при уточнении адреса также передайте postal_code, geo_lat и geo_lon из этой же подсказки.",
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
 *     description="Возвращает до 10 ПВЗ в городе (параметр count, макс. 20) с пагинацией.
 *
 * **Сценарий 1 — выбран только город** (из `POST /api/dadata/suggest/city` или `POST /api/delivery/suggest-address`): передайте `city_fias_id` из `data.city_fias_id` и `page=1`. Рекомендуется также передать `postal_code` из подсказки — он используется для определения кода города в СДЭК. Если в ответе `meta.has_more=true`, запросите `page=2`, `page=3` и т.д.
 *
 * **Сценарий 2 — пользователь уточнил адрес** через подсказку: дополнительно передайте `postal_code`, `geo_lat`, `geo_lon` (и при необходимости `fias_guid` из `data.address_fias_id`) — список сузится и отсортируется по близости; в каждом пункте появится `distance_km`. Пагинация `page` работает так же.
 *
 * `city_fias_id` — обязателен (FIAS города из DaData). `delivery_method_id=1` — доставка до ПВЗ. В production ответ содержит реальные ПВЗ СДЭК выбранного города.",
 *     operationId="DeliveryController.actionPvz",
 *     tags={"Доставка"},
 *     @OA\Parameter(name="city_fias_id", in="query", required=true, @OA\Schema(type="string", description="FIAS ID города из DaData (data.city_fias_id). Получите через POST /api/dadata/suggest/city")),
 *     @OA\Parameter(name="delivery_method_id", in="query", @OA\Schema(type="integer", default=1, description="1 — СДЭК до ПВЗ")),
 *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1, minimum=1, description="Номер страницы (1 — первые 10 пунктов)")),
 *     @OA\Parameter(name="count", in="query", @OA\Schema(type="integer", default=10, minimum=1, maximum=20, description="Пунктов на странице")),
 *     @OA\Parameter(name="postal_code", in="query", @OA\Schema(type="string", description="Почтовый индекс из DaData — помогает определить код города в СДЭК и сузить список ПВЗ")),
 *     @OA\Parameter(name="fias_guid", in="query", @OA\Schema(type="string", description="FIAS адреса из подсказки (data.address_fias_id) — дополнительное сужение")),
 *     @OA\Parameter(name="geo_lat", in="query", @OA\Schema(type="number", format="float", description="Широта из подсказки (data.geo_lat) — сортировка по близости")),
 *     @OA\Parameter(name="geo_lon", in="query", @OA\Schema(type="number", format="float", description="Долгота из подсказки (data.geo_lon) — сортировка по близости")),
 *     @OA\Response(
 *         response=200,
 *         description="Список ПВЗ с метаданными пагинации",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/CdekPvzListResponse"),
 *             @OA\Examples(example="page1", ref="#/components/examples/cdek-pvz-page1"),
 *             @OA\Examples(example="page2", ref="#/components/examples/cdek-pvz-page2"),
 *             @OA\Examples(example="geo", ref="#/components/examples/cdek-pvz-geo")
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
        $page = max(1, (int) Yii::$app->request->get('page', 1));
        $count = min(20, max(1, (int) Yii::$app->request->get('count', DeliveryService::PVZ_LIST_LIMIT)));
        $postalCode = trim((string) Yii::$app->request->get('postal_code', ''));
        $fiasGuid = trim((string) Yii::$app->request->get('fias_guid', ''));
        $geoLat = $this->optionalGeoCoordinate(Yii::$app->request->get('geo_lat'), 'geo_lat');
        $geoLon = $this->optionalGeoCoordinate(Yii::$app->request->get('geo_lon'), 'geo_lon');

        if ($cityFiasId === '') {
            throw new \InvalidArgumentException('city_fias_id is required.');
        }
        if (($geoLat === null) xor ($geoLon === null)) {
            throw new \InvalidArgumentException('geo_lat and geo_lon must be passed together.');
        }

        $result = $this->delivery->listPvzPoints(
            $cityFiasId,
            $deliveryMethodId,
            $page,
            $count,
            $postalCode,
            $fiasGuid,
            $geoLat,
            $geoLon,
        );

        return [
            'status' => 'success',
            'data' => $result['items'],
            'meta' => $result['meta'],
        ];
    }

    private function optionalGeoCoordinate(mixed $value, string $field): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (!is_numeric($value)) {
            throw new \InvalidArgumentException("{$field} must be numeric.");
        }

        return (float) $value;
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
