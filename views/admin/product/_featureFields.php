<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\Product $model */
/** @var app\models\CatalogFeature[] $catalogFeatures */

use app\models\CatalogFeatureValue;

if ($catalogFeatures === []) {
    return;
}
?>
<div class="mb-3">
    <?php foreach ($catalogFeatures as $feature): ?>
        <?php
        $attribute = 'featureValueByFeatureId[' . (int) $feature->id . ']';
        $selected = $model->featureValueByFeatureId[(int) $feature->id] ?? '';
        ?>
        <?= $form->field($model, $attribute)
            ->dropDownList(CatalogFeatureValue::getDropdownOptionsForFeature((int) $feature->id), [
                'value' => $selected,
            ])
            ->label($feature->name_ru) ?>
    <?php endforeach ?>
</div>
