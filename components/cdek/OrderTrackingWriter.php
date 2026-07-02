<?php

declare(strict_types=1);

namespace app\components\cdek;

use app\models\OrderTracking;
use app\models\ShopOrder;

final class OrderTrackingWriter
{
    public static function upsertEstimatedDelivery(ShopOrder $order, ?int $periodMax = null): void
    {
        $periodMax ??= $order->delivery_period_max !== null ? (int) $order->delivery_period_max : null;
        if ($periodMax === null || $periodMax <= 0) {
            return;
        }

        $expectedDelivery = date('Y-m-d', strtotime('+' . $periodMax . ' days'));
        $tracking = self::findOrCreate($order);
        $tracking->expected_delivery = $expectedDelivery;
        $tracking->updated_at = time();
        $tracking->save(false);
    }

    public static function upsertFromCdekStatus(ShopOrder $order, array $status): OrderTracking
    {
        $normalized = CdekOrderNormalizer::normalize($status);
        $trackNumber = $normalized['cdek_number'] !== '' ? $normalized['cdek_number'] : (string) ($order->cdek_track_number ?? '');

        $tracking = self::findOrCreate($order);
        $tracking->provider = 'cdek';
        $tracking->track_number = $trackNumber !== '' ? $trackNumber : null;
        $tracking->current_status = $normalized['status'] !== '' ? $normalized['status'] : $tracking->current_status;
        $tracking->description = $normalized['description'] ?? $tracking->description;
        $tracking->current_city = $normalized['current_city'] ?? $tracking->current_city;
        $tracking->updated_at = time();

        if ($normalized['expected_delivery'] !== null) {
            $tracking->expected_delivery = $normalized['expected_delivery'];
        } elseif ($tracking->expected_delivery === null || $tracking->expected_delivery === '') {
            self::upsertEstimatedDelivery($order);
            $tracking->refresh();
        }

        $tracking->save(false);

        return $tracking;
    }

    public static function upsertOnShipmentRegistration(ShopOrder $order, string $trackNumber, string $status): void
    {
        $tracking = self::findOrCreate($order);
        $tracking->provider = 'cdek';
        $tracking->track_number = $trackNumber !== '' ? $trackNumber : null;
        $tracking->current_status = strtoupper($status);
        $tracking->description = 'Отправление зарегистрировано в СДЭК';
        $tracking->current_city = null;
        $tracking->updated_at = time();

        if ($tracking->expected_delivery === null || $tracking->expected_delivery === '') {
            self::upsertEstimatedDelivery($order);
            $tracking->refresh();
        }

        $tracking->save(false);
    }

    private static function findOrCreate(ShopOrder $order): OrderTracking
    {
        $tracking = OrderTracking::findOne(['order_id' => $order->id]) ?? new OrderTracking();
        $tracking->order_id = (int) $order->id;
        if ($tracking->provider === null || $tracking->provider === '') {
            $tracking->provider = 'cdek';
        }

        return $tracking;
    }
}
