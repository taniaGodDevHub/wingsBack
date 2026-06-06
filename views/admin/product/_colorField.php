<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\Product $model */

use app\models\Color;

$options = Color::getCheckboxOptions();
?>
<?php if ($options === []): ?>
    <p class="text-muted mb-3"><?= Yii::t('app', 'No colors in catalog yet.') ?></p>
<?php else: ?>
    <?= $form->field($model, 'colorIds')->checkboxList($options)->hint(Yii::t('app', 'Used in catalog filters and product cards.')) ?>
<?php endif ?>
