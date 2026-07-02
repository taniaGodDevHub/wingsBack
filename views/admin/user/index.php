<?php

/** @var yii\web\View $this */
/** @var app\models\search\AdminUserSearch $searchModel */
/** @var yii\data\ActiveDataProvider $dataProvider */
/** @var array<int, array{orders_count: int, orders_total: float, last_order_at: int|null, last_order_status: string|null, city: string|null}> $userStats */

use app\models\ShopOrder;
use app\models\User;
use app\services\admin\AdminUserService;
use yii\grid\GridView;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

?>
<div class="admin-user-index">
    <h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <?php $form = ActiveForm::begin([
                'method' => 'get',
                'action' => ['index'],
                'options' => ['class' => 'row g-3 align-items-end'],
            ]) ?>
            <div class="col-md-5">
                <?= $form->field($searchModel, 'q', [
                    'options' => ['class' => 'mb-0'],
                ])->textInput([
                    'placeholder' => Yii::t('app', 'Search by name, phone, email or login'),
                ])->label(Yii::t('app', 'Search')) ?>
            </div>
            <div class="col-md-3">
                <?= $form->field($searchModel, 'status', [
                    'options' => ['class' => 'mb-0'],
                ])->dropDownList([
                    '' => Yii::t('app', 'All users'),
                    'active' => Yii::t('app', 'Active'),
                    'deleted' => Yii::t('app', 'Deleted'),
                    'with_orders' => Yii::t('app', 'With orders'),
                ])->label(Yii::t('app', 'Filter')) ?>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <?= Html::submitButton(Yii::t('app', 'Apply'), ['class' => 'btn btn-primary']) ?>
                <?= Html::a(Yii::t('app', 'Reset'), ['index'], ['class' => 'btn btn-outline-secondary']) ?>
            </div>
            <?php ActiveForm::end() ?>
        </div>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped table-bordered align-middle mb-0'],
        'columns' => [
            [
                'attribute' => 'id',
                'headerOptions' => ['style' => 'width: 70px'],
            ],
            [
                'label' => Yii::t('app', 'Customer'),
                'format' => 'raw',
                'value' => static function (User $model): string {
                    return Html::a(
                        Html::encode($model->getDisplayName()),
                        ['view', 'id' => $model->id],
                        ['class' => 'fw-semibold text-decoration-none'],
                    );
                },
            ],
            [
                'label' => Yii::t('app', 'Phone'),
                'value' => static fn (User $model): string => $model->profile?->phone_number ?? '—',
            ],
            [
                'label' => Yii::t('app', 'Email'),
                'value' => static fn (User $model): string => $model->profile?->email ?? '—',
            ],
            [
                'label' => Yii::t('app', 'City'),
                'value' => static function (User $model) use ($userStats): string {
                    return $userStats[(int) $model->id]['city'] ?? '—';
                },
            ],
            [
                'label' => Yii::t('app', 'Registration date'),
                'value' => static fn (User $model): string => AdminUserService::formatDate((int) $model->created_at),
                'headerOptions' => ['class' => 'text-nowrap'],
            ],
            [
                'label' => Yii::t('app', 'Orders'),
                'contentOptions' => ['class' => 'text-center'],
                'headerOptions' => ['class' => 'text-center text-nowrap'],
                'value' => static function (User $model) use ($userStats): string {
                    $count = $userStats[(int) $model->id]['orders_count'] ?? 0;

                    return (string) $count;
                },
            ],
            [
                'label' => Yii::t('app', 'Total purchases'),
                'contentOptions' => ['class' => 'text-end text-nowrap'],
                'headerOptions' => ['class' => 'text-end text-nowrap'],
                'value' => static function (User $model) use ($userStats): string {
                    $total = $userStats[(int) $model->id]['orders_total'] ?? 0.0;

                    return $total > 0 ? AdminUserService::formatMoney($total) : '—';
                },
            ],
            [
                'label' => Yii::t('app', 'Last order'),
                'format' => 'raw',
                'headerOptions' => ['class' => 'text-nowrap'],
                'value' => static function (User $model) use ($userStats): string {
                    $stats = $userStats[(int) $model->id] ?? null;
                    if ($stats === null || $stats['last_order_at'] === null) {
                        return Html::tag('span', '—', ['class' => 'text-muted']);
                    }

                    $date = AdminUserService::formatDate($stats['last_order_at']);
                    $status = (string) ($stats['last_order_status'] ?? '');
                    if ($status === '') {
                        return Html::encode($date);
                    }

                    $badgeClass = AdminUserService::orderStatusBadgeClass($status);

                    return Html::encode($date) . '<br>'
                        . Html::tag(
                            'span',
                            Html::encode(ShopOrder::statusLabel($status)),
                            ['class' => 'badge ' . $badgeClass . ' mt-1'],
                        );
                },
            ],
            [
                'attribute' => 'status',
                'format' => 'raw',
                'value' => static function (User $model): string {
                    $isActive = $model->status === User::STATUS_ACTIVE;
                    $label = $isActive ? Yii::t('app', 'Active') : Yii::t('app', 'Deleted');
                    $class = $isActive ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary';

                    return Html::tag('span', Html::encode($label), ['class' => 'badge ' . $class]);
                },
            ],
            [
                'class' => yii\grid\ActionColumn::class,
                'template' => '{view}',
                'contentOptions' => ['class' => 'text-center'],
            ],
        ],
    ]) ?>
</div>
