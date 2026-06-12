<?php

declare(strict_types=1);

namespace app\services;

use app\components\mail\AuthCodeMailer;
use app\models\AuthVerificationChallenge;
use app\models\User;
use app\models\UserProfile;
use Yii;
use yii\web\TooManyRequestsHttpException;

class AdminPasswordResetService
{
    public function requestReset(string $email): ?string
    {
        $email = mb_strtolower(trim($email));
        if ($email === '' || UserProfile::findByEmail($email) === null) {
            return null;
        }

        $this->assertThrottle($email);

        $code = (string) random_int(100000, 999999);
        $challenge = new AuthVerificationChallenge();
        $challenge->record_id = $this->generateUuid();
        $challenge->channel = AuthVerificationChallenge::CHANNEL_EMAIL;
        $challenge->destination = $email;
        $challenge->code_hash = Yii::$app->security->generatePasswordHash($code);
        $challenge->type = AuthVerificationChallenge::TYPE_PASSWORD_RESET;
        $challenge->expires_at = time() + (int) Yii::$app->params['authChallengeTtl'];

        if (!$challenge->save()) {
            return null;
        }

        /** @var AuthCodeMailer $mailer */
        $mailer = Yii::$app->authCodeMailer;
        $mailer->sendPasswordResetCode($email, $code);

        return $challenge->record_id;
    }

    public function verifyCode(string $recordId, string $email, string $code): bool
    {
        return $this->findValidChallenge($recordId, $email, $code) !== null;
    }

    public function resetPassword(string $recordId, string $email, string $code, string $newPassword): bool
    {
        $email = mb_strtolower(trim($email));
        $challenge = $this->findValidChallenge($recordId, $email, $code);
        if ($challenge === null) {
            return false;
        }

        $profile = UserProfile::findByEmail($email);
        if ($profile === null) {
            return false;
        }

        $user = User::findIdentity($profile->user_id);
        if ($user === null) {
            return false;
        }

        $user->setPassword($newPassword);
        if (!$user->save(false)) {
            return false;
        }

        $challenge->used_at = time();
        $challenge->save(false);

        return true;
    }

    private function findValidChallenge(string $recordId, string $email, string $code): ?AuthVerificationChallenge
    {
        $email = mb_strtolower(trim($email));
        $challenge = AuthVerificationChallenge::findActive($recordId);

        if ($challenge === null
            || $challenge->channel !== AuthVerificationChallenge::CHANNEL_EMAIL
            || $challenge->destination !== $email
            || $challenge->type !== AuthVerificationChallenge::TYPE_PASSWORD_RESET
            || !$this->validateCode($challenge, $code)
        ) {
            return null;
        }

        return $challenge;
    }

    private function validateCode(AuthVerificationChallenge $challenge, string $code): bool
    {
        if ($code === '') {
            return false;
        }

        return Yii::$app->security->validatePassword($code, $challenge->code_hash);
    }

    private function assertThrottle(string $email): void
    {
        $since = time() - (int) Yii::$app->params['authChallengeThrottleSeconds'];
        $exists = AuthVerificationChallenge::find()
            ->where([
                'channel' => AuthVerificationChallenge::CHANNEL_EMAIL,
                'destination' => $email,
                'type' => AuthVerificationChallenge::TYPE_PASSWORD_RESET,
            ])
            ->andWhere(['>', 'created_at', $since])
            ->exists();

        if ($exists) {
            throw new TooManyRequestsHttpException(Yii::t('app', 'Please wait before requesting a new code.'));
        }
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
