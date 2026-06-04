<?php

declare(strict_types=1);

namespace app\docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Orders", description="Checkout and orders")
 * @OA\Tag(name="Delivery", description="CDEK delivery")
 * @OA\Tag(name="DaData", description="Address suggestions")
 *
 * @OA\Post(path="/api/orders/create", tags={"Orders"}, summary="Create draft order")
 * @OA\Get(path="/api/orders/active", tags={"Orders"}, summary="Active draft order")
 * @OA\Get(path="/api/orders/{order_id}", tags={"Orders"}, summary="Order details")
 * @OA\Post(path="/api/orders/{order_id}/confirm", tags={"Orders"}, summary="Confirm order")
 * @OA\Post(path="/api/delivery/calculate-delivery", tags={"Delivery"}, summary="Calculate delivery")
 */
class OrdersApiDoc
{
}
