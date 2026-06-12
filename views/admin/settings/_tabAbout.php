<?php

/** @var app\models\HomeAbout $aboutModel */

use yii\bootstrap5\ActiveForm;
?>
<?php $form = ActiveForm::begin([
    'options' => [
        'enctype' => 'multipart/form-data',
        'class' => 'admin-home-about-form',
    ],
    'action' => ['banners', 'tab' => 'about'],
    'method' => 'post',
]); ?>
<input type="hidden" name="settings-section" value="about">
<?= $this->render('_homeAboutForm', ['aboutModel' => $aboutModel, 'form' => $form]) ?>
<?php ActiveForm::end() ?>
