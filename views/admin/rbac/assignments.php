<?php

/** @var yii\web\View $this */
/** @var array<int, array<string, mixed>> $rows */
/** @var array<string, \yii\rbac\Role> $roles */

use yii\bootstrap5\ActiveForm;
use yii\helpers\Html;

?>
<div class="admin-rbac-assignments">
    <h1 class="h3 mb-4"><?= Html::encode($this->title) ?></h1>

    <div class="card mb-4">
        <div class="card-body">
            <h2 class="h6"><?= Yii::t('app', 'Assign role') ?></h2>
            <?php $form = ActiveForm::begin(['action' => ['assign'], 'method' => 'post']); ?>
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label"><?= Yii::t('app', 'User ID') ?></label>
                    <input type="number" name="user_id" class="form-control" required min="1">
                </div>
                <div class="col-md-4">
                    <label class="form-label"><?= Yii::t('app', 'Role') ?></label>
                    <select name="role_name" class="form-select" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= Html::encode($role->name) ?>"><?= Html::encode($role->name) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <?= Html::submitButton(Yii::t('app', 'Assign'), ['class' => 'btn btn-primary']) ?>
                </div>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>

    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th><?= Yii::t('app', 'Username') ?></th>
                <th><?= Yii::t('app', 'Roles') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= (int) $row['user_id'] ?></td>
                    <td><?= Html::encode($row['username']) ?></td>
                    <td><?= Html::encode($row['roles']) ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
