<?php

declare(strict_types=1);

namespace app\models;

use app\services\UserRoleService;
use yii\base\Model;

class AdminSignupForm extends Model
{
    public string $login = '';
    public string $password = '';
    public string $passwordRepeat = '';
    public bool $agreeToTerms = false;

    public function rules(): array
    {
        return [
            [['login', 'password', 'passwordRepeat'], 'required'],
            ['login', 'trim'],
            ['login', 'validateLogin'],
            ['password', 'string', 'min' => 6],
            ['passwordRepeat', 'compare', 'compareAttribute' => 'password'],
            ['agreeToTerms', 'compare', 'compareValue' => true, 'message' => Yii::t('app', 'You must agree to the terms.')],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'login' => Yii::t('app', 'Login or email'),
            'password' => Yii::t('app', 'Password'),
            'passwordRepeat' => Yii::t('app', 'Repeat password'),
            'agreeToTerms' => Yii::t('app', 'I agree to the marketplace terms of use and return policy'),
        ];
    }

    public function validateLogin(string $attribute): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        $login = $this->login;
        if (str_contains($login, '@')) {
            if (!filter_var($login, FILTER_VALIDATE_EMAIL)) {
                $this->addError($attribute, Yii::t('app', 'Invalid email address.'));

                return;
            }

            if (UserProfile::findByEmail($login) !== null) {
                $this->addError($attribute, Yii::t('app', 'This email is already registered.'));
            }

            return;
        }

        if (!preg_match('/^[a-zA-Z0-9._-]{3,64}$/', $login)) {
            $this->addError($attribute, Yii::t('app', 'Login must be 3–64 characters: letters, digits, dot, dash or underscore.'));

            return;
        }

        if (User::findByUsername($login) !== null) {
            $this->addError($attribute, Yii::t('app', 'This login is already taken.'));
        }
    }

    public function signup(): User|null
    {
        if (!$this->validate()) {
            return null;
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $isEmail = str_contains($this->login, '@');
            $username = $isEmail
                ? User::generateUsername('user')
                : $this->login;

            $user = new User();
            $user->username = $username;
            $user->auth_key = Yii::$app->security->generateRandomString();
            $user->status = User::STATUS_ACTIVE;
            $user->setPassword($this->password);

            if (!$user->save()) {
                $this->addErrors($user->getErrors());
                $transaction->rollBack();

                return null;
            }

            $profile = new UserProfile();
            $profile->user_id = (int) $user->id;
            if ($isEmail) {
                $profile->email = mb_strtolower(trim($this->login));
                $profile->email_confirmed = false;
            }

            if (!$profile->save()) {
                $this->addErrors($profile->getErrors());
                $transaction->rollBack();

                return null;
            }

            (new UserRoleService())->assignDefaultRole($user);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        return $user;
    }
}
