<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';

$i18n = require __DIR__ . '/i18n.php';

$config = [
    'id' => 'basic',
    'name' => 'Wings',
    'basePath' => dirname(__DIR__),
    'language' => $i18n['language'],
    'sourceLanguage' => $i18n['sourceLanguage'],
    'bootstrap' => ['log', \app\components\api\CorsPreflightBootstrap::class],
    'container' => [
        'singletons' => [
            \yii\mail\MailerInterface::class => require __DIR__ . '/mailer.php',
        ],
    ],
    'aliases' => require __DIR__ . '/aliases.php',
    'components' => [
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            'cookieValidationKey' => 'sFd-2vlPBmxh1O-vH0GqWFMD35IcRi8W',
            'parsers' => [
                'application/json' => \yii\web\JsonParser::class,
            ],
            'enableCsrfValidation' => false,
        ],
        'cache' => [
            'class' => \yii\caching\FileCache::class,
        ],
        'user' => [
            'identityClass' => \app\models\User::class,
            'enableAutoLogin' => true,
            'loginUrl' => ['admin/auth/login'],
        ],
        'errorHandler' => [
            'class' => \app\components\api\ApiErrorHandler::class,
            'errorAction' => 'site/error',
        ],
        'mailer' => \yii\mail\MailerInterface::class,
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => $db,
        'authManager' => require __DIR__ . '/authManager.php',
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'GET swagger/json-schema' => 'swagger/json-schema',
                'GET swagger' => 'swagger/index',
                'POST api/auth/check_user' => 'api/auth/check-user',
                'POST api/auth/phone_registration_confirmed' => 'api/auth/phone-registration-confirmed',
                'POST api/auth/phone_login_get_code' => 'api/auth/phone-login-get-code',
                'POST api/auth/email_registration_confirmed' => 'api/auth/email-registration-confirmed',
                'POST api/auth/email_login_get_code' => 'api/auth/email-login-get-code',
                'POST api/auth/verify_phone_registration' => 'api/auth/verify-phone-registration',
                'POST api/auth/verify_email_registration' => 'api/auth/verify-email-registration',
                'POST api/auth/login_phone_with_code' => 'api/auth/login-phone-with-code',
                'POST api/auth/login_email_with_code' => 'api/auth/login-email-with-code',
                'POST api/auth/refresh_token' => 'api/auth/refresh-token',
                'GET api/auth/my' => 'api/auth/my',
                'GET api/auth/profile' => 'api/auth/profile',
                'PATCH api/auth/profile' => 'api/auth/profile',
                'POST api/auth/send_email_confirmation' => 'api/auth/send-email-confirmation',
                'POST api/auth/verify_email_confirmation' => 'api/auth/verify-email-confirmation',
                'GET api/auth/my_addresses' => 'api/auth/my-addresses',
                'POST api/auth/add_address' => 'api/auth/add-address',
                'PATCH api/update_address/<address_id:\\d+>' => 'api/auth/update-address',
                'DELETE api/auth/delete_address/<address_id:\\d+>' => 'api/auth/delete-address',
                'POST api/orders/create' => 'api/orders/create',
                'GET api/orders/active' => 'api/orders/active',
                'GET api/orders/purchases' => 'api/orders/purchases',
                'GET api/orders/deliveries' => 'api/orders/deliveries',
                'GET api/orders/<order_id:\\d+>' => 'api/orders/view',
                'POST api/orders/<order_id:\\d+>/confirm' => 'api/orders/confirm',
                'GET api/orders/<order_id:\\d+>/delivery-options' => 'api/orders/delivery-options',
                'POST api/dadata/suggest/city' => 'api/delivery/suggest-city',
                'POST api/dadata/suggest/address' => 'api/delivery/dadata-suggest-address',
                'POST api/delivery/suggest-city' => 'api/delivery/suggest-city',
                'POST api/delivery/suggest-address' => 'api/delivery/suggest-address',
                'GET api/delivery/pvz' => 'api/delivery/pvz',
                'POST api/delivery/calculate-delivery' => 'api/delivery/calculate-delivery',
                'GET result/done' => 'site/payment-done',
                'GET result/error' => 'site/payment-error',
                'GET api/catalog/home' => 'api/catalog/home',
                'GET api/catalog/showcase' => 'api/catalog/showcase',
                'GET api/catalog/search/universal' => 'api/catalog/universal',
                'GET api/catalog/categories/simple-tree' => 'api/catalog/simple-tree',
                'GET api/catalog/product/<slug>' => 'api/catalog/product',
                'GET api/catalog/search' => 'api/catalog/search',
                'GET api/catalog/search/category/<slug>' => 'api/catalog/search-category',
                'GET api/news' => 'api/news/index',
                'GET api/news/<slug>' => 'api/news/view',
                'GET api/blago' => 'api/blago/index',
                'GET api/blago/order/<code:[A-Za-z0-9_-]+>' => 'api/blago/order',
                'GET api/contacts' => 'api/contacts/index',
                'POST api/favorites/add' => 'api/favorites/add',
                'POST api/favorites/remove' => 'api/favorites/remove',
                'POST api/favorites/check' => 'api/favorites/check',
                'GET api/favorites/list' => 'api/favorites/list',
                'POST api/favorites/sync' => 'api/favorites/sync',
                'POST api/cart-client/add' => 'api/cart-client/add',
                'POST api/cart-client/update' => 'api/cart-client/update',
                'POST api/cart-client/remove' => 'api/cart-client/remove',
                'GET api/cart-client/list' => 'api/cart-client/list',
                'POST api/cart-client/count' => 'api/cart-client/count',
                'POST api/cart-client/sync' => 'api/cart-client/sync',
            ],
        ],
    ],
    'params' => $params,
    'controllerMap' => [
        'api/auth' => \app\controllers\api\AuthController::class,
        'api/catalog' => \app\controllers\api\CatalogController::class,
        'api/news' => \app\controllers\api\NewsController::class,
        'api/blago' => \app\controllers\api\BlagoController::class,
        'api/contacts' => \app\controllers\api\ContactsController::class,
        'api/cart-client' => \app\controllers\api\CartClientController::class,
        'api/favorites' => \app\controllers\api\FavoritesController::class,
        'api/orders' => \app\controllers\api\OrdersController::class,
        'api/dadata' => \app\controllers\api\DaDataController::class,
        'api/delivery' => \app\controllers\api\DeliveryController::class,
        'admin/auth' => \app\controllers\admin\AuthController::class,
        'admin/profile' => \app\controllers\admin\ProfileController::class,
        'admin/user' => \app\controllers\admin\UserController::class,
        'admin/rbac' => \app\controllers\admin\RbacController::class,
        'admin/settings' => \app\controllers\admin\SettingsController::class,
        'admin/product' => \app\controllers\admin\ProductController::class,
        'admin/news' => \app\controllers\admin\NewsController::class,
        'admin/contacts' => \app\controllers\admin\ContactsController::class,
    ],
];

$config['components'] = array_merge(
    $config['components'],
    require __DIR__ . '/components.php',
    $i18n['components'],
);

if (YII_ENV_DEV) {
    if (class_exists(\yii\debug\Module::class)) {
        $config['bootstrap'][] = 'debug';
        $config['modules']['debug'] = [
            'class' => \yii\debug\Module::class,
            //'allowedIPs' => ['127.0.0.1', '::1'],
        ];
    }

    if (class_exists(\yii\gii\Module::class)) {
        $config['bootstrap'][] = 'gii';
        $config['modules']['gii'] = [
            'class' => \yii\gii\Module::class,
            'generators' => [],
            //'allowedIPs' => ['127.0.0.1', '::1'],
        ];
    }
}

$localWebConfig = __DIR__ . '/web-local.php';
if (is_file($localWebConfig)) {
    $config = \yii\helpers\ArrayHelper::merge($config, require $localWebConfig);
}

return $config;
