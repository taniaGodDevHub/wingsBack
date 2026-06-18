<?php

declare(strict_types=1);

namespace app\components\sms;

use Yii;

/**
 * Временная прослойка до подключения SMS-провайдера.
 * Код не отправляется — он возвращается фронтенду в ответе API (см. smsMockMode).
 */
class MockSmsSender implements SmsSenderInterface
{
    public function sendCode(string $phone, string $code): void
    {
        Yii::info(sprintf('SMS mock: код %s для %s', $code, $phone), __METHOD__);
    }
}
