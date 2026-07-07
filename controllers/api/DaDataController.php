<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use app\components\dadata\DaDataClient;
use app\components\dadata\DaDataSuggestionFormatter;
use OpenApi\Annotations as OA;
use Yii;
use yii\filters\VerbFilter;

/**
 * @OA\Tag(
 *     name="DaData",
 *     description="Подсказки города и адреса через DaData. Для checkout также доступен POST /api/delivery/suggest-address."
 * )
 *
 * @OA\Post(
 *     path="/api/dadata/suggest/city",
 *     summary="Подсказки города (DaData)",
 *     description="Поиск населённого пункта по названию. Используйте `data.city_fias_id` из ответа в `GET /api/delivery/pvz` и при расчёте доставки. Авторизация не требуется.",
 *     operationId="DaDataController.actionSuggestCity",
 *     tags={"DaData"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/DaDataSuggestRequest")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Список городов",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/DaDataSuggestResponse")
 *         )
 *     )
 * )
 *
 * @OA\Post(
 *     path="/api/dadata/suggest/address",
 *     summary="Подсказки полного адреса (DaData)",
 *     description="Поиск по строке адреса (город, улица, дом). Для checkout предпочтительнее POST /api/delivery/suggest-address.",
 *     operationId="DaDataController.actionSuggestAddress",
 *     tags={"DaData"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/DaDataSuggestRequest")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Список подсказок",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/DaDataSuggestResponse")
 *         )
 *     )
 * )
 */
class DaDataController extends BaseApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'suggest-city' => ['POST'],
                'suggest-address' => ['POST'],
            ],
        ];

        return $behaviors;
    }

    public function actionSuggestCity(): array
    {
        $body = Yii::$app->request->bodyParams;
        $query = (string) ($body['query'] ?? '');
        $count = min(20, max(1, (int) ($body['count'] ?? 10)));
        if ($query === '') {
            throw new \InvalidArgumentException('query is required.');
        }

        $client = new DaDataClient();
        $suggestions = $client->suggestCity($query, $count);

        return ['data' => DaDataSuggestionFormatter::formatMany($suggestions)];
    }

    public function actionSuggestAddress(): array
    {
        $body = Yii::$app->request->bodyParams;
        $query = (string) ($body['query'] ?? '');
        $count = min(20, max(1, (int) ($body['count'] ?? 10)));
        if ($query === '') {
            throw new \InvalidArgumentException('query is required.');
        }

        $client = new DaDataClient();
        $suggestions = $client->suggestFullAddress($query, $count);

        return ['data' => DaDataSuggestionFormatter::formatMany($suggestions)];
    }
}
