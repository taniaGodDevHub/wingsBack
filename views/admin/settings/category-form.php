<?php

/** @var yii\web\View $this */
/** @var app\models\Category $model */
/** @var app\models\Category[] $parents */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<?php $form = ActiveForm::begin(); ?>
<?= $form->field($model, 'name')->textInput() ?>
<?= $form->field($model, 'slug')->textInput() ?>
<?= $form->field($model, 'parent_id')->dropDownList(
    ['' => '—'] + array_column($parents, 'name', 'id'),
) ?>
<?= $form->field($model, 'sort_order')->input('number', ['value' => $model->sort_order ?? 0]) ?>
<?= $form->field($model, 'is_active')->checkbox() ?>
<div class="form-group">
    <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
    <?= Html::a(Yii::t('app', 'Cancel'), ['categories'], ['class' => 'btn btn-outline-secondary']) ?>
</div>
<?php ActiveForm::end(); ?>
