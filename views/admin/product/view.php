<?php

/** @var yii\web\View $this */
/** @var app\models\Product $model */

use yii\helpers\Html;
use yii\web\View;

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Products'), 'url' => ['index']];
$this->registerJsFile('@web/js/admin-product-images.js?v=4', ['depends' => [\yii\bootstrap5\BootstrapPluginAsset::class], 'position' => View::POS_END]);
$this->params['breadcrumbs'][] = $this->title;
?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<div class="row">
    <div class="col-lg-7">
        <table class="table table-bordered w-auto">
            <tr><th>ID</th><td><?= (int) $model->id ?></td></tr>
            <tr><th><?= Yii::t('app', 'Product name') ?></th><td><?= Html::encode($model->name) ?></td></tr>
            <tr><th>Slug</th><td><?= Html::encode($model->slug) ?></td></tr>
            <tr><th><?= Yii::t('app', 'Price') ?></th><td><?= (float) $model->price ?> ₽</td></tr>
            <tr>
                <th><?= Yii::t('app', 'Blago') ?></th>
                <td><?= (float) $model->blago > 0 ? (float) $model->blago . ' ₽' : '—' ?></td>
            </tr>
            <tr><th><?= Yii::t('app', 'Brand') ?></th><td><?= Html::encode($model->brand ?? '—') ?></td></tr>
            <tr><th>SKU</th><td><?= Html::encode($model->product_code ?? '—') ?></td></tr>
        </table>
        <p class="mt-3">
            <?= Html::a(Yii::t('app', 'Edit'), ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
            <?= Html::a(Yii::t('app', 'Back to list'), ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </p>
    </div>
    <div class="col-lg-5">
        <?= $this->render('_imageCarousel', [
            'model' => $model,
            'carouselId' => 'product-images-carousel',
            'allowDelete' => true,
            'redirectAction' => 'view',
            'ajaxDelete' => true,
        ]) ?>
        <?= $this->render('_imageUpload', ['model' => $model, 'mode' => 'ajax']) ?>
    </div>
</div>
