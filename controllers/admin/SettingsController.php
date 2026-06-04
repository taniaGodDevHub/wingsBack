<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\models\CatalogFeature;
use app\models\CatalogFeatureValue;
use app\models\Category;
use app\models\Color;
use app\models\HomeBanner;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class SettingsController extends BaseAdminController
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

    public function actionCategories(): string
    {
        $this->view->title = Yii::t('app', 'Categories');

        return $this->render('categories', [
            'dataProvider' => new ActiveDataProvider([
                'query' => Category::find()->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]),
                'pagination' => ['pageSize' => 30],
            ]),
        ]);
    }

    public function actionCategoryForm(?int $id = null): string|Response
    {
        $model = $id !== null ? $this->findCategory($id) : new Category();
        $model->is_active = $model->isNewRecord ? true : $model->is_active;

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Saved successfully.'));
            return $this->redirect(['categories']);
        }

        $this->view->title = $model->isNewRecord
            ? Yii::t('app', 'Create category')
            : Yii::t('app', 'Edit category');

        return $this->render('category-form', [
            'model' => $model,
            'parents' => Category::find()->orderBy(['name' => SORT_ASC])->all(),
        ]);
    }

    public function actionColors(): string
    {
        $this->view->title = Yii::t('app', 'Colors');

        return $this->render('colors', [
            'dataProvider' => new ActiveDataProvider([
                'query' => Color::find()->orderBy(['name' => SORT_ASC]),
                'pagination' => ['pageSize' => 30],
            ]),
        ]);
    }

    public function actionColorForm(?int $id = null): string|Response
    {
        $model = $id !== null ? $this->findColor($id) : new Color();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Saved successfully.'));
            return $this->redirect(['colors']);
        }

        $this->view->title = $model->isNewRecord ? Yii::t('app', 'Create color') : Yii::t('app', 'Edit color');

        return $this->render('color-form', ['model' => $model]);
    }

    public function actionFeatures(): string
    {
        $this->view->title = Yii::t('app', 'Attributes');

        return $this->render('features', [
            'dataProvider' => new ActiveDataProvider([
                'query' => CatalogFeature::find()->orderBy(['id' => SORT_ASC]),
                'pagination' => ['pageSize' => 30],
            ]),
        ]);
    }

    public function actionFeatureForm(?int $id = null): string|Response
    {
        $model = $id !== null ? $this->findFeature($id) : new CatalogFeature();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Saved successfully.'));
            return $this->redirect(['features']);
        }

        $this->view->title = $model->isNewRecord
            ? Yii::t('app', 'Create attribute')
            : Yii::t('app', 'Edit attribute');

        return $this->render('feature-form', ['model' => $model]);
    }

    public function actionFeatureValues(): string
    {
        $this->view->title = Yii::t('app', 'Attribute values');

        return $this->render('feature-values', [
            'dataProvider' => new ActiveDataProvider([
                'query' => CatalogFeatureValue::find()->with('feature')->orderBy(['feature_id' => SORT_ASC, 'id' => SORT_ASC]),
                'pagination' => ['pageSize' => 30],
            ]),
        ]);
    }

    public function actionFeatureValueForm(?int $id = null): string|Response
    {
        $model = $id !== null ? $this->findFeatureValue($id) : new CatalogFeatureValue();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Saved successfully.'));
            return $this->redirect(['feature-values']);
        }

        $this->view->title = $model->isNewRecord
            ? Yii::t('app', 'Create attribute value')
            : Yii::t('app', 'Edit attribute value');

        return $this->render('feature-value-form', [
            'model' => $model,
            'features' => CatalogFeature::find()->orderBy(['name_ru' => SORT_ASC])->all(),
        ]);
    }

    public function actionBanners(): string
    {
        $this->view->title = Yii::t('app', 'Home banners');

        return $this->render('banners', [
            'dataProvider' => new ActiveDataProvider([
                'query' => HomeBanner::find()->orderBy(['sort_order' => SORT_ASC]),
                'pagination' => ['pageSize' => 20],
            ]),
        ]);
    }

    public function actionBannerForm(?int $id = null): string|Response
    {
        $model = $id !== null ? $this->findBanner($id) : new HomeBanner();
        if ($model->isNewRecord) {
            $model->is_active = true;
            $model->sort_order = 0;
        }

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Saved successfully.'));
            return $this->redirect(['banners']);
        }

        $this->view->title = $model->isNewRecord
            ? Yii::t('app', 'Create banner')
            : Yii::t('app', 'Edit banner');

        return $this->render('banner-form', ['model' => $model]);
    }

    private function findCategory(int $id): Category
    {
        $model = Category::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'Category not found.'));
        }

        return $model;
    }

    private function findColor(int $id): Color
    {
        $model = Color::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'Color not found.'));
        }

        return $model;
    }

    private function findFeature(int $id): CatalogFeature
    {
        $model = CatalogFeature::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'Attribute not found.'));
        }

        return $model;
    }

    private function findFeatureValue(int $id): CatalogFeatureValue
    {
        $model = CatalogFeatureValue::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'Attribute value not found.'));
        }

        return $model;
    }

    private function findBanner(int $id): HomeBanner
    {
        $model = HomeBanner::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'Banner not found.'));
        }

        return $model;
    }
}
