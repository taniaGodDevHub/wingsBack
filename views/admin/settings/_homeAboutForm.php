<?php

/** @var yii\web\View $this */
/** @var app\models\HomeAbout $aboutModel */
/** @var yii\bootstrap5\ActiveForm $form */

use yii\bootstrap5\Html;

$previewUrl = $aboutModel->getImagePublicUrl();
$hasLegacyRemoteImage = $aboutModel->image_url !== '' && !$aboutModel->hasLocalImage();
?>
<p class="text-muted mb-4"><?= Yii::t('app', 'Shown on the home page below the banners. One image and a title.') ?></p>

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
                        <?= $hasLegacyRemoteImage
                            ? Yii::t('app', 'Upload a new image to replace the demo banner.')
                            : Yii::t('app', 'Select an image to see preview') ?>
                    </div>
                </div>
                <?= $form->field($aboutModel, 'imageFile', ['options' => ['class' => 'mt-3 mb-0']])
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
        <?= $form->field($aboutModel, 'title')->textInput(['maxlength' => true]) ?>
        <div class="mt-auto pt-2">
            <?= Html::submitButton(Yii::t('app', 'Save about block'), ['class' => 'btn btn-primary']) ?>
        </div>
    </div>
</div>
