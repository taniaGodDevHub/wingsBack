<?php

declare(strict_types=1);

namespace app\services;

use app\models\ShopOrder;

final class OrderCodeGenerator
{
    public function generate(): string
    {
        for ($attempt = 0; $attempt < 50; ++$attempt) {
            $code = 'blago' . random_int(1000, 9999);
            if (!ShopOrder::find()->where(['code' => $code])->exists()) {
                return $code;
            }
        }

        throw new \RuntimeException('Failed to generate unique order code.');
    }
}
