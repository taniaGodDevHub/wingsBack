<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\models\ContactInfo;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\Response;

class ContactsController extends BaseAdminController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['access']['rules'] = [
            [
                'allow' => true,
                'roles' => ['@'],
                'matchCallback' => static fn (): bool => static::canManageCatalog(),
            ],
        ];
        $behaviors['access']['denyCallback'] = static function (): void {
            throw new ForbiddenHttpException(Yii::t('app', 'Access denied.'));
        };

        return $behaviors;
    }

    public function actionIndex(): string|Response
    {
        $this->view->title = Yii::t('app', 'Contact');
        $model = ContactInfo::singleton();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Saved successfully.'));

            return $this->redirect(['index']);
        }

        return $this->render('index', [
            'model' => $model,
        ]);
    }
}
