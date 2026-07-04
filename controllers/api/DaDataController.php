<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use app\components\dadata\DaDataClient;
use app\components\dadata\DaDataSuggestionFormatter;
use Yii;
use yii\filters\VerbFilter;

/**
 * Подсказки адреса через DaData (см. POST /api/delivery/suggest-address в разделе «Доставка»).
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
