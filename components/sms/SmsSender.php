<?php

declare(strict_types=1);

namespace app\components\sms;

use Yii;
use yii\base\Component;

/**
 * Фасад отправки SMS: mock-режим для вёрстки и тестов или SMS.ru в продакшене.
 */
class SmsSender extends Component implements SmsSenderInterface
{
    public function sendCode(string $phone, string $code): void
    {
        $this->resolveSender()->sendCode($phone, $code);
    }

    public function isMockMode(): bool
    {
        if (array_key_exists('smsMockMode', Yii::$app->params)) {
            return (bool) Yii::$app->params['smsMockMode'];
        }

        return trim((string) (Yii::$app->params['smsRuApiId'] ?? '')) === '';
    }

    private function resolveSender(): SmsSenderInterface
    {
        if ($this->isMockMode()) {
            return new MockSmsSender();
        }

        /** @var SmsRuClient $smsRu */
        $smsRu = Yii::$app->smsRu;

        return $smsRu;
    }
}
