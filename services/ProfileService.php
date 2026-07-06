<?php

declare(strict_types=1);

namespace app\services;

use app\components\api\ApiHttpException;
use app\models\User;
use app\models\UserProfile;
use Yii;
use yii\web\ServerErrorHttpException;

class ProfileService
{
    public function getProfile(User $user): array
    {
        $profile = $this->requireProfile($user);

        return $this->formatProfile($user, $profile);
    }

    /** @param array<string, mixed> $data */
    public function updateProfile(User $user, array $data): array
    {
        $profile = $this->requireProfile($user);

        if (isset($data['name'])) {
            $profile->name = (string) $data['name'];
            $profile->i = (string) $data['name'];
        }
        if (isset($data['surname'])) {
            $profile->surname = (string) $data['surname'];
            $profile->f = (string) $data['surname'];
        }
        if (isset($data['gender'])) {
            $profile->gender = (string) $data['gender'];
        }
        if (isset($data['birth_date']) && $data['birth_date'] !== '') {
            $profile->birth_date = (string) $data['birth_date'];
        }
        if (!empty($data['password'])) {
            $user->setPassword((string) $data['password']);
            $user->save(false);
        }
        if (array_key_exists('news_subscribed', $data)) {
            $subscribed = filter_var($data['news_subscribed'], FILTER_VALIDATE_BOOLEAN);
            if ($subscribed) {
                $email = trim((string) ($profile->email ?? ''));
                if ($email === '') {
                    throw ApiHttpException::validation([
                        'news_subscribed' => [Yii::t('app', 'Add and confirm email in profile to subscribe to news.')],
                    ]);
                }
                if (!$profile->email_confirmed) {
                    throw ApiHttpException::validation([
                        'news_subscribed' => [Yii::t('app', 'Confirm email in profile to subscribe to news.')],
                    ]);
                }
            }
            $profile->news_subscribed = $subscribed;
        }

        if (!$profile->save()) {
            throw ApiHttpException::validation(\app\components\api\ApiErrorHandler::validationDetail($profile));
        }

        return $this->formatProfile($user, $profile);
    }

    public function sendEmailConfirmation(User $user, string $email): array
    {
        $email = mb_strtolower(trim($email));
        if ($email === '') {
            throw new \InvalidArgumentException('email is required.');
        }

        return (new AuthService())->startEmailConfirmation($user, $email);
    }

    public function verifyEmailConfirmation(User $user, string $email, string $code, string $recordId): array
    {
        (new AuthService())->verifyEmailConfirmation($user, $email, $code, $recordId);
        $profile = $this->requireProfile($user);

        return ['ok' => true, 'email_confirmed' => (bool) $profile->email_confirmed];
    }

    private function requireProfile(User $user): UserProfile
    {
        $profile = $user->profile;
        if ($profile === null) {
            throw new ServerErrorHttpException('Profile not found.');
        }

        return $profile;
    }

    private function formatProfile(User $user, UserProfile $profile): array
    {
        return [
            'id' => (int) $user->id,
            'name' => $profile->i ?? $profile->name,
            'surname' => $profile->f ?? $profile->surname,
            'gender' => $profile->gender,
            'birth_date' => $profile->birth_date,
            'phone_number' => $profile->phone_number,
            'phone_number_confirmed' => (bool) $profile->phone_number_confirmed,
            'email' => $profile->email,
            'email_confirmed' => (bool) $profile->email_confirmed,
            'news_subscribed' => (bool) $profile->news_subscribed,
        ];
    }
}
