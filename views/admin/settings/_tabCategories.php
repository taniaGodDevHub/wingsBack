<?php

/** @var array<string, app\models\HomeGenderBlock> $genderBlocks */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;
?>
<p class="text-muted mb-4"><?= Yii::t('app', 'Gender blocks on the home page. Upload one image for each block.') ?></p>

<?php $form = ActiveForm::begin([
    'options' => [
        'enctype' => 'multipart/form-data',
        'class' => 'admin-gender-blocks-form',
    ],
    'action' => ['/admin/settings/banners', 'tab' => 'categories'],
    'method' => 'post',
]); ?>
<input type="hidden" name="settings-section" value="categories">

<div class="row g-4">
    <?php foreach ($genderBlocks as $code => $block): ?>
        <?php $previewUrl = $block->getImagePublicUrl(); ?>
        <div class="col-lg-6">
            <div class="card admin-banner-form h-100">
                <div class="card-body">
                    <h3 class="h6 mb-3"><?= Html::encode($block->getDisplayName()) ?></h3>
                    <div class="admin-banner-form__preview-wrap mb-3" data-admin-image-preview>
                        <img data-preview-img
                             <?php if ($previewUrl !== null): ?>src="<?= Html::encode($previewUrl) ?>"<?php endif ?>
                             alt=""
                             class="admin-banner-form__preview-img<?= $previewUrl === null ? ' d-none' : '' ?>">
                        <div data-preview-placeholder
                             class="admin-banner-form__preview-placeholder<?= $previewUrl !== null ? ' d-none' : '' ?>">
                            <?= Yii::t('app', 'Select an image to see preview') ?>
                        </div>
                    </div>
                    <?= $form->field($block, 'imageFile', ['options' => ['class' => 'mb-2']])
                        ->fileInput([
                            'name' => "HomeGenderBlock[{$code}][imageFile]",
                            'data-preview-input' => true,
                            'accept' => 'image/jpeg,image/png,image/webp,image/gif',
                        ])
                        ->label(Yii::t('app', 'Upload image')) ?>
                    <p class="form-text mb-0"><?= Yii::t('app', 'JPEG, PNG, WebP or GIF, up to 5 MB.') ?></p>
                </div>
            </div>
        </div>
    <?php endforeach ?>
</div>

<div class="mt-4">
    <?= Html::submitButton(Yii::t('app', 'Save category blocks'), ['class' => 'btn btn-primary']) ?>
</div>

<?php ActiveForm::end() ?>
