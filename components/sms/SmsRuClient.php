<?php

declare(strict_types=1);

namespace app\components\sms;

use Yii;
use yii\base\Component;

class SmsRuClient extends Component implements SmsSenderInterface
{
    private const CHECK_URL = 'https://sms.ru/auth/check';
    private const SEND_URL = 'https://sms.ru/sms/send';

    /** @return array<string, mixed>|null */
    public function checkApiId(): ?array
    {
        $apiId = $this->getApiId();
        if ($apiId === '') {
            return null;
        }

        return $this->request(self::CHECK_URL, [
            'api_id' => $apiId,
            'json' => 1,
        ]);
    }

    public function sendCode(string $phone, string $code): void
    {
        $apiId = $this->getApiId();
        if ($apiId === '') {
            Yii::warning('smsRuApiId is empty; SMS not sent.', __METHOD__);
            return;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if ($digits === '') {
            Yii::warning('Invalid phone number for SMS.ru.', __METHOD__);
            return;
        }

        $payload = [
            'api_id' => $apiId,
            'to' => $digits,
            'msg' => 'Код: ' . $code,
            'json' => 1,
        ];

        $from = trim((string) (Yii::$app->params['smsRuFrom'] ?? ''));
        if ($from !== '') {
            $payload['from'] = $from;
        }

        $userIp = Yii::$app->request->userIP ?? null;
        if (is_string($userIp) && $userIp !== '' && filter_var($userIp, FILTER_VALIDATE_IP)) {
            $payload['ip'] = $userIp;
        }

        $data = $this->request(self::SEND_URL, $payload);
        if ($data === null) {
            return;
        }

        if (($data['status'] ?? '') !== 'OK') {
            $this->logError('SMS.ru send rejected.', $data);
            return;
        }

        $smsStatus = $data['sms'][$digits] ?? null;
        if (is_array($smsStatus) && ($smsStatus['status'] ?? '') === 'OK') {
            return;
        }

        $this->logError('SMS.ru send failed for recipient.', is_array($smsStatus) ? $smsStatus : $data);
    }

    private function getApiId(): string
    {
        return trim((string) (Yii::$app->params['smsRuApiId'] ?? ''));
    }

    /** @param array<string, mixed> $fields */
    private function request(string $url, array $fields): ?array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            Yii::error('SMS.ru curl_init failed.', __METHOD__);
            return null;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fields,
        ]);

        $body = curl_exec($ch);
        if ($body === false) {
            Yii::error('SMS.ru request failed: ' . curl_error($ch), __METHOD__);
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        $data = json_decode($body, true);
        if (!is_array($data)) {
            Yii::error('SMS.ru invalid JSON response: ' . $body, __METHOD__);
            return null;
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    private function logError(string $message, array $data): void
    {
        Yii::error($message . ' ' . json_encode([
            'status_code' => $data['status_code'] ?? null,
            'status_text' => $data['status_text'] ?? null,
        ], JSON_UNESCAPED_UNICODE), __METHOD__);
    }
}
