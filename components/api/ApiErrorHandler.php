<?php

declare(strict_types=1);

namespace app\components\api;

use Yii;
use yii\base\InvalidArgumentException;
use yii\base\Model;
use yii\db\IntegrityException;
use yii\helpers\Url;
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

        if ($this->shouldRenderFlashRedirect($exception)) {
            $this->renderFlashRedirect($exception);

            return;
        }

        parent::renderException($exception);
    }

    private function shouldRenderFlashRedirect(\Throwable $exception): bool
    {
        if (!Yii::$app->has('request') || !Yii::$app->has('response') || !Yii::$app->has('session')) {
            return false;
        }

        $request = Yii::$app->request;

        if ($request->isAjax || $request->getIsPjax()) {
            return false;
        }

        if (str_starts_with((string) $request->pathInfo, 'api/')) {
            return false;
        }

        return true;
    }

    private function renderFlashRedirect(\Throwable $exception): void
    {
        try {
            Yii::$app->session->setFlash('error', $this->resolveUserMessage($exception));
        } catch (\Throwable) {
            parent::renderException($exception);

            return;
        }

        Yii::$app->response->clear();
        Yii::$app->response->redirect($this->resolveRedirectUrl())->send();
        Yii::$app->end();
    }

    private function resolveUserMessage(\Throwable $exception): string
    {
        if ($exception instanceof HttpException && $exception->statusCode < 500) {
            return $exception->getMessage() !== ''
                ? $exception->getMessage()
                : Yii::t('app', 'An error occurred while processing your request.');
        }

        if ($exception instanceof IntegrityException) {
            return Yii::t('app', 'Failed to save data. Check required fields and try again.');
        }

        if (YII_DEBUG && $exception->getMessage() !== '') {
            return $exception->getMessage();
        }

        return Yii::t('app', 'An error occurred while processing your request.');
    }

    private function resolveRedirectUrl(): string
    {
        $request = Yii::$app->request;
        $referrer = $request->referrer;

        if (is_string($referrer) && $referrer !== '' && !str_contains($referrer, '/site/error')) {
            return $referrer;
        }

        if ($request->isPost) {
            return Url::to($request->url, true);
        }

        return Url::to(Yii::$app->homeUrl, true);
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
