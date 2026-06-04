<?php

declare(strict_types=1);

namespace app\components\api;

use yii\web\HttpException;

class CheckoutApiException extends HttpException
{
    public static function conflict(string $message): self
    {
        return new self(409, $message);
    }

    public static function serviceUnavailable(string $message = 'External service unavailable'): self
    {
        return new self(503, $message);
    }
}
