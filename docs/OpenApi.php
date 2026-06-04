<?php

declare(strict_types=1);

namespace app\docs;

use OpenApi\Annotations as OA;

/**
 * @OA\OpenApi(
 *     @OA\Info(
 *         title="Wings API",
 *         version="1.0.0",
 *         description="Auth, cart and favorites API"
 *     ),
 *     @OA\Server(url="/", description="API server")
 * )
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="sessionId",
 *     type="apiKey",
 *     in="header",
 *     name="X-Session-ID"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="refreshToken",
 *     type="apiKey",
 *     in="header",
 *     name="Refresh-Token"
 * )
 */
class OpenApiSpec
{
}
