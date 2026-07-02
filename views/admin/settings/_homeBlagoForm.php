<?php

/** @var app\models\HomeBlago $blagoModel */
/** @var yii\bootstrap5\ActiveForm $form */

use yii\bootstrap5\Html;

$previewUrl = $blagoModel->getImagePublicUrl();
$hasLegacyRemoteImage = $blagoModel->image_url !== '' && !$blagoModel->hasLocalImage();
?>
<p class="text-muted mb-4"><?= Yii::t('app', 'Shown on the home page. Charity collection block with title, dates, amount and image.') ?></p>

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
                <?= $form->field($blagoModel, 'imageFile', ['options' => ['class' => 'mt-3 mb-0']])
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
        <?= $form->field($blagoModel, 'title')->textInput(['maxlength' => true]) ?>
        <div class="row g-3">
            <div class="col-md-6">
                <?= $form->field($blagoModel, 'collectionStartInput')->input('date') ?>
            </div>
            <div class="col-md-6">
                <?= $form->field($blagoModel, 'collectionEndInput')->input('date') ?>
            </div>
        </div>
        <?= $form->field($blagoModel, 'amount')->input('number', [
            'min' => 0,
            'step' => '0.01',
        ]) ?>
        <div class="mt-auto pt-2">
            <?= Html::submitButton(Yii::t('app', 'Save blago block'), ['class' => 'btn btn-primary']) ?>
        </div>
    </div>
</div>
