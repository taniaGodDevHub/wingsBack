<?php

declare(strict_types=1);

namespace app\services\admin;

use app\models\OrderItem;
use app\models\OrderTracking;
use app\models\ProductSize;
use app\models\ShopOrder;
use app\models\Size;
use app\models\User;
use app\models\UserAddress;
use yii\db\Expression;

final class AdminUserService
{
    /** @var array<int, string|null> */
    private static array $productSingleSizeCache = [];

    /** @return array<int, array{orders_count: int, orders_total: float, last_order_at: int|null, last_order_status: string|null}> */
    public function listStatsForUsers(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $stats = [];
        foreach ($userIds as $userId) {
            $stats[(int) $userId] = [
                'orders_count' => 0,
                'orders_total' => 0.0,
                'last_order_at' => null,
                'last_order_status' => null,
            ];
        }

        $aggregates = ShopOrder::find()
            ->select([
                'user_id',
                'orders_count' => new Expression('COUNT(*)'),
                'orders_total' => new Expression('COALESCE(SUM(total_price), 0)'),
                'last_order_at' => new Expression('MAX(created_at)'),
            ])
            ->where(['user_id' => $userIds])
            ->andWhere(['not', ['status' => ShopOrder::STATUS_DRAFT]])
            ->groupBy('user_id')
            ->asArray()
            ->all();

        foreach ($aggregates as $row) {
            $userId = (int) $row['user_id'];
            if (!isset($stats[$userId])) {
                continue;
            }
            $stats[$userId]['orders_count'] = (int) $row['orders_count'];
            $stats[$userId]['orders_total'] = (float) $row['orders_total'];
            $stats[$userId]['last_order_at'] = $row['last_order_at'] !== null ? (int) $row['last_order_at'] : null;
        }

        $lastOrders = ShopOrder::find()
            ->alias('o')
            ->innerJoin(
                ['latest' => ShopOrder::find()
                    ->select(['user_id', 'max_created' => new Expression('MAX(created_at)')])
                    ->where(['user_id' => $userIds])
                    ->andWhere(['not', ['status' => ShopOrder::STATUS_DRAFT]])
                    ->groupBy('user_id')],
                'o.user_id = latest.user_id AND o.created_at = latest.max_created',
            )
            ->select(['o.user_id', 'o.status'])
            ->asArray()
            ->all();

        foreach ($lastOrders as $row) {
            $userId = (int) $row['user_id'];
            if (isset($stats[$userId])) {
                $stats[$userId]['last_order_status'] = (string) $row['status'];
            }
        }

        return $stats;
    }

    /**
     * @return array{
     *     summary: array{
     *         orders_count: int,
     *         orders_total: float,
     *         completed_count: int,
     *         in_progress_count: int,
     *         average_order_total: float|null,
     *         last_order_at: int|null
     *     },
     *     orders: list<ShopOrder>,
     *     addresses: list<UserAddress>
     * }
     */
    public function getUserDetail(User $user): array
    {
        $orders = ShopOrder::find()
            ->where(['user_id' => (int) $user->id])
            ->andWhere(['not', ['status' => ShopOrder::STATUS_DRAFT]])
            ->with(['items', 'tracking'])
            ->orderBy(['created_at' => SORT_DESC])
            ->limit(50)
            ->all();

        $addresses = UserAddress::find()
            ->where(['user_id' => (int) $user->id])
            ->orderBy(['updated_at' => SORT_DESC])
            ->all();

        $ordersCount = count($orders);
        $ordersTotal = 0.0;
        $completedCount = 0;
        $inProgressCount = 0;
        $lastOrderAt = null;

        foreach ($orders as $order) {
            $ordersTotal += (float) $order->total_price;
            if ($lastOrderAt === null || (int) $order->created_at > $lastOrderAt) {
                $lastOrderAt = (int) $order->created_at;
            }

            if (in_array($order->status, [ShopOrder::STATUS_DELIVERED, ShopOrder::STATUS_COMPLETED], true)) {
                ++$completedCount;
            } elseif ($order->status !== ShopOrder::STATUS_CANCELLED && $order->status !== ShopOrder::STATUS_RETURNED) {
                ++$inProgressCount;
            }
        }

        return [
            'summary' => [
                'orders_count' => $ordersCount,
                'orders_total' => $ordersTotal,
                'completed_count' => $completedCount,
                'in_progress_count' => $inProgressCount,
                'average_order_total' => $ordersCount > 0 ? round($ordersTotal / $ordersCount, 2) : null,
                'last_order_at' => $lastOrderAt,
            ],
            'orders' => $orders,
            'addresses' => $addresses,
        ];
    }

    public static function formatMoney(float $amount): string
    {
        return number_format($amount, 0, '.', ' ') . ' ₽';
    }

    public static function formatDate(?int $timestamp): string
    {
        if ($timestamp === null || $timestamp <= 0) {
            return '—';
        }

        return date('d.m.Y', $timestamp);
    }

    public static function formatDateTime(?int $timestamp): string
    {
        if ($timestamp === null || $timestamp <= 0) {
            return '—';
        }

        return date('d.m.Y H:i', $timestamp);
    }

    public static function orderStatusBadgeClass(string $status): string
    {
        return match ($status) {
            ShopOrder::STATUS_COMPLETED, ShopOrder::STATUS_DELIVERED => 'bg-success-subtle text-success',
            ShopOrder::STATUS_CANCELLED, ShopOrder::STATUS_RETURNED => 'bg-danger-subtle text-danger',
            ShopOrder::STATUS_AWAITING_PAYMENT => 'bg-warning-subtle text-warning',
            ShopOrder::STATUS_DELIVERING, ShopOrder::STATUS_SHIPPED => 'bg-info-subtle text-info',
            default => 'bg-secondary-subtle text-secondary',
        };
    }

    /** @return list<array{id: int, name: string, size_value: string|null, quantity: int, unit_price: float, total_price: float}> */
    public static function orderItemsSummary(ShopOrder $order): array
    {
        $items = [];
        foreach ($order->items as $item) {
            if (!$item instanceof OrderItem) {
                continue;
            }
            $sizeValue = $item->size_value !== null && $item->size_value !== ''
                ? (string) $item->size_value
                : self::resolveProductSingleSizeValue((int) $item->product_id);
            $items[] = [
                'id' => (int) $item->id,
                'name' => (string) $item->name,
                'size_value' => $sizeValue,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
            ];
        }

        return $items;
    }

    private static function resolveProductSingleSizeValue(int $productId): ?string
    {
        if ($productId <= 0) {
            return null;
        }
        if (array_key_exists($productId, self::$productSingleSizeCache)) {
            return self::$productSingleSizeCache[$productId];
        }

        $sizes = ProductSize::find()
            ->alias('ps')
            ->innerJoin(['s' => Size::tableName()], 's.id = ps.size_id')
            ->where(['ps.product_id' => $productId])
            ->select('s.size_value')
            ->distinct()
            ->column();

        $resolved = count($sizes) === 1 ? (string) $sizes[0] : null;
        self::$productSingleSizeCache[$productId] = $resolved;

        return $resolved;
    }

    public static function orderDeliveryLabel(ShopOrder $order, ?OrderTracking $tracking): string
    {
        if ($tracking?->expected_delivery !== null && $tracking->expected_delivery !== '') {
            if ($order->completed_at !== null && ShopOrder::isDeliveryCompleted($order->status)) {
                return self::formatDate((int) $order->completed_at);
            }

            return $tracking->expected_delivery;
        }

        if ($order->delivery_period_min !== null && $order->delivery_period_max !== null) {
            return $order->delivery_period_min === $order->delivery_period_max
                ? (string) $order->delivery_period_max . ' дн.'
                : $order->delivery_period_min . '–' . $order->delivery_period_max . ' дн.';
        }

        return '—';
    }
}
