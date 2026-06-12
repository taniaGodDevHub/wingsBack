<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\base\Model;
use yii\base\Security;

/**
 * @property-read User|null $user
 */
class AdminLoginForm extends Model
{
    public string $login = '';
    public string $password = '';
    public bool $rememberMe = true;

    private User|null $_user = null;
    private bool $_userLoaded = false;

    public function __construct(private readonly Security $security, $config = [])
    {
        parent::__construct($config);
    }

    public function rules(): array
    {
        return [
            [['login', 'password'], 'required'],
            ['login', 'trim'],
            ['rememberMe', 'boolean'],
            ['password', 'validatePassword'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'login' => Yii::t('app', 'Login or email'),
            'password' => Yii::t('app', 'Password'),
            'rememberMe' => Yii::t('app', 'Remember Me'),
        ];
    }

    public function validatePassword(string $attribute, array|null $params): void
    {
        if ($this->hasErrors()) {
            return;
        }

        $user = $this->getUser();
        $hash = $user?->getPasswordHash();

        if ($user === null || $hash === null || !$this->security->validatePassword($this->password, $hash)) {
            $this->addError($attribute, Yii::t('app', 'Incorrect login or password.'));
        }
    }

    public function login(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $user = $this->getUser();
        if ($user === null) {
            return false;
        }

        return Yii::$app->user->login($user, $this->rememberMe ? 3600 * 24 * 30 : 0);
    }

    public function getUser(): User|null
    {
        if (!$this->_userLoaded) {
            $this->_user = User::findByLogin($this->login);
            $this->_userLoaded = true;
        }

        return $this->_user;
    }
}
