<?php

/** @var yii\web\View $this */
/** @var app\models\Product $model */
/** @var string $carouselId */
/** @var bool $allowDelete */
/** @var string $redirectAction */
/** @var bool $ajaxDelete */

use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

$images = $model->images;
$allowDelete = $allowDelete ?? false;
$ajaxDelete = $ajaxDelete ?? false;
$redirectAction = $redirectAction ?? 'update';
$hasImages = $images !== [];
$carouselId = $carouselId ?? 'product-images-carousel';

$serverImages = [];
foreach ($images as $image) {
    $serverImages[] = [
        'id' => (int) $image->id,
        'url' => $image->publicUrl,
        'sortOrder' => (int) $image->sort_order,
    ];
}
?>
<div class="product-image-gallery mb-3"
     id="product-images-carousel-wrap"
     data-carousel-id="<?= Html::encode($carouselId) ?>"
     data-product-id="<?= $model->isNewRecord ? '' : (int) $model->id ?>"
     data-allow-server-delete="<?= $allowDelete ? '1' : '0' ?>"
     data-ajax-delete="<?= $ajaxDelete ? '1' : '0' ?>"
     data-allow-reorder="<?= (!$model->isNewRecord && $allowDelete) ? '1' : '0' ?>"
     data-redirect-action="<?= Html::encode($redirectAction) ?>"
     data-server-images="<?= Html::encode(Json::encode($serverImages)) ?>"
     data-label-delete="<?= Html::encode(Yii::t('app', 'Delete')) ?>"
     data-label-confirm="<?= Html::encode(Yii::t('app', 'Delete this photo?')) ?>"
     data-label-empty="<?= Html::encode(Yii::t('app', 'No photos yet.')) ?>"
     data-label-main="<?= Html::encode(Yii::t('app', 'Main photo')) ?>"
     data-label-drag="<?= Html::encode(Yii::t('app', 'Drag to reorder')) ?>"
     data-label-hint="<?= Html::encode(Yii::t('app', 'After uploading images, drag photos to change display order. The first photo is shown in the catalog.')) ?>"
     data-label-error="<?= Html::encode(Yii::t('app', 'Something went wrong. Please try again.')) ?>"
     data-label-order-saved="<?= Html::encode(Yii::t('app', 'Image order saved.')) ?>"
     <?php if (!$model->isNewRecord): ?>
     data-delete-url-pattern="<?= Html::encode(Url::to(['/admin/product/delete-image', 'productId' => (int) $model->id, 'imageId' => '__IMAGE_ID__'])) ?>"
     data-reorder-url="<?= Html::encode(Url::to(['/admin/product/reorder-images', 'id' => (int) $model->id])) ?>"
     <?php endif ?>>
    <h2 class="h5"><?= Yii::t('app', 'Product photos') ?></h2>
    <p class="text-muted small mb-3"><?= Yii::t('app', 'After uploading images, drag photos to change display order. The first photo is shown in the catalog.') ?></p>
    <div id="<?= Html::encode($carouselId) ?>" class="product-image-gallery__list<?= $hasImages ? '' : ' d-none' ?>">
        <?php foreach ($images as $index => $image): ?>
            <div class="product-image-gallery__item"
                 data-server-image="1"
                 data-image-id="<?= (int) $image->id ?>"
                 draggable="<?= (!$model->isNewRecord && $allowDelete) ? 'true' : 'false' ?>">
                <?php if ($index === 0): ?>
                    <span class="product-image-gallery__badge"><?= Yii::t('app', 'Main photo') ?></span>
                <?php endif ?>
                <span class="product-image-gallery__drag" title="<?= Html::encode(Yii::t('app', 'Drag to reorder')) ?>" aria-hidden="true">⋮⋮</span>
                <?php if ($allowDelete): ?>
                    <?= $this->render('_deleteImageButton', [
                        'deleteUrl' => Url::to(['/admin/product/delete-image', 'productId' => $model->id, 'imageId' => $image->id]),
                    ]) ?>
                <?php endif ?>
                <img src="<?= Html::encode($image->publicUrl) ?>"
                     class="product-image-gallery__img"
                     alt="<?= Html::encode($model->name) ?>">
            </div>
        <?php endforeach ?>
    </div>
    <div class="product-image-gallery__empty text-muted text-center py-4<?= $hasImages ? ' d-none' : '' ?>">
        <?= Yii::t('app', 'No photos yet.') ?>
    </div>
</div>
