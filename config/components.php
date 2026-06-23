<?php

return [
    'jwt' => [
        'class' => \app\components\auth\JwtService::class,
    ],
    'smsRu' => [
        'class' => \app\components\sms\SmsRuClient::class,
    ],
    'authCodeMailer' => [
        'class' => \app\components\mail\AuthCodeMailer::class,
    ],
];
