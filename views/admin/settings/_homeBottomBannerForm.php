<?php

/** @var app\models\HomeBottomBanner $bottomBannerModel */
/** @var yii\bootstrap5\ActiveForm $form */

use yii\bootstrap5\Html;

$previewUrl = $bottomBannerModel->getImagePublicUrl();
?>
<p class="text-muted mb-4"><?= Yii::t('app', 'Shown at the bottom of the home page. One image, button text and link.') ?></p>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card admin-banner-form__preview-card h-100 border rounded-3">
            <div class="card-body d-flex flex-column">
                <h3 class="h6 text-muted mb-3"><?= Yii::t('app', 'Image preview') ?></h3>
                <div class="admin-banner-form__preview-wrap flex-grow-1" data-admin-image-preview>
                    <img data-preview-img
                         <?php if ($previewUrl !== null): ?>src="<?= Html::encode($previewUrl) ?>"<?php endif ?>
                         alt=""
                         class="admin-banner-form__preview-img<?= $previewUrl === null ? ' d-none' : '' ?>">
                    <div data-preview-placeholder
                         class="admin-banner-form__preview-placeholder<?= $previewUrl !== null ? ' d-none' : '' ?>">
                        <?= Yii::t('app', 'Select an image to see preview') ?>
                    </div>
                </div>
                <?= $form->field($bottomBannerModel, 'imageFile', ['options' => ['class' => 'mt-3 mb-0']])
                    ->fileInput([
                        'data-preview-input' => true,
                        'accept' => 'image/jpeg,image/png,image/webp,image/gif',
                    ])
                    ->label(Yii::t('app', 'Upload image')) ?>
                <p class="form-text mb-0"><?= Yii::t('app', 'JPEG, PNG, WebP or GIF, up to 5 MB.') ?></p>
            </div>
        </div>
    </div>

    <div class="col-lg-7 d-flex flex-column">
        <?= $form->field($bottomBannerModel, 'button_text')->textInput(['maxlength' => true]) ?>
        <?= $form->field($bottomBannerModel, 'button_url')->textInput([
            'maxlength' => true,
            'placeholder' => '/catalog',
        ]) ?>
        <div class="mt-auto pt-2">
            <?= Html::submitButton(Yii::t('app', 'Save bottom banner'), ['class' => 'btn btn-primary']) ?>
        </div>
    </div>
</div>
