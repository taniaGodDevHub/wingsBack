<?php

/** @var string $tab */

use yii\helpers\Html;
use yii\helpers\Url;

$tabs = [
    'main' => Yii::t('app', 'Main'),
    'about' => Yii::t('app', 'About us'),
    'categories' => Yii::t('app', 'Categories'),
    'bottom' => Yii::t('app', 'Bottom banner'),
    'blago' => Yii::t('app', 'Blago'),
];
?>
<ul class="nav nav-tabs admin-page-settings-tabs mb-4" role="tablist">
    <?php foreach ($tabs as $key => $label): ?>
        <li class="nav-item" role="presentation">
            <?= Html::a(
                $label,
                Url::to(['/admin/settings/banners', 'tab' => $key]),
                [
                    'class' => 'nav-link' . ($tab === $key ? ' active' : ''),
                    'role' => 'tab',
                    'aria-selected' => $tab === $key ? 'true' : 'false',
                ],
            ) ?>
        </li>
    <?php endforeach ?>
</ul>
