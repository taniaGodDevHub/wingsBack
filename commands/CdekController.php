<?php

declare(strict_types=1);

namespace app\commands;

use app\components\cdek\TrackingSyncService;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Консольные команды СДЭК.
 */
class CdekController extends Controller
{
    public function actionSyncTracking(): int
    {
        $updated = (new TrackingSyncService())->syncAll();
        $this->stdout("Synced {$updated} order(s).\n");

        return ExitCode::OK;
    }
}
