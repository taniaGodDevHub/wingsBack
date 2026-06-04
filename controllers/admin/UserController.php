<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\models\User;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

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

        return $behaviors;
    }

    public function actionIndex(): string
    {
        $this->view->title = Yii::t('app', 'Users list');

        $dataProvider = new ActiveDataProvider([
            'query' => User::find()->with('profile')->orderBy(['id' => SORT_ASC]),
            'pagination' => ['pageSize' => 20],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView(int $id): string
    {
        $model = User::find()->with('profile')->where(['id' => $id])->one();
        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'User not found.'));
        }

        $this->view->title = Yii::t('app', 'User #{id}', ['id' => $id]);
        $auth = Yii::$app->authManager;
        $roles = [];
        foreach ($auth->getRolesByUser((string) $id) as $role) {
            $roles[] = $role->name;
        }

        return $this->render('view', [
            'model' => $model,
            'roles' => $roles,
        ]);
    }
}
