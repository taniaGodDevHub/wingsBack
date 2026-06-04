<?php

declare(strict_types=1);

namespace app\components\api;

use yii\web\HttpException;

class ApiHttpException extends HttpException
{
    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self(401, $message);
    }

    public static function notFound(string $message = 'Not Found'): self
    {
        return new self(404, $message);
    }

    public static function validation(array $detail): self
    {
        $e = new self(422, 'Validation Error');
        $e->detail = $detail;

        return $e;
    }

    /** @var array<int, array<string, mixed>>|string|null */
    public array|string|null $detail = null;
}
