<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use app\services\DeliveryService;
use app\services\OrderService;
use Yii;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\VerbFilter;
use yii\web\UnauthorizedHttpException;

class OrdersController extends BaseApiController
{
    private OrderService $orders;
    private DeliveryService $delivery;

    public function init(): void
    {
        parent::init();
        $this->orders = new OrderService();
        $this->delivery = new DeliveryService();
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
        ];
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'create' => ['POST'],
                'active' => ['GET'],
                'view' => ['GET'],
                'confirm' => ['POST'],
                'delivery-options' => ['GET'],
                'purchases' => ['GET'],
                'deliveries' => ['GET'],
            ],
        ];

        return $behaviors;
    }

    private function requireUser(): \app\models\User
    {
        $user = Yii::$app->user->identity;
        if ($user === null) {
            throw new UnauthorizedHttpException('Unauthorized');
        }

        return $user;
    }

    public function actionCreate(): array
    {
        $user = $this->requireUser();
        $body = Yii::$app->request->bodyParams;

        return $this->orders->create($user, $body['items'] ?? [], $body['comment'] ?? null);
    }

    public function actionActive(): array
    {
        $user = $this->requireUser();

        return $this->orders->getActive((int) $user->id);
    }

    public function actionView(int $order_id): array
    {
        $user = $this->requireUser();

        return $this->orders->getDetails($order_id, (int) $user->id);
    }

    public function actionConfirm(int $order_id): array
    {
        $user = $this->requireUser();

        return $this->orders->confirm($order_id, (int) $user->id, Yii::$app->request->bodyParams);
    }

    public function actionDeliveryOptions(int $order_id): array
    {
        $user = $this->requireUser();
        $cityFiasId = Yii::$app->request->get('city_fias_id');

        return $this->delivery->deliveryOptions($order_id, (int) $user->id, $cityFiasId !== null ? (string) $cityFiasId : null);
    }

    public function actionPurchases(): array
    {
        $user = $this->requireUser();
        $page = (int) Yii::$app->request->get('page', 1);
        $pageSize = (int) Yii::$app->request->get('page_size', 999);

        return $this->orders->purchases((int) $user->id, $page, $pageSize);
    }

    public function actionDeliveries(): array
    {
        $user = $this->requireUser();
        $page = (int) Yii::$app->request->get('page', 1);
        $pageSize = (int) Yii::$app->request->get('page_size', 999);

        return $this->orders->deliveries((int) $user->id, $page, $pageSize);
    }
}
