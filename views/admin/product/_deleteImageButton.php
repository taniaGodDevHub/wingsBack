<?php

/** @var string $deleteUrl */
/** @var int|null $pendingIndex */

use yii\helpers\Html;

$pendingIndex = $pendingIndex ?? null;
$deleteLabel = Yii::t('app', 'Delete');
$icon = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6"/><path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/></svg>
SVG;

if ($pendingIndex !== null) {
    echo Html::button($icon, [
        'type' => 'button',
        'class' => 'btn btn-sm btn-danger product-image-delete-btn product-image-delete--pending',
        'data-pending-index' => (int) $pendingIndex,
        'title' => $deleteLabel,
        'aria-label' => $deleteLabel,
    ]);
    return;
}

echo Html::button($icon, [
    'type' => 'button',
    'class' => 'btn btn-sm btn-danger product-image-delete-btn product-image-delete product-image-delete--ajax',
    'data-delete-url' => $deleteUrl,
    'data-confirm' => Yii::t('app', 'Delete this photo?'),
    'title' => $deleteLabel,
    'aria-label' => $deleteLabel,
]);
