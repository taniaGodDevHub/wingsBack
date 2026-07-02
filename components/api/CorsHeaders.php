<?php

declare(strict_types=1);

namespace app\components\api;

use Yii;
use yii\web\HeaderCollection;
use yii\web\Request;

final class CorsHeaders
{
    private const LOCAL_ORIGIN_PATTERN = '/^https?:\/\/(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/';

    private const ALLOWED_ORIGINS = [
        'https://e-wings.ru',
        'https://www.e-wings.ru',
        'http://e-wings.ru',
        'http://www.e-wings.ru',
    ];

    public static function apply(HeaderCollection $headers, ?Request $request = null): void
    {
        $request ??= Yii::$app->request;
        $origin = (string) $request->headers->get('Origin', '');

        if ($origin !== '' && self::isOriginAllowed($origin)) {
            $headers->set('Access-Control-Allow-Origin', $origin);
            $headers->set('Access-Control-Allow-Credentials', 'true');
            $headers->set('Vary', 'Origin');
        } elseif ($origin === '') {
            $headers->set('Access-Control-Allow-Origin', '*');
        }

        $headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $headers->set(
            'Access-Control-Allow-Headers',
            'Content-Type, Authorization, X-Session-ID, Refresh-Token, X-Requested-With',
        );
        $headers->set('Access-Control-Max-Age', '86400');
    }

    private static function isOriginAllowed(string $origin): bool
    {
        if (preg_match(self::LOCAL_ORIGIN_PATTERN, $origin)) {
            return true;
        }

        $extra = Yii::$app->params['corsAllowedOrigins'] ?? [];

        return in_array($origin, array_merge(self::ALLOWED_ORIGINS, $extra), true);
    }
}
