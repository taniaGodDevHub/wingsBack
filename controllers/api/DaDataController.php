<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use app\components\dadata\DaDataClient;
use Yii;
use yii\filters\VerbFilter;

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

        return ['data' => $client->suggestCity($query, $count)];
    }

    public function actionSuggestAddress(): array
    {
        $body = Yii::$app->request->bodyParams;
        $query = (string) ($body['query'] ?? '');
        $count = min(20, max(1, (int) ($body['count'] ?? 10)));
        $cityFiasId = isset($body['city_fias_id']) ? (string) $body['city_fias_id'] : null;
        if ($query === '') {
            throw new \InvalidArgumentException('query is required.');
        }

        $client = new DaDataClient();
        $suggestions = $client->suggestAddress($query, $cityFiasId, $count);

        return [
            'data' => array_map(static function (array $row): array {
                $data = $row['data'] ?? [];

                return [
                    'value' => $row['value'] ?? '',
                    'unrestricted_value' => $row['unrestricted_value'] ?? '',
                    'data' => [
                        'address_fias_id' => $data['address_fias_id'] ?? $data['fias_id'] ?? null,
                        'house_fias_id' => $data['house_fias_id'] ?? $data['fias_id'] ?? null,
                        'postal_code' => $data['postal_code'] ?? null,
                        'geo_lat' => $data['geo_lat'] ?? null,
                        'geo_lon' => $data['geo_lon'] ?? null,
                    ],
                ];
            }, $suggestions),
        ];
    }
}
