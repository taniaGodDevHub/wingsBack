<?php

declare(strict_types=1);

namespace app\components\cdek;

use Yii;
use yii\base\Component;

/**
 * Клиент СДЭК API v2.
 *
 * При cdekMockMode=true или пустых credentials возвращает mock-данные.
 *
 * @see https://cdekrussia.ru/integration
 */
final class CdekClient extends Component
{
    private const TOKEN_CACHE_KEY = 'cdek_oauth_token';

    public function isMockMode(): bool
    {
        if ((bool) (Yii::$app->params['cdekMockMode'] ?? true)) {
            return true;
        }

        $clientId = (string) (Yii::$app->params['cdekClientId'] ?? '');
        $clientSecret = (string) (Yii::$app->params['cdekClientSecret'] ?? '');

        return $clientId === '' || $clientSecret === '';
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function calculateTariffList(int $fromCityCode, int $toCityCode, int $weightGrams): array
    {
        if ($this->isMockMode()) {
            return CdekMockData::tariffOptions($toCityCode, $weightGrams);
        }

        $payload = [
            'type' => 1,
            'from_location' => ['code' => $fromCityCode],
            'to_location' => ['code' => $toCityCode],
            'packages' => [
                ['weight' => max(1, $weightGrams)],
            ],
        ];

        $response = $this->request('POST', '/v2/calculator/tarifflist', $payload);
        if ($response === null || !isset($response['tariff_codes']) || !is_array($response['tariff_codes'])) {
            return CdekMockData::tariffOptions($toCityCode, $weightGrams);
        }

        $result = [];
        foreach ($response['tariff_codes'] as $tariff) {
            if (!is_array($tariff)) {
                continue;
            }
            $result[] = [
                'tariff_code' => (int) ($tariff['tariff_code'] ?? 0),
                'delivery_sum' => (float) ($tariff['delivery_sum'] ?? 0),
                'period_min' => (int) ($tariff['period_min'] ?? 0),
                'period_max' => (int) ($tariff['period_max'] ?? 0),
                'to_city_code' => $toCityCode,
                'weight_grams' => $weightGrams,
            ];
        }

        return $result !== [] ? $result : CdekMockData::tariffOptions($toCityCode, $weightGrams);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listDeliveryPoints(int $cityCode): array
    {
        if ($this->isMockMode()) {
            return CdekMockData::deliveryPoints($cityCode);
        }

        $response = $this->request('GET', '/v2/deliverypoints', null, [
            'city_code' => (string) $cityCode,
            'type' => 'PVZ',
        ]);
        if ($response === null || !is_array($response)) {
            return CdekMockData::deliveryPoints($cityCode);
        }

        $points = [];
        foreach ($response as $row) {
            if (!is_array($row)) {
                continue;
            }
            $location = is_array($row['location'] ?? null) ? $row['location'] : [];
            $points[] = [
                'code' => (string) ($row['code'] ?? ''),
                'name' => (string) ($row['name'] ?? 'ПВЗ СДЭК'),
                'address' => (string) ($location['address'] ?? $row['address'] ?? ''),
                'work_time' => (string) ($row['work_time'] ?? ''),
                'lat' => isset($location['latitude']) ? (float) $location['latitude'] : null,
                'lon' => isset($location['longitude']) ? (float) $location['longitude'] : null,
                'city_code' => $cityCode,
            ];
        }

        return $points !== [] ? $points : CdekMockData::deliveryPoints($cityCode);
    }

    public function resolveCityCode(?string $cityFiasId, ?string $cityName = null): int
    {
        if ($this->isMockMode()) {
            return CdekMockData::resolveCityCode($cityFiasId);
        }

        $query = [];
        if ($cityFiasId !== null && $cityFiasId !== '') {
            $query['fias_guid'] = $cityFiasId;
        } elseif ($cityName !== null && $cityName !== '') {
            $query['city'] = $cityName;
        } else {
            return (int) (Yii::$app->params['cdekFromCityCode'] ?? 44);
        }

        $response = $this->request('GET', '/v2/location/cities', null, $query);
        if (is_array($response) && isset($response[0]['code'])) {
            return (int) $response[0]['code'];
        }

        return CdekMockData::resolveCityCode($cityFiasId);
    }

    /** @param array<string, mixed> $payload */
    public function createOrder(array $payload): array
    {
        if ($this->isMockMode()) {
            return CdekMockData::createOrderResponse();
        }

        $response = $this->request('POST', '/v2/orders', $payload);

        return is_array($response) ? $response : CdekMockData::createOrderResponse();
    }

    /** @return array<string, mixed> */
    public function getOrderStatus(string $uuid): array
    {
        if ($this->isMockMode()) {
            return CdekMockData::orderStatus($uuid);
        }

        $response = $this->request('GET', '/v2/orders/' . rawurlencode($uuid));

        return is_array($response) ? $response : CdekMockData::orderStatus($uuid);
    }

    public function getFromCityCode(): int
    {
        return (int) (Yii::$app->params['cdekFromCityCode'] ?? 44);
    }

    public function getAccessToken(): ?string
    {
        if ($this->isMockMode()) {
            return 'mock-token';
        }

        $cache = Yii::$app->cache;
        $cached = $cache->get(self::TOKEN_CACHE_KEY);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $clientId = (string) (Yii::$app->params['cdekClientId'] ?? '');
        $clientSecret = (string) (Yii::$app->params['cdekClientSecret'] ?? '');
        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        $baseUrl = rtrim((string) (Yii::$app->params['cdekApiBaseUrl'] ?? 'https://api.edu.cdek.ru'), '/');
        $body = http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\nAccept: application/json\r\n",
                'content' => $body,
                'timeout' => 10,
            ],
        ]);

        $raw = @file_get_contents("{$baseUrl}/v2/oauth/token", false, $context);
        if ($raw === false) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['access_token'])) {
            return null;
        }

        $ttl = max(60, (int) ($decoded['expires_in'] ?? 3600) - 60);
        $cache->set(self::TOKEN_CACHE_KEY, (string) $decoded['access_token'], $ttl);

        return (string) $decoded['access_token'];
    }

    /**
     * @param array<string, mixed>|null $payload
     * @param array<string, string> $query
     * @return array<string, mixed>|list<array<string, mixed>>|null
     */
    private function request(string $method, string $path, ?array $payload = null, array $query = []): array|null
    {
        $token = $this->getAccessToken();
        if ($token === null) {
            return null;
        }

        $baseUrl = rtrim((string) (Yii::$app->params['cdekApiBaseUrl'] ?? 'https://api.edu.cdek.ru'), '/');
        $url = $baseUrl . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        $headers = "Accept: application/json\r\nAuthorization: Bearer {$token}\r\n";
        $content = null;
        if ($payload !== null) {
            $content = json_encode($payload, JSON_UNESCAPED_UNICODE);
            if ($content === false) {
                return null;
            }
            $headers .= "Content-Type: application/json\r\n";
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => $headers,
                'content' => $content ?? '',
                'timeout' => 12,
                'ignore_errors' => true,
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }
}
