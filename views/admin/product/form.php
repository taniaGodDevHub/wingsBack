<?php

/** @var yii\web\View $this */
/** @var app\models\Product $model */
/** @var array<int|string, string> $categoryOptions */
/** @var array<string, string> $genderOptions */
/** @var app\models\CatalogFeature[] $catalogFeatures */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\web\View;

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Products'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$this->registerJsFile('@web/js/admin-product-images.js', ['depends' => [\yii\bootstrap5\BootstrapPluginAsset::class], 'position' => View::POS_END]);

$canManageImages = !$model->isNewRecord;
$redirectAction = $model->isNewRecord ? 'create' : 'update';
?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data', 'id' => 'product-form']]); ?>
<div class="row">
    <div class="order-last order-lg-0">
        <div class="row">
            <div class="col-12 col-md-6">
                <div class="order-first order-lg-0 mb-4 mb-lg-0">
                    <?= $this->render('_imageCarousel', [
                        'model' => $model,
                        'carouselId' => 'product-images-carousel',
                        'allowDelete' => $canManageImages,
                        'redirectAction' => $redirectAction,
                        'ajaxDelete' => $canManageImages,
                    ]) ?>
                    <?php if ($canManageImages): ?>
                        <?= $this->render('_imageUpload', ['model' => $model, 'mode' => 'ajax']) ?>
                    <?php else: ?>
                        <?= $this->render('_imageUpload', ['model' => $model, 'mode' => 'embedded']) ?>
                    <?php endif ?>
                </div>
                <div class="row">
                    <div class="col-12 col-md-4">
                        <?= $form->field($model, 'is_available')->checkbox() ?>
                    </div>
                    <div class="col-12 col-md-4">
                        <?= $form->field($model, 'is_bestseller')->checkbox() ?>
                    </div>
                    <div class="col-12 col-md-4">
                        <?= $form->field($model, 'is_featured_home')->checkbox() ?>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6">
                <?= $form->field($model, 'name')->textInput() ?>
                <?= $form->field($model, 'slug')->textInput() ?>
                <div class="row">
                    <div class="col-12 col-md-6">
                            <?= $form->field($model, 'categoryId')->dropDownList($categoryOptions) ?>
                    </div>
                    <div class="col-12 col-md-6">
                            <?= $form->field($model, 'gender')->dropDownList($genderOptions) ?>
                    </div>     
                 </div>
                
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-3">
                        <?= $form->field($model, 'product_code')->textInput() ?>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <?= $form->field($model, 'price')->input('number', ['step' => '0.01']) ?>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <?= $form->field($model, 'old_price')->input('number', ['step' => '0.01']) ?>
                    </div>
                    <div class="col-12 col-md-6 col-lg-3">
                        <?= $form->field($model, 'blago')
                            ->input('number', ['step' => '0.01', 'min' => '0']) ?>
                    </div>
                </div>
                
                <?= $this->render('_featureFields', [
                    'form' => $form,
                    'model' => $model,
                    'catalogFeatures' => $catalogFeatures,
                ]) ?>
                <?= $form->field($model, 'featured_sort')->input('number') ?>
                <?= $form->field($model, 'bestseller_rank')->input('number') ?>
                
            </div>
        </div>
    </div>

</div>
<div class="mt-3">
    <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
    <?= Html::a(Yii::t('app', 'Cancel'), ['index'], ['class' => 'btn btn-outline-secondary']) ?>
</div>
<?php ActiveForm::end(); ?>
