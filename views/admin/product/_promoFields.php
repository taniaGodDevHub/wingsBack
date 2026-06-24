<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\Product $model */

$this->registerCss(<<<'CSS'
.product-promo-fields {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-bottom: 1rem;
}

.product-promo-field {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    flex: 1 1 16rem;
    min-width: 16rem;
    padding: 0.75rem 1rem;
}

.product-promo-field__row {
    align-items: center;
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem 1rem;
}

.product-promo-field__toggle {
    flex: 0 0 auto;
}

.product-promo-field__sort {
    flex: 1 1 6rem;
    min-width: 6rem;
}

.product-promo-field__toggle .form-check {
    margin-bottom: 0;
}

.product-promo-field__sort .mb-3 {
    margin-bottom: 0 !important;
}
CSS);
?>
<div class="product-promo-fields">
    <div class="product-promo-field">
        <div class="product-promo-field__row">
            <div class="product-promo-field__toggle">
                <?= $form->field($model, 'is_bestseller', ['options' => ['class' => 'mb-0']])->checkbox() ?>
            </div>
            <div class="product-promo-field__sort">
                <?= $form->field($model, 'bestseller_rank', ['options' => ['class' => 'mb-0']])->input('number', ['min' => 0]) ?>
            </div>
        </div>
    </div>
    <div class="product-promo-field">
        <div class="product-promo-field__row">
            <div class="product-promo-field__toggle">
                <?= $form->field($model, 'is_featured_home', ['options' => ['class' => 'mb-0']])->checkbox() ?>
            </div>
            <div class="product-promo-field__sort">
                <?= $form->field($model, 'featured_sort', ['options' => ['class' => 'mb-0']])->input('number', ['min' => 0]) ?>
            </div>
        </div>
    </div>
</div>
