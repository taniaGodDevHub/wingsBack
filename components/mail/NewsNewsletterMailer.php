<?php

declare(strict_types=1);

namespace app\components\mail;

use app\models\News;
use Yii;
use yii\base\Component;
use yii\mail\MailerInterface;

class NewsNewsletterMailer extends Component
{
    public function sendArticleNotification(string $email, News $news, string $articleUrl): bool
    {
        /** @var MailerInterface $mailer */
        $mailer = Yii::$app->mailer;
        $subject = Yii::$app->params['newsNewsletterEmailSubject']
            ?? Yii::t('app', 'New article on Wings');

        return $mailer->compose('@app/mail/news-published', [
            'news' => $news,
            'articleUrl' => $articleUrl,
        ])
            ->setFrom([Yii::$app->params['senderEmail'] => Yii::$app->params['senderName']])
            ->setTo($email)
            ->setSubject($subject . ': ' . $news->title)
            ->send();
    }
}
