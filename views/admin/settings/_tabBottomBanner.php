<?php

/** @var app\models\HomeBottomBanner $bottomBannerModel */

use yii\bootstrap5\ActiveForm;
?>
<?php $form = ActiveForm::begin([
    'options' => [
        'enctype' => 'multipart/form-data',
        'class' => 'admin-bottom-banner-form',
    ],
    'action' => ['/admin/settings/banners', 'tab' => 'bottom'],
    'method' => 'post',
]); ?>
<input type="hidden" name="settings-section" value="bottom">
<?= $this->render('_homeBottomBannerForm', ['bottomBannerModel' => $bottomBannerModel, 'form' => $form]) ?>
<?php ActiveForm::end() ?>
