<?php

/** @var app\models\HomeBlago $blagoModel */

use yii\bootstrap5\ActiveForm;
?>
<?php $form = ActiveForm::begin([
    'options' => [
        'enctype' => 'multipart/form-data',
        'class' => 'admin-home-blago-form',
    ],
    'action' => ['/admin/settings/banners', 'tab' => 'blago'],
    'method' => 'post',
]); ?>
<input type="hidden" name="settings-section" value="blago">
<?= $this->render('_homeBlagoForm', ['blagoModel' => $blagoModel, 'form' => $form]) ?>
<?php ActiveForm::end() ?>
