<?php

/** @var yii\web\View $this */
/** @var app\models\CatalogFeatureValue $model */
/** @var app\models\CatalogFeature[] $features */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Attribute values'), 'url' => ['feature-values']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<?php $form = ActiveForm::begin(); ?>
<?= $form->field($model, 'feature_id')->dropDownList(array_column($features, 'name_ru', 'id')) ?>
<?= $form->field($model, 'name')->textInput() ?>
<?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
<?= Html::a(Yii::t('app', 'Cancel'), ['feature-values'], ['class' => 'btn btn-outline-secondary']) ?>
<?php ActiveForm::end(); ?>
