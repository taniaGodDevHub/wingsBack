<?php

declare(strict_types=1);

namespace app\services;

use app\models\GuestSession;
use Yii;

class GuestSessionService
{
    public function ensure(string $sessionId): void
    {
        if ($sessionId === '') {
            return;
        }

        $model = GuestSession::findOne(['session_id' => $sessionId]);
        if ($model === null) {
            $model = new GuestSession();
            $model->session_id = $sessionId;
            $model->save(false);
        } else {
            $model->updated_at = time();
            $model->save(false);
        }
    }

    public function resolveFromRequest(): string
    {
        $request = Yii::$app->request;
        $sessionId = (string) $request->headers->get('X-Session-ID', '');
        if ($sessionId === '') {
            $body = $request->bodyParams;
            $sessionId = (string) ($body['session_id'] ?? '');
        }
        if ($sessionId === '') {
            $sessionId = (string) $request->get('session_id', '');
        }

        if ($sessionId !== '') {
            $this->ensure($sessionId);
        }

        return $sessionId;
    }
}
