<?php

/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\grid\GridView;
use yii\helpers\Html;
?>
<p class="text-muted mb-3"><?= Yii::t('app', 'Banners are shown on the home page as a slideshow. Order is set by sort order.') ?></p>
<p><?= Html::a(Yii::t('app', 'Create banner'), ['/admin/settings/banner-form'], ['class' => 'btn btn-primary mb-3']) ?></p>
<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'id',
        [
            'label' => Yii::t('app', 'Image'),
            'format' => 'raw',
            'value' => static function ($model): string {
                $url = $model->getImagePublicUrl();
                if ($url === null) {
                    return '—';
                }

                return Html::img($url, [
                    'alt' => '',
                    'class' => 'admin-banner-list__thumb',
                ]);
            },
        ],
        'title',
        'button_text',
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
                    ['/admin/settings/banner-form', 'id' => $model->id],
                    ['class' => 'btn btn-sm btn-outline-secondary'],
                ),
            ],
        ],
    ],
]) ?>
