<?php

/** @var yii\web\View $this */
/** @var app\models\Size $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<?php $form = ActiveForm::begin(); ?>
<?= $form->field($model, 'rus_label')->textInput() ?>
<?= $form->field($model, 'size_value')->textInput() ?>
<?= $form->field($model, 'default_chest_circumference')->textInput() ?>
<?= $form->field($model, 'sort_order')->input('number', ['min' => 0]) ?>
<div class="form-group">
    <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
    <?= Html::a(Yii::t('app', 'Cancel'), ['sizes'], ['class' => 'btn btn-outline-secondary']) ?>
</div>
<?php ActiveForm::end(); ?>
