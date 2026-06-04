<?php

declare(strict_types=1);

namespace app\services;

use app\models\User;

class CartSyncService
{
    public function sync(User $user, string $sessionId): array
    {
        return (new CartService())->sync($user, $sessionId);
    }
}
