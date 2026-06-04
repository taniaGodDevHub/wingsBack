<?php

declare(strict_types=1);

namespace app\docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Cart", description="Shopping cart")
 *
 * @OA\Schema(
 *     schema="SyncRequest",
 *     required={"session_id"},
 *     @OA\Property(property="session_id", type="string")
 * )
 * @OA\Schema(
 *     schema="SyncOkResponse",
 *     @OA\Property(property="ok", type="boolean", example=true)
 * )
 *
 * @OA\Post(
 *     path="/api/cart-client/sync",
 *     tags={"Cart"},
 *     summary="Merge guest cart into user cart",
 *     security={{"bearerAuth":{}}, {"sessionId":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/SyncRequest")),
 *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/SyncOkResponse"))
 * )
 */
class CartApiDoc
{
}
