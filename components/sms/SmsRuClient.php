<?php

declare(strict_types=1);

namespace app\components\sms;

use Yii;
use yii\base\Component;

class SmsRuClient extends Component
{
    private const SEND_URL = 'https://sms.ru/sms/send';

    public function sendCode(string $phone, string $code): bool
    {
        $apiId = Yii::$app->params['smsRuApiId'] ?? '';
        if ($apiId === '') {
            Yii::warning('smsRuApiId is empty; SMS not sent.', __METHOD__);
            return YII_ENV_DEV;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            return false;
        }

        $url = self::SEND_URL . '?' . http_build_query([
            'api_id' => $apiId,
            'to' => $digits,
            'msg' => 'Код: ' . $code,
            'json' => 1,
        ]);

        $body = @file_get_contents($url);
        if ($body === false) {
            Yii::error('SMS.ru request failed.', __METHOD__);
            return false;
        }

        $data = json_decode($body, true);
        return is_array($data) && ($data['status'] ?? '') === 'OK';
    }
}
