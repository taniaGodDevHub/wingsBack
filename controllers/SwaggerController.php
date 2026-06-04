<?php

namespace app\controllers;

use OpenApi\Generator;
use Swagger\Annotations as SWG;
use Swagger\Annotations\Contact;
use Yii;
use yii\web\Controller;

class SwaggerController extends Controller
{
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

    public function actionJsonSchema()
    {
        header('Access-Control-Allow-Origin: *');
        $openapi = Generator::scan([
            Yii::getAlias('@app/docs'),
            Yii::getAlias('@app/controllers'),
            Yii::getAlias('@app/models'),
        ]);

        return $openapi->toJson();
    }
}