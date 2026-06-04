<?php

declare(strict_types=1);

namespace app\services;

use app\components\api\ApiHttpException;
use app\models\User;
use Yii;
use yii\web\IdentityInterface;

final class ApiOwnerContext
{
    public function __construct(
        public readonly ?int $userId,
        public readonly ?string $sessionId,
    ) {
    }

    public static function resolve(bool $requireAuth = false, bool $requireGuestOrAuth = true): self
    {
        $user = Yii::$app->user->identity;
        $sessionId = (new GuestSessionService())->resolveFromRequest();

        if ($user instanceof IdentityInterface) {
            if ($sessionId !== '') {
                (new GuestSessionService())->ensure($sessionId);
            }

            return new self((int) $user->getId(), $sessionId !== '' ? $sessionId : null);
        }

        if ($requireAuth) {
            throw ApiHttpException::unauthorized();
        }

        if ($requireGuestOrAuth) {
            if ($sessionId === '') {
                $request = Yii::$app->request;
                $body = $request->bodyParams;
                $sessionId = (string) ($body['session_id'] ?? $request->get('session_id', ''));
                if ($sessionId !== '') {
                    (new GuestSessionService())->ensure($sessionId);
                }
            }
            if ($sessionId === '') {
                throw ApiHttpException::unauthorized();
            }

            return new self(null, $sessionId);
        }

        return new self(null, null);
    }
}
