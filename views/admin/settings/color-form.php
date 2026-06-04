<?php

/** @var yii\web\View $this */
/** @var app\models\Color $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Colors'), 'url' => ['colors']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<?php $form = ActiveForm::begin(); ?>
<?= $form->field($model, 'name')->textInput() ?>
<?= $form->field($model, 'hex')->textInput(['placeholder' => '#111111']) ?>
<?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
<?= Html::a(Yii::t('app', 'Cancel'), ['colors'], ['class' => 'btn btn-outline-secondary']) ?>
<?php ActiveForm::end(); ?>
