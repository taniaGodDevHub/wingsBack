<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\bootstrap5\Html;
use yii\helpers\Url;

?>
<div class="navbar-custom">
    <div class="topbar container-fluid">
        <div class="d-flex align-items-center gap-lg-2 gap-1">
            <div class="logo-topbar">
                <a href="<?= Url::home() ?>" class="logo-light">
                    <span class="logo-lg">
                        <img src="<?= Yii::getAlias('@web/img/logo.svg') ?>" alt="<?= Html::encode(Yii::$app->name) ?>" style="max-height: 70px;">
                    </span>
                    <span class="logo-sm">
                        <img src="<?= Yii::getAlias('@web/img/logo-sm.svg') ?>" alt="<?= Html::encode(Yii::$app->name) ?>" style="max-height: 70px;">
                    </span>
                </a>
            </div>

            <button class="button-toggle-menu" type="button" aria-label="<?= Yii::t('app', 'Toggle menu') ?>">
                <i class="ri-menu-5-line"></i>
            </button>
        </div>

        <ul class="topbar-menu d-flex align-items-center gap-3">
            <li class="d-none d-sm-inline-block">
                <div class="nav-link" id="light-dark-mode" role="button" tabindex="0" aria-label="<?= Yii::t('app', 'Switch theme') ?>">
                    <i class="ri-moon-line font-22"></i>
                </div>
            </li>

            <li class="dropdown">
                <a class="nav-link dropdown-toggle arrow-none nav-user px-2" data-bs-toggle="dropdown" href="#" role="button" aria-haspopup="false" aria-expanded="false">
                    <span class="d-lg-flex flex-column gap-1 d-none">
                        <h5 class="my-0">
                            <?php if (Yii::$app->user->isGuest): ?>
                                <?= Yii::t('app', 'Guest') ?>
                            <?php else: ?>
                                <?= Html::encode(Yii::$app->user->identity->username) ?>
                            <?php endif ?>
                        </h5>
                    </span>
                    <i class="ri-user-3-line font-22 d-lg-none"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated profile-dropdown">
                    <?php if (Yii::$app->user->isGuest): ?>
                        <?= Html::a(
                            '<i class="ri-login-box-line font-16 me-1"></i><span>' . Yii::t('app', 'Login') . '</span>',
                            ['/site/login'],
                            ['class' => 'dropdown-item'],
                        ) ?>
                    <?php else: ?>
                        <?= Html::beginForm(['/site/logout'], 'post', ['class' => 'd-inline']) ?>
                        <?= Html::submitButton(
                            '<i class="ri-logout-box-r-line font-16 me-1"></i><span>' . Yii::t('app', 'Logout') . '</span>',
                            ['class' => 'dropdown-item border-0 bg-transparent w-100 text-start'],
                        ) ?>
                        <?= Html::endForm() ?>
                    <?php endif ?>
                </div>
            </li>
        </ul>
    </div>
</div>
