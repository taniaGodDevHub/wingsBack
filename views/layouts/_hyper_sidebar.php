<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use app\controllers\admin\BaseAdminController;
use yii\helpers\Html;
use yii\helpers\Url;

$pageSettingsOpen = str_starts_with(Yii::$app->controller->route, 'admin/settings/banner');
$settingsOpen = str_starts_with(Yii::$app->controller->route, 'admin/settings/')
    && !$pageSettingsOpen;
$usersOpen = str_starts_with(Yii::$app->controller->route, 'admin/user/');
$contactsOpen = str_starts_with(Yii::$app->controller->route, 'admin/contacts/');

?>
<div class="leftside-menu menuitem-active">
    <a href="<?= Url::home() ?>" class="logo logo-light">
        <span class="logo-lg">
            <img src="<?= Yii::getAlias('@web/img/logo.png') ?>" alt="<?= Html::encode(Yii::$app->name) ?>" style="max-height: 70px;">
        </span>
        <span class="logo-sm">
            <img src="<?= Yii::getAlias('@web/img/logo.png') ?>" alt="<?= Html::encode(Yii::$app->name) ?>" style="max-height: 70px;">
        </span>
    </a>

    <div class="button-sm-hover" data-bs-toggle="tooltip" data-bs-placement="right" title="<?= Yii::t('app', 'Show Full Sidebar') ?>">
        <i class="ri-checkbox-blank-circle-line align-middle"></i>
    </div>

    <div class="button-close-fullsidebar">
        <i class="ri-close-fill align-middle"></i>
    </div>

    <div class="h-100" id="leftside-menu-container">
        <ul class="side-nav">
            <li class="side-nav-title"><?= Yii::t('app', 'Navigation') ?></li>

            <li class="side-nav-item">
                <?= Html::a(
                    '<i class="uil-home-alt"></i><span> ' . Yii::t('app', 'Home') . ' </span>',
                    ['/site/index'],
                    ['class' => 'side-nav-link'],
                ) ?>
            </li>

            <?php if (!Yii::$app->user->isGuest && BaseAdminController::canManageCatalog()): ?>
            <li class="side-nav-item">
                <?= Html::a(
                    '<i class="ri-shopping-bag-3-line"></i><span> ' . Yii::t('app', 'Products') . ' </span>',
                    ['/admin/product/index'],
                    ['class' => 'side-nav-link'],
                ) ?>
            </li>
            <li class="side-nav-item">
                <?= Html::a(
                    '<i class="ri-newspaper-line"></i><span> ' . Yii::t('app', 'News') . ' </span>',
                    ['/admin/news/index'],
                    ['class' => 'side-nav-link'],
                ) ?>
            </li>
            <li class="side-nav-item">
                <?= Html::a(
                    '<i class="ri-pages-line"></i><span> ' . Yii::t('app', 'Page settings') . ' </span>',
                    ['/admin/settings/banners'],
                    ['class' => 'side-nav-link' . ($pageSettingsOpen ? ' active' : '')],
                ) ?>
            </li>
            <li class="side-nav-item">
                <a class="side-nav-link" data-bs-toggle="collapse" href="#sidebarSettings" aria-expanded="<?= $settingsOpen ? 'true' : 'false' ?>">
                    <i class="ri-settings-3-line"></i>
                    <span> <?= Yii::t('app', 'Settings') ?> </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse<?= $settingsOpen ? ' show' : '' ?>" id="sidebarSettings">
                    <ul class="side-nav-second-level">
                        <li class="side-nav-item">
                            <?= Html::a(Yii::t('app', 'Categories'), ['/admin/settings/categories'], ['class' => 'side-nav-link']) ?>
                        </li>
                        <li class="side-nav-item">
                            <?= Html::a(Yii::t('app', 'Colors'), ['/admin/settings/colors'], ['class' => 'side-nav-link']) ?>
                        </li>
                        <li class="side-nav-item">
                            <?= Html::a(Yii::t('app', 'Sizes'), ['/admin/settings/sizes'], ['class' => 'side-nav-link']) ?>
                        </li>
                        <li class="side-nav-item">
                            <?= Html::a(Yii::t('app', 'Attributes'), ['/admin/settings/features'], ['class' => 'side-nav-link']) ?>
                        </li>
                        <li class="side-nav-item">
                            <?= Html::a(Yii::t('app', 'Attribute values'), ['/admin/settings/feature-values'], ['class' => 'side-nav-link']) ?>
                        </li>
                    </ul>
                </div>
            </li>
            <?php endif; ?>

            <?php if (!Yii::$app->user->isGuest && BaseAdminController::canManageUsers()): ?>
            <li class="side-nav-item">
                <?= Html::a(
                    '<i class="ri-group-line"></i><span> ' . Yii::t('app', 'Users') . ' </span>',
                    ['/admin/user/index'],
                    ['class' => 'side-nav-link' . ($usersOpen ? ' active' : '')],
                ) ?>
            </li>
            <?php endif; ?>

            <?php if (!Yii::$app->user->isGuest && BaseAdminController::canManageCatalog()): ?>
            <li class="side-nav-item">
                <?= Html::a(
                    '<i class="ri-contacts-line"></i><span> ' . Yii::t('app', 'Contact') . ' </span>',
                    ['/admin/contacts/index'],
                    ['class' => 'side-nav-link' . ($contactsOpen ? ' active' : '')],
                ) ?>
            </li>
            <?php endif; ?>

        </ul>
    </div>
</div>
