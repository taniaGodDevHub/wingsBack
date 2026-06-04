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
      data-sidenav-size="default">
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
</head>
<body class="authentication-bg">
<?php $this->beginBody() ?>

<div class="account-pages pt-2 pt-sm-5 pb-4 pb-sm-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xxl-5 col-xl-6 col-lg-8 col-md-9">
                <div class="d-flex justify-content-end mb-2">
                    <div class="nav-link px-2" id="light-dark-mode" role="button" tabindex="0" aria-label="<?= Yii::t('app', 'Switch theme') ?>">
                        <i class="ri-moon-line font-22"></i>
                    </div>
                </div>
                <?= Alert::widget() ?>
                <?= $content ?>
            </div>
        </div>
    </div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
