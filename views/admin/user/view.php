<?php

/** @var yii\web\View $this */
/** @var app\models\User $model */
/** @var string[] $roles */

use yii\helpers\Html;

$this->params['breadcrumbs'][] = ['label' => Yii::t('app', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$profile = $model->profile;
?>
<div class="admin-user-view">
    <h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>

    <table class="table table-bordered w-auto">
        <tr><th>ID</th><td><?= (int) $model->id ?></td></tr>
        <tr><th><?= Yii::t('app', 'Username') ?></th><td><?= Html::encode($model->username) ?></td></tr>
        <tr><th><?= Yii::t('app', 'Email') ?></th><td><?= Html::encode($profile?->email ?? '—') ?></td></tr>
        <tr><th><?= Yii::t('app', 'Phone') ?></th><td><?= Html::encode($profile?->phone_number ?? '—') ?></td></tr>
        <tr><th><?= Yii::t('app', 'Roles') ?></th><td><?= Html::encode($roles === [] ? '—' : implode(', ', $roles)) ?></td></tr>
    </table>

    <p><?= Html::a(Yii::t('app', 'Back to list'), ['index'], ['class' => 'btn btn-outline-secondary']) ?></p>
</div>
