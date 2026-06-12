<?php

declare(strict_types=1);

namespace app\models;

use app\services\AdminPasswordResetService;
use Yii;
use yii\base\Model;

class AdminResetPasswordForm extends Model
{
    public string $email = '';
    public string $code = '';
    public string $password = '';
    public string $passwordRepeat = '';
    public string $recordId = '';

    public function rules(): array
    {
        return [
            [['email', 'code', 'password', 'passwordRepeat', 'recordId'], 'required'],
            ['email', 'trim'],
            ['email', 'email'],
            ['code', 'string', 'length' => 6],
            ['password', 'string', 'min' => 6],
            ['passwordRepeat', 'compare', 'compareAttribute' => 'password'],
            ['recordId', 'validateRecordId'],
            ['code', 'validateReset'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'email' => Yii::t('app', 'Email'),
            'code' => Yii::t('app', 'Verification code'),
            'password' => Yii::t('app', 'New password'),
            'passwordRepeat' => Yii::t('app', 'Repeat password'),
        ];
    }

    public function validateRecordId(string $attribute): void
    {
        if ($this->hasErrors($attribute)) {
            return;
        }

        if ($this->recordId === '') {
            $this->addError($attribute, Yii::t('app', 'Request a password reset code first.'));
        }
    }

    public function validateReset(string $attribute): void
    {
        if ($this->hasErrors()) {
            return;
        }

        $service = new AdminPasswordResetService();
        if (!$service->verifyCode($this->recordId, $this->email, $this->code)) {
            $this->addError($attribute, Yii::t('app', 'Invalid or expired verification code.'));
        }
    }

    public function resetPassword(): bool
    {
        if (!$this->validate()) {
            return false;
        }

        $saved = (new AdminPasswordResetService())->resetPassword(
            $this->recordId,
            $this->email,
            $this->code,
            $this->password,
        );

        if (!$saved) {
            $this->addError('code', Yii::t('app', 'Invalid or expired verification code.'));
        }

        return $saved;
    }
}
