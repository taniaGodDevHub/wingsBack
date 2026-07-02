<?php

declare(strict_types=1);

namespace app\components\cdek;

use app\models\OrderTracking;
use app\models\ShopOrder;

/**
 * Синхронизация статусов отправлений СДЭК.
 *
 * @see https://cdekrussia.ru/integration
 */
final class TrackingSyncService
{
    public function __construct(private readonly CdekClient $cdek = new CdekClient())
    {
    }

    public function syncAll(): int
    {
        if ($this->cdek->isMockMode()) {
            return 0;
        }

        $orders = ShopOrder::find()
            ->where(['not', ['cdek_order_uuid' => null]])
            ->andWhere(['not', ['status' => ShopOrder::STATUS_COMPLETED]])
            ->andWhere(['not', ['status' => ShopOrder::STATUS_CANCELLED]])
            ->all();

        $updated = 0;
        foreach ($orders as $order) {
            if ($this->syncOrder($order)) {
                ++$updated;
            }
        }

        return $updated;
    }

    public function syncOrder(ShopOrder $order): bool
    {
        $uuid = (string) ($order->cdek_order_uuid ?? '');
        if ($uuid === '') {
            return false;
        }

        $status = $this->cdek->getOrderStatus($uuid);
        $this->applyStatus($order, $status);

        return true;
    }

    /** @param array<string, mixed> $status */
    private function applyStatus(ShopOrder $order, array $status): void
    {
        $cdekStatus = strtoupper((string) ($status['status'] ?? $status['current_status'] ?? ''));
        $trackNumber = (string) ($status['cdek_number'] ?? $order->cdek_track_number ?? '');

        if ($trackNumber !== '') {
            $order->cdek_track_number = $trackNumber;
        }

        $tracking = OrderTracking::findOne(['order_id' => $order->id]) ?? new OrderTracking();
        $tracking->order_id = (int) $order->id;
        $tracking->provider = 'cdek';
        $tracking->track_number = $trackNumber !== '' ? $trackNumber : null;
        $tracking->current_status = $cdekStatus;
        $tracking->description = (string) ($status['description'] ?? null);
        $tracking->current_city = (string) ($status['current_city'] ?? null);
        $tracking->updated_at = time();
        $tracking->expected_delivery = (string) ($status['expected_delivery'] ?? $tracking->expected_delivery);
        $tracking->save(false);

        $order->status = $this->mapCdekStatusToOrderStatus($cdekStatus, $order->status);
        $order->save(false);
    }

    private function mapCdekStatusToOrderStatus(string $cdekStatus, string $current): string
    {
        return match ($cdekStatus) {
            'DELIVERED', 'POSTOMAT_RECEIVED' => ShopOrder::STATUS_DELIVERED,
            'NOT_DELIVERED', 'RETURNED' => ShopOrder::STATUS_RETURNED,
            'ACCEPTED', 'CREATED', 'RECEIVED_AT_SHIPMENT_WAREHOUSE' => ShopOrder::STATUS_PROCESSING,
            'TAKEN_BY_TRANSPORTER', 'SENT_TO_TRANSIT_CITY', 'ACCEPTED_IN_TRANSIT_CITY',
            'SENT_TO_RECIPIENT_CITY', 'ACCEPTED_IN_RECIPIENT_CITY' => ShopOrder::STATUS_DELIVERING,
            'READY_FOR_PICKUP', 'TAKEN_BY_COURIER' => ShopOrder::STATUS_SHIPPED,
            default => $current,
        };
    }
}
