<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$i18n = require __DIR__ . '/i18n.php';

$config = [
    'id' => 'basic-console',
    'basePath' => dirname(__DIR__),
    'language' => $i18n['language'],
    'sourceLanguage' => $i18n['sourceLanguage'],
    'bootstrap' => ['log'],
    'controllerNamespace' => 'app\commands',
    'aliases' => array_merge(
        require __DIR__ . '/aliases.php',
        [
            '@tests' => '@app/tests',
        ],
    ),
    'components' => [
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'mailer' => require __DIR__ . '/mailer.php',
        'log' => [
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'authManager' => require __DIR__ . '/authManager.php',
    ] + (require __DIR__ . '/components.php') + $i18n['components'],
    'params' => $params,
    /*
    'controllerMap' => [
        'fixture' => [ // Fixture generation command line.
            'class' => 'yii\faker\FixtureController',
        ],
    ],
    */
];

if (YII_ENV_DEV) {
    if (class_exists(\yii\debug\Module::class)) {
        $config['bootstrap'][] = 'debug';
        $config['modules']['debug'] = [
            'class' => \yii\debug\Module::class,
            // uncomment the following to add your IP if you are not connecting from localhost.
            //'allowedIPs' => ['127.0.0.1', '::1'],
        ];
    }

    if (class_exists(\yii\gii\Module::class)) {
        $config['bootstrap'][] = 'gii';
        $config['modules']['gii'] = [
            'class' => \yii\gii\Module::class,
        ];
    }
}

return $config;
