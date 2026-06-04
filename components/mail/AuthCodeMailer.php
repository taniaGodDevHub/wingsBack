<?php

declare(strict_types=1);

namespace app\components\mail;

use Yii;
use yii\base\Component;
use yii\mail\MailerInterface;

class AuthCodeMailer extends Component
{
    public function sendCode(string $email, string $code): bool
    {
        /** @var MailerInterface $mailer */
        $mailer = Yii::$app->mailer;

        return $mailer->compose('@app/mail/auth-code', ['code' => $code])
            ->setFrom([Yii::$app->params['authCodeEmailFrom'] => Yii::$app->params['senderName']])
            ->setTo($email)
            ->setSubject(Yii::$app->params['authCodeEmailSubject'])
            ->send();
    }
}
