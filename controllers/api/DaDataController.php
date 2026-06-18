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
 *     description="Подсказки полного адреса (город, улица и дом в одной строке) через DaData"
 * )
 *
 * @OA\Post(
 *     path="/api/dadata/suggest/address",
 *     summary="Подсказки полного адреса",
 *     description="actionSuggestAddress — поиск по одной строке: город, улица и дом вместе. В ответе value — краткая подпись для списка, full_address — полный адрес с почтовым индексом, postal_code — индекс отдельным полем.",
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
 *         description="Список подсказок адреса",
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
                'suggest-address' => ['POST'],
            ],
        ];

        return $behaviors;
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
