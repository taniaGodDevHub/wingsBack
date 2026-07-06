<?php

declare(strict_types=1);

namespace app\controllers\api;

use app\components\api\BaseApiController;
use app\models\ContactInfo;
use OpenApi\Annotations as OA;
use yii\filters\VerbFilter;

/**
 * @OA\Tag(
 *     name="Контакты",
 *     description="Контактные данные магазина"
 * )
 *
 * @OA\Get(
 *     path="/api/contacts",
 *     summary="Контакты магазина",
 *     description="Телефон, email, Telegram и время работы для связи с магазином. Авторизация не требуется.",
 *     operationId="contactsView",
 *     tags={"Контакты"},
 *     @OA\Response(
 *         response=200,
 *         description="Контактные данные",
 *         @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(ref="#/components/schemas/ContactInfoResponse")
 *         )
 *     )
 * )
 */
class ContactsController extends BaseApiController
{
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        $behaviors['verbs'] = [
            'class' => VerbFilter::class,
            'actions' => [
                'index' => ['GET'],
            ],
        ];

        return $behaviors;
    }

    /** @return array<string, mixed> */
    public function actionIndex(): array
    {
        return ContactInfo::singleton()->toApiArray();
    }
}
