<?php

/** @var yii\web\View $this */
/** @var app\models\CatalogFeatureValue $model */
/** @var app\models\CatalogFeature[] $features */
/** @var int|null $colorFeatureId */

use app\models\CatalogFeature;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;


$colorFeatureId ??= CatalogFeature::find()
    ->select('id')
    ->where(['code' => CatalogFeature::CODE_COLOR])
    ->scalar();
$colorFeatureId = $colorFeatureId !== false && $colorFeatureId !== null ? (int) $colorFeatureId : null;
$showHex = $colorFeatureId !== null
    && (($model->feature_id !== null && (int) $model->feature_id === $colorFeatureId)
        || ($model->feature !== null && $model->feature->isColor()));
?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<?php $form = ActiveForm::begin(['options' => ['id' => 'feature-value-form']]); ?>
<?= $form->field($model, 'feature_id')->dropDownList(
    array_column($features, 'name_ru', 'id'),
    $colorFeatureId !== null ? ['data-color-feature-id' => $colorFeatureId] : [],
) ?>
<?= $form->field($model, 'name')->textInput() ?>
<?php if ($colorFeatureId !== null): ?>
    <div id="feature-value-hex-field" class="<?= $showHex ? '' : 'd-none' ?>">
        <?= $this->render('_hexColorField', ['form' => $form, 'model' => $model, 'attribute' => 'hex']) ?>
    </div>
<?php endif ?>
<?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
<?= Html::a(Yii::t('app', 'Cancel'), ['feature-values'], ['class' => 'btn btn-outline-secondary']) ?>
<?php ActiveForm::end(); ?>
<?php if ($colorFeatureId !== null): ?>
<script>
(function () {
    var select = document.querySelector('#feature-value-form select[name="CatalogFeatureValue[feature_id]"]');
    var hexField = document.getElementById('feature-value-hex-field');
    if (!select || !hexField) {
        return;
    }
    var colorFeatureId = String(select.dataset.colorFeatureId || '');
    function toggleHex() {
        var show = select.value === colorFeatureId;
        hexField.classList.toggle('d-none', !show);
        if (show && window.initHexColorFields) {
            window.initHexColorFields();
        }
    }
    select.addEventListener('change', toggleHex);
    toggleHex();
})();
</script>
<?php endif ?>
