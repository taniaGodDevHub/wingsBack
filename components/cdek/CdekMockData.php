<?php

declare(strict_types=1);

namespace app\components\cdek;

/**
 * Mock-данные СДЭК до подключения ЛК.
 *
 * @see https://cdekrussia.ru/integration
 */
final class CdekMockData
{
    public const TARIFF_PVZ = 136;
    public const TARIFF_COURIER = 137;

    /** @return list<array<string, mixed>> */
    public static function tariffOptions(int $toCityCode, int $weightGrams): array
    {
        return [
            [
                'tariff_code' => self::TARIFF_PVZ,
                'delivery_sum' => 350.0,
                'period_min' => 2,
                'period_max' => 4,
                'to_city_code' => $toCityCode,
                'weight_grams' => $weightGrams,
            ],
            [
                'tariff_code' => self::TARIFF_COURIER,
                'delivery_sum' => 490.0,
                'period_min' => 2,
                'period_max' => 4,
                'to_city_code' => $toCityCode,
                'weight_grams' => $weightGrams,
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function deliveryPoints(int $cityCode): array
    {
        return [
            [
                'code' => 'MSK1',
                'name' => 'СДЭК ПВЗ Тверская',
                'address' => 'г Москва, ул Тверская, д 7',
                'work_time' => 'Пн-Пт 10:00-20:00, Сб-Вс 10:00-18:00',
                'lat' => 55.7641,
                'lon' => 37.6054,
                'city_code' => $cityCode,
            ],
            [
                'code' => 'MSK2',
                'name' => 'СДЭК ПВЗ Арбат',
                'address' => 'г Москва, ул Арбат, д 12',
                'work_time' => 'Ежедневно 09:00-21:00',
                'lat' => 55.7520,
                'lon' => 37.5925,
                'city_code' => $cityCode,
            ],
            [
                'code' => 'MSK3',
                'name' => 'СДЭК ПВЗ Китай-город',
                'address' => 'г Москва, ул Маросейка, д 3',
                'work_time' => 'Пн-Вс 10:00-22:00',
                'lat' => 55.7576,
                'lon' => 37.6331,
                'city_code' => $cityCode,
            ],
            [
                'code' => 'MSK4',
                'name' => 'СДЭК ПВЗ Смоленская',
                'address' => 'г Москва, Смоленская пл, д 3',
                'work_time' => 'Пн-Пт 10:00-20:00',
                'lat' => 55.7471,
                'lon' => 37.5808,
                'city_code' => $cityCode,
            ],
            [
                'code' => 'MSK5',
                'name' => 'СДЭК ПВЗ Покровка',
                'address' => 'г Москва, ул Покровка, д 17',
                'work_time' => 'Ежедневно 09:00-21:00',
                'lat' => 55.7586,
                'lon' => 37.6492,
                'city_code' => $cityCode,
            ],
            [
                'code' => 'MSK6',
                'name' => 'СДЭК ПВЗ Курская',
                'address' => 'г Москва, ул Земляной Вал, д 50',
                'work_time' => 'Пн-Сб 10:00-20:00',
                'lat' => 55.7570,
                'lon' => 37.6590,
                'city_code' => $cityCode,
            ],
            [
                'code' => 'MSK7',
                'name' => 'СДЭК ПВЗ Белорусская',
                'address' => 'г Москва, Лесная ул, д 5',
                'work_time' => 'Пн-Пт 09:00-20:00',
                'lat' => 55.7763,
                'lon' => 37.5865,
                'city_code' => $cityCode,
            ],
            [
                'code' => 'MSK8',
                'name' => 'СДЭК ПВЗ Сокол',
                'address' => 'г Москва, Ленинградский пр-кт, д 69',
                'work_time' => 'Ежедневно 10:00-21:00',
                'lat' => 55.8052,
                'lon' => 37.5151,
                'city_code' => $cityCode,
            ],
            [
                'code' => 'MSK9',
                'name' => 'СДЭК ПВЗ ВДНХ',
                'address' => 'г Москва, пр-кт Мира, д 119',
                'work_time' => 'Пн-Вс 10:00-22:00',
                'lat' => 55.8298,
                'lon' => 37.6361,
                'city_code' => $cityCode,
            ],
            [
                'code' => 'MSK10',
                'name' => 'СДЭК ПВЗ Юго-Западная',
                'address' => 'г Москва, пр-кт Вернадского, д 86',
                'work_time' => 'Пн-Пт 10:00-20:00',
                'lat' => 55.6633,
                'lon' => 37.4828,
                'city_code' => $cityCode,
            ],
            [
                'code' => 'MSK11',
                'name' => 'СДЭК ПВЗ Таганская',
                'address' => 'г Москва, Таганская пл, д 12',
                'work_time' => 'Ежедневно 09:00-21:00',
                'lat' => 55.7414,
                'lon' => 37.6533,
                'city_code' => $cityCode,
            ],
            [
                'code' => 'MSK12',
                'name' => 'СДЭК ПВЗ Кунцево',
                'address' => 'г Москва, ул Молдавская, д 5',
                'work_time' => 'Пн-Сб 10:00-20:00',
                'lat' => 55.7308,
                'lon' => 37.4465,
                'city_code' => $cityCode,
            ],
        ];
    }

    public static function resolveCityCode(?string $cityFiasId): int
    {
        return match ($cityFiasId) {
            '0c5b2444-70a3-4b20-878f-b0f2b8daecf0' => 44,
            'c2deb16a-0330-4f05-821f-1d09c93331e6' => 137,
            'f073f850-6c3b-4329-90ba-3c1489d457a1' => 435,
            default => 44,
        };
    }

    /** @return array<string, mixed> */
    public static function createOrderResponse(): array
    {
        return [
            'uuid' => sprintf(
                '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0x0fff) | 0x4000,
                random_int(0, 0x3fff) | 0x8000,
                random_int(0, 0xffff),
                random_int(0, 0xffff),
                random_int(0, 0xffff),
            ),
            'cdek_number' => '10123456789',
            'status' => 'ACCEPTED',
        ];
    }

    /** @return array<string, mixed> */
    public static function orderStatus(string $uuid): array
    {
        return [
            'entity' => [
                'uuid' => $uuid,
                'cdek_number' => '10123456789',
                'planned_delivery_date' => date('Y-m-d', strtotime('+3 days')),
                'statuses' => [
                    [
                        'code' => 'ACCEPTED',
                        'name' => 'Принят складом СДЭК',
                        'date_time' => date('c'),
                        'city' => 'Москва',
                    ],
                ],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function deliveredOrderStatus(string $uuid): array
    {
        $deliveryDate = date('Y-m-d', strtotime('-1 day'));

        return [
            'entity' => [
                'uuid' => $uuid,
                'cdek_number' => '10123456789',
                'planned_delivery_date' => $deliveryDate,
                'delivery_date' => $deliveryDate,
                'statuses' => [
                    [
                        'code' => 'DELIVERED',
                        'name' => 'Заказ вручён получателю',
                        'date_time' => $deliveryDate . 'T14:00:00+03:00',
                        'city' => 'Москва',
                    ],
                ],
            ],
        ];
    }
}
