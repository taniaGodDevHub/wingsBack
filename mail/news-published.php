<?php

/** @var yii\web\View $this */
/** @var app\models\News $news */
/** @var string $articleUrl */

use yii\helpers\Html;

$imageUrl = $news->getImagePublicUrl();
$subtitle = trim((string) ($news->subtitle ?? ''));
?>
<p><?= Yii::t('app', 'A new article has been published on Wings:') ?></p>

<h2><?= Html::encode($news->title) ?></h2>

<?php if ($subtitle !== ''): ?>
<p><?= Html::encode($subtitle) ?></p>
<?php endif; ?>

<?php if ($imageUrl !== null && $imageUrl !== ''): ?>
<p>
    <a href="<?= Html::encode($articleUrl) ?>">
        <img src="<?= Html::encode($imageUrl) ?>" alt="<?= Html::encode($news->title) ?>" style="max-width:100%;height:auto;">
    </a>
</p>
<?php endif; ?>

<p>
    <a href="<?= Html::encode($articleUrl) ?>"><?= Yii::t('app', 'Read the article') ?></a>
</p>

<p><?= Yii::t('app', 'You received this email because you subscribed to Wings news.') ?></p>
