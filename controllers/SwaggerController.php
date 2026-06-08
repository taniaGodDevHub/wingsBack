<?php

declare(strict_types=1);

namespace app\controllers;

use OpenApi\Generator;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class SwaggerController extends Controller
{
    public $layout = false;

    /**
     * @inheritdoc
     */
    public function actions(): array
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
        ];
    }

    public function actionJsonSchema(): Response
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'application/json; charset=UTF-8');
        $response->headers->set('Access-Control-Allow-Origin', '*');

        $openapi = Generator::scan([
            Yii::getAlias('@app/docs'),
            Yii::getAlias('@app/controllers'),
            Yii::getAlias('@app/models'),
        ]);

        $response->content = $openapi->toJson();

        return $response;
    }
}
