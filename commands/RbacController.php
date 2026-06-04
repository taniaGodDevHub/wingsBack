<?php

declare(strict_types=1);

namespace app\commands;

use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Инициализация ролей и разрешений RBAC.
 *
 * Запуск: php yii rbac/init
 */
class RbacController extends Controller
{
    /** ID пользователя admin из models/User (демо-данные). */
    private const ADMIN_USER_ID = '100';

    /**
     * Создаёт роли, разрешения и назначает admin пользователю admin.
     */
    public function actionInit(): int
    {
        $auth = \Yii::$app->authManager;

        $auth->removeAll();

        $manageUsers = $auth->createPermission('manageUsers');
        $manageUsers->description = 'Управление пользователями';
        $auth->add($manageUsers);

        $manageRbac = $auth->createPermission('manageRbac');
        $manageRbac->description = 'Управление ролями и разрешениями';
        $auth->add($manageRbac);

        $manageCatalog = $auth->createPermission('manageCatalog');
        $manageCatalog->description = 'Управление каталогом и настройками магазина';
        $auth->add($manageCatalog);

        $admin = $auth->createRole('admin');
        $admin->description = 'Администратор';
        $auth->add($admin);
        $auth->addChild($admin, $manageUsers);
        $auth->addChild($admin, $manageRbac);
        $auth->addChild($admin, $manageCatalog);

        $user = $auth->createRole('user');
        $user->description = 'Зарегистрированный пользователь';
        $auth->add($user);

        $auth->assign($admin, self::ADMIN_USER_ID);

        $this->stdout("RBAC initialized.\n");
        $this->stdout("Role \"admin\" assigned to user id " . self::ADMIN_USER_ID . " (username: admin).\n");

        return ExitCode::OK;
    }
}
