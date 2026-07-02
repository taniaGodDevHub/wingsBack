<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use app\models\News;
use yii\grid\GridView;
use yii\helpers\Html;

?>
<h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>
<p><?= Html::a(Yii::t('app', 'Create news'), ['create'], ['class' => 'btn btn-primary mb-3']) ?></p>
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'id',
        [
            'label' => Yii::t('app', 'Image'),
            'format' => 'raw',
            'value' => static function (News $model): string {
                $url = $model->getImagePublicUrl();
                if ($url === null || $url === '') {
                    return Html::tag('span', '—', ['class' => 'text-muted']);
                }

                return Html::img($url, [
                    'alt' => $model->title,
                    'style' => 'width:70px;height:70px;object-fit:cover;border-radius:4px',
                ]);
            },
            'contentOptions' => ['class' => 'text-center', 'style' => 'width:82px'],
            'headerOptions' => ['class' => 'text-center'],
        ],
        'title',
        'slug',
        [
            'attribute' => 'created_at',
            'value' => static fn (News $model): string => Yii::$app->formatter->asDatetime((int) $model->created_at),
        ],
        [
            'attribute' => 'is_published',
            'value' => static fn (News $model): string => $model->is_published ? Yii::t('app', 'Yes') : Yii::t('app', 'No'),
        ],
        [
            'class' => yii\grid\ActionColumn::class,
            'template' => '{update} {delete}',
            'contentOptions' => ['class' => 'text-center text-nowrap'],
            'headerOptions' => ['class' => 'text-center'],
            'buttons' => [
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
                        'data-confirm' => Yii::t('app', 'Are you sure you want to delete this news?'),
                        'data-method' => 'post',
                    ],
                ),
            ],
        ],
    ],
]) ?>
