<?php

/** @var yii\web\View $this */
/** @var app\models\AdminSignupForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = Yii::t('app', 'Register');
?>
<div class="admin-auth-card">
    <h1 class="admin-auth-card__title"><?= Html::encode(Yii::t('app', 'Register')) ?></h1>

    <?php $form = ActiveForm::begin([
        'id' => 'admin-register-form',
        'options' => ['class' => 'admin-auth-form'],
        'fieldConfig' => [
            'options' => ['class' => 'admin-auth-form__field'],
            'labelOptions' => ['class' => 'visually-hidden'],
            'inputOptions' => ['class' => 'admin-auth-form__input'],
            'errorOptions' => ['class' => 'admin-auth-form__error'],
        ],
    ]) ?>

    <?= $form->field($model, 'login')->textInput([
        'autofocus' => true,
        'placeholder' => Yii::t('app', 'Login or email'),
        'autocomplete' => 'username',
    ]) ?>

    <?= $form->field($model, 'password')->passwordInput([
        'placeholder' => Yii::t('app', 'Password'),
        'autocomplete' => 'new-password',
    ]) ?>

    <?= $form->field($model, 'passwordRepeat')->passwordInput([
        'placeholder' => Yii::t('app', 'Repeat password'),
        'autocomplete' => 'new-password',
    ]) ?>

    <div class="admin-auth-form__actions">
        <?= Html::submitButton(Yii::t('app', 'Register'), ['class' => 'admin-auth-form__submit']) ?>
    </div>

    <?= $form->field($model, 'agreeToTerms', [
        'options' => ['class' => 'admin-auth-form__terms'],
        'template' => "{input}\n{label}\n{error}",
    ])->checkbox([
        'class' => 'admin-auth-form__terms-input',
        'label' => false,
    ], false)->label(Yii::t('app', 'I agree to the marketplace terms of use and return policy'), [
        'class' => 'admin-auth-form__terms-label',
    ]) ?>

    <?php ActiveForm::end() ?>

    <p class="admin-auth-card__switch">
        <?= Yii::t('app', 'Already have an account?') ?>
        <?= Html::a(Yii::t('app', 'Sign in'), ['login']) ?>
    </p>
</div>
