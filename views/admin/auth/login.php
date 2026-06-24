<?php

/** @var yii\web\View $this */
/** @var app\models\AdminLoginForm $model */
/** @var bool $alreadySignedIn */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = Yii::t('app', 'Sign in');
$alreadySignedIn = $alreadySignedIn ?? false;
?>
<div class="admin-auth-card">
    <h1 class="admin-auth-card__title"><?= Html::encode(Yii::t('app', 'Sign in')) ?></h1>

    <?php if ($alreadySignedIn): ?>
        <p class="admin-auth-card__notice">
            <?= Yii::t('app', 'You are signed in but do not have admin access yet.') ?>
        </p>
        <?= Html::beginForm(['/admin/auth/logout'], 'post', ['class' => 'admin-auth-form__actions']) ?>
        <?= Html::submitButton(Yii::t('app', 'Logout'), ['class' => 'admin-auth-form__submit']) ?>
        <?= Html::endForm() ?>
    <?php else: ?>

    <?php $form = ActiveForm::begin([
        'id' => 'admin-login-form',
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
        'autocomplete' => 'current-password',
    ]) ?>

    <p class="admin-auth-form__forgot">
        <?= Html::a(Yii::t('app', 'Forgot password?'), ['request-password-reset']) ?>
    </p>

    <div class="admin-auth-form__actions">
        <?= Html::submitButton(Yii::t('app', 'Sign in'), ['class' => 'admin-auth-form__submit']) ?>
    </div>

    <?php ActiveForm::end() ?>

    <p class="admin-auth-card__switch text-muted small">
        <?= Yii::t('app', 'Admin sign-in hint') ?>
    </p>

    <p class="admin-auth-card__switch">
        <?= Yii::t('app', 'No account yet?') ?>
        <?= Html::a(Yii::t('app', 'Register'), ['register']) ?>
    </p>

    <?php endif ?>
</div>
