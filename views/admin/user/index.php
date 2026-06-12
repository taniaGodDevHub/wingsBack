<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\grid\GridView;
use yii\helpers\Html;

?>
<div class="admin-user-index">
    <h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped table-bordered'],
        'columns' => [
            'id',
            'username',
            [
                'label' => Yii::t('app', 'Email'),
                'value' => static fn ($model) => $model->profile?->email ?? '—',
            ],
            [
                'label' => Yii::t('app', 'Phone'),
                'value' => static fn ($model) => $model->profile?->phone_number ?? '—',
            ],
            [
                'attribute' => 'status',
                'value' => static function ($model): string {
                    return $model->status === \app\models\User::STATUS_ACTIVE
                        ? Yii::t('app', 'Active')
                        : Yii::t('app', 'Deleted');
                },
            ],
            [
                'class' => yii\grid\ActionColumn::class,
                'template' => '{view}',
            ],
        ],
    ]) ?>
</div>
