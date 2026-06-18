<?php

return [
    'jwt' => [
        'class' => \app\components\auth\JwtService::class,
    ],
    'smsRu' => [
        'class' => \app\components\sms\SmsRuClient::class,
    ],
    'smsSender' => [
        'class' => \app\components\sms\SmsSender::class,
    ],
    'authCodeMailer' => [
        'class' => \app\components\mail\AuthCodeMailer::class,
    ],
];
