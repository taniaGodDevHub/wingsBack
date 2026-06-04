<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use app\services\ApiOwnerContext;
use app\services\CartService;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;
use yii\web\UnauthorizedHttpException;

class CartClientController extends BaseApiController
{
    private CartService $cart;

    public function init(): void
    {
        parent::init();
        $this->cart = new CartService();
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => CompositeAuth::class,
            'authMethods' => [HttpBearerAuth::class],
            'optional' => ['add', 'update', 'remove', 'list', 'count'],
        ];
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'add' => ['POST'],
                'update' => ['POST'],
                'remove' => ['POST'],
                'list' => ['GET'],
                'count' => ['POST'],
                'sync' => ['POST'],
            ],
        ];

        return $behaviors;
    }

    public function actionAdd(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $body = Yii::$app->request->bodyParams;
        $productId = (int) ($body['product_id'] ?? 0);
        $quantity = (int) ($body['quantity'] ?? 1);
        $cartId = isset($body['cart_id']) && $body['cart_id'] !== null ? (int) $body['cart_id'] : null;
        if ($productId <= 0) {
            throw new \InvalidArgumentException('product_id is required.');
        }

        return $this->cart->add($owner, $productId, $quantity, $cartId);
    }

    public function actionUpdate(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $body = Yii::$app->request->bodyParams;
        $productId = (int) ($body['product_id'] ?? 0);
        $quantity = (int) ($body['quantity'] ?? 1);
        $cartId = isset($body['cart_id']) ? (int) $body['cart_id'] : null;
        if ($productId <= 0) {
            throw new \InvalidArgumentException('product_id is required.');
        }

        return $this->cart->update($owner, $productId, $quantity, $cartId);
    }

    public function actionRemove(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $body = Yii::$app->request->bodyParams;
        $productId = (int) ($body['product_id'] ?? 0);
        $cartId = isset($body['cart_id']) ? (int) $body['cart_id'] : null;
        if ($productId <= 0) {
            throw new \InvalidArgumentException('product_id is required.');
        }

        return $this->cart->remove($owner, $productId, $cartId);
    }

    public function actionList(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);

        return $this->cart->list($owner, 1, 200);
    }

    public function actionCount(): array
    {
        $owner = ApiOwnerContext::resolve(false, true);
        $body = Yii::$app->request->bodyParams;
        $cartId = isset($body['cart_id']) ? (int) $body['cart_id'] : null;
        $items = $body['items'] ?? [];
        if (!is_array($items)) {
            throw new \InvalidArgumentException('items must be an array.');
        }

        return $this->cart->count($owner, $cartId, $items);
    }

    public function actionSync(): array
    {
        $user = Yii::$app->user->identity;
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        $body = Yii::$app->request->bodyParams;
        $sessionId = (string) ($body['session_id'] ?? Yii::$app->request->headers->get('X-Session-ID', ''));

        return $this->cart->sync($user, $sessionId);
    }
}
