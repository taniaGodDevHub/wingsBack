<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\Product $model */
/** @var array<int|string, string> $productGroupOptions */

use yii\helpers\Html;

$this->registerCss(<<<'CSS'
.product-group-field {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
    padding: 0.75rem 1rem;
}

.product-group-field__title {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.product-group-field__help {
    color: var(--bs-secondary-color);
    font-size: 0.75rem;
    line-height: 1.35;
    margin-bottom: 0.5rem;
}

.product-group-field__row {
    align-items: flex-start;
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1rem;
}

.product-group-field__select,
.product-group-field__create {
    flex: 1 1 12rem;
    min-width: 12rem;
}

.product-group-field__select .mb-3,
.product-group-field__create .mb-3 {
    margin-bottom: 0 !important;
}
CSS);

?>
<div class="product-group-field">
    <div class="product-group-field__title"><?= Yii::t('app', 'Product group') ?></div>
    <p class="product-group-field__help mb-0"><?= Yii::t('app', 'Product group card help') ?></p>

    <div class="product-group-field__row mt-3">
        <div class="product-group-field__select">
            <?= $form->field($model, 'product_group_id', ['options' => ['class' => 'mb-0']])->dropDownList($productGroupOptions) ?>
        </div>
        <div class="product-group-field__create">
            <?= $form->field($model, 'newProductGroupName', ['options' => ['class' => 'mb-0']])->textInput([
                'placeholder' => Yii::t('app', 'Product group name'),
            ]) ?>
        </div>
    </div>
</div>
