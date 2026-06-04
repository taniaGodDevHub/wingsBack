<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\grid\GridView;
use yii\helpers\Html;

$this->params['breadcrumbs'][] = Yii::t('app', 'Settings');
$this->params['breadcrumbs'][] = $this->title;
?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<p><?= Html::a(Yii::t('app', 'Create attribute'), ['feature-form'], ['class' => 'btn btn-primary mb-3']) ?></p>
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'id',
        'name_ru',
        [
            'class' => yii\grid\ActionColumn::class,
            'template' => '{update}',
            'buttons' => [
                'update' => static fn ($url, $model) => Html::a(
                    Yii::t('app', 'Edit'),
                    ['feature-form', 'id' => $model->id],
                    ['class' => 'btn btn-sm btn-outline-secondary'],
                ),
            ],
        ],
    ],
]) ?>
