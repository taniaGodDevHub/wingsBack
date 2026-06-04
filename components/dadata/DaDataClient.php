<?php

declare(strict_types=1);

namespace app\components\dadata;

use Yii;

final class DaDataClient
{
    private const SUGGEST_URL = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/suggest';

    public function suggestCity(string $query, int $count): array
    {
        $result = $this->request('/address', [
            'query' => $query,
            'count' => $count,
            'from_bound' => ['value' => 'city'],
            'to_bound' => ['value' => 'city'],
        ]);

        if ($result !== null) {
            return $result;
        }

        return $this->mockCities($query, $count);
    }

    public function suggestAddress(string $query, ?string $cityFiasId, int $count): array
    {
        $payload = [
            'query' => $query,
            'count' => $count,
        ];
        if ($cityFiasId !== null && $cityFiasId !== '') {
            $payload['locations'] = [['city_fias_id' => $cityFiasId]];
        }

        $result = $this->request('/address', $payload);
        if ($result !== null) {
            return $result;
        }

        return $this->mockAddresses($query, $cityFiasId, $count);
    }

    /** @param array<string, mixed> $payload */
    private function request(string $path, array $payload): ?array
    {
        $apiKey = (string) (Yii::$app->params['dadataApiKey'] ?? '');
        if ($apiKey === '') {
            return null;
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAccept: application/json\r\nAuthorization: Token {$apiKey}\r\n",
                'content' => $body,
                'timeout' => 8,
            ],
        ]);

        $raw = @file_get_contents(self::SUGGEST_URL . $path, false, $context);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['suggestions']) || !is_array($decoded['suggestions'])) {
            return null;
        }

        return $decoded['suggestions'];
    }

    /** @return array<int, array<string, mixed>> */
    private function mockCities(string $query, int $count): array
    {
        $q = mb_strtolower($query);
        $all = [
            [
                'value' => 'Москва',
                'unrestricted_value' => 'г Москва',
                'data' => ['city_fias_id' => '0c5b2444-70a3-4b20-878f-b0f2b8daecf0'],
            ],
            [
                'value' => 'Санкт-Петербург',
                'unrestricted_value' => 'г Санкт-Петербург',
                'data' => ['city_fias_id' => 'c2deb16a-0330-4f05-821f-1d09c93331e6'],
            ],
        ];
        $filtered = array_values(array_filter(
            $all,
            static fn (array $row): bool => $q === '' || str_contains(mb_strtolower($row['value']), $q),
        ));

        return array_slice($filtered, 0, $count);
    }

    /** @return array<int, array<string, mixed>> */
    private function mockAddresses(string $query, ?string $cityFiasId, int $count): array
    {
        $items = [
            [
                'value' => 'ул Тверская, д 7',
                'unrestricted_value' => 'г Москва, ул Тверская, д 7',
                'data' => [
                    'address_fias_id' => 'fias-id-1',
                    'house_fias_id' => 'fias-id-house-1',
                    'postal_code' => '125009',
                    'geo_lat' => '55.7641',
                    'geo_lon' => '37.6054',
                ],
            ],
        ];

        if ($query !== '') {
            $items[0]['value'] = $query;
        }

        return array_slice($items, 0, $count);
    }
}
