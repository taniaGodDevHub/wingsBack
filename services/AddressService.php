<?php

declare(strict_types=1);

namespace app\services;

use app\components\api\ApiHttpException;
use app\models\User;
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
        $address = new UserAddress();
        $address->user_id = $userId;
        $this->fillAddress($address, $data);
        if (!$address->save()) {
            throw ApiHttpException::validation(\app\components\api\ApiErrorHandler::validationDetail($address));
        }

        return $address->toShortArray();
    }

    /** @param array<string, mixed> $data */
    public function update(int $userId, int $addressId, array $data): array
    {
        $address = $this->findOwned($userId, $addressId);
        $this->fillAddress($address, $data);
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
    private function fillAddress(UserAddress $address, array $data): void
    {
        $map = [
            'city_id', 'city_fias_id', 'fias_id', 'kladr_id', 'city_name', 'region',
            'postal_code', 'latitude', 'longitude', 'full_address',
        ];
        foreach ($map as $field) {
            if (array_key_exists($field, $data)) {
                $address->$field = $data[$field] !== null ? (string) $data[$field] : null;
            }
        }
        if (isset($data['city_id'])) {
            $address->city_id = (int) $data['city_id'];
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
}
