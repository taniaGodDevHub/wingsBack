<?php

/** @var yii\web\View $this */
/** @var string $code */

?>
<p><?= Yii::t('app', 'Your password reset code:') ?> <strong><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?></strong></p>
<p><?= Yii::t('app', 'The code is valid for a limited time. If you did not request a reset, ignore this email.') ?></p>
