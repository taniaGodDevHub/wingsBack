<?php

declare(strict_types=1);

namespace app\components\api;

use Yii;
use yii\base\Application;
use yii\base\BootstrapInterface;
use yii\web\Response;

final class CorsPreflightBootstrap implements BootstrapInterface
{
    public function bootstrap($app): void
    {
        $app->on(Application::EVENT_BEFORE_REQUEST, static function () use ($app): void {
            $request = $app->request;
            if (!$request->isOptions) {
                return;
            }

            $pathInfo = $request->pathInfo;
            if ($pathInfo === '' || !str_starts_with($pathInfo, 'api/')) {
                return;
            }

            $response = $app->response;
            CorsHeaders::apply($response->headers);
            $response->statusCode = 200;
            $response->format = Response::FORMAT_RAW;
            $response->content = '';
            $response->send();
            $app->end();
        });
    }
}
