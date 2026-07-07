<?php

declare(strict_types=1);

namespace app\services;

use app\components\api\CheckoutApiException;
use app\components\cdek\CdekClient;
use app\components\cdek\CdekMockData;
use app\components\cdek\OrderTrackingWriter;
use app\models\OrderItem;
use app\models\ShopOrder;
use Yii;

final class DeliveryService
{
    public const METHOD_CDEK_PVZ_ID = 1;
    public const METHOD_CDEK_COURIER_ID = 2;
    public const CODE_CDEK_PVZ = 'cdek_pvz';
    public const CODE_CDEK_COURIER = 'cdek_courier';
    public const PROVIDER_CDEK = 'cdek';
    public const PVZ_LIST_LIMIT = 10;

    /** @deprecated use METHOD_CDEK_PVZ_ID */
    public const METHOD_CDEK_ID = self::METHOD_CDEK_PVZ_ID;
    /** @deprecated use CODE_CDEK_PVZ */
    public const METHOD_CDEK_CODE = self::CODE_CDEK_PVZ;

    private CdekClient $cdek;

    public function __construct(?CdekClient $cdek = null)
    {
        $this->cdek = $cdek ?? Yii::$app->cdek;
    }

    /** @return array<int, array<string, mixed>> */
    public function deliveryOptions(int $orderId, int $userId, ?string $cityFiasId): array
    {
        $order = $this->requireEditableOrder($orderId, $userId);
        if ($cityFiasId !== null && $cityFiasId !== '') {
            $order->city_fias_id = $cityFiasId;
            $order->save(false);
        }

        $toCityCode = $this->cdek->resolveCityCode($cityFiasId);
        $weightGrams = $this->orderWeightGrams($order);
        $tariffs = $this->cdek->calculateTariffList($this->cdek->getFromCityCode(), $toCityCode, $weightGrams);

        $options = [];
        foreach ($tariffs as $tariff) {
            $tariffCode = (int) ($tariff['tariff_code'] ?? 0);
            $mapping = $this->mapTariffToMethod($tariffCode);
            if ($mapping === null) {
                continue;
            }

            [$methodId, $code, $name, $isPvz] = $mapping;
            $options[] = [
                'id' => $methodId,
                'name' => $name,
                'code' => $code,
                'is_pvz' => $isPvz,
                'price' => (float) ($tariff['delivery_sum'] ?? 0),
                'period_min' => (int) ($tariff['period_min'] ?? 0),
                'period_max' => (int) ($tariff['period_max'] ?? 0),
                'tariff_code' => $tariffCode,
            ];
        }

        if ($options === []) {
            return $this->mockDeliveryOptions();
        }

        return $options;
    }

    public function calculateDelivery(int $orderId, int $userId, string $cityFiasId, int $deliveryMethodId): array
    {
        $mapping = $this->mapMethodId($deliveryMethodId);
        if ($mapping === null) {
            throw new \InvalidArgumentException('Unsupported delivery_method_id.');
        }

        [$code, $tariffCode, $isPvz] = $mapping;

        $order = $this->requireEditableOrder($orderId, $userId);
        $order->city_fias_id = $cityFiasId;
        $order->delivery_method_id = $deliveryMethodId;
        $order->delivery_provider = self::PROVIDER_CDEK;
        $order->delivery_method_code = $code;
        $order->cdek_tariff_code = $tariffCode;

        $toCityCode = $this->cdek->resolveCityCode($cityFiasId);
        $weightGrams = $this->orderWeightGrams($order);
        $tariff = $this->findTariff($toCityCode, $weightGrams, $tariffCode);

        $periodMin = (int) ($tariff['period_min'] ?? 2);
        $periodMax = (int) ($tariff['period_max'] ?? 4);
        $deliveryCost = (float) ($tariff['delivery_sum'] ?? ($isPvz ? 350.0 : 490.0));
        $label = sprintf('Доставка СДЭК %d-%d дн.', $periodMin, $periodMax);

        $order->delivery_cost = $deliveryCost;
        $order->delivery_period_min = $periodMin;
        $order->delivery_period_max = $periodMax;
        $order->save(false);

        OrderTrackingWriter::upsertEstimatedDelivery($order, $periodMax);

        $items = [];
        foreach (OrderItem::find()->where(['order_id' => $order->id])->all() as $item) {
            $item->delivery_label = $label;
            $item->save(false);
            $items[] = [
                'order_item_id' => (int) $item->id,
                'product_id' => (int) $item->product_id,
                'delivery_label' => $label,
            ];
        }

        $itemsTotal = (float) OrderItem::find()->where(['order_id' => $order->id])->sum('total_price');

        return [
            'provider' => self::PROVIDER_CDEK,
            'method_code' => $code,
            'delivery_cost' => $deliveryCost,
            'period_min' => $periodMin,
            'period_max' => $periodMax,
            'total_with_delivery' => $itemsTotal + $deliveryCost,
            'items' => $items,
        ];
    }

    /** @return array{items: list<array<string, mixed>>, meta: array{page: int, count: int, has_more: bool}} */
    public function listPvzPoints(
        string $cityFiasId,
        int $deliveryMethodId,
        int $page = 1,
        int $count = self::PVZ_LIST_LIMIT,
        ?string $postalCode = null,
        ?string $fiasGuid = null,
        ?float $geoLat = null,
        ?float $geoLon = null,
    ): array {
        if ($deliveryMethodId !== self::METHOD_CDEK_PVZ_ID) {
            throw new \InvalidArgumentException('PVZ list is available only for cdek_pvz delivery method.');
        }

        $cityCode = $this->cdek->resolveCityCode(
            $cityFiasId,
            null,
            $postalCode !== '' ? $postalCode : null,
        );

        return $this->cdek->listDeliveryPoints(
            $cityCode,
            $count,
            $page,
            $postalCode !== '' ? $postalCode : null,
            $fiasGuid !== '' ? $fiasGuid : null,
            $geoLat,
            $geoLon,
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function suggestCity(string $query, int $count): array
    {
        $suggestions = (new \app\components\dadata\DaDataClient())->suggestCity($query, $count);

        return \app\components\dadata\DaDataSuggestionFormatter::formatMany($suggestions);
    }

    /** @return array<int, array<string, mixed>> */
    public function suggestAddressForCheckout(string $query, int $deliveryMethodId, int $count): array
    {
        if (!in_array($deliveryMethodId, [self::METHOD_CDEK_PVZ_ID, self::METHOD_CDEK_COURIER_ID], true)) {
            throw new \InvalidArgumentException('Unsupported delivery_method_id.');
        }

        $suggestions = (new \app\components\dadata\DaDataClient())->suggestFullAddress($query, $count);
        $formatted = \app\components\dadata\DaDataSuggestionFormatter::formatMany($suggestions);

        return array_map(static function (array $row): array {
            return array_merge($row, ['pvz_code' => null]);
        }, $formatted);
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

    private function orderWeightGrams(ShopOrder $order): int
    {
        // TODO: использовать weight из Product, когда поле появится в каталоге
        $unitWeight = (int) (Yii::$app->params['cdekDefaultPackageWeightGrams'] ?? 500);
        $itemsCount = (int) OrderItem::find()->where(['order_id' => $order->id])->sum('quantity');

        return max($unitWeight, $unitWeight * max(1, $itemsCount));
    }

    /** @return array{0: string, 1: int, 2: bool}|null */
    private function mapMethodId(int $deliveryMethodId): ?array
    {
        return match ($deliveryMethodId) {
            self::METHOD_CDEK_PVZ_ID => [self::CODE_CDEK_PVZ, CdekMockData::TARIFF_PVZ, true],
            self::METHOD_CDEK_COURIER_ID => [self::CODE_CDEK_COURIER, CdekMockData::TARIFF_COURIER, false],
            default => null,
        };
    }

    /** @return array{0: int, 1: string, 2: string, 3: bool}|null */
    private function mapTariffToMethod(int $tariffCode): ?array
    {
        return match ($tariffCode) {
            CdekMockData::TARIFF_PVZ => [self::METHOD_CDEK_PVZ_ID, self::CODE_CDEK_PVZ, 'СДЭК до ПВЗ', true],
            CdekMockData::TARIFF_COURIER => [self::METHOD_CDEK_COURIER_ID, self::CODE_CDEK_COURIER, 'СДЭК курьером', false],
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function findTariff(int $toCityCode, int $weightGrams, int $tariffCode): array
    {
        foreach ($this->cdek->calculateTariffList($this->cdek->getFromCityCode(), $toCityCode, $weightGrams) as $tariff) {
            if ((int) ($tariff['tariff_code'] ?? 0) === $tariffCode) {
                return $tariff;
            }
        }

        return [
            'tariff_code' => $tariffCode,
            'delivery_sum' => $tariffCode === CdekMockData::TARIFF_PVZ ? 350.0 : 490.0,
            'period_min' => 2,
            'period_max' => 4,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function mockDeliveryOptions(): array
    {
        return [
            [
                'id' => self::METHOD_CDEK_PVZ_ID,
                'name' => 'СДЭК до ПВЗ',
                'code' => self::CODE_CDEK_PVZ,
                'is_pvz' => true,
                'price' => 350.0,
                'period_min' => 2,
                'period_max' => 4,
                'tariff_code' => CdekMockData::TARIFF_PVZ,
            ],
            [
                'id' => self::METHOD_CDEK_COURIER_ID,
                'name' => 'СДЭК курьером',
                'code' => self::CODE_CDEK_COURIER,
                'is_pvz' => false,
                'price' => 490.0,
                'period_min' => 2,
                'period_max' => 4,
                'tariff_code' => CdekMockData::TARIFF_COURIER,
            ],
        ];
    }
}
