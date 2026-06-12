<?php

/** @var yii\web\View $this */
/** @var app\models\Color $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<?php $form = ActiveForm::begin(); ?>
<?= $form->field($model, 'name')->textInput() ?>
<?= $this->render('_hexColorField', ['form' => $form, 'model' => $model, 'attribute' => 'hex']) ?>
<?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
<?= Html::a(Yii::t('app', 'Cancel'), ['colors'], ['class' => 'btn btn-outline-secondary']) ?>
<?php ActiveForm::end(); ?>
