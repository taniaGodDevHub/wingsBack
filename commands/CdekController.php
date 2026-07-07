<?php

declare(strict_types=1);

namespace app\commands;

use app\components\cdek\CdekClient;
use app\components\cdek\TrackingSyncService;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Консольные команды СДЭК.
 *
 * Периодическая синхронизация: `yii cdek/sync-tracking` (рекомендуется cron каждые 15–30 мин).
 */
class CdekController extends Controller
{
    public function actionCheck(): int
    {
        /** @var CdekClient $cdek */
        $cdek = Yii::$app->cdek;
        $diag = $cdek->diagnostics();

        $this->stdout("CDEK diagnostics\n");
        $this->stdout('  mock_mode: ' . ($diag['mock_mode'] ? 'yes' : 'no') . "\n");
        $this->stdout('  credentials_configured: ' . ($diag['credentials_configured'] ? 'yes' : 'no') . "\n");
        $this->stdout('  token_available: ' . ($diag['token_available'] ? 'yes' : 'no') . "\n");
        $this->stdout('  api_base_url: ' . $diag['api_base_url'] . "\n");

        if ($diag['mock_mode']) {
            $this->stderr("CDEK runs in mock mode. PVZ list will not use live data.\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (!$diag['token_available']) {
            $this->stderr("CDEK OAuth token is not available. Check client_id/client_secret and api_base_url.\n");

            return ExitCode::UNSPECIFIED_ERROR;
        }

        $cityCode = $cdek->resolveCityCode(
            'f073f850-6c3b-4329-90ba-3c1489d457a1',
            null,
            '350000',
        );
        $this->stdout("  krasnodar_city_code: {$cityCode}\n");

        $pvz = $cdek->listDeliveryPoints($cityCode, 2, 1, '350000');
        $this->stdout('  krasnodar_pvz_count: ' . count($pvz['items']) . "\n");
        foreach ($pvz['items'] as $point) {
            $this->stdout('    - ' . ($point['code'] ?? '') . ': ' . ($point['address'] ?? '') . "\n");
        }

        return ExitCode::OK;
    }

    public function actionSyncTracking(): int
    {
        $updated = (new TrackingSyncService())->syncAll();
        $this->stdout("Synced {$updated} order(s).\n");

        return ExitCode::OK;
    }
}
