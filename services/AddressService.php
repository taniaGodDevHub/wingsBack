<?php

declare(strict_types=1);

namespace app\services;

use app\components\api\ApiHttpException;
use app\components\api\CheckoutApiException;
use app\models\ShopOrder;
use app\models\UserAddress;

class AddressService
{
    public function list(int $userId): array
    {
        $addresses = UserAddress::find()
            ->where(['user_id' => $userId])
            ->orderBy(['id' => SORT_DESC])
            ->all();

        return [
            'addresses' => array_map(static fn (UserAddress $a): array => $a->toApiArray(), $addresses),
        ];
    }

    /** @param array<string, mixed> $data */
    public function add(int $userId, array $data): array
    {
        $address = $this->upsert($userId, $data);

        return $address->toShortArray();
    }

    public function saveFromOrder(int $userId, ShopOrder $order, bool $isPvz): ?UserAddress
    {
        $fullAddress = trim((string) ($order->destination_address ?: $order->delivery_address));
        if ($fullAddress === '') {
            return null;
        }

        $pvzCode = $isPvz
            ? $this->normalizePvzCode((string) ($order->pvz_code ?: $order->destination_id))
            : null;

        if ($isPvz && $pvzCode === null) {
            return null;
        }

        return $this->upsert($userId, [
            'is_pvz' => $isPvz,
            'pvz_code' => $pvzCode,
            'city_fias_id' => $order->city_fias_id,
            'fias_id' => $isPvz ? null : $this->resolveFiasId((string) $order->destination_id),
            'full_address' => $fullAddress,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function update(int $userId, int $addressId, array $data): array
    {
        $address = $this->findOwned($userId, $addressId);
        $this->fillAddress($address, $data);

        $duplicate = $this->findDuplicate(
            $userId,
            (bool) $address->is_pvz,
            $this->normalizePvzCode($address->pvz_code),
            (string) ($address->city_fias_id ?? ''),
            $address->fias_id,
            (string) $address->full_address,
            (int) $address->id,
        );
        if ($duplicate !== null) {
            throw CheckoutApiException::conflict('Address already exists.');
        }

        $address->save(false);

        return $address->toShortArray();
    }

    public function delete(int $userId, int $addressId): array
    {
        $address = $this->findOwned($userId, $addressId);
        $address->delete();

        return ['ok' => true];
    }

    /** @param array<string, mixed> $data */
    private function upsert(int $userId, array $data): UserAddress
    {
        $isPvz = (bool) ($data['is_pvz'] ?? false);
        $pvzCode = $isPvz ? $this->normalizePvzCode((string) ($data['pvz_code'] ?? '')) : null;
        $fullAddress = trim((string) ($data['full_address'] ?? ''));
        if ($fullAddress === '') {
            throw new \InvalidArgumentException('full_address is required.');
        }

        $fiasId = $isPvz ? null : $this->resolveFiasId((string) ($data['fias_id'] ?? ''));
        $cityFiasId = (string) ($data['city_fias_id'] ?? '');

        $existing = $this->findDuplicate($userId, $isPvz, $pvzCode, $cityFiasId, $fiasId, $fullAddress);
        if ($existing !== null) {
            return $existing;
        }

        $address = new UserAddress();
        $address->user_id = $userId;
        $this->fillAddress($address, $data);
        $address->is_pvz = $isPvz;
        $address->pvz_code = $pvzCode;
        $address->full_address = $fullAddress;
        if (!$address->save()) {
            throw ApiHttpException::validation(\app\components\api\ApiErrorHandler::validationDetail($address));
        }

        return $address;
    }

    private function findDuplicate(
        int $userId,
        bool $isPvz,
        ?string $pvzCode,
        string $cityFiasId,
        ?string $fiasId,
        string $fullAddress,
        ?int $excludeId = null,
    ): ?UserAddress {
        $query = UserAddress::find()->where(['user_id' => $userId, 'is_pvz' => $isPvz]);
        if ($excludeId !== null) {
            $query->andWhere(['<>', 'id', $excludeId]);
        }

        if ($isPvz) {
            if ($pvzCode === null) {
                return null;
            }

            return $query->andWhere(['pvz_code' => $pvzCode])->one();
        }

        if ($fiasId !== null && $fiasId !== '') {
            $byFias = (clone $query)->andWhere(['fias_id' => $fiasId])->one();
            if ($byFias !== null) {
                return $byFias;
            }
        }

        $normalized = $this->normalizeFullAddress($fullAddress);
        if ($normalized === '') {
            return null;
        }

        $candidates = $query->andWhere(['is_pvz' => false])->all();
        foreach ($candidates as $candidate) {
            if ($this->normalizeFullAddress((string) $candidate->full_address) !== $normalized) {
                continue;
            }

            $candidateCityFias = trim((string) ($candidate->city_fias_id ?? ''));
            if ($cityFiasId !== '' && $candidateCityFias !== '' && $candidateCityFias !== $cityFiasId) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

    /** @param array<string, mixed> $data */
    private function fillAddress(UserAddress $address, array $data): void
    {
        $map = [
            'city_id', 'city_fias_id', 'fias_id', 'kladr_id', 'city_name', 'region',
            'postal_code', 'latitude', 'longitude', 'full_address', 'pvz_code',
        ];
        foreach ($map as $field) {
            if (array_key_exists($field, $data)) {
                $address->$field = $data[$field] !== null ? (string) $data[$field] : null;
            }
        }
        if (array_key_exists('city_id', $data)) {
            $address->city_id = $data['city_id'] !== null ? (int) $data['city_id'] : null;
        }
        if (array_key_exists('is_pvz', $data)) {
            $address->is_pvz = (bool) $data['is_pvz'];
        }
        if (array_key_exists('pvz_code', $data)) {
            $address->pvz_code = $this->normalizePvzCode((string) ($data['pvz_code'] ?? ''));
        }
    }

    private function findOwned(int $userId, int $addressId): UserAddress
    {
        $address = UserAddress::findOne(['id' => $addressId, 'user_id' => $userId]);
        if ($address === null) {
            throw ApiHttpException::notFound('Address not found');
        }

        return $address;
    }

    private function normalizePvzCode(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        return $code !== '' ? $code : null;
    }

    private function normalizeFullAddress(string $address): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($address));

        return $normalized !== null ? mb_strtolower($normalized) : '';
    }

    private function resolveFiasId(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1) {
            return $value;
        }

        return null;
    }
}
