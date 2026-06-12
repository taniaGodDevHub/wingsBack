<?php

/** @var yii\web\View $this */
/** @var app\models\AdminResetPasswordForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = Yii::t('app', 'New password');
?>
<div class="admin-auth-card">
    <h1 class="admin-auth-card__title"><?= Html::encode(Yii::t('app', 'New password')) ?></h1>
    <p class="admin-auth-card__notice">
        <?= Yii::t('app', 'Enter the code from the email and choose a new password.') ?>
    </p>

    <?php $form = ActiveForm::begin([
        'id' => 'admin-reset-password-form',
        'options' => ['class' => 'admin-auth-form'],
        'fieldConfig' => [
            'options' => ['class' => 'admin-auth-form__field'],
            'labelOptions' => ['class' => 'visually-hidden'],
            'inputOptions' => ['class' => 'admin-auth-form__input'],
            'errorOptions' => ['class' => 'admin-auth-form__error'],
        ],
    ]) ?>

    <?= $form->field($model, 'email')->textInput([
        'placeholder' => Yii::t('app', 'Email'),
        'autocomplete' => 'email',
        'type' => 'email',
        'readonly' => $model->email !== '',
    ]) ?>

    <?= $form->field($model, 'code')->textInput([
        'autofocus' => true,
        'placeholder' => Yii::t('app', 'Verification code'),
        'autocomplete' => 'one-time-code',
        'inputmode' => 'numeric',
        'maxlength' => 6,
    ]) ?>

    <?= $form->field($model, 'password')->passwordInput([
        'placeholder' => Yii::t('app', 'New password'),
        'autocomplete' => 'new-password',
    ]) ?>

    <?= $form->field($model, 'passwordRepeat')->passwordInput([
        'placeholder' => Yii::t('app', 'Repeat password'),
        'autocomplete' => 'new-password',
    ]) ?>

    <?= $form->field($model, 'recordId')->hiddenInput()->label(false) ?>

    <div class="admin-auth-form__actions">
        <?= Html::submitButton(Yii::t('app', 'Save password'), ['class' => 'admin-auth-form__submit']) ?>
    </div>

    <?php ActiveForm::end() ?>

    <p class="admin-auth-card__switch">
        <?= Html::a(Yii::t('app', 'Send code again'), ['request-password-reset']) ?>
        ·
        <?= Html::a(Yii::t('app', 'Back to sign in'), ['login']) ?>
    </p>
</div>
