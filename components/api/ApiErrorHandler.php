<?php

declare(strict_types=1);

namespace app\components\api;

use Yii;
use yii\base\Model;
use yii\db\IntegrityException;
use yii\helpers\Url;
use yii\web\ErrorHandler;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ApiErrorHandler extends ErrorHandler
{
    protected function renderException($exception): void
    {
        if (Yii::$app->has('request') && $this->isApiRequest()) {
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

        if ($this->isApiRequest()) {
            return false;
        }

        if (!($exception instanceof HttpException) && !($exception instanceof IntegrityException)) {
            return false;
        }

        if ($exception instanceof NotFoundHttpException && $this->isLikelyTruncatedAdminRoute($request->pathInfo)) {
            return false;
        }

        return true;
    }

    private function isLikelyTruncatedAdminRoute(string $pathInfo): bool
    {
        if ($pathInfo === '' || str_contains($pathInfo, '/')) {
            return false;
        }

        return in_array($pathInfo, [
            'banners',
            'banner-form',
            'categories',
            'category-form',
            'colors',
            'color-form',
            'sizes',
            'size-form',
            'features',
            'feature-form',
            'feature-values',
            'feature-value-form',
        ], true);
    }

    private function renderFlashRedirect(\Throwable $exception): void
    {
        $redirectUrl = $this->resolveRedirectUrl();
        if ($this->isRedirectLoop($redirectUrl)) {
            parent::renderException($exception);

            return;
        }

        try {
            Yii::$app->session->setFlash('error', $this->resolveUserMessage($exception));
        } catch (\Throwable) {
            parent::renderException($exception);

            return;
        }

        Yii::$app->response->clear();
        Yii::$app->response->redirect($redirectUrl)->send();
        Yii::$app->end();
    }

    private function isRedirectLoop(string $redirectUrl): bool
    {
        $request = Yii::$app->request;
        $current = $this->normalizeRequestUrl($request->url);
        $target = $this->normalizeRequestUrl($redirectUrl);

        if ($target === $current) {
            return true;
        }

        return $request->pathInfo === '' && $target === $this->normalizeRequestUrl(Url::to(Yii::$app->homeUrl));
    }

    private function normalizeRequestUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $path = parse_url($url, PHP_URL_PATH) ?? '';
            $query = parse_url($url, PHP_URL_QUERY);

            return $path . (is_string($query) && $query !== '' ? '?' . $query : '');
        }

        return $url;
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
            return Url::to($request->url);
        }

        return Url::to(Yii::$app->homeUrl);
    }

    private function renderApiException(\Throwable $exception): void
    {
        $response = Yii::$app->response;
        $response->format = Response::FORMAT_JSON;
        $response->isSent = false;
        $response->stream = null;
        $response->content = null;

        if ($exception instanceof ApiHttpException && is_array($exception->detail)) {
            $response->statusCode = $exception->statusCode;
            $response->data = ['detail' => $exception->detail];
            $this->sendApiResponse($response);

            return;
        }

        if ($exception instanceof CheckoutApiException) {
            $response->statusCode = $exception->statusCode;
            $response->data = ['message' => $exception->getMessage()];
            $this->sendApiResponse($response);

            return;
        }

        if ($exception instanceof HttpException) {
            $response->statusCode = $exception->statusCode;
            if ($exception->statusCode >= 500) {
                $response->data = ['detail' => $this->buildServerErrorDetail($exception)];
            } else {
                $response->data = [
                    'detail' => $exception->getMessage() ?: $this->defaultDetail($exception->statusCode),
                ];
            }
            $this->sendApiResponse($response);

            return;
        }

        if ($exception instanceof \InvalidArgumentException) {
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
            $this->sendApiResponse($response);

            return;
        }

        if ($exception instanceof IntegrityException) {
            $response->statusCode = 409;
            $response->data = [
                'detail' => [
                    'message' => $exception->getMessage() !== ''
                        ? $exception->getMessage()
                        : Yii::t('app', 'Failed to save data. Check required fields and try again.'),
                    'type' => $exception::class,
                ],
            ];
            $this->sendApiResponse($response);

            return;
        }

        $response->statusCode = 500;
        $response->data = ['detail' => $this->buildServerErrorDetail($exception)];
        $this->sendApiResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildServerErrorDetail(\Throwable $exception): array
    {
        $detail = [
            'message' => $exception->getMessage() !== ''
                ? $exception->getMessage()
                : Yii::t('app', 'An error occurred while processing your request.'),
            'type' => $exception::class,
        ];

        if (YII_DEBUG) {
            $detail['file'] = $exception->getFile();
            $detail['line'] = $exception->getLine();
            $detail['trace'] = array_slice(explode("\n", $exception->getTraceAsString()), 0, 20);
        }

        return $detail;
    }

    private function sendApiResponse(Response $response): void
    {
        CorsHeaders::apply($response->headers);
        $response->send();
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

    private function isApiRequest(): bool
    {
        if ($this->requestUriLooksLikeApi()) {
            return true;
        }

        if (!Yii::$app->has('request')) {
            return false;
        }

        try {
            $pathInfo = (string) Yii::$app->request->pathInfo;

            return $pathInfo === 'api'
                || str_starts_with($pathInfo, 'api/')
                || str_contains($pathInfo, '/api/');
        } catch (\Throwable) {
            return false;
        }
    }

    private function requestUriLooksLikeApi(): bool
    {
        $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');

        return str_contains($requestUri, '/api/') || str_contains($requestUri, '/api?');
    }

    /**
     * @return array<string, mixed>
     */
    protected function convertExceptionToArray($exception): array
    {
        if (!$this->isApiRequest()) {
            return parent::convertExceptionToArray($exception);
        }

        if ($exception instanceof ApiHttpException && is_array($exception->detail)) {
            return ['detail' => $exception->detail];
        }

        if ($exception instanceof CheckoutApiException) {
            return ['message' => $exception->getMessage()];
        }

        if ($exception instanceof HttpException && $exception->statusCode < 500) {
            return [
                'detail' => $exception->getMessage() ?: $this->defaultDetail($exception->statusCode),
            ];
        }

        if ($exception instanceof \InvalidArgumentException) {
            return [
                'detail' => [
                    [
                        'loc' => ['body'],
                        'msg' => $exception->getMessage(),
                        'type' => 'value_error',
                    ],
                ],
            ];
        }

        if ($exception instanceof IntegrityException) {
            return [
                'detail' => [
                    'message' => $exception->getMessage() !== ''
                        ? $exception->getMessage()
                        : Yii::t('app', 'Failed to save data. Check required fields and try again.'),
                    'type' => $exception::class,
                ],
            ];
        }

        return ['detail' => $this->buildServerErrorDetail($exception)];
    }
}
