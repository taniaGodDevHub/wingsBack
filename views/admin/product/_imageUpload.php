<?php

/** @var yii\web\View $this */
/** @var app\models\Product $model */
/** @var string $mode embedded|ajax */

use yii\helpers\Html;
use yii\helpers\Url;

$mode = $mode ?? 'embedded';
?>
<div class="product-image-upload card mb-4">
    <div class="card-body">
        <h2 class="h5 card-title"><?= Yii::t('app', 'Upload photos') ?></h2>
        <?php if ($mode === 'ajax'): ?>
            <div id="product-images-upload-form"
                 data-action="<?= Html::encode(Url::to(['upload-images', 'id' => $model->id])) ?>"
                 data-csrf-param="<?= Html::encode(Yii::$app->request->csrfParam) ?>"
                 data-csrf-token="<?= Html::encode(Yii::$app->request->csrfToken) ?>"
                 data-empty-message="<?= Html::encode(Yii::t('app', 'Select at least one file.')) ?>">
                <div class="mb-3">
                    <input type="file"
                           name="productImages[]"
                           id="product-images-input"
                           class="form-control product-images-file-input"
                           accept="image/jpeg,image/png,image/webp,image/gif"
                           multiple>
                    <div class="form-text"><?= Yii::t('app', 'JPEG, PNG, WebP or GIF, up to 5 MB each. You can select several files at once.') ?></div>
                    <div class="form-text"><?= Yii::t('app', 'After uploading images, drag photos to change display order. The first photo is shown in the catalog.') ?></div>
                </div>
                <div id="product-images-upload-errors" class="alert alert-danger d-none" role="alert"></div>
                <button type="button" class="btn btn-outline-primary" id="product-images-upload-btn">
                    <?= Yii::t('app', 'Upload selected') ?>
                </button>
            </div>
        <?php else: ?>
            <p class="text-muted"><?= Yii::t('app', 'Photos will be uploaded when you save the product. After saving you can add, view and delete photos here.') ?></p>
            <div class="mb-0">
                <label class="form-label" for="product-images-input-form"><?= Yii::t('app', 'Select files') ?></label>
                <input type="file"
                       name="productImages[]"
                       id="product-images-input-form"
                       class="form-control product-images-file-input"
                       accept="image/jpeg,image/png,image/webp,image/gif"
                       multiple
                       form="product-form">
                <div class="form-text"><?= Yii::t('app', 'JPEG, PNG, WebP or GIF, up to 5 MB each. You can select several files at once.') ?></div>
                <div class="form-text"><?= Yii::t('app', 'After uploading images, drag photos to change display order. The first photo is shown in the catalog.') ?></div>
            </div>
        <?php endif ?>
    </div>
</div>
