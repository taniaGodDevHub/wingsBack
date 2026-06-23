<?php

/** @var yii\web\View $this */
/** @var app\models\Color $model */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;
use yii\web\View;

$this->registerJsFile('@web/js/helpers/slugHelper.js', ['depends' => [\yii\web\JqueryAsset::class], 'position' => View::POS_END]);
$this->registerJsFile('@web/js/admin-slug.js', ['depends' => [\yii\web\JqueryAsset::class], 'position' => View::POS_END]);

?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<?php $form = ActiveForm::begin(); ?>
<div data-admin-slug>
    <?= $form->field($model, 'name')->textInput() ?>
    <?= $form->field($model, 'slug')->textInput() ?>
</div>
<?= $this->render('_hexColorField', ['form' => $form, 'model' => $model, 'attribute' => 'hex']) ?>
<div class="form-group">
    <?= Html::submitButton(Yii::t('app', 'Save'), ['class' => 'btn btn-primary']) ?>
    <?= Html::a(Yii::t('app', 'Cancel'), ['colors'], ['class' => 'btn btn-outline-secondary']) ?>
</div>
<?php ActiveForm::end(); ?>
