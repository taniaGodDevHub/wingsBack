<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\models\User;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;

class RbacController extends BaseAdminController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['access']['rules'] = [
            [
                'allow' => true,
                'roles' => ['@'],
                'matchCallback' => static fn (): bool => static::canManageRbac(),
            ],
        ];
        $behaviors['access']['denyCallback'] = static function (): void {
            throw new ForbiddenHttpException(Yii::t('app', 'Access denied.'));
        };

        return $behaviors;
    }

    public function actionRoles(): string
    {
        $this->view->title = Yii::t('app', 'Roles');

        return $this->render('items', [
            'title' => $this->view->title,
            'items' => Yii::$app->authManager->getRoles(),
        ]);
    }

    public function actionPermissions(): string
    {
        $this->view->title = Yii::t('app', 'Permissions');

        return $this->render('items', [
            'title' => $this->view->title,
            'items' => Yii::$app->authManager->getPermissions(),
        ]);
    }

    public function actionAssignments(): string
    {
        $this->view->title = Yii::t('app', 'Role assignments');

        $auth = Yii::$app->authManager;
        $rows = [];
        foreach (User::find()->orderBy(['id' => SORT_ASC])->all() as $user) {
            $roleNames = array_keys($auth->getRolesByUser((string) $user->id));
            $rows[] = [
                'user_id' => (int) $user->id,
                'username' => $user->username,
                'roles' => $roleNames === [] ? '—' : implode(', ', $roleNames),
            ];
        }

        return $this->render('assignments', [
            'rows' => $rows,
            'roles' => $auth->getRoles(),
        ]);
    }

    public function actionAssign(): \yii\web\Response
    {
        $userId = (string) (Yii::$app->request->post('user_id') ?? '');
        $roleName = (string) (Yii::$app->request->post('role_name') ?? '');
        if ($userId === '' || $roleName === '') {
            throw new BadRequestHttpException(Yii::t('app', 'User and role are required.'));
        }

        $auth = Yii::$app->authManager;
        $role = $auth->getRole($roleName);
        if ($role === null) {
            throw new BadRequestHttpException(Yii::t('app', 'Role not found.'));
        }

        $auth->revokeAll($userId);
        $auth->assign($role, $userId);
        Yii::$app->session->setFlash('success', Yii::t('app', 'Role assigned successfully.'));

        return $this->redirect(['assignments']);
    }
}
