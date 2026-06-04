<?php

declare(strict_types=1);

namespace app\components\api;

use Yii;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\web\ErrorHandler;
use yii\web\HttpException;
use yii\web\Response;

class ApiErrorHandler extends ErrorHandler
{
    protected function renderException($exception): void
    {
        if (Yii::$app->has('request') && str_starts_with((string) Yii::$app->request->pathInfo, 'api/')) {
            $this->renderApiException($exception);

            return;
        }

        parent::renderException($exception);
    }

    private function renderApiException(\Throwable $exception): void
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_JSON;

        if ($exception instanceof ApiHttpException && is_array($exception->detail)) {
            $response->statusCode = $exception->statusCode;
            $response->data = ['detail' => $exception->detail];

            return;
        }

        if ($exception instanceof CheckoutApiException) {
            $response->statusCode = $exception->statusCode;
            $response->data = ['message' => $exception->getMessage()];

            return;
        }

        if ($exception instanceof HttpException) {
            $response->statusCode = $exception->statusCode;
            $response->data = ['detail' => $exception->getMessage() ?: $this->defaultDetail($exception->statusCode)];

            return;
        }

        if ($exception instanceof InvalidArgumentException) {
            $response->statusCode = 422;
            $response->data = [
                'detail' => [
                    [
                        'loc' => ['body'],
                        'msg' => $exception->getMessage(),
                        'type' => 'value_error',
                    ],
                ],
            ];

            return;
        }

        parent::renderException($exception);
    }

    public static function validationDetail(Model $model): array
    {
        $detail = [];
        foreach ($model->getErrors() as $attribute => $messages) {
            foreach ($messages as $message) {
                $detail[] = [
                    'loc' => ['body', $attribute],
                    'msg' => $message,
                    'type' => 'value_error',
                ];
            }
        }

        return $detail;
    }

    private function defaultDetail(int $statusCode): string
    {
        return match ($statusCode) {
            401 => 'Unauthorized',
            404 => 'Not Found',
            422 => 'Validation Error',
            default => 'Error',
        };
    }
}
