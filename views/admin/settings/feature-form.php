<?php

/** @var yii\web\View $this */
/** @var app\models\CatalogFeature $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<?php $form = ActiveForm::begin(); ?>
<?= $form->field($model, 'name_ru')->textInput() ?>
<?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
<?= Html::a(Yii::t('app', 'Cancel'), ['features'], ['class' => 'btn btn-outline-secondary']) ?>
<?php ActiveForm::end(); ?>
