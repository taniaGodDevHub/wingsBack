<?php

/** @var yii\web\View $this */
/** @var app\models\Product $model */

use app\models\ProductSize;
use yii\helpers\Html;

$sizeOptions = array_values(array_unique(array_merge(
    ProductSize::getDistinctSizeValues(),
    $model->sizeValuesInStock,
)));
$inStock = array_flip($model->sizeValuesInStock);
$inputName = Html::getInputName($model, 'sizeValuesInStock[]');

$this->registerCss(<<<'CSS'
.product-size-field__label {
    display: block;
    font-weight: 700;
    letter-spacing: 0.04em;
    margin-bottom: 0.75rem;
    text-transform: uppercase;
}

.product-size-field__buttons {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.product-size-btn {
    align-items: center;
    background: #fff;
    border: 1px solid #d9d9d9;
    color: #9a9a9a;
    cursor: pointer;
    display: inline-flex;
    font-size: 0.95rem;
    font-weight: 600;
    height: 3rem;
    justify-content: center;
    min-width: 3rem;
    overflow: hidden;
    padding: 0 0.75rem;
    position: relative;
    transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    user-select: none;
}

.product-size-btn:not(.product-size-btn--in-stock)::after {
    background-color: #9a9a9a;
    content: '';
    height: 1px;
    left: 50%;
    pointer-events: none;
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%) rotate(-45deg);
    width: 140%;
}

.product-size-btn:hover {
    border-color: #bdbdbd;
}

.product-size-btn--in-stock {
    background: #1a1a1a;
    border-color: #1a1a1a;
    color: #fff;
}

.product-size-btn--in-stock:hover {
    background: #333;
    border-color: #333;
    color: #fff;
}
CSS);
?>
<div class="mb-3 product-size-field" id="product-size-field">
    <span class="product-size-field__label"><?= Html::encode($model->getAttributeLabel('sizeValuesInStock')) ?></span>
    <?php if ($sizeOptions === []): ?>
        <p class="text-muted mb-0"><?= Yii::t('app', 'No sizes in catalog yet.') ?></p>
    <?php else: ?>
        <div class="product-size-field__buttons" role="group" aria-label="<?= Html::encode($model->getAttributeLabel('sizeValuesInStock')) ?>">
            <?php foreach ($sizeOptions as $size): ?>
                <?php
                $isInStock = isset($inStock[$size]);
                $buttonClass = 'product-size-btn' . ($isInStock ? ' product-size-btn--in-stock' : '');
                ?>
                <button
                    type="button"
                    class="<?= $buttonClass ?>"
                    data-size="<?= Html::encode($size) ?>"
                    aria-pressed="<?= $isInStock ? 'true' : 'false' ?>"
                ><?= Html::encode($size) ?></button>
            <?php endforeach ?>
        </div>
        <div id="product-size-inputs" class="d-none">
            <?php foreach ($model->sizeValuesInStock as $size): ?>
                <?php if (in_array($size, $sizeOptions, true)): ?>
                    <input type="hidden" name="<?= Html::encode($inputName) ?>" value="<?= Html::encode($size) ?>">
                <?php endif ?>
            <?php endforeach ?>
        </div>
    <?php endif ?>
</div>
