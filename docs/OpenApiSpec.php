<?php

declare(strict_types=1);

namespace app\docs;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="Wings API",
 *         version="1.0.0",
 *         description="REST API интернет-магазина: авторизация, каталог, корзина, избранное, заказы и доставка"
 *     ),
 *     @OA\Server(url="/", description="API-сервер")
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="JWT access token в заголовке Authorization: Bearer {token}"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sessionId",
 *     type="apiKey",
 *     in="header",
 *     name="X-Session-ID",
 *     description="ID гостевой сессии для корзины и избранного без авторизации"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="refreshToken",
 *     type="apiKey",
 *     in="header",
 *     name="Refresh-Token",
 *     description="Refresh token для обновления JWT"
 * )
 */
class OpenApiSpec
{
}
