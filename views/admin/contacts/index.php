<?php

/** @var yii\web\View $this */
/** @var app\models\ContactInfo $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

?>
<div class="admin-contacts-index">
    <h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>

    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <p class="text-muted mb-4">
                <?= Yii::t('app', 'Contact details shown to customers on the site and in the app.') ?>
            </p>

            <?php $form = ActiveForm::begin(); ?>

            <div class="row g-3">
                <div class="col-md-6">
                    <?= $form->field($model, 'phone')->textInput([
                        'maxlength' => true,
                        'placeholder' => '+7 (999) 123-45-67',
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'email')->textInput([
                        'maxlength' => true,
                        'type' => 'email',
                        'placeholder' => 'info@example.com',
                    ]) ?>
                </div>
                <div class="col-md-6">
                    <?= $form->field($model, 'telegram')->textInput([
                        'maxlength' => true,
                        'placeholder' => '@wings_shop',
                    ]) ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'work_hours_from')->input('time') ?>
                </div>
                <div class="col-md-3">
                    <?= $form->field($model, 'work_hours_to')->input('time') ?>
                </div>
            </div>

            <div class="mt-2">
                <?= Html::submitButton(Yii::t('app', 'Save changes'), ['class' => 'btn btn-primary']) ?>
            </div>

            <?php ActiveForm::end() ?>
        </div>
    </div>
</div>
