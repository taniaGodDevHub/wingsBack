<?php

declare(strict_types=1);

namespace app\commands;

use app\components\cdek\TrackingSyncService;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Консольные команды СДЭК.
 *
 * Периодическая синхронизация: `yii cdek/sync-tracking` (рекомендуется cron каждые 15–30 мин).
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
