<?php

/** @var yii\web\View $this */
/** @var app\models\Category $model */
/** @var app\models\Category[] $parents */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\web\View;

$this->registerJsFile('@web/js/helpers/slugHelper.js?v=3', ['depends' => [\yii\web\JqueryAsset::class], 'position' => View::POS_END]);
$this->registerJsFile('@web/js/admin-slug.js?v=2', ['depends' => [\yii\web\JqueryAsset::class], 'position' => View::POS_END]);

?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<?php $form = ActiveForm::begin(); ?>
<div data-admin-slug>
    <?= $form->field($model, 'name')->textInput() ?>
    <?= $form->field($model, 'slug')->textInput() ?>
</div>
<?= $form->field($model, 'parent_id')->dropDownList(
    ['' => Yii::t('app', 'No parent')] + array_column($parents, 'name', 'id'),
) ?>
<?= $form->field($model, 'sort_order')->input('number', ['value' => $model->sort_order ?? 0]) ?>
<?= $form->field($model, 'is_active')->checkbox() ?>
<div class="form-group">
    <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
    <?= Html::a(Yii::t('app', 'Cancel'), ['categories'], ['class' => 'btn btn-outline-secondary']) ?>
</div>
<?php ActiveForm::end(); ?>
