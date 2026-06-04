<?php

return [
    'language' => 'ru-RU',
    'sourceLanguage' => 'en-US',
    'components' => [
        'i18n' => [
            'translations' => [
                'yii*' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'basePath' => '@yii/messages',
                    'sourceLanguage' => 'en-US',
                ],
                'app*' => [
                    'class' => \yii\i18n\PhpMessageSource::class,
                    'basePath' => '@app/messages',
                    'sourceLanguage' => 'en-US',
                    'fileMap' => [
                        'app' => 'app.php',
                    ],
                ],
            ],
        ],
        'formatter' => [
            'class' => \yii\i18n\Formatter::class,
            'locale' => 'ru-RU',
            'defaultTimeZone' => 'Europe/Moscow',
            'dateFormat' => 'php:d.m.Y',
            'datetimeFormat' => 'php:d.m.Y H:i',
        ],
    ],
];
