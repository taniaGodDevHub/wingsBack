<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\models\News;
use app\services\NewsImageUploadService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class NewsController extends BaseAdminController
{
    private ?NewsImageUploadService $uploadService = null;

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
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'delete' => ['POST'],
            ],
        ];

        return $behaviors;
    }

    public function actionIndex(): string
    {
        $this->view->title = Yii::t('app', 'News');

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider([
                'query' => News::find()->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]),
                'pagination' => ['pageSize' => 20],
            ]),
        ]);
    }

    public function actionCreate(): string|Response
    {
        $model = new News();
        $model->is_published = true;

        if ($this->saveForm($model)) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Saved successfully.'));
            return $this->redirect(['index']);
        }

        $this->view->title = Yii::t('app', 'Create news');

        return $this->render('form', ['model' => $model]);
    }

    public function actionUpdate(int $id): string|Response
    {
        $model = $this->findModel($id);

        if ($this->saveForm($model)) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Saved successfully.'));
            return $this->redirect(['index']);
        }

        $this->view->title = Yii::t('app', 'Edit news');

        return $this->render('form', ['model' => $model]);
    }

    public function actionDelete(int $id): Response
    {
        $model = $this->findModel($id);
        $this->upload()->removeLocalFileIfOwned($model->image_url);
        $model->delete();
        Yii::$app->session->setFlash('success', Yii::t('app', 'News deleted.'));

        return $this->redirect(['index']);
    }

    private function saveForm(News $model): bool
    {
        try {
            if (!$model->load(Yii::$app->request->post())) {
                return false;
            }

            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if (!$model->validate()) {
                return false;
            }

            if ($model->imageFile !== null) {
                $uploadError = $this->upload()->upload($model, $model->imageFile);
                if ($uploadError !== null) {
                    $model->addError('imageFile', $uploadError);
                    return false;
                }
            }

            return $model->save();
        } catch (\Throwable $e) {
            Yii::error([
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'post' => Yii::$app->request->post(),
            ], __METHOD__);

            $errorMessage = Yii::t('app', 'Something went wrong. Please try again.');
            if (YII_DEBUG) {
                $errorMessage .= ' (' . $e->getMessage() . ')';
            }
            $model->addError('title', $errorMessage);

            return false;
        }
    }

    private function upload(): NewsImageUploadService
    {
        if ($this->uploadService === null) {
            $this->uploadService = new NewsImageUploadService();
        }

        return $this->uploadService;
    }

    private function findModel(int $id): News
    {
        $model = News::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'News not found.'));
        }

        return $model;
    }
}
