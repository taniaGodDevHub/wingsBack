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
     * @return array{
     *     items: list<array<string, mixed>>,
     *     meta: array{page: int, count: int, has_more: bool}
     * }
     */
    public function listDeliveryPoints(
        int $cityCode,
        int $limit = 10,
        int $page = 1,
        ?string $postalCode = null,
        ?string $fiasGuid = null,
        ?float $geoLat = null,
        ?float $geoLon = null,
    ): array {
        $limit = max(1, min(20, $limit));
        $page = max(1, $page);

        if ($this->isMockMode()) {
            return $this->paginateDeliveryPoints(
                $this->prepareMockDeliveryPoints($cityCode, $postalCode, $fiasGuid, $geoLat, $geoLon),
                $page,
                $limit,
                $geoLat,
                $geoLon,
            );
        }

        $query = [
            'city_code' => (string) $cityCode,
            'type' => 'PVZ',
        ];
        if ($postalCode !== null && $postalCode !== '') {
            $query['postal_code'] = $postalCode;
        }
        if ($fiasGuid !== null && $fiasGuid !== '') {
            $query['fias_guid'] = $fiasGuid;
        }

        if ($geoLat !== null && $geoLon !== null) {
            $query['size'] = '500';
            $response = $this->request('GET', '/v2/deliverypoints', null, $query);
            $points = $this->mapDeliveryPointRows(is_array($response) ? $response : [], $cityCode);

            if ($points === []) {
                $points = $this->prepareMockDeliveryPoints($cityCode, $postalCode, $fiasGuid, $geoLat, $geoLon);
            }

            return $this->paginateDeliveryPoints($points, $page, $limit, $geoLat, $geoLon);
        }

        $query['page'] = (string) ($page - 1);
        $query['size'] = (string) ($limit + 1);

        $response = $this->request('GET', '/v2/deliverypoints', null, $query);
        $points = $this->mapDeliveryPointRows(is_array($response) ? $response : [], $cityCode);

        if ($points === []) {
            return $this->paginateDeliveryPoints(
                $this->prepareMockDeliveryPoints($cityCode, $postalCode, $fiasGuid, null, null),
                $page,
                $limit,
                null,
                null,
            );
        }

        $hasMore = count($points) > $limit;
        $items = array_slice($points, 0, $limit);

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'count' => count($items),
                'has_more' => $hasMore,
            ],
        ];
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
            return CdekOrderNormalizer::normalize(CdekMockData::orderStatus($uuid));
        }

        $response = $this->request('GET', '/v2/orders/' . rawurlencode($uuid));

        return CdekOrderNormalizer::normalize(
            is_array($response) ? $response : CdekMockData::orderStatus($uuid),
        );
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
     * @param list<array<string, mixed>> $rows
     * @return list<array<string, mixed>>
     */
    private function mapDeliveryPointRows(array $rows, int $cityCode): array
    {
        $points = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $points[] = $this->mapDeliveryPointRow($row, $cityCode);
        }

        return $points;
    }

    /** @param array<string, mixed> $row */
    private function mapDeliveryPointRow(array $row, int $cityCode, ?float $distanceKm = null): array
    {
        $location = is_array($row['location'] ?? null) ? $row['location'] : [];
        $point = [
            'code' => (string) ($row['code'] ?? ''),
            'name' => (string) ($row['name'] ?? 'ПВЗ СДЭК'),
            'address' => (string) ($location['address'] ?? $row['address'] ?? ''),
            'work_time' => (string) ($row['work_time'] ?? ''),
            'lat' => isset($location['latitude']) ? (float) $location['latitude'] : null,
            'lon' => isset($location['longitude']) ? (float) $location['longitude'] : null,
            'city_code' => $cityCode,
        ];
        if ($distanceKm !== null) {
            $point['distance_km'] = round($distanceKm, 1);
        }

        return $point;
    }

    /**
     * @param list<array<string, mixed>> $points
     * @return array{
     *     items: list<array<string, mixed>>,
     *     meta: array{page: int, count: int, has_more: bool}
     * }
     */
    private function paginateDeliveryPoints(
        array $points,
        int $page,
        int $limit,
        ?float $geoLat,
        ?float $geoLon,
    ): array {
        if ($geoLat !== null && $geoLon !== null) {
            usort($points, function (array $left, array $right) use ($geoLat, $geoLon): int {
                $leftDistance = $this->distanceKm(
                    $geoLat,
                    $geoLon,
                    isset($left['lat']) ? (float) $left['lat'] : null,
                    isset($left['lon']) ? (float) $left['lon'] : null,
                );
                $rightDistance = $this->distanceKm(
                    $geoLat,
                    $geoLon,
                    isset($right['lat']) ? (float) $right['lat'] : null,
                    isset($right['lon']) ? (float) $right['lon'] : null,
                );

                return $leftDistance <=> $rightDistance;
            });

            $points = array_map(function (array $point) use ($geoLat, $geoLon): array {
                $distanceKm = $this->distanceKm(
                    $geoLat,
                    $geoLon,
                    isset($point['lat']) ? (float) $point['lat'] : null,
                    isset($point['lon']) ? (float) $point['lon'] : null,
                );
                if ($distanceKm !== null) {
                    $point['distance_km'] = round($distanceKm, 1);
                }

                return $point;
            }, $points);
        }

        $offset = ($page - 1) * $limit;
        $slice = array_slice($points, $offset, $limit + 1);
        $hasMore = count($slice) > $limit;
        $items = array_slice($slice, 0, $limit);

        return [
            'items' => $items,
            'meta' => [
                'page' => $page,
                'count' => count($items),
                'has_more' => $hasMore,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function prepareMockDeliveryPoints(
        int $cityCode,
        ?string $postalCode,
        ?string $_fiasGuid = null,
        ?float $_geoLat = null,
        ?float $_geoLon = null,
    ): array {
        $points = CdekMockData::deliveryPoints($cityCode);
        if ($postalCode !== null && $postalCode !== '') {
            $filtered = array_values(array_filter(
                $points,
                static fn (array $point): bool => str_contains((string) ($point['address'] ?? ''), $postalCode),
            ));
            if ($filtered !== []) {
                $points = $filtered;
            }
        }

        return $points;
    }

    private function distanceKm(float $latFrom, float $lonFrom, ?float $latTo, ?float $lonTo): ?float
    {
        if ($latTo === null || $lonTo === null) {
            return null;
        }

        $earthRadiusKm = 6371.0;
        $latDelta = deg2rad($latTo - $latFrom);
        $lonDelta = deg2rad($lonTo - $lonFrom);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($latFrom)) * cos(deg2rad($latTo)) * sin($lonDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
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
