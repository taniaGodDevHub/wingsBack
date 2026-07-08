<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use app\services\NewsSubscriptionService;
use OpenApi\Annotations as OA;
use Yii;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\MethodNotAllowedHttpException;

/**
 * @OA\Tag(
 *     name="Подписки",
 *     description="Подписка на рассылку новостей"
 * )
 *
 * @OA\Post(
 *     path="/api/subscriptions/newsletter",
 *     summary="Подписка на рассылку новостей",
 *     description="Подписывает email на новости. Если email найден в профиле, обновляется профильная подписка. Если профиль не найден, email сохраняется в отдельной таблице рассылки.",
 *     operationId="subscriptionsNewsletterSubscribe",
 *     tags={"Подписки"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/NewsSubscriptionRequest")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Подписка оформлена",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/NewsSubscriptionResponse")
 *         )
 *     )
 * )
 *
 * @OA\Delete(
 *     path="/api/subscriptions/newsletter",
 *     summary="Отмена подписки на рассылку новостей",
 *     description="Отписывает email от новостей. Если email найден в профиле, отключается профильная подписка. Если профиля нет, email удаляется из отдельной таблицы рассылки.",
 *     operationId="subscriptionsNewsletterUnsubscribe",
 *     tags={"Подписки"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/NewsUnsubscribeRequest")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Подписка отменена",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/NewsSubscriptionResponse")
 *         )
 *     )
 * )
 */
class SubscriptionsController extends BaseApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'newsletter' => ['POST', 'DELETE'],
            ],
        ];

        return $behaviors;
    }

    /** @return array<string, mixed> */
    public function actionNewsletter(): array
    {
        $email = (string) (Yii::$app->request->bodyParams['email'] ?? '');
        if ($email === '') {
            throw new BadRequestHttpException('email is required.');
        }

        $service = new NewsSubscriptionService();

        return match (Yii::$app->request->method) {
            'POST' => $service->subscribeByEmail($email),
            'DELETE' => $service->unsubscribeByEmail($email),
            default => throw new MethodNotAllowedHttpException(),
        };
    }
}
