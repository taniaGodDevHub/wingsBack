<?php

declare(strict_types=1);

namespace app\services;

use app\components\api\CheckoutApiException;
use app\models\OrderItem;
use app\models\ShopOrder;

final class DeliveryService
{
    public const METHOD_CDEK_ID = 1;
    public const METHOD_CDEK_CODE = 'cdek_standard';
    public const PROVIDER_CDEK = 'cdek';

    /** @return array<int, array<string, mixed>> */
    public function deliveryOptions(int $orderId, int $userId, ?string $cityFiasId): array
    {
        $order = $this->requireEditableOrder($orderId, $userId);
        if ($cityFiasId !== null && $cityFiasId !== '') {
            $order->city_fias_id = $cityFiasId;
            $order->save(false);
        }

        return [
            [
                'id' => self::METHOD_CDEK_ID,
                'name' => 'СДЭК',
                'code' => self::METHOD_CDEK_CODE,
                'is_pvz' => false,
            ],
        ];
    }

    public function calculateDelivery(int $orderId, int $userId, string $cityFiasId, int $deliveryMethodId): array
    {
        if ($deliveryMethodId !== self::METHOD_CDEK_ID) {
            throw new \InvalidArgumentException('Unsupported delivery_method_id.');
        }

        $order = $this->requireEditableOrder($orderId, $userId);
        $order->city_fias_id = $cityFiasId;
        $order->delivery_method_id = $deliveryMethodId;
        $order->delivery_provider = self::PROVIDER_CDEK;
        $order->delivery_method_code = self::METHOD_CDEK_CODE;
        $order->save(false);

        $items = [];
        foreach (OrderItem::find()->where(['order_id' => $order->id])->all() as $item) {
            $label = 'Доставка CDEK 2-4 дня';
            $item->delivery_label = $label;
            $item->save(false);
            $items[] = [
                'order_item_id' => (int) $item->id,
                'product_id' => (int) $item->product_id,
                'delivery_label' => $label,
            ];
        }

        return [
            'provider' => self::PROVIDER_CDEK,
            'method_code' => self::METHOD_CDEK_CODE,
            'items' => $items,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function suggestAddressForCheckout(string $query, ?string $cityFiasId, int $deliveryMethodId, int $count): array
    {
        if ($deliveryMethodId !== self::METHOD_CDEK_ID) {
            throw new \InvalidArgumentException('Unsupported delivery_method_id.');
        }

        $suggestions = (new \app\components\dadata\DaDataClient())->suggestAddress($query, $cityFiasId, $count);

        return array_map(static function (array $row): array {
            return [
                'value' => $row['value'] ?? '',
                'unrestricted_value' => $row['unrestricted_value'] ?? '',
                'pvz_code' => null,
                'data' => [
                    'address_fias_id' => $row['data']['address_fias_id'] ?? $row['data']['fias_id'] ?? null,
                    'house_fias_id' => $row['data']['house_fias_id'] ?? $row['data']['fias_id'] ?? null,
                ],
            ];
        }, $suggestions);
    }

    private function requireEditableOrder(int $orderId, int $userId): ShopOrder
    {
        $order = ShopOrder::findOne(['id' => $orderId, 'user_id' => $userId]);
        if ($order === null) {
            throw CheckoutApiException::conflict('Order not found');
        }
        if (!$order->isEditable()) {
            throw CheckoutApiException::conflict('Заказ уже оформлен или срок резерва истек');
        }

        return $order;
    }
}
