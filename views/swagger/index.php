<?php

declare(strict_types=1);

/** @var yii\web\View $this */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Wings API';

$schemaUrl = Url::to(['/swagger/json-schema'], true);
$swaggerBase = Url::to('@web/swagger-ui', true);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= Html::encode($this->title) ?></title>
    <link rel="stylesheet" href="<?= Html::encode($swaggerBase) ?>/swagger-ui.css">
    <style>
        html { box-sizing: border-box; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; background: #fafafa; }
    </style>
</head>
<body>
<div id="swagger-ui"></div>
<script src="<?= Html::encode($swaggerBase) ?>/swagger-ui-bundle.js" charset="UTF-8"></script>
<script src="<?= Html::encode($swaggerBase) ?>/swagger-ui-standalone-preset.js" charset="UTF-8"></script>
<script>
window.onload = function () {
    window.ui = SwaggerUIBundle({
        url: <?= json_encode($schemaUrl, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) ?>,
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset,
        ],
        plugins: [
            SwaggerUIBundle.plugins.DownloadUrl,
        ],
        layout: 'StandaloneLayout',
        oauth2RedirectUrl: <?= json_encode($swaggerBase . '/oauth2-redirect.html', JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) ?>,
    });
};
</script>
</body>
</html>
