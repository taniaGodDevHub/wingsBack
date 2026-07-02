<?php

declare(strict_types=1);

namespace app\services;

use app\models\User;
use Yii;
use yii\rbac\ManagerInterface;

final class UserRoleService
{
    public function __construct(private readonly ?ManagerInterface $auth = null)
    {
    }

    public function assignDefaultRole(User $user): void
    {
        $auth = $this->auth ?? Yii::$app->authManager;
        $role = $auth->getRole('user');
        if ($role === null) {
            return;
        }

        if ($auth->getAssignment('user', (string) $user->id) !== null) {
            return;
        }

        $auth->assign($role, (string) $user->id);
    }
}
