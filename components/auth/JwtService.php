<?php

declare(strict_types=1);

namespace app\components\auth;

use app\models\RefreshToken;
use app\models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;

class JwtService extends Component
{
    public function issueTokenPair(User $user): array
    {
        $accessToken = $this->createAccessToken($user);
        $refreshToken = $this->createRefreshToken($user);

        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type' => 'bearer',
        ];
    }

    public function createAccessToken(User $user): string
    {
        $now = time();
        $payload = [
            'sub' => (int) $user->id,
            'iat' => $now,
            'exp' => $now + (int) Yii::$app->params['jwtAccessTtl'],
            'type' => 'access',
        ];

        return JWT::encode($payload, $this->getSecret(), 'HS256');
    }

    public function validateAccessToken(string $token): ?User
    {
        try {
            $decoded = JWT::decode($token, new Key($this->getSecret(), 'HS256'));
            if (($decoded->type ?? null) !== 'access') {
                return null;
            }

            return User::findIdentity((int) $decoded->sub);
        } catch (\Throwable) {
            return null;
        }
    }

    public function rotateRefreshToken(string $plainRefreshToken): ?array
    {
        $hash = hash('sha256', $plainRefreshToken);
        $model = RefreshToken::findValidByHash($hash);
        if ($model === null) {
            return null;
        }

        $user = User::findIdentity($model->user_id);
        if ($user === null) {
            return null;
        }

        $model->delete();

        return $this->issueTokenPair($user);
    }

    private function createRefreshToken(User $user): string
    {
        $plain = Yii::$app->security->generateRandomString(64);
        $model = new RefreshToken();
        $model->user_id = (int) $user->id;
        $model->token_hash = hash('sha256', $plain);
        $model->expires_at = time() + (int) Yii::$app->params['jwtRefreshTtl'];
        if (!$model->save()) {
            throw new InvalidConfigException('Failed to save refresh token.');
        }

        return $plain;
    }

    private function getSecret(): string
    {
        $secret = Yii::$app->params['jwtSecret'] ?? '';
        if ($secret === '') {
            throw new InvalidConfigException('jwtSecret is not configured.');
        }

        return $secret;
    }
}
