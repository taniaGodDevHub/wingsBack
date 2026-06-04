<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use app\services\ApiOwnerContext;
use app\services\FavoritesService;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;
use yii\web\UnauthorizedHttpException;

class FavoritesController extends BaseApiController
{
    private FavoritesService $favorites;

    public function init(): void
    {
        parent::init();
        $this->favorites = new FavoritesService();
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::class,
            'authMethods' => [HttpBearerAuth::class],
            'optional' => ['add', 'remove', 'check', 'list'],
        ];
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'add' => ['POST'],
                'remove' => ['POST'],
                'check' => ['POST'],
                'list' => ['GET'],
                'sync' => ['POST'],
            ],
        ];

        return $behaviors;
    }

    public function actionAdd(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $productId = (int) (Yii::$app->request->bodyParams['product_id'] ?? 0);
        if ($productId <= 0) {
            throw new \InvalidArgumentException('product_id is required.');
        }

        return $this->favorites->add($owner, $productId);
    }

    public function actionRemove(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $productId = (int) (Yii::$app->request->bodyParams['product_id'] ?? 0);
        if ($productId <= 0) {
            throw new \InvalidArgumentException('product_id is required.');
        }

        return $this->favorites->remove($owner, $productId);
    }

    public function actionCheck(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $productIds = Yii::$app->request->bodyParams['product_ids'] ?? [];
        if (!is_array($productIds)) {
            throw new \InvalidArgumentException('product_ids must be an array.');
        }

        return $this->favorites->check($owner, $productIds);
    }

    public function actionList(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $page = (int) Yii::$app->request->get('page', 1);
        $pageSize = (int) Yii::$app->request->get('page_size', 200);

        return $this->favorites->list($owner, $page, $pageSize);
    }

    public function actionSync(): array
    {
        $user = Yii::$app->user->identity;
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        $body = Yii::$app->request->bodyParams;
        $sessionId = (string) ($body['session_id'] ?? Yii::$app->request->headers->get('X-Session-ID', ''));

        return $this->favorites->sync($user, $sessionId);
    }
}
