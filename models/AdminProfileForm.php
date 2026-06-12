<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\base\Model;

class AdminProfileForm extends Model
{
    public string $username = '';
    public string $email = '';
    public string $firstName = '';
    public string $lastName = '';
    public string $phoneNumber = '';

    private int $userId = 0;

    public function rules(): array
    {
        return [
            [['username'], 'required'],
            [['email', 'firstName', 'lastName', 'phoneNumber'], 'trim'],
            ['email', 'email'],
            ['email', 'string', 'max' => 255],
            ['email', 'validateEmailUnique'],
            [['firstName', 'lastName'], 'string', 'max' => 100],
            ['phoneNumber', 'string', 'max' => 32],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'username' => Yii::t('app', 'Account login'),
            'email' => Yii::t('app', 'Email'),
            'firstName' => Yii::t('app', 'First name'),
            'lastName' => Yii::t('app', 'Last name'),
            'phoneNumber' => Yii::t('app', 'Phone'),
        ];
    }

    public function loadFromUser(User $user): void
    {
        $profile = $user->profile;
        $this->userId = (int) $user->id;
        $this->username = $user->username;
        $this->email = $profile?->email ?? '';
        $this->firstName = $profile?->i ?? $profile?->name ?? '';
        $this->lastName = $profile?->f ?? $profile?->surname ?? '';
        $this->phoneNumber = $profile?->phone_number ?? '';
    }

    public function validateEmailUnique(string $attribute): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $email = mb_strtolower(trim($this->email));
        if ($email === '') {
            return;
        }

        $existing = UserProfile::findByEmail($email);
        if ($existing !== null && (int) $existing->user_id !== $this->userId) {
            $this->addError($attribute, Yii::t('app', 'This email is already registered.'));
        }
    }

    public function save(User $user): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $profile = $user->profile;
        if ($profile === null) {
            $profile = new UserProfile();
            $profile->user_id = (int) $user->id;
        }

        $email = mb_strtolower(trim($this->email));
        if ($email !== '' && $email !== mb_strtolower(trim((string) $profile->email))) {
            $profile->email = $email;
            $profile->email_confirmed = false;
        } elseif ($email === '') {
            $profile->email = null;
            $profile->email_confirmed = false;
        }

        $phone = $this->phoneNumber !== ''
            ? PhoneNormalizer::normalize($this->phoneNumber)
            : null;
        if ($phone !== $profile->phone_number) {
            $profile->phone_number = $phone;
            $profile->phone_number_confirmed = false;
        }

        $this->firstName = trim($this->firstName);
        $this->lastName = trim($this->lastName);
        $profile->i = $this->firstName !== '' ? $this->firstName : null;
        $profile->name = $profile->i;
        $profile->f = $this->lastName !== '' ? $this->lastName : null;
        $profile->surname = $profile->f;

        if (!$profile->save()) {
            $this->addErrors($profile->getErrors());

            return false;
        }

        return true;
    }
}
