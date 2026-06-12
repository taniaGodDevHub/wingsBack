<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\models\AdminProfileForm;
use app\models\User;
use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class ProfileController extends Controller
{
    public $layout = 'main';

    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    public function actionEdit(): Response|string
    {
        $user = $this->findCurrentUser();
        $model = new AdminProfileForm();
        $model->loadFromUser($user);

        if ($model->load(Yii::$app->request->post()) && $model->save($user)) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Profile saved successfully.'));

            return $this->redirect(['edit']);
        }

        return $this->render('edit', [
            'model' => $model,
            'user' => $user,
            'roles' => $this->getUserRoles($user),
        ]);
    }

    private function findCurrentUser(): User
    {
        $identity = Yii::$app->user->identity;
        if (!$identity instanceof User) {
            throw new NotFoundHttpException(Yii::t('app', 'User not found.'));
        }

        $user = User::find()->with('profile')->where(['id' => $identity->id])->one();
        if ($user === null) {
            throw new NotFoundHttpException(Yii::t('app', 'User not found.'));
        }

        return $user;
    }

    /** @return string[] */
    private function getUserRoles(User $user): array
    {
        $roles = [];
        foreach (Yii::$app->authManager->getRolesByUser((string) $user->id) as $role) {
            $roles[] = $role->description ?: $role->name;
        }

        return $roles;
    }
}
