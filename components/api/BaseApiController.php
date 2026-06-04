<?php

declare(strict_types=1);

namespace app\components\api;

use Yii;
use yii\rest\Controller;
use yii\web\Response;

class BaseApiController extends Controller
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['contentNegotiator']['formats']['application/json'] = Response::FORMAT_JSON;

        return $behaviors;
    }

    public function beforeAction($action): bool
    {
        $this->applyCorsHeaders();

        if (Yii::$app->request->isOptions) {
            Yii::$app->response->statusCode = 200;
            Yii::$app->response->format = Response::FORMAT_RAW;
            Yii::$app->response->data = '';
            Yii::$app->end();

            return false;
        }

        return parent::beforeAction($action);
    }

    protected function applyCorsHeaders(): void
    {
        $headers = Yii::$app->response->headers;
        $headers->set('Access-Control-Allow-Origin', '*');
        $headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $headers->set(
            'Access-Control-Allow-Headers',
            'Content-Type, Authorization, X-Session-ID, Refresh-Token, X-Requested-With',
        );
        $headers->set('Access-Control-Max-Age', '86400');
    }
}
