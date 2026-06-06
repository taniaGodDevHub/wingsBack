<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var yii\db\ActiveRecord $model */
/** @var string $attribute */

use yii\helpers\Html;
use yii\web\View;

$this->registerJsFile('@web/js/admin-color-hex.js', ['depends' => [\yii\web\JqueryAsset::class], 'position' => View::POS_END]);

$pickerValue = '#000000';
if (!empty($model->{$attribute})) {
    $raw = trim((string) $model->{$attribute});
    if ($raw !== '' && $raw[0] !== '#') {
        $raw = '#' . $raw;
    }
    if (preg_match('/^#[0-9A-Fa-f]{6}$/', $raw) || preg_match('/^#[0-9A-Fa-f]{3}$/', $raw)) {
        $pickerValue = strlen($raw) === 4
            ? '#' . $raw[1] . $raw[1] . $raw[2] . $raw[2] . $raw[3] . $raw[3]
            : $raw;
        $pickerValue = strtolower($pickerValue);
    }
}
?>
<div class="hex-color-field">
    <?= $form->field($model, $attribute, [
        'options' => ['class' => 'mb-0'],
        'template' => '{label}<div class="d-flex align-items-start gap-2"><input type="color" class="form-control form-control-color hex-color-field__picker flex-shrink-0" value="' . Html::encode($pickerValue) . '" title="' . Html::encode(Yii::t('app', 'Pick a color')) . '"><div class="flex-grow-1">{input}{error}</div></div>{hint}',
    ])->textInput([
        'maxlength' => 7,
        'class' => 'form-control hex-color-field__text',
        'placeholder' => '#111111',
    ]) ?>
    <p class="form-text mb-0"><?= Yii::t('app', 'Pick a color from the palette or enter a hex code (e.g. #111111).') ?></p>
</div>
