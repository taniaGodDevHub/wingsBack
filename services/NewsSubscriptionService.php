<?php

declare(strict_types=1);

namespace app\services;

use app\components\api\ApiHttpException;
use app\models\NewsSubscriptionEmail;
use app\models\UserProfile;

class NewsSubscriptionService
{
    /** @return array<string, mixed> */
    public function subscribeByEmail(string $email): array
    {
        return $this->setSubscriptionByEmail($email, true);
    }

    /** @return array<string, mixed> */
    public function unsubscribeByEmail(string $email): array
    {
        return $this->setSubscriptionByEmail($email, false);
    }

    public function syncProfileEmail(UserProfile $profile): void
    {
        $email = mb_strtolower(trim((string) ($profile->email ?? '')));
        if ($email === '') {
            return;
        }

        $external = NewsSubscriptionEmail::findOne(['email' => $email]);
        if ($external === null) {
            return;
        }

        $profile->news_subscribed = true;
        $external->delete();
    }

    /** @return array<string, mixed> */
    private function setSubscriptionByEmail(string $email, bool $subscribed): array
    {
        $normalizedEmail = mb_strtolower(trim($email));
        if ($normalizedEmail === '' || !filter_var($normalizedEmail, FILTER_VALIDATE_EMAIL)) {
            throw ApiHttpException::validation(['email' => ['Invalid email.']]);
        }

        $profile = UserProfile::findByEmail($normalizedEmail);
        if ($profile !== null) {
            $profile->news_subscribed = $subscribed;
            if (!$profile->save()) {
                throw ApiHttpException::validation(\app\components\api\ApiErrorHandler::validationDetail($profile));
            }

            NewsSubscriptionEmail::deleteAll(['email' => $normalizedEmail]);

            return [
                'ok' => true,
                'email' => $normalizedEmail,
                'news_subscribed' => (bool) $profile->news_subscribed,
                'source' => 'profile',
            ];
        }

        if ($subscribed) {
            $external = NewsSubscriptionEmail::findOne(['email' => $normalizedEmail]) ?? new NewsSubscriptionEmail();
            $external->email = $normalizedEmail;
            if (!$external->save()) {
                throw ApiHttpException::validation(\app\components\api\ApiErrorHandler::validationDetail($external));
            }
        } else {
            NewsSubscriptionEmail::deleteAll(['email' => $normalizedEmail]);
        }

        return [
            'ok' => true,
            'email' => $normalizedEmail,
            'news_subscribed' => $subscribed,
            'source' => 'newsletter',
        ];
    }
}
