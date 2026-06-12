<?php

/** @var yii\web\View $this */
/** @var app\models\AdminProfileForm $model */
/** @var app\models\User $user */
/** @var string[] $roles */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\helpers\Url;

$this->title = Yii::t('app', 'Edit profile');
$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Profile'), 'url' => ['edit']];
$this->params['breadcrumbs'][] = $this->title;

$profile = $user->profile;
$displayName = trim(($profile?->i ?? $profile?->name ?? '') . ' ' . ($profile?->f ?? $profile?->surname ?? ''));
if ($displayName === '') {
    $displayName = $user->username;
}

$fieldTemplate = "{label}\n<div class=\"admin-profile-form__control\">{input}{error}</div>";
$labelOptions = ['class' => 'admin-profile-form__label col-form-label'];
$fieldOptions = ['class' => 'admin-profile-form__row'];
?>
<div class="admin-profile-page">
    <h1 class="admin-profile-page__title h3 mb-4"><?= Html::encode($this->title) ?></h1>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card admin-profile-card">
                <div class="card-body">
                    <h2 class="admin-profile-card__heading"><?= Yii::t('app', 'Personal data') ?></h2>

                    <?php $form = ActiveForm::begin([
                        'id' => 'admin-profile-form',
                        'options' => ['class' => 'admin-profile-form'],
                        'fieldConfig' => [
                            'template' => $fieldTemplate,
                            'labelOptions' => $labelOptions,
                            'options' => $fieldOptions,
                            'inputOptions' => ['class' => 'form-control'],
                            'errorOptions' => ['class' => 'invalid-feedback d-block'],
                        ],
                    ]) ?>

                    <?= $form->field($model, 'username')->textInput(['readonly' => true]) ?>
                    <?= $form->field($model, 'email')->textInput(['type' => 'email']) ?>
                    <?= $form->field($model, 'firstName')->textInput() ?>
                    <?= $form->field($model, 'lastName')->textInput() ?>
                    <?= $form->field($model, 'phoneNumber')->textInput() ?>

                    <div class="admin-profile-form__actions">
                        <?= Html::submitButton(
                            '<i class="ri-save-line me-1"></i>' . Yii::t('app', 'Save changes'),
                            ['class' => 'btn btn-primary'],
                        ) ?>
                        <?= Html::a(
                            '<i class="ri-arrow-left-line me-1"></i>' . Yii::t('app', 'Back'),
                            Yii::$app->request->referrer ?: Url::home(),
                            ['class' => 'btn btn-secondary'],
                        ) ?>
                    </div>

                    <?php ActiveForm::end() ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card admin-profile-card admin-profile-summary">
                <div class="card-body text-center">
                    <h2 class="admin-profile-card__heading text-start"><?= Yii::t('app', 'Profile information') ?></h2>

                    <div class="admin-profile-summary__avatar" aria-hidden="true">
                        <i class="ri-user-3-line"></i>
                    </div>

                    <h3 class="admin-profile-summary__name">
                        #<?= (int) $user->id ?> <?= Html::encode($displayName) ?>
                    </h3>

                    <ul class="admin-profile-summary__list list-unstyled text-start">
                        <li>
                            <i class="ri-mail-line"></i>
                            <span><?= Html::encode($profile?->email ?: Yii::t('app', 'Not specified')) ?></span>
                        </li>
                        <li>
                            <i class="ri-phone-line"></i>
                            <span><?= Html::encode($profile?->phone_number ?: Yii::t('app', 'Not specified')) ?></span>
                        </li>
                        <li>
                            <i class="ri-calendar-line"></i>
                            <span>
                                <?= Yii::t('app', 'Registration date') ?>:
                                <?= Yii::$app->formatter->asDate($user->created_at, 'php:d.m.Y') ?>
                            </span>
                        </li>
                        <li>
                            <i class="ri-shield-user-line"></i>
                            <span>
                                <?= Yii::t('app', 'Role') ?>:
                                <?php if ($roles === []): ?>
                                    <?= Yii::t('app', 'Not specified') ?>
                                <?php else: ?>
                                    <?php foreach ($roles as $role): ?>
                                        <span class="badge admin-profile-summary__role"><?= Html::encode($role) ?></span>
                                    <?php endforeach ?>
                                <?php endif ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
