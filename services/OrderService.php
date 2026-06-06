<?php

declare(strict_types=1);

namespace app\services;

use app\components\api\ApiHttpException;
use app\components\api\CheckoutApiException;
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
        $order->status = ShopOrder::STATUS_DRAFT;
        $order->payment_status = 'pending';
        $order->expires_at = time() + (int) (Yii::$app->params['orderDraftTtl'] ?? 1800);
        $order->comment = $comment;
        $order->total_price = 0;
        $order->save(false);

        $total = 0.0;
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
        $order->save(false);

        return [
            'order_id' => (int) $order->id,
            'expires_at' => (int) $order->expires_at,
            'status' => $order->status,
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
            'expires_at' => (int) $order->expires_at,
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

        $order->delivery_method_id = (int) ($payload['delivery_method_id'] ?? DeliveryService::METHOD_CDEK_ID);
        $order->city_fias_id = (string) ($payload['city_fias_id'] ?? '');
        $order->destination_id = (string) ($payload['destination_id'] ?? '');
        $order->destination_address = (string) ($payload['destination_address'] ?? '');
        $order->delivery_address = $order->destination_address;
        $order->payment_method = (string) ($payload['payment_method'] ?? 'cash');
        $order->delivery_provider = DeliveryService::PROVIDER_CDEK;
        $order->delivery_method_code = DeliveryService::METHOD_CDEK_CODE;
        $order->status = ShopOrder::STATUS_AWAITING_PAYMENT;
        $order->payment_status = 'pending';

        $baseUrl = rtrim((string) (Yii::$app->params['paymentBaseUrl'] ?? 'https://pay.example.com'), '/');
        $order->payment_url = "{$baseUrl}/invoice/{$order->id}";
        $order->save(false);

        return [
            'order_id' => (int) $order->id,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'delivery_provider' => $order->delivery_provider,
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
            $row = [
                'id' => (int) $order->id,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'created_at' => gmdate('c', $order->created_at),
                'completed_at' => $order->completed_at !== null ? gmdate('c', $order->completed_at) : null,
                'delivery_address' => $order->delivery_address,
                'total_price' => (float) $order->total_price,
                'items' => [],
            ];

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
            if (!$fullItems) {
                $row['items_count'] = $itemsCount;
            }

            $tracking = OrderTracking::findOne(['order_id' => $order->id]);
            if ($tracking !== null) {
                $row['tracking'] = [
                    'provider' => $tracking->provider,
                    'track_number' => $tracking->track_number,
                    'current_status' => $tracking->current_status,
                    'description' => $tracking->description,
                    'current_city' => $tracking->current_city,
                    'updated_at' => $tracking->updated_at !== null ? gmdate('c', $tracking->updated_at) : null,
                    'expected_delivery' => $tracking->expected_delivery,
                ];
            }

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
        $items = [];
        foreach (OrderItem::find()->where(['order_id' => $order->id])->all() as $item) {
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

        return [
            'id' => (int) $order->id,
            'status' => $order->status,
            'expires_at' => $order->expires_at !== null ? (int) $order->expires_at : null,
            'total_price' => (float) $order->total_price,
            'payment_status' => $order->payment_status,
            'delivery_provider' => $order->delivery_provider,
            'delivery_method_code' => $order->delivery_method_code,
            'items' => $items,
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
