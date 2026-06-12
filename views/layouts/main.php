<?php

declare(strict_types=1);

/** @var yii\web\View $this */
/** @var string $content */

use app\widgets\Alert;
use yii\helpers\Html;

$this->render('_head');

$initialTheme = 'light';
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>"
      data-bs-theme="<?= Html::encode($initialTheme) ?>"
      data-layout-mode="fluid"
      data-menu-color="dark"
      data-topbar-color="light"
      data-layout-position="fixed"
      data-sidenav-size="default"
      class="menuitem-active">
<head>
    <script>
        try {
            var t = localStorage.getItem('wings-theme');
            if (t === 'dark' || t === 'light') {
                document.documentElement.setAttribute('data-bs-theme', t);
            }
        } catch (e) {}
    </script>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
</head>
<body>
<?php $this->beginBody() ?>

<div class="wrapper">
    <div class="container-fluid">
        <?= $this->render('_hyper_topbar') ?>
    </div>

    <?= $this->render('_hyper_sidebar') ?>

    <main id="main" class="flex-shrink-0" role="main">
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <?= Alert::widget() ?>
                    <?= $content ?>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer footer-alt">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12 text-center text-md-start">
                    &copy; <?= Html::encode(Yii::$app->name) ?> <?= date('Y') ?>
                </div>
            </div>
        </div>
    </footer>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
