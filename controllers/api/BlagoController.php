<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\ApiHttpException;
use app\components\api\BaseApiController;
use app\services\BlagoService;
use OpenApi\Annotations as OA;
use yii\filters\VerbFilter;

/**
 * @OA\Tag(
 *     name="Благо",
 *     description="Блок сбора блага на сайте"
 * )
 *
 * @OA\Get(
 *     path="/api/blago",
 *     summary="Блок «Благо»",
 *     description="Возвращает заголовок, даты сбора, сумму и изображение блока «Благо».

Авторизация не требуется. Если блок не заполнен — 404.",
 *     operationId="blagoView",
 *     tags={"Благо"},
 *     @OA\Response(
 *         response=200,
 *         description="Данные блока «Благо»",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/ShowcaseBlago")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Блок не найден или не заполнен",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(@OA\Property(property="detail", type="string", example="Blago not found"))
 *         )
 *     )
 * )
 *
 * @OA\Get(
 *     path="/api/blago/order/{code}",
 *     summary="Информация о заказе по коду благо",
 *     description="Возвращает номер заказа, дату, сумму заказа и сумму благо по коду `blagoXXXX`.",
 *     operationId="blagoOrderByCode",
 *     tags={"Благо"},
 *     @OA\Parameter(
 *         name="code",
 *         in="path",
 *         required=true,
 *         description="Код заказа вида blagoXXXX",
 *         @OA\Schema(type="string", example="blago7418")
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Данные заказа по коду благо",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/BlagoOrderInfoResponse")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Заказ с указанным кодом не найден",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(@OA\Property(property="detail", type="string", example="Order not found"))
 *         )
 *     )
 * )
 */
class BlagoController extends BaseApiController
{
    private BlagoService $blago;

    public function init(): void
    {
        parent::init();
        $this->blago = new BlagoService();
    }

    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'index' => ['GET'],
                'order' => ['GET'],
            ],
        ];

        return $behaviors;
    }

    /** @return array{title: string, collection_start_at: int, collection_end_at: int, amount: float, image_url: string} */
    public function actionIndex(): array
    {
        $data = $this->blago->getForApi();
        if ($data === null) {
            throw ApiHttpException::notFound('Blago not found');
        }

        return $data;
    }

    /** @return array{order_id: int, code: string, created_at: string, total_price: float, blago_total: float} */
    public function actionOrder(string $code): array
    {
        $data = $this->blago->getOrderByBlagoCode($code);
        if ($data === null) {
            throw ApiHttpException::notFound('Order not found');
        }

        return $data;
    }
}
