<?php

/** @var yii\web\View $this */
/** @var app\models\AdminRequestPasswordResetForm $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = Yii::t('app', 'Password recovery');
?>
<div class="admin-auth-card">
    <h1 class="admin-auth-card__title"><?= Html::encode(Yii::t('app', 'Password recovery')) ?></h1>
    <p class="admin-auth-card__notice">
        <?= Yii::t('app', 'Enter the email linked to your account. We will send a verification code.') ?>
    </p>

    <?php $form = ActiveForm::begin([
        'id' => 'admin-request-password-reset-form',
        'options' => ['class' => 'admin-auth-form'],
        'fieldConfig' => [
            'options' => ['class' => 'admin-auth-form__field'],
            'labelOptions' => ['class' => 'visually-hidden'],
            'inputOptions' => ['class' => 'admin-auth-form__input'],
            'errorOptions' => ['class' => 'admin-auth-form__error'],
        ],
    ]) ?>

    <?= $form->field($model, 'email')->textInput([
        'autofocus' => true,
        'placeholder' => Yii::t('app', 'Email'),
        'autocomplete' => 'email',
        'type' => 'email',
    ]) ?>

    <div class="admin-auth-form__actions">
        <?= Html::submitButton(Yii::t('app', 'Send code'), ['class' => 'admin-auth-form__submit']) ?>
    </div>

    <?php ActiveForm::end() ?>

    <p class="admin-auth-card__switch">
        <?= Html::a(Yii::t('app', 'Back to sign in'), ['login']) ?>
    </p>
</div>
