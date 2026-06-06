<?php

declare(strict_types=1);

namespace app\controllers\admin;

use app\models\CartItem;
use app\models\CatalogFeature;
use app\models\CatalogFeatureValue;
use app\models\Category;
use app\models\Color;
use app\models\FavoriteItem;
use app\models\Gender;
use app\models\Product;
use app\models\ProductSize;
use app\services\ProductImageUploadService;
use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\VerbFilter;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\web\UploadedFile;

class ProductController extends BaseAdminController
{
    private ?ProductImageUploadService $imageUploadService = null;

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
                'delete-image' => ['POST'],
                'upload-images' => ['POST'],
                'reorder-images' => ['POST'],
            ],
        ];

        return $behaviors;
    }

    public function actionIndex(): string
    {
        $this->view->title = Yii::t('app', 'Products');

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider([
                'query' => Product::find()->with(['images', 'sizes'])->orderBy(['id' => SORT_DESC]),
                'pagination' => ['pageSize' => 20],
            ]),
        ]);
    }

    public function actionView(int $id): string
    {
        $model = $this->findModel($id);
        $this->view->title = Yii::t('app', 'Product #{id}', ['id' => $id]);

        return $this->render('view', ['model' => $model]);
    }

    public function actionCreate(): string|Response
    {
        $model = new Product();
        $model->is_available = true;

        if ($this->loadProductForm($model) && $model->save()) {
            $this->saveProductCategory($model);
            $this->saveProductFeatureValues($model);
            $this->saveProductSizes($model);
            $this->processImageUploads($model);
            Yii::$app->session->setFlash('success', Yii::t('app', 'Saved successfully.'));

            return $this->redirect(['index']);
        }

        $this->view->title = Yii::t('app', 'Create product');

        return $this->render('form', $this->formViewParams($model));
    }

    public function actionDelete(int $id): Response
    {
        $model = $this->findModel($id);

        foreach ($model->images as $image) {
            $this->imageUpload()->deleteImage($image);
        }

        CartItem::deleteAll(['product_id' => $id]);
        FavoriteItem::deleteAll(['product_id' => $id]);
        $model->delete();

        Yii::$app->session->setFlash('success', Yii::t('app', 'Product deleted.'));

        return $this->redirect(['index']);
    }

    public function actionUpdate(int $id): string|Response
    {
        $model = $this->findModel($id);

        if ($this->loadProductForm($model) && $model->save()) {
            $this->saveProductCategory($model);
            $this->saveProductFeatureValues($model);
            $this->saveProductSizes($model);
            $this->processImageUploads($model);
            Yii::$app->session->setFlash('success', Yii::t('app', 'Saved successfully.'));

            return $this->redirect(['index']);
        }

        $this->view->title = Yii::t('app', 'Edit product');

        return $this->render('form', $this->formViewParams($model));
    }

    public function actionUploadImages(int $id): Response
    {
        $model = $this->findModel($id);
        $files = UploadedFile::getInstancesByName('productImages');
        $errors = $this->imageUpload()->uploadMany($model, $files);

        if (Yii::$app->request->isAjax) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            if ($errors !== []) {
                return $this->asJson(['success' => false, 'errors' => $errors]);
            }

            return $this->asJson([
                'success' => true,
                'carouselHtml' => $this->renderCarouselPartial($id),
            ]);
        }

        if ($errors !== []) {
            Yii::$app->session->setFlash('error', implode(' ', $errors));
        } else {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Images uploaded.'));
        }

        return $this->redirect(['update', 'id' => $model->id]);
    }

    public function actionDeleteImage(int $productId = 0, int $imageId = 0): Response
    {
        $request = Yii::$app->request;
        $productId = $productId > 0
            ? $productId
            : (int) $request->post('productId', $request->get('productId', $request->post('id', $request->get('id', 0))));
        $imageId = $imageId > 0
            ? $imageId
            : (int) $request->post('imageId', $request->get('imageId', 0));

        if ($productId <= 0 || $imageId <= 0) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            return $this->asJson([
                'success' => false,
                'message' => Yii::t('app', 'Image not found.'),
                'error' => Yii::t('app', 'Image not found.'),
                'carouselHtml' => null,
            ]);
        }

        $model = $this->findModel($productId);
        $image = $this->imageUpload()->findImageForProduct((int) $model->id, $imageId);

        if ($image === null) {
            return $this->deleteImageJsonResponse(
                $productId,
                false,
                Yii::t('app', 'Image not found.'),
            );
        }

        $this->imageUpload()->deleteImage($image);

        return $this->deleteImageJsonResponse($productId, true, Yii::t('app', 'Image deleted.'));
    }

    public function actionReorderImages(int $id = 0): Response
    {
        $request = Yii::$app->request;
        $productId = $id > 0
            ? $id
            : (int) $request->post('productId', $request->get('productId', $request->post('id', 0)));

        if ($productId <= 0) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            return $this->asJson([
                'success' => false,
                'error' => Yii::t('app', 'Product not found.'),
                'carouselHtml' => null,
            ]);
        }

        $this->findModel($productId);
        $imageIds = $request->post('imageIds', []);
        if (!is_array($imageIds)) {
            $imageIds = $imageIds !== '' && $imageIds !== null ? [(int) $imageIds] : [];
        }
        $imageIds = array_values(array_filter(array_map('intval', $imageIds)));

        $error = $this->imageUpload()->reorderImages($productId, $imageIds);

        if ($this->isJsonDeleteRequest()) {
            Yii::$app->response->format = Response::FORMAT_JSON;

            if ($error !== null) {
                return $this->asJson([
                    'success' => false,
                    'error' => $error,
                    'carouselHtml' => null,
                ]);
            }

            return $this->asJson([
                'success' => true,
                'message' => Yii::t('app', 'Image order saved.'),
                'carouselHtml' => $this->renderCarouselPartial($productId),
            ]);
        }

        if ($error !== null) {
            Yii::$app->session->setFlash('error', $error);
        } else {
            Yii::$app->session->setFlash('success', Yii::t('app', 'Image order saved.'));
        }

        $redirect = (string) $request->post('redirect', 'update');

        return $this->redirect($redirect === 'view'
            ? ['view', 'id' => $productId]
            : ['update', 'id' => $productId]);
    }

    private function deleteImageJsonResponse(int $productId, bool $success, string $message): Response
    {
        if (!$this->isJsonDeleteRequest()) {
            Yii::$app->session->setFlash($success ? 'success' : 'error', $message);
            $redirect = (string) Yii::$app->request->post('redirect', 'update');

            return $this->redirect($redirect === 'view'
                ? ['view', 'id' => $productId]
                : ['update', 'id' => $productId]);
        }

        Yii::$app->response->format = Response::FORMAT_JSON;

        return $this->asJson([
            'success' => $success,
            'message' => $message,
            'error' => $success ? null : $message,
            'carouselHtml' => $this->renderCarouselPartial($productId),
        ]);
    }

    private function isJsonDeleteRequest(): bool
    {
        $request = Yii::$app->request;
        if ($request->isAjax) {
            return true;
        }

        $accept = (string) $request->headers->get('Accept', '');

        return str_contains($accept, 'application/json');
    }

    private function renderCarouselPartial(int $productId): string
    {
        $redirect = (string) Yii::$app->request->post('redirect', 'update');
        if (!in_array($redirect, ['update', 'view'], true)) {
            $redirect = 'update';
        }

        return $this->renderPartial('_imageCarousel', [
            'model' => $this->findModel($productId),
            'carouselId' => 'product-images-carousel',
            'allowDelete' => true,
            'redirectAction' => $redirect,
            'ajaxDelete' => true,
        ]);
    }

    private function loadProductForm(Product $model): bool
    {
        if (!$model->load(Yii::$app->request->post())) {
            return false;
        }

        $post = Yii::$app->request->post('Product', []);
        if (!isset($post['sizeValuesInStock'])) {
            $model->sizeValuesInStock = [];
        }

        return true;
    }

    private function saveProductCategory(Product $product): void
    {
        $product->unlinkAll('categories', true);

        $categoryId = $product->categoryId;
        if ($categoryId === null || $categoryId <= 0) {
            return;
        }

        $category = Category::findOne(['id' => $categoryId]);
        if ($category !== null) {
            $product->link('categories', $category);
        }
    }

    private function saveProductFeatureValues(Product $product): void
    {
        $product->unlinkAll('featureValues', true);

        foreach ($product->featureValueByFeatureId as $featureId => $valueId) {
            $featureId = (int) $featureId;
            $valueId = (int) $valueId;
            if ($featureId <= 0 || $valueId <= 0) {
                continue;
            }

            $feature = CatalogFeature::findOne($featureId);
            if ($feature !== null && $feature->isColor()) {
                $color = Color::findOne($valueId);
                if ($color === null) {
                    continue;
                }

                $value = CatalogFeatureValue::ensureForColor($color);
                if ($value !== null) {
                    $product->link('featureValues', $value);
                }
                continue;
            }

            $value = CatalogFeatureValue::findOne(['id' => $valueId, 'feature_id' => $featureId]);
            if ($value !== null) {
                $product->link('featureValues', $value);
            }
        }
    }

    private function saveProductSizes(Product $product): void
    {
        ProductSize::deleteAll(['product_id' => $product->id]);

        $values = is_array($product->sizeValuesInStock) ? $product->sizeValuesInStock : [];
        $allowed = array_flip(ProductSize::getStandardSizeValues());

        foreach (array_unique($values) as $sizeValue) {
            $sizeValue = trim((string) $sizeValue);
            if ($sizeValue === '' || !isset($allowed[$sizeValue])) {
                continue;
            }

            $size = new ProductSize();
            $size->product_id = (int) $product->id;
            $size->size_value = $sizeValue;
            $size->save(false);
        }
    }

    /** @return array<string, mixed> */
    private function formViewParams(Product $model): array
    {
        return [
            'model' => $model,
            'categoryOptions' => Category::getDropdownOptions(),
            'genderOptions' => Gender::getDropdownOptions(),
            'catalogFeatures' => CatalogFeature::findAllForAdminForm(),
        ];
    }

    private function processImageUploads(Product $model): void
    {
        $files = UploadedFile::getInstancesByName('productImages');
        $errors = $this->imageUpload()->uploadMany($model, $files);
        if ($errors !== []) {
            Yii::$app->session->setFlash('error', implode(' ', $errors));
        }
    }

    private function imageUpload(): ProductImageUploadService
    {
        if ($this->imageUploadService === null) {
            $this->imageUploadService = new ProductImageUploadService();
        }

        return $this->imageUploadService;
    }

    private function findModel(int $id): Product
    {
        $model = Product::find()
            ->with(['images', 'categories', 'sizes', 'featureValues.feature'])
            ->where(['id' => $id])
            ->one();
        if ($model === null) {
            throw new NotFoundHttpException(Yii::t('app', 'Product not found.'));
        }

        return $model;
    }
}
