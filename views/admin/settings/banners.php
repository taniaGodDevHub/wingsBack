<?php

/** @var yii\web\View $this */
/** @var string $tab */
/** @var app\models\HomeAbout $aboutModel */
/** @var app\models\HomeBottomBanner $bottomBannerModel */
/** @var array<string, app\models\HomeGenderBlock> $genderBlocks */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\helpers\Html;
use yii\web\View;

$this->registerJsFile('@web/js/admin-image-preview.js', ['depends' => [\yii\web\JqueryAsset::class], 'position' => View::POS_END]);
?>
<h1 class="h3 mb-2"><?= Html::encode($this->title) ?></h1>
<p class="text-muted mb-3"><?= Yii::t('app', 'Manage home page content: banners, about us block and gender categories.') ?></p>

<?= $this->render('_pageSettingsTabs', ['tab' => $tab]) ?>

<div class="admin-page-settings-tab-content">
    <?php if ($tab === 'main'): ?>
        <?= $this->render('_tabMain', ['dataProvider' => $dataProvider]) ?>
    <?php elseif ($tab === 'about'): ?>
        <?= $this->render('_tabAbout', ['aboutModel' => $aboutModel]) ?>
    <?php elseif ($tab === 'categories'): ?>
        <?= $this->render('_tabCategories', ['genderBlocks' => $genderBlocks]) ?>
    <?php else: ?>
        <?= $this->render('_tabBottomBanner', ['bottomBannerModel' => $bottomBannerModel]) ?>
    <?php endif ?>
</div>
