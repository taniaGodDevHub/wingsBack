<?php

declare(strict_types=1);

/**
 * Local web overrides. Copy to web-local.php (gitignored).
 *
 * For subdirectory install at http://projects/wingsBack/web/:
 * 1. cp config/web-local.example.php config/web-local.php
 * 2. cp web/.htaccess.local.example web/.htaccess
 */
return [
    'aliases' => [
        '@webuploads' => '/wingsBack/web/uploads/',
        '@httpwebuploads' => 'http://projects/wingsBack/web/uploads/',
        '@httpweb' => 'http://projects/wingsBack/web/',
        '@httpapp' => 'http://projects/wingsBack/',
    ],
    'components' => [
        'request' => [
            'baseUrl' => '/wingsBack/web',
            'scriptUrl' => '/wingsBack/web/index.php',
        ],
    ],
];
