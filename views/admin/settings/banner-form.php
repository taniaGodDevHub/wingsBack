<?php

/** @var yii\web\View $this */
/** @var app\models\HomeBanner $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Home banners'), 'url' => ['banners']];
$this->params['breadcrumbs'][] = $this->title;
?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<?php $form = ActiveForm::begin(); ?>
<?= $form->field($model, 'image_url')->textInput() ?>
<?= $form->field($model, 'sort_order')->input('number') ?>
<?= $form->field($model, 'is_active')->checkbox() ?>
<?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
<?= Html::a(Yii::t('app', 'Cancel'), ['banners'], ['class' => 'btn btn-outline-secondary']) ?>
<?php ActiveForm::end(); ?>
