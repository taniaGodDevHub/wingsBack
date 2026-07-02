<?php

declare(strict_types=1);

namespace app\components\cdek;

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
        $normalized = CdekOrderNormalizer::normalizeCreateResponse($response);

        $order->cdek_order_uuid = $normalized['uuid'] !== '' ? $normalized['uuid'] : null;
        $order->cdek_track_number = $normalized['cdek_number'] !== '' ? $normalized['cdek_number'] : null;
        if ($order->status === ShopOrder::STATUS_AWAITING_PAYMENT) {
            $order->status = ShopOrder::STATUS_PROCESSING;
        }
        $order->save(false);

        OrderTrackingWriter::upsertOnShipmentRegistration(
            $order,
            $normalized['cdek_number'],
            $normalized['status'],
        );

        if (!$this->cdek->isMockMode() && $order->cdek_order_uuid !== null) {
            (new TrackingSyncService($this->cdek))->syncOrder($order);
        }
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
}
