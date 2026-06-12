<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\base\Model;

class AdminRequestPasswordResetForm extends Model
{
    public string $email = '';

    public function rules(): array
    {
        return [
            ['email', 'required'],
            ['email', 'trim'],
            ['email', 'email'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'email' => Yii::t('app', 'Email'),
        ];
    }
}
