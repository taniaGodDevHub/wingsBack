<?php

declare(strict_types=1);

namespace app\components\cdek;

/**
 * Нормализация ответов СДЭК API v2 (плоский mock и вложенный entity).
 */
final class CdekOrderNormalizer
{
    /**
     * @param array<string, mixed> $response
     * @return array{
     *     status: string,
     *     current_status: string,
     *     cdek_number: string,
     *     description: string|null,
     *     current_city: string|null,
     *     expected_delivery: string|null,
     *     delivery_date: string|null,
     *     delivered_at: int|null,
     *     uuid: string
     * }
     */
    public static function normalize(array $response): array
    {
        $entity = is_array($response['entity'] ?? null) ? $response['entity'] : $response;
        $latestStatus = self::latestStatus(is_array($entity['statuses'] ?? null) ? $entity['statuses'] : []);

        $code = strtoupper((string) (
            $latestStatus['code']
            ?? $entity['status']
            ?? $response['status']
            ?? $response['current_status']
            ?? ''
        ));

        $name = trim((string) ($latestStatus['name'] ?? $response['description'] ?? ''));

        $plannedDate = self::normalizeDate(
            $entity['planned_delivery_date']
            ?? $entity['date_planned']
            ?? $response['expected_delivery']
            ?? null,
        );

        $deliveryDate = self::normalizeDate(
            $entity['delivery_date']
            ?? (is_array($entity['delivery_detail'] ?? null) ? ($entity['delivery_detail']['date'] ?? null) : null)
            ?? $entity['date_delivery']
            ?? null,
        );

        $expectedDelivery = $deliveryDate ?? $plannedDate;

        $city = trim((string) (
            $latestStatus['city']
            ?? $latestStatus['city_code']
            ?? $response['current_city']
            ?? ''
        ));

        $deliveredAt = self::resolveDeliveredAt($code, $deliveryDate, $latestStatus);

        return [
            'status' => $code,
            'current_status' => $code,
            'cdek_number' => (string) ($entity['cdek_number'] ?? $response['cdek_number'] ?? ''),
            'description' => $name !== '' ? $name : null,
            'current_city' => $city !== '' ? $city : null,
            'expected_delivery' => $expectedDelivery,
            'delivery_date' => $deliveryDate,
            'delivered_at' => $deliveredAt,
            'uuid' => (string) ($entity['uuid'] ?? $response['uuid'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $response
     * @return array{uuid: string, cdek_number: string, status: string}
     */
    public static function normalizeCreateResponse(array $response): array
    {
        $entity = is_array($response['entity'] ?? null) ? $response['entity'] : $response;
        $requests = is_array($response['requests'] ?? null) ? $response['requests'] : [];
        $requestEntity = is_array($requests[0]['entity'] ?? null) ? $requests[0]['entity'] : [];

        $uuid = (string) (
            $entity['uuid']
            ?? $requestEntity['uuid']
            ?? $response['uuid']
            ?? ''
        );

        $cdekNumber = (string) (
            $entity['cdek_number']
            ?? $response['cdek_number']
            ?? $response['track_number']
            ?? ''
        );

        $status = strtoupper((string) (
            $entity['status']
            ?? $response['status']
            ?? 'ACCEPTED'
        ));

        return [
            'uuid' => $uuid,
            'cdek_number' => $cdekNumber,
            'status' => $status,
        ];
    }

    /** @param list<mixed> $statuses @return array<string, mixed> */
    private static function latestStatus(array $statuses): array
    {
        $latest = [];
        $latestTimestamp = 0;

        foreach ($statuses as $status) {
            if (!is_array($status)) {
                continue;
            }

            $timestamp = strtotime((string) ($status['date_time'] ?? '')) ?: 0;
            if ($latest === [] || $timestamp >= $latestTimestamp) {
                $latest = $status;
                $latestTimestamp = $timestamp;
            }
        }

        return $latest;
    }

    private static function normalizeDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = strtotime((string) $value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    /** @param array<string, mixed> $latestStatus */
    private static function resolveDeliveredAt(string $code, ?string $deliveryDate, array $latestStatus): ?int
    {
        if ($deliveryDate !== null) {
            $timestamp = strtotime($deliveryDate . ' 12:00:00');

            return $timestamp !== false ? $timestamp : time();
        }

        if (!in_array($code, ['DELIVERED', 'POSTOMAT_RECEIVED'], true)) {
            return null;
        }

        $statusTimestamp = strtotime((string) ($latestStatus['date_time'] ?? ''));

        return $statusTimestamp !== false ? $statusTimestamp : time();
    }
}
