<?php

declare(strict_types=1);

namespace app\services;

use app\components\api\ApiHttpException;
use app\components\api\CheckoutApiException;
use app\components\cdek\OrderTrackingWriter;
use app\models\OrderItem;
use app\models\OrderTracking;
use app\models\Product;
use app\models\ShopOrder;
use app\models\User;
use Yii;

class OrderService
{
    /** @param array<int, array<string, mixed>> $items */
    public function create(User $user, array $items, ?string $comment = null): array
    {
        if ($items === []) {
            throw new \InvalidArgumentException('items is required.');
        }

        $existing = ShopOrder::findDraftForUser((int) $user->id);
        if ($existing !== null) {
            $existing->delete();
        }

        $order = new ShopOrder();
        $order->user_id = (int) $user->id;
        $order->code = (new OrderCodeGenerator())->generate();
        $order->status = ShopOrder::STATUS_DRAFT;
        $order->payment_status = 'pending';
        $order->expires_at = time() + (int) (Yii::$app->params['orderDraftTtl'] ?? 1800);
        $order->comment = $comment;
        $order->total_price = 0;
        $order->blago_total = 0;
        $order->save(false);

        $total = 0.0;
        $blagoTotal = 0.0;
        foreach ($items as $row) {
            $productId = (int) ($row['product_id'] ?? 0);
            $quantity = max(1, (int) ($row['quantity'] ?? 1));
            $unitPrice = isset($row['unit_price']) ? (float) $row['unit_price'] : null;

            $product = Product::findAvailable($productId);
            if ($product === null) {
                throw ApiHttpException::notFound('Product not found');
            }
            if ($unitPrice === null) {
                $unitPrice = (float) $product->price;
            }

            $lineTotal = $unitPrice * $quantity;
            $total += $lineTotal;
            $blagoTotal += (float) $product->blago * $quantity;

            $orderItem = new OrderItem();
            $orderItem->order_id = (int) $order->id;
            $orderItem->product_id = $productId;
            $orderItem->name = $product->name;
            $orderItem->quantity = $quantity;
            $orderItem->unit_price = $unitPrice;
            $orderItem->total_price = $lineTotal;
            $orderItem->save(false);
        }

        $order->total_price = $total;
        $order->blago_total = round($blagoTotal, 2);
        $order->save(false);

        return [
            'order_id' => (int) $order->id,
            'code' => (string) $order->code,
            'expires_at' => (int) $order->expires_at,
            'status' => $order->status,
            'blago_total' => (float) $order->blago_total,
        ];
    }

    public function getActive(int $userId): array
    {
        $order = ShopOrder::findDraftForUser($userId);
        if ($order === null) {
            throw ApiHttpException::notFound('No active order');
        }

        return [
            'order_id' => (int) $order->id,
            'code' => (string) $order->code,
            'expires_at' => (int) $order->expires_at,
            'blago_total' => (float) $order->blago_total,
        ];
    }

    public function getDetails(int $orderId, int $userId): array
    {
        $order = $this->findUserOrder($orderId, $userId);

        return $this->formatOrderDetails($order);
    }

    /** @param array<string, mixed> $payload */
    public function confirm(int $orderId, int $userId, array $payload): array
    {
        $order = $this->findUserOrder($orderId, $userId);
        if (!$order->isEditable()) {
            throw CheckoutApiException::conflict('Заказ уже оформлен или срок резерва истек');
        }

        $deliveryMethodId = (int) ($payload['delivery_method_id'] ?? DeliveryService::METHOD_CDEK_PVZ_ID);
        $cityFiasId = (string) ($payload['city_fias_id'] ?? '');
        $isPvz = (bool) ($payload['is_pvz'] ?? $deliveryMethodId === DeliveryService::METHOD_CDEK_PVZ_ID);

        if ($cityFiasId === '') {
            throw new \InvalidArgumentException('city_fias_id is required.');
        }

        if ($order->delivery_cost === null) {
            (new DeliveryService())->calculateDelivery($orderId, $userId, $cityFiasId, $deliveryMethodId);
            $order->refresh();
        }

        $pvzCode = (string) ($payload['pvz_code'] ?? '');
        $destinationId = (string) ($payload['destination_id'] ?? '');

        $order->delivery_method_id = $deliveryMethodId;
        $order->city_fias_id = $cityFiasId;
        $order->destination_id = $destinationId;
        $order->destination_address = (string) ($payload['destination_address'] ?? '');
        $order->delivery_address = $order->destination_address;
        $order->payment_method = (string) ($payload['payment_method'] ?? 'cash');
        $order->delivery_provider = DeliveryService::PROVIDER_CDEK;
        $order->delivery_method_code = $deliveryMethodId === DeliveryService::METHOD_CDEK_COURIER_ID
            ? DeliveryService::CODE_CDEK_COURIER
            : DeliveryService::CODE_CDEK_PVZ;

        if ($isPvz) {
            $order->pvz_code = $pvzCode !== '' ? $pvzCode : ($destinationId !== '' ? $destinationId : null);
        } else {
            $order->pvz_code = null;
        }

        $itemsTotal = (float) OrderItem::find()->where(['order_id' => $order->id])->sum('total_price');
        $deliveryCost = (float) ($order->delivery_cost ?? 0);
        $order->total_price = $itemsTotal + $deliveryCost;

        $order->status = ShopOrder::STATUS_AWAITING_PAYMENT;
        $order->payment_status = 'pending';

        $baseUrl = rtrim((string) (Yii::$app->params['paymentBaseUrl'] ?? 'https://pay.example.com'), '/');
        $order->payment_url = "{$baseUrl}/invoice/{$order->id}";
        $order->save(false);

        OrderTrackingWriter::upsertEstimatedDelivery($order);

        if ((bool) (Yii::$app->params['cdekCreateOnConfirm'] ?? false)) {
            (new \app\components\cdek\ShipmentService())->registerShipment($order);
        }

        return [
            'order_id' => (int) $order->id,
            'code' => (string) $order->code,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'delivery_provider' => $order->delivery_provider,
            'delivery_cost' => $deliveryCost,
            'total_price' => (float) $order->total_price,
            'blago_total' => (float) $order->blago_total,
            'payment_url' => $order->payment_url,
        ];
    }

    public function purchases(int $userId, int $page, int $pageSize): array
    {
        $statuses = [
            ShopOrder::STATUS_PROCESSING,
            ShopOrder::STATUS_COMPLETED,
            ShopOrder::STATUS_DELIVERED,
            ShopOrder::STATUS_AWAITING_PAYMENT,
        ];

        return $this->listOrders($userId, $statuses, $page, $pageSize, true, true);
    }

    public function deliveries(int $userId, int $page, int $pageSize): array
    {
        $statuses = [
            ShopOrder::STATUS_DELIVERING,
            ShopOrder::STATUS_SHIPPED,
            ShopOrder::STATUS_DELIVERED,
        ];

        return $this->listOrders($userId, $statuses, $page, $pageSize, false, false);
    }

    /** @param string[] $statuses */
    private function listOrders(int $userId, array $statuses, int $page, int $pageSize, bool $fullItems, bool $includeFilters): array
    {
        $query = ShopOrder::find()
            ->where(['user_id' => $userId])
            ->andWhere(['status' => $statuses])
            ->andWhere(['not', ['status' => ShopOrder::STATUS_DRAFT]])
            ->orderBy(['created_at' => SORT_DESC]);

        $orders = $query->offset(max(0, ($page - 1) * $pageSize))->limit($pageSize)->all();
        $result = [];
        foreach ($orders as $order) {
            $order->populateRelation('items', OrderItem::find()->where(['order_id' => $order->id])->all());
            $tracking = OrderTracking::findOne(['order_id' => $order->id]);
            $row = $this->formatOrderCardBase($order, $tracking);
            $row['items'] = [];

            $itemsCount = 0;
            foreach ($order->items as $item) {
                $itemsCount += $item->quantity;
                $product = Product::find()->where(['id' => $item->product_id])->with(['images'])->one();
                $productInfo = $this->productInfo($product, $item->name);
                if ($fullItems) {
                    $row['items'][] = [
                        'id' => (int) $item->id,
                        'product_id' => (int) $item->product_id,
                        'quantity' => (int) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'total_price' => (float) $item->total_price,
                        'product_info' => $productInfo,
                    ];
                } else {
                    $row['items'][] = [
                        'id' => (int) $item->id,
                        'product_id' => (int) $item->product_id,
                        'product_info' => $productInfo,
                    ];
                }
            }
            $row['items_count'] = $itemsCount;

            $result[] = $row;
        }

        $response = ['orders' => $result];
        if ($includeFilters) {
            $response['available_filters'] = ['filters' => []];
        }

        return $response;
    }

    private function findUserOrder(int $orderId, int $userId): ShopOrder
    {
        $order = ShopOrder::findOne(['id' => $orderId, 'user_id' => $userId]);
        if ($order === null) {
            throw ApiHttpException::notFound('Order not found');
        }

        return $order;
    }

    private function formatOrderDetails(ShopOrder $order): array
    {
        $tracking = OrderTracking::findOne(['order_id' => $order->id]);
        $items = [];
        $itemsCount = 0;
        foreach (OrderItem::find()->where(['order_id' => $order->id])->all() as $item) {
            $itemsCount += $item->quantity;
            $items[] = [
                'id' => (int) $item->id,
                'order_item_id' => (int) $item->id,
                'product_id' => (int) $item->product_id,
                'name' => $item->name,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'delivery_label' => $item->delivery_label,
            ];
        }

        return array_merge($this->formatOrderCardBase($order, $tracking), [
            'expires_at' => $order->expires_at !== null ? (int) $order->expires_at : null,
            'delivery_provider' => $order->delivery_provider,
            'delivery_method_code' => $order->delivery_method_code,
            'items_count' => $itemsCount,
            'items' => $items,
        ]);
    }

    /** @return array<string, mixed> */
    private function formatOrderCardBase(ShopOrder $order, ?OrderTracking $tracking): array
    {
        $estimatedDelivery = $tracking?->expected_delivery;
        if ($order->completed_at !== null && ShopOrder::isDeliveryCompleted($order->status)) {
            $estimatedDelivery = date('Y-m-d', (int) $order->completed_at);
        }

        $row = [
            'id' => (int) $order->id,
            'code' => $order->code,
            'status' => $order->status,
            'status_label' => ShopOrder::statusLabel($order->status),
            'payment_status' => $order->payment_status,
            'created_at' => gmdate('c', $order->created_at),
            'completed_at' => $order->completed_at !== null ? gmdate('c', $order->completed_at) : null,
            'estimated_delivery' => $estimatedDelivery,
            'delivery_address' => $order->delivery_address,
            'total_price' => (float) $order->total_price,
            'blago_total' => (float) $order->blago_total,
            'show_details' => $order->status === ShopOrder::STATUS_COMPLETED,
            'timeline_steps' => $this->buildTimelineSteps($order, $estimatedDelivery),
        ];

        $formattedTracking = $this->formatTracking($tracking, $order);
        if ($formattedTracking !== null) {
            $row['tracking'] = $formattedTracking;
        }

        return $row;
    }

    /** @return list<array{key: string, label: string, date: string|null, completed: bool}> */
    private function buildTimelineSteps(ShopOrder $order, ?string $estimatedDelivery): array
    {
        $isDelivered = ShopOrder::isDeliveryCompleted($order->status);
        $deliveryDate = $order->completed_at !== null
            ? gmdate('c', (int) $order->completed_at)
            : $estimatedDelivery;

        return [
            [
                'key' => 'ordered',
                'label' => 'дата заказа',
                'date' => gmdate('c', $order->created_at),
                'completed' => true,
            ],
            [
                'key' => 'assembly',
                'label' => 'сборка',
                'date' => null,
                'completed' => ShopOrder::isAssemblyCompleted($order->status),
            ],
            [
                'key' => 'delivery',
                'label' => $isDelivered ? 'Доставлен' : 'Примерная доставка',
                'date' => $deliveryDate,
                'completed' => $isDelivered,
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    private function formatTracking(?OrderTracking $tracking, ?ShopOrder $order = null): ?array
    {
        if ($tracking === null) {
            return null;
        }

        $deliveryDate = null;
        if ($order !== null && $order->completed_at !== null && ShopOrder::isDeliveryCompleted($order->status)) {
            $deliveryDate = date('Y-m-d', (int) $order->completed_at);
        }

        return [
            'provider' => $tracking->provider,
            'track_number' => $tracking->track_number,
            'current_status' => $tracking->current_status,
            'description' => $tracking->description,
            'current_city' => $tracking->current_city,
            'updated_at' => $tracking->updated_at !== null ? gmdate('c', $tracking->updated_at) : null,
            'expected_delivery' => $tracking->expected_delivery,
            'delivery_date' => $deliveryDate,
        ];
    }

    /** @return array<string, mixed> */
    private function productInfo(?Product $product, string $fallbackName): array
    {
        if ($product === null) {
            return ['name' => $fallbackName, 'brand' => null, 'product_code' => null, 'images' => []];
        }

        $images = [];
        foreach ($product->images as $image) {
            $images[] = ['url' => $image->publicUrl];
        }

        return [
            'name' => $product->name,
            'brand' => $product->brand,
            'product_code' => $product->product_code,
            'images' => $images,
        ];
    }
}
