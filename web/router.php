<?php

declare(strict_types=1);

/**
 * Router for PHP built-in web server.
 * Usage: php yii serve --router=@app/web/router.php
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
if ($uri !== '' && $uri !== '/' && is_file(__DIR__ . $uri)) {
    return false;
}

$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';

require __DIR__ . '/index.php';
