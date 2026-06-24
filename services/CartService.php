<?php

declare(strict_types=1);

namespace app\services;

use app\components\api\ApiHttpException;
use app\models\Cart;
use app\models\CartItem;
use app\models\GuestSession;
use app\models\Product;
use app\models\ProductSize;
use app\services\catalog\ProductPresenter;
use Yii;

class CartService
{
    public function add(ApiOwnerContext $owner, int $productId, string $sizeValue, int $quantity, ?int $cartId): array
    {
        $product = $this->requireProduct($productId);
        $sizeValue = $this->requireProductSize($product, $sizeValue);
        $quantity = max(1, $quantity);
        $cart = $this->resolveCart($owner, $cartId, true);

        $item = $this->findCartItem((int) $cart->id, $productId, $sizeValue);
        if ($item === null) {
            $item = new CartItem();
            $item->cart_id = (int) $cart->id;
            $item->product_id = $productId;
            $item->size_value = $sizeValue;
            $item->quantity = $quantity;
            $item->save(false);
        } else {
            $item->quantity += $quantity;
            $item->save(false);
        }

        return $this->buildItemActionResponse($item);
    }

    public function update(ApiOwnerContext $owner, int $productId, string $sizeValue, int $quantity, ?int $cartId): array
    {
        $sizeValue = $this->normalizeSizeValue($sizeValue);
        if ($sizeValue === '') {
            throw new \InvalidArgumentException('size_value is required.');
        }

        $cart = $this->resolveCart($owner, $cartId, false);
        if ($cart === null) {
            throw ApiHttpException::notFound('Cart not found');
        }

        $item = $this->findCartItem((int) $cart->id, $productId, $sizeValue);
        if ($item === null) {
            throw ApiHttpException::notFound('Product not found in cart');
        }

        if ($quantity <= 0) {
            $item->delete();

            return [
                'cart_id' => (int) $cart->id,
                'product_id' => $productId,
                'size_value' => $sizeValue,
                'quantity' => 0,
                'is_in_cart' => false,
            ];
        }

        $item->quantity = $quantity;
        $item->save(false);

        return $this->buildItemActionResponse($item);
    }

    public function remove(ApiOwnerContext $owner, int $productId, string $sizeValue, ?int $cartId): array
    {
        $sizeValue = $this->normalizeSizeValue($sizeValue);
        if ($sizeValue === '') {
            throw new \InvalidArgumentException('size_value is required.');
        }

        $cart = $this->resolveCart($owner, $cartId, false);
        if ($cart === null) {
            throw ApiHttpException::notFound('Cart not found');
        }

        CartItem::deleteAll([
            'cart_id' => $cart->id,
            'product_id' => $productId,
            'size_value' => $sizeValue,
        ]);

        return [
            'cart_id' => (int) $cart->id,
            'product_id' => $productId,
            'size_value' => $sizeValue,
            'is_in_cart' => false,
        ];
    }

    public function list(ApiOwnerContext $owner, int $page, int $pageSize): array
    {
        $page = max(1, $page);
        $pageSize = min(200, max(1, $pageSize));

        $cart = $this->resolveCart($owner, null, false);
        if ($cart === null) {
            return [
                'cart_id' => null,
                'items' => [],
                'summary' => [
                    'items_count' => 0,
                    'total_amount' => 0,
                ],
            ];
        }

        $items = CartItem::find()->where(['cart_id' => $cart->id])->all();
        $productIds = array_map(static fn (CartItem $i): int => (int) $i->product_id, $items);
        $products = Product::find()
            ->where(['id' => $productIds])
            ->with(['images'])
            ->indexBy('id')
            ->all();

        $responseItems = [];
        $totalQty = 0;
        $totalAmount = 0.0;
        foreach ($items as $item) {
            $product = $products[$item->product_id] ?? null;
            if ($product === null) {
                continue;
            }
            $totalQty += $item->quantity;
            $totalAmount += (float) $product->price * $item->quantity;
            $unitPrice = (float) $product->price;
            $responseItems[] = [
                'product_id' => (int) $item->product_id,
                'size_value' => $item->size_value,
                'cart_id' => (int) $cart->id,
                'quantity' => (int) $item->quantity,
                'unit_price' => $unitPrice,
                'price_show' => $unitPrice,
                'product_info' => ProductPresenter::cartProductInfo($product),
            ];
        }

        $offset = ($page - 1) * $pageSize;

        return [
            'cart_id' => (int) $cart->id,
            'items' => array_slice($responseItems, $offset, $pageSize),
            'summary' => [
                'items_count' => $totalQty,
                'total_amount' => round($totalAmount, 2),
            ],
        ];
    }

    /** @param array<int, mixed> $itemsInput */
    public function count(ApiOwnerContext $owner, ?int $cartId, array $itemsInput): array
    {
        $cart = $this->resolveCart($owner, $cartId, false);
        if ($cart === null || $itemsInput === []) {
            return [
                'selected_items_count' => 0,
                'selected_total_amount' => 0,
            ];
        }

        $count = 0;
        $total = 0.0;

        foreach ($itemsInput as $row) {
            if (is_array($row)) {
                $productId = (int) ($row['product_id'] ?? 0);
                $sizeValue = $this->normalizeSizeValue((string) ($row['size_value'] ?? ''));
                $quantity = max(1, (int) ($row['quantity'] ?? 1));
                $unitPrice = isset($row['unit_price']) ? (float) $row['unit_price'] : null;
                $item = $sizeValue !== ''
                    ? $this->findCartItem((int) $cart->id, $productId, $sizeValue)
                    : null;
                if ($item !== null) {
                    $quantity = $item->quantity;
                }
                $product = Product::findOne($productId);
                if ($product === null) {
                    continue;
                }
                if ($unitPrice === null) {
                    $unitPrice = (float) $product->price;
                }
                $count += $quantity;
                $total += $unitPrice * $quantity;
                continue;
            }

            $productId = (int) $row;
            $cartItems = CartItem::find()
                ->where(['cart_id' => $cart->id, 'product_id' => $productId])
                ->all();
            if ($cartItems === []) {
                continue;
            }
            $product = Product::findOne($productId);
            if ($product === null) {
                continue;
            }
            foreach ($cartItems as $cartItem) {
                $count += $cartItem->quantity;
                $total += (float) $product->price * $cartItem->quantity;
            }
        }

        return [
            'selected_items_count' => $count,
            'selected_total_amount' => round($total, 2),
        ];
    }

    public function sync(\app\models\User $user, string $sessionId): array
    {
        if ($sessionId === '') {
            throw new \InvalidArgumentException('session_id is required.');
        }

        $guestSession = GuestSession::findOne(['session_id' => $sessionId]);
        $guestCart = Cart::findActiveForSession($sessionId);
        if ($guestSession !== null && $guestSession->cart_merged_at !== null && $guestCart === null) {
            return $this->buildSyncResult($user, 0);
        }

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $guestCart = Cart::findActiveForSession($sessionId);
            $userCart = Cart::findActiveForUser((int) $user->id);
            if ($userCart === null) {
                $userCart = new Cart();
                $userCart->user_id = (int) $user->id;
                $userCart->is_active = true;
                $userCart->save(false);
            }

            $merged = 0;
            if ($guestCart !== null && (int) $guestCart->id !== (int) $userCart->id) {
                $guestItems = CartItem::find()->where(['cart_id' => $guestCart->id])->all();
                foreach ($guestItems as $guestItem) {
                    $existing = $this->findCartItem(
                        (int) $userCart->id,
                        (int) $guestItem->product_id,
                        $guestItem->size_value,
                    );
                    if ($existing !== null) {
                        $existing->quantity += $guestItem->quantity;
                        $existing->save(false);
                        $guestItem->delete();
                    } else {
                        $guestItem->cart_id = (int) $userCart->id;
                        $guestItem->save(false);
                    }
                    $merged++;
                }
                $guestCart->is_active = false;
                $guestCart->save(false);

                $guestSession ??= GuestSession::findOne(['session_id' => $sessionId]);
                if ($guestSession !== null) {
                    $guestSession->cart_merged_at = time();
                    $guestSession->save(false);
                }
            }

            $result = $this->buildSyncResult($user, $merged, (int) $userCart->id);
            $transaction->commit();

            return $result;
        } catch (\Throwable $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /** @return array{merged_items_count: int, result_cart_id: int, result_items_count: int} */
    private function buildSyncResult(\app\models\User $user, int $merged, ?int $cartId = null): array
    {
        $userCart = $cartId !== null
            ? Cart::findOne($cartId)
            : Cart::findActiveForUser((int) $user->id);

        if ($userCart === null) {
            $userCart = new Cart();
            $userCart->user_id = (int) $user->id;
            $userCart->is_active = true;
            $userCart->save(false);
        }

        $itemsCount = (int) CartItem::find()->where(['cart_id' => $userCart->id])->sum('quantity') ?: 0;

        return [
            'merged_items_count' => $merged,
            'result_cart_id' => (int) $userCart->id,
            'result_items_count' => $itemsCount,
        ];
    }

    private function requireProduct(int $productId): Product
    {
        $product = Product::findAvailable($productId);
        if ($product === null) {
            throw ApiHttpException::notFound('Product not found');
        }

        return $product;
    }

    private function normalizeSizeValue(string $sizeValue): string
    {
        return trim($sizeValue);
    }

    private function requireProductSize(Product $product, string $sizeValue): string
    {
        $sizeValue = $this->normalizeSizeValue($sizeValue);
        if ($sizeValue === '') {
            throw new \InvalidArgumentException('size_value is required.');
        }

        $exists = ProductSize::find()
            ->alias('ps')
            ->innerJoin(['s' => \app\models\Size::tableName()], 's.id = ps.size_id')
            ->where([
                'ps.product_id' => $product->id,
                's.size_value' => $sizeValue,
                'ps.is_in_stock' => true,
            ])
            ->exists();
        if (!$exists) {
            throw new \InvalidArgumentException('size_value is not available for this product.');
        }

        return $sizeValue;
    }

    private function findCartItem(int $cartId, int $productId, string $sizeValue): ?CartItem
    {
        return CartItem::findOne([
            'cart_id' => $cartId,
            'product_id' => $productId,
            'size_value' => $sizeValue,
        ]);
    }

    /** @return array{cart_id: int, product_id: int, size_value: string, quantity: int, is_in_cart: true} */
    private function buildItemActionResponse(CartItem $item): array
    {
        return [
            'cart_id' => (int) $item->cart_id,
            'product_id' => (int) $item->product_id,
            'size_value' => $item->size_value,
            'quantity' => (int) $item->quantity,
            'is_in_cart' => true,
        ];
    }

    private function resolveCart(ApiOwnerContext $owner, ?int $cartId, bool $create): ?Cart
    {
        if ($cartId !== null) {
            $cart = Cart::findOne(['id' => $cartId, 'is_active' => true]);
            if ($cart === null) {
                return null;
            }
            if ($owner->userId !== null && (int) $cart->user_id !== $owner->userId) {
                return null;
            }
            if ($owner->userId === null && $cart->session_id !== $owner->sessionId) {
                return null;
            }

            return $cart;
        }

        if ($owner->userId !== null) {
            $cart = Cart::findActiveForUser($owner->userId);
        } else {
            $cart = Cart::findActiveForSession((string) $owner->sessionId);
        }

        if ($cart === null && $create) {
            $cart = new Cart();
            $cart->is_active = true;
            if ($owner->userId !== null) {
                $cart->user_id = $owner->userId;
            } else {
                $cart->session_id = $owner->sessionId;
            }
            $cart->save(false);
        }

        return $cart;
    }
}
