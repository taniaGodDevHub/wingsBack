<?php

declare(strict_types=1);

namespace app\components\cdek;

use app\models\OrderTracking;
use app\models\ShopOrder;
use Yii;

/**
 * Регистрация отправления в СДЭК после оплаты.
 *
 * @see https://cdekrussia.ru/integration
 */
final class ShipmentService
{
    public function __construct(private readonly CdekClient $cdek = new CdekClient())
    {
    }

    public function registerShipment(ShopOrder $order): void
    {
        if ($order->cdek_order_uuid !== null && $order->cdek_order_uuid !== '') {
            return;
        }

        $response = $this->cdek->createOrder($this->buildOrderPayload($order));

        $uuid = (string) ($response['uuid'] ?? '');
        $trackNumber = (string) ($response['cdek_number'] ?? $response['track_number'] ?? '10123456789');

        $order->cdek_order_uuid = $uuid !== '' ? $uuid : null;
        $order->cdek_track_number = $trackNumber !== '' ? $trackNumber : null;
        if ($order->status === ShopOrder::STATUS_AWAITING_PAYMENT) {
            $order->status = ShopOrder::STATUS_PROCESSING;
        }
        $order->save(false);

        $this->upsertTracking($order, $trackNumber, (string) ($response['status'] ?? 'ACCEPTED'));
    }

    /** @return array<string, mixed> */
    private function buildOrderPayload(ShopOrder $order): array
    {
        return [
            'type' => 1,
            'number' => (string) $order->id,
            'tariff_code' => (int) ($order->cdek_tariff_code ?? Yii::$app->params['cdekDefaultTariffCode'] ?? 136),
            'comment' => $order->comment,
            'from_location' => ['code' => $this->cdek->getFromCityCode()],
            'to_location' => $order->pvz_code !== null && $order->pvz_code !== ''
                ? ['code' => $order->pvz_code]
                : ['address' => (string) $order->delivery_address],
            'recipient' => [
                'name' => 'Получатель',
            ],
        ];
    }

    private function upsertTracking(ShopOrder $order, string $trackNumber, string $status): void
    {
        $tracking = OrderTracking::findOne(['order_id' => $order->id]) ?? new OrderTracking();
        $tracking->order_id = (int) $order->id;
        $tracking->provider = 'cdek';
        $tracking->track_number = $trackNumber;
        $tracking->current_status = $status;
        $tracking->description = 'Отправление зарегистрировано в СДЭК';
        $tracking->current_city = null;
        $tracking->updated_at = time();
        if ($order->delivery_period_max !== null) {
            $tracking->expected_delivery = date('Y-m-d', strtotime('+' . $order->delivery_period_max . ' days'));
        }
        $tracking->save(false);
    }
}
