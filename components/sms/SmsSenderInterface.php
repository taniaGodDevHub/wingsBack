<?php

declare(strict_types=1);

namespace app\components\sms;

interface SmsSenderInterface
{
    public function sendCode(string $phone, string $code): void;
}
