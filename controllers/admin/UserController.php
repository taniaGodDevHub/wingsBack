<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\models\search\AdminUserSearch;
use app\models\User;
use app\services\admin\AdminUserService;
use Yii;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class UserController extends BaseAdminController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['access']['rules'] = [
            [
                'allow' => true,
                'roles' => ['@'],
                'matchCallback' => static fn (): bool => static::canManageUsers(),
            ],
        ];
        $behaviors['access']['denyCallback'] = static function (): void {
            throw new ForbiddenHttpException(Yii::t('app', 'Access denied.'));
        };
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'assign-role' => ['POST'],
            ],
        ];

        return $behaviors;
    }

    public function actionIndex(): string
    {
        $this->view->title = Yii::t('app', 'Users list');

        $searchModel = new AdminUserSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        /** @var list<User> $models */
        $models = $dataProvider->getModels();
        $userIds = array_map(static fn (User $user): int => (int) $user->id, $models);
        $userStats = (new AdminUserService())->listStatsForUsers($userIds);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'userStats' => $userStats,
        ]);
    }

    public function actionView(int $id): string
    {
        $model = User::find()->with('profile')->where(['id' => $id])->one();
        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'User not found.'));
        }

        $this->view->title = $model->getDisplayName() . ' #' . $id;

        $auth = Yii::$app->authManager;
        $roles = [];
        foreach ($auth->getRolesByUser((string) $id) as $role) {
            $roles[] = $role->name;
        }

        $detail = (new AdminUserService())->getUserDetail($model);

        return $this->render('view', [
            'model' => $model,
            'roles' => $roles,
            'availableRoles' => $auth->getRoles(),
            'summary' => $detail['summary'],
            'orders' => $detail['orders'],
            'addresses' => $detail['addresses'],
        ]);
    }

    public function actionAssignRole(int $id): Response
    {
        $model = User::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'User not found.'));
        }

        $roleName = trim((string) Yii::$app->request->post('role_name', ''));
        if ($roleName === '') {
            throw new BadRequestHttpException(Yii::t('app', 'User and role are required.'));
        }

        $auth = Yii::$app->authManager;
        $role = $auth->getRole($roleName);
        if ($role === null) {
            throw new BadRequestHttpException(Yii::t('app', 'Role not found.'));
        }

        $auth->revokeAll((string) $model->id);
        $auth->assign($role, (string) $model->id);

        Yii::$app->session->setFlash('success', Yii::t('app', 'Role assigned successfully.'));

        return $this->redirect(['view', 'id' => $id]);
    }
}
