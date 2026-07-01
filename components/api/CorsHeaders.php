<?php

declare(strict_types=1);

namespace app\components\api;

use yii\web\HeaderCollection;

final class CorsHeaders
{
    public static function apply(HeaderCollection $headers): void
    {
        $headers->set('Access-Control-Allow-Origin', '*');
        $headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $headers->set(
            'Access-Control-Allow-Headers',
            'Content-Type, Authorization, X-Session-ID, Refresh-Token, X-Requested-With',
        );
        $headers->set('Access-Control-Max-Age', '86400');
    }
}
