<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use app\controllers\admin\BaseAdminController;
use yii\bootstrap5\Nav;
use yii\bootstrap5\NavBar;
use yii\helpers\Html;

$items = [];

if (BaseAdminController::canManageCatalog()) {
    $items[] = [
        'label' => Yii::t('app', 'Products'),
        'url' => ['/admin/product/index'],
    ];

    $items[] = [
        'label' => Yii::t('app', 'Page settings'),
        'url' => ['/admin/settings/banners'],
    ];

    $items[] = [
        'label' => Yii::t('app', 'Settings'),
        'items' => [
            ['label' => Yii::t('app', 'Categories'), 'url' => ['/admin/settings/categories']],
            ['label' => Yii::t('app', 'Colors'), 'url' => ['/admin/settings/colors']],
            ['label' => Yii::t('app', 'Attributes'), 'url' => ['/admin/settings/features']],
            ['label' => Yii::t('app', 'Attribute values'), 'url' => ['/admin/settings/feature-values']],
        ],
    ];
}

if (BaseAdminController::canManageUsers()) {
    $items[] = [
        'label' => Yii::t('app', 'Users'),
        'url' => ['/admin/user/index'],
    ];
}

$items[] = [
    'label' => Yii::t('app', 'Login'),
    'url' => ['/site/login'],
    'visible' => Yii::$app->user->isGuest,
];
$items[] = [
    'label' => Yii::t('app', 'Logout ({username})', [
        'username' => Html::encode(Yii::$app->user->identity?->username ?? ''),
    ]),
    'url' => ['/site/logout'],
    'linkOptions' => [
        'data-method' => 'post',
        'class' => 'nav-link logout',
    ],
    'visible' => !Yii::$app->user->isGuest,
];

?>
<header id="header">
    <?php NavBar::begin(
        [
            'brandLabel' => Yii::$app->name,
            'brandUrl' => Yii::$app->homeUrl,
            'options' => ['class' => 'navbar-expand-md navbar-dark bg-dark fixed-top'],
        ],
    ) ?>
    <?= Nav::widget(
        [
            'options' => ['class' => 'navbar-nav me-auto'],
            'encodeLabels' => false,
            'items' => $items,
        ],
    ) ?>
    <?= Html::button(
        '&#127769;',
        [
            'id' => 'theme-toggle',
            'class' => 'btn btn-link nav-link fs-5',
            'aria-label' => Yii::t('app', 'Switch to dark mode'),
        ],
    ) ?>
    <?php NavBar::end() ?>
</header>
