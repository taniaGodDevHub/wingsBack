<?php

/** @var yii\web\View $this */
/** @var app\models\HomeBanner $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\web\View;


$this->registerJsFile('@web/js/admin-image-preview.js', ['depends' => [\yii\web\JqueryAsset::class], 'position' => View::POS_END]);

$previewUrl = $model->getImagePublicUrl();
$hasLegacyRemoteImage = $model->image_url !== '' && !$model->hasLocalImage();
?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>

<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data', 'class' => 'admin-banner-form']]); ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card admin-banner-form__preview-card h-100">
            <div class="card-body d-flex flex-column">
                <h2 class="h6 text-muted mb-3"><?= Yii::t('app', 'Image preview') ?></h2>
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
                <?= $form->field($model, 'imageFile', ['options' => ['class' => 'mt-3 mb-0']])
                    ->fileInput([
                        'data-preview-input' => true,
                        'accept' => 'image/jpeg,image/png,image/webp,image/gif',
                    ])
                    ->label(Yii::t('app', 'Upload image')) ?>
                <p class="form-text mb-0"><?= Yii::t('app', 'JPEG, PNG, WebP or GIF, up to 5 MB.') ?></p>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
                <?= $form->field($model, 'text')->textarea(['rows' => 4]) ?>
                <?= $form->field($model, 'button_text')->textInput(['maxlength' => true]) ?>
                <?= $form->field($model, 'button_url')->textInput(['maxlength' => true, 'placeholder' => '/catalog']) ?>
                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($model, 'sort_order')->input('number', ['min' => 0]) ?>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <?= $form->field($model, 'is_active')->checkbox() ?>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
                    <?= Html::a(Yii::t('app', 'Cancel'), ['/admin/settings/banners', 'tab' => 'main'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php ActiveForm::end() ?>
