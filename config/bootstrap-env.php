<?php

declare(strict_types=1);

$localEnv = __DIR__ . '/env-local.php';
if (is_file($localEnv)) {
    require $localEnv;
}

defined('YII_DEBUG') or define(
    'YII_DEBUG',
    filter_var(getenv('YII_DEBUG') !== false ? getenv('YII_DEBUG') : '0', FILTER_VALIDATE_BOOLEAN),
);

defined('YII_ENV') or define(
    'YII_ENV',
    getenv('YII_ENV') !== false ? getenv('YII_ENV') : 'prod',
);
