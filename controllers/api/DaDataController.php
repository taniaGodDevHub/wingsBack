<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use app\components\dadata\DaDataClient;
use app\components\dadata\DaDataSuggestionFormatter;
use Yii;
use yii\filters\VerbFilter;

/**
 * Legacy controller for DaData endpoints.
 * OpenAPI annotations are defined in DeliveryController to avoid duplicates.
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
