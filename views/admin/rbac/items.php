<?php

/** @var yii\web\View $this */
/** @var string $title */
/** @var array<string, \yii\rbac\Item> $items */

use yii\helpers\Html;

?>
<div class="admin-rbac-items">
    <h1 class="h3 mb-4"><?= Html::encode($title) ?></h1>

    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th><?= Yii::t('app', 'Item name') ?></th>
                <th><?= Yii::t('app', 'Description') ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><code><?= Html::encode($item->name) ?></code></td>
                    <td><?= Html::encode($item->description ?? '—') ?></td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
