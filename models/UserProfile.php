<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $phone_number
 * @property string|null $email
 * @property string|null $name
 * @property string|null $f фамилия
 * @property string|null $i имя
 * @property string|null $surname
 * @property string|null $gender
 * @property string|null $birth_date
 * @property bool $phone_number_confirmed
 * @property bool $email_confirmed
 * @property bool $news_subscribed
 */
class UserProfile extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%user_profile}}';
    }

    public function rules(): array
    {
        return [
            [['user_id'], 'required'],
            [['user_id'], 'integer'],
            [['phone_number'], 'string', 'max' => 32],
            [['email'], 'email'],
            [['email'], 'string', 'max' => 255],
            [['name'], 'string', 'max' => 255],
            [['f', 'i', 'surname'], 'string', 'max' => 100],
            [['f', 'i', 'surname'], 'trim'],
            [['gender'], 'string', 'max' => 16],
            [['birth_date'], 'date', 'format' => 'php:Y-m-d'],
            [['phone_number_confirmed', 'email_confirmed', 'news_subscribed'], 'boolean'],
        ];
    }

    public function getUser(): \yii\db\ActiveQuery
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getDisplayName(?string $fallbackUsername = null): string
    {
        $parts = array_values(array_filter([
            trim((string) ($this->f ?? '')),
            trim((string) ($this->i ?? '')),
            trim((string) ($this->surname ?? '')),
        ], static fn (string $part): bool => $part !== ''));

        if ($parts !== []) {
            return implode(' ', $parts);
        }

        $name = trim((string) ($this->name ?? ''));
        if ($name !== '') {
            return $name;
        }

        return $fallbackUsername ?? '';
    }

    public function getGenderLabel(): ?string
    {
        $code = trim((string) ($this->gender ?? ''));
        if ($code === '') {
            return null;
        }

        $options = Gender::getDropdownOptions();

        return $options[$code] ?? $code;
    }

    public static function findByPhone(string $phone): ?static
    {
        return static::findOne(['phone_number' => PhoneNormalizer::normalize($phone)]);
    }

    public static function findByEmail(string $email): ?static
    {
        return static::findOne(['email' => mb_strtolower(trim($email))]);
    }
}
