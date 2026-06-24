<?php

/** @var yii\web\View $this */
/** @var app\models\Product $model */

use app\models\Product;
use yii\helpers\Html;
use yii\helpers\Url;

if ($model->isNewRecord || $model->product_group_id === null || (int) $model->product_group_id <= 0) {
    return;
}

$variants = Product::find()
    ->where(['product_group_id' => (int) $model->product_group_id])
    ->with(['featureValues.feature'])
    ->orderBy(['id' => SORT_ASC])
    ->all();

if ($variants === []) {
    return;
}

$currentId = (int) $model->id;

$this->registerCss(<<<'CSS'
.product-group-colors {
    margin-top: 1.5rem;
}

.product-group-colors__label {
    display: block;
    font-weight: 700;
    letter-spacing: 0.04em;
    margin-bottom: 0.75rem;
    text-transform: uppercase;
}

.product-group-colors__list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.product-group-colors__swatch {
    border: 1px solid #d9d9d9;
    border-radius: 0.25rem;
    display: block;
    flex-shrink: 0;
    height: 1.75rem;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
    width: 2.5rem;
}

a.product-group-colors__swatch:hover {
    border-color: #9a9a9a;
    box-shadow: 0 0 0 1px #9a9a9a;
}

.product-group-colors__swatch--current {
    box-shadow: 0 0 0 2px #1a1a1a;
    cursor: default;
}
CSS);

?>
<div class="product-group-colors">
    <span class="product-group-colors__label"><?= Yii::t('app', 'Colors in group') ?></span>
    <div class="product-group-colors__list">
        <?php foreach ($variants as $variant): ?>
            <?php
            $color = $variant->getColorData();
            $hex = $color !== null && ($color['hex'] ?? '') !== '' ? (string) $color['hex'] : '#e8e8e8';
            $title = $color !== null ? (string) ($color['name'] ?? $variant->name) : (string) $variant->name;
            $isCurrent = (int) $variant->id === $currentId;
            $swatchOptions = [
                'class' => 'product-group-colors__swatch' . ($isCurrent ? ' product-group-colors__swatch--current' : ''),
                'style' => 'background-color: ' . $hex . ';',
                'title' => $title,
                'aria-label' => $title,
            ];
            ?>
            <?php if ($isCurrent): ?>
                <span <?= Html::renderTagAttributes($swatchOptions) ?>></span>
            <?php else: ?>
                <?= Html::a('', ['update', 'id' => (int) $variant->id], $swatchOptions) ?>
            <?php endif ?>
        <?php endforeach ?>
    </div>
</div>
