<?php

declare(strict_types=1);

namespace app\assets;

use yii\bootstrap5\BootstrapAsset;
use yii\web\AssetBundle;
use yii\web\YiiAsset;

/**
 * Hyper admin theme (Coderthemes) + application styles.
 */
class AppAsset extends AssetBundle
{
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css = [
        ['css/main.css', 'id' => 'main-css'],
        ['css/vendor.css', 'id' => 'vendor-css'],
        ['css/unicons.css', 'id' => 'unicons-css'],
        ['css/materialdesignicons.min.css', 'id' => 'material-icons-css'],
        ['css/remixicon.css', 'id' => 'remix-icon-css'],
        ['css/site.css', 'id' => 'site-css'],
    ];
    public $js = [
        'js/helpers/windowHelper.js',
        'js/hyper-config.js',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        'js/app.js',
        'js/hyper-theme.js',
    ];
    public $depends = [
        YiiAsset::class,
        BootstrapAsset::class,
    ];
}
