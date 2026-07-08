<?php

declare(strict_types=1);

namespace app\services;

use app\models\HomeBlago;
use app\models\ShopOrder;

final class BlagoService
{
    /** @return array{title: string, collection_start_at: int, collection_end_at: int, amount: float, image_url: string}|null */
    public function getForApi(): ?array
    {
        $blago = HomeBlago::findOne(1);
        if (
            $blago === null
            || $blago->title === ''
            || $blago->getImagePublicUrl() === null
            || (int) $blago->collection_start_at <= 0
            || (int) $blago->collection_end_at <= 0
        ) {
            return null;
        }

        return $blago->toApiArray();
    }

    /** @return array{order_id: int, code: string, created_at: string, total_price: float, blago_total: float}|null */
    public function getOrderByBlagoCode(string $code): ?array
    {
        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $order = ShopOrder::find()->where(['code' => $code])->one();
        if ($order === null) {
            return null;
        }

        return [
            'order_id' => (int) $order->id,
            'code' => (string) $order->code,
            'created_at' => gmdate('c', (int) $order->created_at),
            'total_price' => (float) $order->total_price,
            'blago_total' => (float) $order->blago_total,
        ];
    }
}
