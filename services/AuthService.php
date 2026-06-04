<?php

declare(strict_types=1);

namespace app\services;

use app\components\auth\JwtService;
use app\components\mail\AuthCodeMailer;
use app\components\sms\SmsRuClient;
use app\models\AuthVerificationChallenge;
use app\models\PhoneNormalizer;
use app\models\User;
use app\models\UserProfile;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\TooManyRequestsHttpException;
use yii\web\UnauthorizedHttpException;

class AuthService
{
    public function checkUser(?string $phoneNumber, ?string $email, bool $isEmail): array
    {
        if ($isEmail) {
            $normalized = mb_strtolower(trim((string) $email));
            $exists = $normalized !== '' && UserProfile::findByEmail($normalized) !== null;

            return [
                'register' => !$exists,
                'command' => $exists ? 'login by email' : 'register to email',
            ];
        }

        $normalized = PhoneNormalizer::normalize((string) $phoneNumber);
        if ($normalized === '') {
            throw new BadRequestHttpException('Invalid phone number.');
        }
        $exists = UserProfile::findByPhone($normalized) !== null;

        return [
            'register' => !$exists,
            'command' => $exists ? 'login by phone' : 'register to phone',
        ];
    }

    public function startPhoneRegistration(string $phoneNumber): array
    {
        return $this->startChallenge(
            AuthVerificationChallenge::CHANNEL_PHONE,
            PhoneNormalizer::normalize($phoneNumber),
            AuthVerificationChallenge::TYPE_REGISTRATION,
            true,
        );
    }

    public function startPhoneLogin(string $phoneNumber): array
    {
        $normalized = PhoneNormalizer::normalize($phoneNumber);
        if (UserProfile::findByPhone($normalized) === null) {
            throw new NotFoundHttpException('User not found.');
        }

        return $this->startChallenge(
            AuthVerificationChallenge::CHANNEL_PHONE,
            $normalized,
            AuthVerificationChallenge::TYPE_LOGIN,
            true,
        );
    }

    public function startEmailRegistration(string $email): array
    {
        return $this->startChallenge(
            AuthVerificationChallenge::CHANNEL_EMAIL,
            mb_strtolower(trim($email)),
            AuthVerificationChallenge::TYPE_REGISTRATION,
            true,
        );
    }

    public function startEmailLogin(string $email): array
    {
        $normalized = mb_strtolower(trim($email));
        if (UserProfile::findByEmail($normalized) === null) {
            throw new NotFoundHttpException('User not found.');
        }

        $result = $this->startChallenge(
            AuthVerificationChallenge::CHANNEL_EMAIL,
            $normalized,
            AuthVerificationChallenge::TYPE_LOGIN,
            false,
        );

        return [
            'record_id' => $result['record_id'],
            'code' => $result['activation_code'] ?? null,
        ];
    }

    public function verifyPhoneRegistration(string $phoneNumber, string $code, string $recordId): array
    {
        $challenge = $this->validateChallenge(
            $recordId,
            AuthVerificationChallenge::CHANNEL_PHONE,
            PhoneNormalizer::normalize($phoneNumber),
            AuthVerificationChallenge::TYPE_REGISTRATION,
            $code,
        );

        return $this->completeRegistration($challenge, $phoneNumber, null);
    }

    public function verifyEmailRegistration(string $email, string $code, string $recordId): array
    {
        $challenge = $this->validateChallenge(
            $recordId,
            AuthVerificationChallenge::CHANNEL_EMAIL,
            mb_strtolower(trim($email)),
            AuthVerificationChallenge::TYPE_REGISTRATION,
            $code,
        );

        return $this->completeRegistration($challenge, null, $email);
    }

    public function loginPhoneWithCode(string $code, string $recordId): array
    {
        $challenge = AuthVerificationChallenge::findActive($recordId);
        if ($challenge === null
            || $challenge->channel !== AuthVerificationChallenge::CHANNEL_PHONE
            || $challenge->type !== AuthVerificationChallenge::TYPE_LOGIN
            || !$this->validateCode($challenge, $code)
        ) {
            throw new UnauthorizedHttpException('Invalid code or record.');
        }

        return $this->completeLogin($challenge);
    }

    public function startEmailConfirmation(User $user, string $email): array
    {
        $profile = $user->profile;
        $normalized = mb_strtolower(trim($email));
        if ($profile === null || $profile->email !== $normalized) {
            throw new BadRequestHttpException('Email does not match profile.');
        }

        $result = $this->startChallenge(
            AuthVerificationChallenge::CHANNEL_EMAIL,
            $normalized,
            AuthVerificationChallenge::TYPE_EMAIL_CONFIRM,
            true,
        );

        return array_merge($result, ['ttl_seconds' => (int) Yii::$app->params['authChallengeTtl']]);
    }

    public function verifyEmailConfirmation(User $user, string $email, string $code, string $recordId): void
    {
        $normalized = mb_strtolower(trim($email));
        $challenge = $this->validateChallenge(
            $recordId,
            AuthVerificationChallenge::CHANNEL_EMAIL,
            $normalized,
            AuthVerificationChallenge::TYPE_EMAIL_CONFIRM,
            $code,
        );

        $profile = $user->profile;
        if ($profile === null) {
            throw new NotFoundHttpException('Profile not found.');
        }

        $profile->email_confirmed = true;
        $profile->save(false);
        $challenge->used_at = time();
        $challenge->save(false);
    }

    public function loginEmailWithCode(string $code, string $recordId): array
    {
        $challenge = AuthVerificationChallenge::findActive($recordId);
        if ($challenge === null
            || $challenge->channel !== AuthVerificationChallenge::CHANNEL_EMAIL
            || $challenge->type !== AuthVerificationChallenge::TYPE_LOGIN
            || !$this->validateCode($challenge, $code)
        ) {
            throw new UnauthorizedHttpException('Invalid code or record.');
        }

        return $this->completeLogin($challenge);
    }

    private function startChallenge(string $channel, string $destination, string $type, bool $useOkWrapper): array
    {
        if ($destination === '') {
            throw new BadRequestHttpException('Invalid destination.');
        }

        $this->assertThrottle($channel, $destination);

        $code = (string) random_int(100000, 999999);
        $challenge = new AuthVerificationChallenge();
        $challenge->record_id = $this->generateUuid();
        $challenge->channel = $channel;
        $challenge->destination = $destination;
        $challenge->code_hash = Yii::$app->security->generatePasswordHash($code);
        $challenge->type = $type;
        $challenge->expires_at = time() + (int) Yii::$app->params['authChallengeTtl'];

        if (!$challenge->save()) {
            throw new ServerErrorHttpException('Failed to create verification challenge.');
        }

        $this->dispatchCode($channel, $destination, $code);

        $response = [
            'record_id' => $challenge->record_id,
        ];
        if (!empty(Yii::$app->params['exposeActivationCode'])) {
            $key = $useOkWrapper ? 'activation_code' : 'code';
            $response[$key] = $code;
        }
        if ($useOkWrapper) {
            return array_merge(['ok' => true], $response);
        }

        return $response;
    }

    private function dispatchCode(string $channel, string $destination, string $code): void
    {
        if ($channel === AuthVerificationChallenge::CHANNEL_PHONE) {
            /** @var SmsRuClient $sms */
            $sms = Yii::$app->smsRu;
            $sms->sendCode($destination, $code);
            return;
        }

        /** @var AuthCodeMailer $mailer */
        $mailer = Yii::$app->authCodeMailer;
        $mailer->sendCode($destination, $code);
    }

    private function validateChallenge(
        string $recordId,
        string $channel,
        string $destination,
        string $type,
        string $code,
    ): AuthVerificationChallenge {
        $challenge = AuthVerificationChallenge::findActive($recordId);
        if ($challenge === null
            || $challenge->channel !== $channel
            || $challenge->destination !== $destination
            || $challenge->type !== $type
            || !$this->validateCode($challenge, $code)
        ) {
            throw new UnauthorizedHttpException('Invalid code or record.');
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

    private function completeRegistration(
        AuthVerificationChallenge $challenge,
        ?string $phone,
        ?string $email,
    ): array {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $user = new User();
            $user->username = User::generateUsername($challenge->channel);
            $user->auth_key = Yii::$app->security->generateRandomString();
            $user->status = User::STATUS_ACTIVE;
            if (!$user->save()) {
                throw new ServerErrorHttpException('Failed to create user.');
            }

            $profile = new UserProfile();
            $profile->user_id = (int) $user->id;
            if ($phone !== null) {
                $profile->phone_number = PhoneNormalizer::normalize($phone);
                $profile->phone_number_confirmed = true;
            }
            if ($email !== null) {
                $profile->email = mb_strtolower(trim($email));
                $profile->email_confirmed = true;
            }
            if (!$profile->save()) {
                throw new ServerErrorHttpException('Failed to create profile.');
            }

            $auth = Yii::$app->authManager;
            $role = $auth->getRole('user');
            if ($role !== null) {
                $auth->assign($role, (string) $user->id);
            }

            $challenge->used_at = time();
            $challenge->save(false);

            $transaction->commit();
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }

        /** @var JwtService $jwt */
        $jwt = Yii::$app->jwt;

        return $jwt->issueTokenPair($user);
    }

    private function completeLogin(AuthVerificationChallenge $challenge): array
    {
        $profile = $challenge->channel === AuthVerificationChallenge::CHANNEL_PHONE
            ? UserProfile::findByPhone($challenge->destination)
            : UserProfile::findByEmail($challenge->destination);

        if ($profile === null) {
            throw new NotFoundHttpException('User not found.');
        }

        $user = User::findIdentity($profile->user_id);
        if ($user === null) {
            throw new NotFoundHttpException('User not found.');
        }

        $challenge->used_at = time();
        $challenge->save(false);

        /** @var JwtService $jwt */
        $jwt = Yii::$app->jwt;

        return $jwt->issueTokenPair($user);
    }

    private function assertThrottle(string $channel, string $destination): void
    {
        $since = time() - (int) Yii::$app->params['authChallengeThrottleSeconds'];
        $exists = AuthVerificationChallenge::find()
            ->where(['channel' => $channel, 'destination' => $destination])
            ->andWhere(['>', 'created_at', $since])
            ->exists();

        if ($exists) {
            throw new TooManyRequestsHttpException('Please wait before requesting a new code.');
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
