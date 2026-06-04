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
<div class="product-image-carousel mb-3"
     id="<?= Html::encode($carouselId) ?>-wrap"
     data-carousel-id="<?= Html::encode($carouselId) ?>"
     data-product-id="<?= $model->isNewRecord ? '' : (int) $model->id ?>"
     data-allow-server-delete="<?= $allowDelete ? '1' : '0' ?>"
     data-ajax-delete="<?= $ajaxDelete ? '1' : '0' ?>"
     data-redirect-action="<?= Html::encode($redirectAction) ?>"
     data-server-images="<?= Html::encode(Json::encode($serverImages)) ?>"
     data-label-delete="<?= Html::encode(Yii::t('app', 'Delete')) ?>"
     data-label-confirm="<?= Html::encode(Yii::t('app', 'Delete this photo?')) ?>"
     data-label-empty="<?= Html::encode(Yii::t('app', 'No photos yet.')) ?>"
     data-label-prev="<?= Html::encode(Yii::t('app', 'Previous')) ?>"
     data-label-next="<?= Html::encode(Yii::t('app', 'Next')) ?>"
     data-label-photo="<?= Html::encode(Yii::t('app', 'Photo {n}')) ?>"
     data-label-error="<?= Html::encode(Yii::t('app', 'Something went wrong. Please try again.')) ?>"
     <?php if (!$model->isNewRecord): ?>
     data-delete-url-pattern="<?= Html::encode(Url::to(['/admin/product/delete-image', 'productId' => (int) $model->id, 'imageId' => '__IMAGE_ID__'])) ?>"
     <?php endif ?>>
    <h2 class="h5"><?= Yii::t('app', 'Product photos') ?></h2>
    <div id="<?= Html::encode($carouselId) ?>"
         class="carousel slide product-image-carousel__slider"
         data-bs-ride="false">
        <?php $slideCount = count($images); ?>
        <?php if ($slideCount > 1): ?>
            <div class="carousel-indicators">
                <?php foreach ($images as $index => $image): ?>
                    <button type="button"
                            data-bs-target="#<?= Html::encode($carouselId) ?>"
                            data-bs-slide-to="<?= (int) $index ?>"
                            class="<?= $index === 0 ? 'active' : '' ?>"
                            <?= $index === 0 ? 'aria-current="true"' : '' ?>
                            aria-label="<?= Html::encode(Yii::t('app', 'Photo {n}', ['n' => $index + 1])) ?>"></button>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="carousel-indicators"></div>
        <?php endif ?>
        <div class="carousel-inner">
            <?php if ($hasImages): ?>
                <?php foreach ($images as $index => $image): ?>
                    <div class="carousel-item<?= $index === 0 ? ' active' : '' ?>"
                         data-server-image="1"
                         data-image-id="<?= (int) $image->id ?>">
                        <div class="product-image-carousel__frame p-2">
                            <?php if ($allowDelete): ?>
                                <?= $this->render('_deleteImageButton', [
                                    'deleteUrl' => Url::to(['delete-image', 'productId' => $model->id, 'imageId' => $image->id]),
                                ]) ?>
                            <?php endif ?>
                            <img src="<?= Html::encode($image->publicUrl) ?>"
                                 class="product-image-carousel__img"
                                 alt="<?= Html::encode($model->name) ?>">
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="carousel-item active product-image-carousel__empty-item">
                    <div class="product-image-carousel__frame p-2 text-center">
                        <span class="text-muted"><?= Yii::t('app', 'No photos yet.') ?></span>
                    </div>
                </div>
            <?php endif ?>
        </div>
        <button class="carousel-control-prev<?= $slideCount > 1 ? '' : ' d-none' ?>" type="button" data-bs-target="#<?= Html::encode($carouselId) ?>" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden"><?= Yii::t('app', 'Previous') ?></span>
        </button>
        <button class="carousel-control-next<?= $slideCount > 1 ? '' : ' d-none' ?>" type="button" data-bs-target="#<?= Html::encode($carouselId) ?>" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden"><?= Yii::t('app', 'Next') ?></span>
        </button>
    </div>
</div>
