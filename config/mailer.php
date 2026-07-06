<?php

declare(strict_types=1);

$smtpHost = getenv('SMTP_HOST') ?: 'smtp.timeweb.ru';
$smtpPort = (int) (getenv('SMTP_PORT') ?: 465);
$smtpScheme = getenv('SMTP_SCHEME') ?: 'smtps';
$smtpUser = getenv('SMTP_USER') ?: 'info@e-wings.ru';
$smtpPassword = getenv('SMTP_PASSWORD') ?: '';
$smtpFromName = getenv('SMTP_FROM_NAME') ?: 'Wings';

$useFileTransport = getenv('MAIL_USE_FILE_TRANSPORT');
if ($useFileTransport === false || $useFileTransport === '') {
    $useFileTransport = $smtpPassword === '';
} else {
    $useFileTransport = filter_var($useFileTransport, FILTER_VALIDATE_BOOLEAN);
}

$config = [
    'class' => \yii\symfonymailer\Mailer::class,
    'viewPath' => '@app/mail',
    'useFileTransport' => $useFileTransport,
    'messageConfig' => [
        'from' => [$smtpUser => $smtpFromName],
    ],
];

if (!$useFileTransport) {
    $config['transport'] = [
        'scheme' => $smtpScheme,
        'host' => $smtpHost,
        'username' => $smtpUser,
        'password' => $smtpPassword,
        'port' => $smtpPort,
    ];
}

return $config;
