<?php

/** @var yii\web\View $this */

use yii\helpers\Html;

$this->title = Yii::t('app', 'About');
?>
<div class="site-about d-flex align-items-center justify-content-center text-center">
    <div class="site-about-content mx-auto">
        <h1 class="display-6 fw-semibold mb-3"><?= Yii::t('app', 'This is the About page.') ?></h1>

        <p class="text-body-secondary mb-4">
            <?= Yii::t('app', 'You may modify the following file to customize its content:') ?>
            <?php if (YII_DEBUG): ?>
                <code class="d-block mt-2"><?= __FILE__ ?></code>
            <?php endif; ?>
        </p>

        <?= Html::a(
            Yii::t('app', 'Go to Homepage'),
            Yii::$app->homeUrl,
            ['class' => 'btn btn-outline-primary btn-lg'],
        ) ?>
    </div>
</div>
