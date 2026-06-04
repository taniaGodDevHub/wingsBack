<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\grid\GridView;
use yii\helpers\Html;

$this->params['breadcrumbs'][] = Yii::t('app', 'Settings');
$this->params['breadcrumbs'][] = $this->title;
?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<p><?= Html::a(Yii::t('app', 'Create banner'), ['banner-form'], ['class' => 'btn btn-primary mb-3']) ?></p>
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'id',
        'image_url:url',
        'sort_order',
        [
            'attribute' => 'is_active',
            'value' => static fn ($m) => $m->is_active ? Yii::t('app', 'Yes') : Yii::t('app', 'No'),
        ],
        [
            'class' => yii\grid\ActionColumn::class,
            'template' => '{update}',
            'buttons' => [
                'update' => static fn ($url, $model) => Html::a(
                    Yii::t('app', 'Edit'),
                    ['banner-form', 'id' => $model->id],
                    ['class' => 'btn btn-sm btn-outline-secondary'],
                ),
            ],
        ],
    ],
]) ?>
