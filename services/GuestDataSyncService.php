<?php

declare(strict_types=1);

namespace app\services;

use app\models\User;

class GuestDataSyncService
{
    public function syncForUser(User $user, string $sessionId): array
    {
        if ($sessionId === '') {
            return [
                'skipped' => true,
                'reason' => 'no_session',
            ];
        }

        return [
            'skipped' => false,
            'cart' => (new CartService())->sync($user, $sessionId),
            'favorites' => (new FavoritesService())->sync($user, $sessionId),
        ];
    }
}
