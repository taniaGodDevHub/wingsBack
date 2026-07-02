<?php

declare(strict_types=1);

namespace app\models;

use app\components\auth\JwtService;
use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * @property int $id
 * @property string $username
 * @property string $auth_key
 * @property string|null $password_hash
 * @property int $status
 * @property int $created_at
 * @property int $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    public const STATUS_DELETED = 0;
    public const STATUS_ACTIVE = 10;

    public static function tableName(): string
    {
        return '{{%user}}';
    }

    public function behaviors(): array
    {
        return [TimestampBehavior::class];
    }

    public function rules(): array
    {
        return [
            [['username', 'auth_key'], 'required'],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
            ['username', 'string', 'max' => 255],
            ['password_hash', 'string', 'max' => 255],
        ];
    }

    public static function findIdentity($id): ?static
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findIdentityByAccessToken($token, $type = null): ?static
    {
        /** @var JwtService $jwt */
        $jwt = Yii::$app->jwt;

        return $jwt->validateAccessToken($token);
    }

    public static function findByUsername(string $username): ?static
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    public static function findByLogin(string $login): ?static
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }

        if (str_contains($login, '@')) {
            $profile = UserProfile::findByEmail($login);

            return $profile?->user;
        }

        return static::findByUsername($login);
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function getAuthKey(): ?string
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->auth_key === $authKey;
    }

    public function getPasswordHash(): ?string
    {
        return $this->password_hash;
    }

    public function getProfile(): \yii\db\ActiveQuery
    {
        return $this->hasOne(UserProfile::class, ['user_id' => 'id']);
    }

    public function getOrders(): \yii\db\ActiveQuery
    {
        return $this->hasMany(ShopOrder::class, ['user_id' => 'id']);
    }

    public function getAddresses(): \yii\db\ActiveQuery
    {
        return $this->hasMany(UserAddress::class, ['user_id' => 'id']);
    }

    public function getDisplayName(): string
    {
        $profile = $this->profile;
        if ($profile === null) {
            return $this->username;
        }

        return $profile->getDisplayName($this->username);
    }

    public static function generateUsername(string $prefix): string
    {
        return $prefix . '_' . substr(Yii::$app->security->generateRandomString(8), 0, 8);
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }
}
