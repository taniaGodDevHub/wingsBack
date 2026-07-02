<?php

/** @var yii\web\View $this */
/** @var app\models\News $model */

use app\services\NewsImageUploadService;
use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
use yii\web\View;

$this->registerJsFile('@web/js/helpers/slugHelper.js?v=3', ['depends' => [\yii\web\JqueryAsset::class], 'position' => View::POS_END]);
$this->registerJsFile('@web/js/admin-slug.js?v=2', ['depends' => [\yii\web\JqueryAsset::class], 'position' => View::POS_END]);
$this->registerJsFile('@web/js/admin-image-preview.js', ['depends' => [\yii\web\JqueryAsset::class], 'position' => View::POS_END]);

$previewUrl = $model->getImagePublicUrl();
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
                        <?= Yii::t('app', 'Select an image to see preview') ?>
                    </div>
                </div>
                <?= $form->field($model, 'imageFile', ['options' => ['class' => 'mt-3 mb-0']])
                    ->fileInput([
                        'data-preview-input' => true,
                        'accept' => 'image/jpeg,image/png,image/webp,image/gif',
                    ])
                    ->label(Yii::t('app', 'Upload image')) ?>
                <p class="form-text mb-0"><?= Yii::t('app', 'JPEG, PNG, WebP or GIF, up to {max} MB.', ['max' => NewsImageUploadService::MAX_MEGABYTES]) ?></p>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-body">
                <div data-admin-slug class="row">
                    <div class="col-md-6">
                        <?= $form->field($model, 'title')->textInput(['maxlength' => true]) ?>
                    </div>
                    <div class="col-md-6">
                        <?= $form->field($model, 'slug')->textInput(['maxlength' => true]) ?>
                    </div>
                </div>
                <?= $form->field($model, 'subtitle')->textInput(['maxlength' => true]) ?>
                <?= $form->field($model, 'text')->textarea(['rows' => 10]) ?>
                <div class="row">
                    <div class="col-md-6">
                        <?= $form->field($model, 'createdAtInput')->input('datetime-local') ?>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <?= $form->field($model, 'is_published')->checkbox() ?>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
                    <?= Html::a(Yii::t('app', 'Cancel'), ['index'], ['class' => 'btn btn-outline-secondary']) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php ActiveForm::end() ?>
