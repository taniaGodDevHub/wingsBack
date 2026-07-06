<?php

declare(strict_types=1);

namespace app\services;

use app\components\mail\NewsNewsletterMailer;
use app\models\News;
use app\models\User;
use app\models\UserProfile;
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

        foreach ($this->findSubscribers() as $profile) {
            $email = mb_strtolower(trim((string) $profile->email));
            if ($email === '') {
                continue;
            }

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

    /** @return UserProfile[] */
    private function findSubscribers(): array
    {
        return UserProfile::find()
            ->alias('p')
            ->innerJoin(['u' => User::tableName()], 'u.id = p.user_id')
            ->where([
                'p.news_subscribed' => true,
                'p.email_confirmed' => true,
                'u.status' => User::STATUS_ACTIVE,
            ])
            ->andWhere(['not', ['p.email' => null]])
            ->andWhere(['<>', 'p.email', ''])
            ->all();
    }

    private function buildArticleUrl(News $news): string
    {
        $baseUrl = rtrim((string) (Yii::$app->params['frontendBaseUrl'] ?? 'https://e-wings.ru'), '/');

        return $baseUrl . '/news/' . rawurlencode((string) $news->slug);
    }
}
