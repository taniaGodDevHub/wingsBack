<?php

declare(strict_types=1);

namespace app\docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Favorites", description="Favorites list")
 *
 * @OA\Post(
 *     path="/api/favorites/sync",
 *     tags={"Favorites"},
 *     summary="Merge guest favorites into user favorites",
 *     security={{"bearerAuth":{}}, {"sessionId":{}}},
 *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/SyncRequest")),
 *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/SyncOkResponse"))
 * )
 */
class FavoritesApiDoc
{
}
