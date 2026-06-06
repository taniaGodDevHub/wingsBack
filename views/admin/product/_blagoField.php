<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\Product $model */

use app\models\Product;
use yii\helpers\Html;

$inputId = Html::getInputId($model, 'blago_input');
$unitId = Html::getInputId($model, 'blago_unit');
$priceId = Html::getInputId($model, 'price');
?>
<div class="mb-3 field-<?= Html::getInputId($model, 'blago_input') ?>">
    <div class="d-flex justify-content-between align-items-baseline gap-2">
        <?= Html::activeLabel($model, 'blago_input', ['class' => 'form-label mb-0']) ?>
        <small
            class="text-muted d-none"
            id="product-blago-preview"
            data-price-field="<?= $priceId ?>"
            style="font-size: 0.75rem; white-space: nowrap;"
        ></small>
    </div>
    <div class="input-group">
        <?= Html::activeInput('number', $model, 'blago_input', [
            'class' => 'form-control',
            'step' => '0.01',
            'min' => '0',
            'id' => $inputId,
        ]) ?>
        <?= Html::activeDropDownList($model, 'blago_unit', Product::getBlagoUnitOptions(), [
            'class' => 'form-select',
            'id' => $unitId,
            'style' => 'max-width: 5.5rem',
        ]) ?>
    </div>
    <?= Html::error($model, 'blago_input', ['class' => 'invalid-feedback d-block']) ?>
    <?= Html::error($model, 'blago_unit', ['class' => 'invalid-feedback d-block']) ?>
</div>
