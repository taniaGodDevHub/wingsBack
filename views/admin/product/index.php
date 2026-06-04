<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\grid\GridView;
use yii\helpers\Html;

$this->params['breadcrumbs'][] = $this->title;
?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<p><?= Html::a(Yii::t('app', 'Create product'), ['create'], ['class' => 'btn btn-primary mb-3']) ?></p>
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'id',
        'name',
        'slug',
        'price',
        [
            'attribute' => 'is_available',
            'value' => static fn ($m) => $m->is_available ? Yii::t('app', 'Yes') : Yii::t('app', 'No'),
        ],
        [
            'class' => yii\grid\ActionColumn::class,
            'template' => '{view} {update} {delete}',
            'contentOptions' => ['class' => 'text-center text-nowrap'],
            'headerOptions' => ['class' => 'text-center'],
            'buttons' => [
                'view' => static fn ($url) => Html::a(
                    '<i class="ri-eye-line"></i>',
                    $url,
                    [
                        'class' => 'text-primary me-2',
                        'title' => Yii::t('app', 'View'),
                        'aria-label' => Yii::t('app', 'View'),
                        'style' => 'text-decoration: none; font-size: 18px;',
                    ],
                ),
                'update' => static fn ($url) => Html::a(
                    '<i class="ri-edit-line"></i>',
                    $url,
                    [
                        'class' => 'text-warning me-2',
                        'title' => Yii::t('app', 'Edit'),
                        'aria-label' => Yii::t('app', 'Edit'),
                        'style' => 'text-decoration: none; font-size: 18px;',
                    ],
                ),
                'delete' => static fn ($url) => Html::a(
                    '<i class="ri-delete-bin-line"></i>',
                    $url,
                    [
                        'class' => 'text-danger',
                        'title' => Yii::t('app', 'Delete'),
                        'aria-label' => Yii::t('app', 'Delete'),
                        'style' => 'text-decoration: none; font-size: 18px;',
                        'data-confirm' => Yii::t('app', 'Are you sure you want to delete this product?'),
                        'data-method' => 'post',
                    ],
                ),
            ],
        ],
    ],
]) ?>
