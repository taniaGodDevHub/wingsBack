<?php

declare(strict_types=1);

namespace app\services;

use app\components\mail\NewsNewsletterMailer;
use app\models\News;
use app\models\NewsSubscriptionEmail;
use app\models\User;
use Yii;

final class NewsNewsletterService
{
    public function sendForArticle(News $news): int
    {
        if (!$news->is_published) {
            return 0;
        }

        $articleUrl = $this->buildArticleUrl($news);
        $mailer = new NewsNewsletterMailer();
        $sent = 0;

        foreach ($this->findSubscriberEmails() as $email) {
            try {
                if ($mailer->sendArticleNotification($email, $news, $articleUrl)) {
                    ++$sent;
                } else {
                    Yii::warning("News newsletter was not sent to {$email}", __METHOD__);
                }
            } catch (\Throwable $e) {
                Yii::error([
                    'message' => $e->getMessage(),
                    'email' => $email,
                    'news_id' => $news->id,
                ], __METHOD__);
            }
        }

        return $sent;
    }

    /** @return string[] */
    private function findSubscriberEmails(): array
    {
        $profileEmails = \app\models\UserProfile::find()
            ->alias('p')
            ->innerJoin(['u' => User::tableName()], 'u.id = p.user_id')
            ->select('p.email')
            ->where([
                'p.news_subscribed' => true,
                'p.email_confirmed' => true,
                'u.status' => User::STATUS_ACTIVE,
            ])
            ->andWhere(['not', ['p.email' => null]])
            ->andWhere(['<>', 'p.email', ''])
            ->asArray()
            ->column();

        $externalEmails = NewsSubscriptionEmail::find()
            ->select('email')
            ->asArray()
            ->column();

        $allEmails = array_map(
            static fn (string $email): string => mb_strtolower(trim($email)),
            array_merge($profileEmails, $externalEmails),
        );

        $allEmails = array_values(array_unique(array_filter(
            $allEmails,
            static fn (string $email): bool => $email !== '',
        )));

        return $allEmails;
    }

    private function buildArticleUrl(News $news): string
    {
        $baseUrl = rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? 'https://e-wings.ru'), '/');

        return $baseUrl . '/news/' . rawurlencode((string) $news->slug);
    }
}
