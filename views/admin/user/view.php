<?php

/** @var yii\web\View $this */
/** @var app\models\User $model */
/** @var string[] $roles */
/** @var array<string, \yii\rbac\Role> $availableRoles */
/** @var array{orders_count: int, orders_total: float, completed_count: int, in_progress_count: int, average_order_total: float|null, last_order_at: int|null} $summary */
/** @var list<app\models\ShopOrder> $orders */
/** @var list<app\models\UserAddress> $addresses */

use app\models\ShopOrder;
use app\models\User;
use app\services\admin\AdminUserService;
use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

$profile = $model->profile;
$currentRole = $roles[0] ?? 'user';
?>
<div class="admin-user-view">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1"><?= Html::encode($model->getDisplayName()) ?></h1>
            <p class="text-muted mb-0">
                <?= Yii::t('app', 'User #{id}', ['id' => (int) $model->id]) ?>
                · <?= Yii::t('app', 'Registration date') ?>: <?= Html::encode(AdminUserService::formatDate((int) $model->created_at)) ?>
            </p>
        </div>
        <div>
            <?= Html::a(Yii::t('app', 'Back to list'), ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>
    </div>

    <?php if (Yii::$app->session->hasFlash('success')): ?>
        <div class="alert alert-success"><?= Html::encode((string) Yii::$app->session->getFlash('success')) ?></div>
    <?php endif ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small"><?= Yii::t('app', 'Orders') ?></div>
                    <div class="h4 mb-0"><?= (int) $summary['orders_count'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small"><?= Yii::t('app', 'Total purchases') ?></div>
                    <div class="h4 mb-0"><?= Html::encode(AdminUserService::formatMoney((float) $summary['orders_total'])) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small"><?= Yii::t('app', 'Average order') ?></div>
                    <div class="h4 mb-0">
                        <?= $summary['average_order_total'] !== null
                            ? Html::encode(AdminUserService::formatMoney((float) $summary['average_order_total']))
                            : '—' ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small"><?= Yii::t('app', 'Last order') ?></div>
                    <div class="h4 mb-0"><?= Html::encode(AdminUserService::formatDate($summary['last_order_at'])) ?></div>
                    <div class="small text-muted mt-1">
                        <?= Yii::t('app', 'Completed') ?>: <?= (int) $summary['completed_count'] ?>
                        · <?= Yii::t('app', 'In progress') ?>: <?= (int) $summary['in_progress_count'] ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent">
                    <h2 class="h5 mb-0"><?= Yii::t('app', 'Contact information') ?></h2>
                </div>
                <div class="card-body p-0">
                    <table class="table table-bordered mb-0">
                        <tr>
                            <th class="w-50"><?= Yii::t('app', 'Username') ?></th>
                            <td><?= Html::encode($model->username) ?></td>
                        </tr>
                        <tr>
                            <th><?= Yii::t('app', 'Phone') ?></th>
                            <td>
                                <?= Html::encode($profile?->phone_number ?? '—') ?>
                                <?php if ($profile?->phone_number_confirmed): ?>
                                    <span class="badge bg-success-subtle text-success ms-1"><?= Yii::t('app', 'Confirmed') ?></span>
                                <?php endif ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= Yii::t('app', 'Email') ?></th>
                            <td>
                                <?= Html::encode($profile?->email ?? '—') ?>
                                <?php if ($profile?->email_confirmed): ?>
                                    <span class="badge bg-success-subtle text-success ms-1"><?= Yii::t('app', 'Confirmed') ?></span>
                                <?php endif ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= Yii::t('app', 'Gender') ?></th>
                            <td><?= Html::encode($profile?->getGenderLabel() ?? Yii::t('app', 'Not specified')) ?></td>
                        </tr>
                        <tr>
                            <th><?= Yii::t('app', 'Birth date') ?></th>
                            <td>
                                <?php
                                $birthDate = $profile?->birth_date;
                                echo $birthDate !== null && $birthDate !== ''
                                    ? Html::encode(date('d.m.Y', strtotime((string) $birthDate)))
                                    : Yii::t('app', 'Not specified');
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= Yii::t('app', 'Role') ?></th>
                            <td>
                                <?php $roleForm = ActiveForm::begin([
                                    'action' => ['assign-role', 'id' => $model->id],
                                    'method' => 'post',
                                    'options' => ['class' => 'd-flex flex-wrap align-items-center gap-2 mb-0'],
                                ]) ?>
                                <select name="role_name" class="form-select form-select-sm" style="max-width: 220px" required>
                                    <?php foreach ($availableRoles as $role): ?>
                                        <option value="<?= Html::encode($role->name) ?>"<?= $currentRole === $role->name ? ' selected' : '' ?>>
                                            <?= Html::encode($role->name) ?>
                                        </option>
                                    <?php endforeach ?>
                                </select>
                                <?= Html::submitButton(Yii::t('app', 'Save role'), ['class' => 'btn btn-sm btn-primary']) ?>
                                <?php ActiveForm::end() ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= Yii::t('app', 'Status') ?></th>
                            <td>
                                <?php
                                $isActive = $model->status === User::STATUS_ACTIVE;
                                $statusLabel = $isActive ? Yii::t('app', 'Active') : Yii::t('app', 'Deleted');
                                $statusClass = $isActive ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary';
                                ?>
                                <span class="badge <?= $statusClass ?>"><?= Html::encode($statusLabel) ?></span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-transparent">
                    <h2 class="h5 mb-0"><?= Yii::t('app', 'Delivery addresses') ?></h2>
                </div>
                <div class="card-body">
                    <?php if ($addresses === []): ?>
                        <p class="text-muted mb-0"><?= Yii::t('app', 'No saved addresses.') ?></p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($addresses as $address): ?>
                                <div class="list-group-item px-0">
                                    <div class="fw-semibold">
                                        <?= Html::encode($address->city_name ?? Yii::t('app', 'Not specified')) ?>
                                        <?php if ($address->is_pvz): ?>
                                            <span class="badge text-bg-secondary ms-1">ПВЗ <?= Html::encode($address->pvz_code) ?></span>
                                        <?php endif ?>
                                    </div>
                                    <div class="text-muted"><?= Html::encode($address->full_address) ?></div>
                                    <div class="small text-muted mt-1">
                                        <?= Yii::t('app', 'Updated at') ?>:
                                        <?= Html::encode(AdminUserService::formatDateTime((int) $address->updated_at)) ?>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mt-4">
        <div class="card-header bg-transparent">
            <h2 class="h5 mb-0"><?= Yii::t('app', 'Order history') ?></h2>
        </div>
        <div class="card-body p-0">
            <?php if ($orders === []): ?>
                <p class="text-muted mb-0 p-3"><?= Yii::t('app', 'No orders yet.') ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped align-middle mb-0">
                        <thead>
                        <tr>
                            <th><?= Yii::t('app', 'Order') ?></th>
                            <th><?= Yii::t('app', 'Date') ?></th>
                            <th><?= Yii::t('app', 'Status') ?></th>
                            <th><?= Yii::t('app', 'Delivery') ?></th>
                            <th><?= Yii::t('app', 'Track number') ?></th>
                            <th class="text-end"><?= Yii::t('app', 'Amount') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            $tracking = $order->tracking;
                            $items = AdminUserService::orderItemsSummary($order);
                            $collapseId = 'order-items-' . (int) $order->id;
                            ?>
                            <tr>
                                <td class="text-nowrap">
                                    <div>#<?= (int) $order->id ?></div>
                                    <div class="small text-muted"><?= Html::encode($order->code !== null && $order->code !== '' ? (string) $order->code : '—') ?></div>
                                </td>
                                <td class="text-nowrap"><?= Html::encode(AdminUserService::formatDateTime((int) $order->created_at)) ?></td>
                                <td>
                                    <span class="badge <?= AdminUserService::orderStatusBadgeClass((string) $order->status) ?>">
                                        <?= Html::encode(ShopOrder::statusLabel((string) $order->status)) ?>
                                    </span>
                                </td>
                                <td>
                                    <div><?= Html::encode(AdminUserService::orderDeliveryLabel($order, $tracking)) ?></div>
                                    <?php if ($order->delivery_address !== null && $order->delivery_address !== ''): ?>
                                        <div class="small text-muted"><?= Html::encode($order->delivery_address) ?></div>
                                    <?php endif ?>
                                </td>
                                <td class="text-nowrap">
                                    <?= Html::encode($tracking?->track_number ?? $order->cdek_track_number ?? '—') ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <div><?= Html::encode(AdminUserService::formatMoney((float) $order->total_price)) ?></div>
                                    <div class="small text-muted">
                                        <?= Yii::t('app', 'Blago') ?>: <?= Html::encode(AdminUserService::formatMoney((float) $order->blago_total)) ?>
                                    </div>
                                </td>
                            </tr>
                            <?php if ($items !== []): ?>
                                <tr>
                                    <td colspan="6" class="bg-light">
                                        <button class="btn btn-link btn-sm text-decoration-none p-0"
                                                type="button"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#<?= $collapseId ?>"
                                                aria-expanded="false"
                                                aria-controls="<?= $collapseId ?>">
                                            <?= Yii::t('app', 'Order items') ?> (<?= count($items) ?>)
                                        </button>
                                        <div class="collapse mt-2" id="<?= $collapseId ?>">
                                            <ul class="mb-0 ps-3">
                                                <?php foreach ($items as $item): ?>
                                                    <li>
                                                        <?= Html::encode($item['name']) ?>
                                                        · <?= Yii::t('app', 'Size') ?>: <?= Html::encode($item['size_value'] ?? '—') ?>
                                                        · <?= (int) $item['quantity'] ?> <?= Yii::t('app', 'pcs.') ?>
                                                        · <?= Html::encode(AdminUserService::formatMoney((float) $item['total_price'])) ?>
                                                    </li>
                                                <?php endforeach ?>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif ?>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
