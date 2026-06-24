<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\grid\GridView;
use yii\helpers\Html;

?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<p><?= Html::a(Yii::t('app', 'Create size'), ['size-form'], ['class' => 'btn btn-primary mb-3']) ?></p>
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'id',
        'rus_label',
        'size_value',
        'default_chest_circumference',
        'sort_order',
        [
            'class' => yii\grid\ActionColumn::class,
            'template' => '{update} {delete}',
            'contentOptions' => ['class' => 'text-nowrap'],
            'buttons' => [
                'update' => static fn ($url, $model) => Html::a(
                    Yii::t('app', 'Edit'),
                    ['size-form', 'id' => $model->id],
                    ['class' => 'btn btn-sm btn-outline-secondary me-1'],
                ),
                'delete' => static fn ($url, $model) => Html::a(
                    Yii::t('app', 'Delete'),
                    ['size-delete', 'id' => $model->id],
                    [
                        'class' => 'btn btn-sm btn-outline-danger',
                        'data-confirm' => Yii::t('app', 'Are you sure you want to delete this size?'),
                        'data-method' => 'post',
                    ],
                ),
            ],
        ],
    ],
]) ?>
