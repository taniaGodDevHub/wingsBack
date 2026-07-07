<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property bool $is_pvz
 * @property string|null $pvz_code
 * @property int|null $city_id
 * @property string|null $city_fias_id
 * @property string|null $fias_id
 * @property string|null $kladr_id
 * @property string|null $city_name
 * @property string|null $region
 * @property string|null $postal_code
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string $full_address
 * @property int $created_at
 * @property int $updated_at
 */
class UserAddress extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%user_address}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['user_id', 'full_address'], 'required'],
            [['user_id', 'city_id'], 'integer'],
            [['is_pvz'], 'boolean'],
            [['full_address'], 'string', 'max' => 512],
            [['pvz_code'], 'string', 'max' => 32],
            [['city_fias_id', 'fias_id'], 'string', 'max' => 64],
            [['kladr_id', 'postal_code', 'latitude', 'longitude'], 'string', 'max' => 32],
            [['city_name', 'region'], 'string', 'max' => 255],
        ];
    }

    public function toApiArray(): array
    {
        return [
            'id' => (int) $this->id,
            'is_pvz' => (bool) $this->is_pvz,
            'pvz_code' => $this->pvz_code,
            'city_id' => $this->city_id !== null ? (int) $this->city_id : null,
            'city_fias_id' => $this->city_fias_id,
            'fias_id' => $this->fias_id,
            'kladr_id' => $this->kladr_id,
            'city_name' => $this->city_name,
            'region' => $this->region,
            'postal_code' => $this->postal_code,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'full_address' => $this->full_address,
        ];
    }

    public function toShortArray(): array
    {
        return [
            'id' => (int) $this->id,
            'is_pvz' => (bool) $this->is_pvz,
            'pvz_code' => $this->pvz_code,
            'city_name' => $this->city_name,
            'full_address' => $this->full_address,
        ];
    }
}
