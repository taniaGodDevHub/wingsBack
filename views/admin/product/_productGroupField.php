<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\Product $model */
/** @var array<int|string, string> $productGroupOptions */

use yii\helpers\Html;

?>
<div class="product-group-field">
    <div class="row">
        <div class="col-12 col-md-6">
            <?= $form->field($model, 'product_group_id')->dropDownList($productGroupOptions) ?>
        </div>
        <div class="col-12 col-md-6">
            <?= $form->field($model, 'newProductGroupName')->textInput([
                'placeholder' => Yii::t('app', 'Create new product group'),
            ]) ?>
        </div>
    </div>
</div>
