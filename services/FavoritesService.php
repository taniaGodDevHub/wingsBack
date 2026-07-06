<?php

declare(strict_types=1);

namespace app\services;

use app\components\api\ApiHttpException;
use app\models\FavoriteItem;
use app\models\GuestSession;
use app\models\Product;
use app\services\catalog\ProductPresenter;
class FavoritesService
{
    public function add(ApiOwnerContext $owner, int $productId): array
    {
        $this->requireProduct($productId);
        $this->upsertFavorite($owner, $productId);

        return ['product_id' => $productId, 'is_favorite' => true];
    }

    public function remove(ApiOwnerContext $owner, int $productId): array
    {
        FavoriteItem::deleteAll($this->ownerCondition($owner, $productId));

        return ['product_id' => $productId, 'is_favorite' => false];
    }

    /** @param int[] $productIds */
    public function check(ApiOwnerContext $owner, array $productIds): array
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        $favorites = [];
        foreach ($productIds as $id) {
            $favorites[(string) $id] = false;
        }
        if ($productIds === []) {
            return ['favorites' => $favorites];
        }

        $rows = FavoriteItem::find()
            ->select('product_id')
            ->where($this->ownerWhere($owner))
            ->andWhere(['product_id' => $productIds])
            ->column();

        foreach ($rows as $productId) {
            $favorites[(string) $productId] = true;
        }

        return ['favorites' => $favorites];
    }

    public function list(ApiOwnerContext $owner, int $page, int $pageSize): array
    {
        $page = max(1, $page);
        $pageSize = min(200, max(1, $pageSize));

        $query = FavoriteItem::find()
            ->where($this->ownerWhere($owner))
            ->orderBy(['id' => SORT_DESC]);

        $total = (int) $query->count();
        $pages = max(1, (int) ceil($total / $pageSize));
        if ($page > $pages) {
            return [
                'page' => $page,
                'pages' => $pages,
                'total' => $total,
                'items' => [],
            ];
        }

        $items = $query->offset(($page - 1) * $pageSize)->limit($pageSize)->all();
        $productIds = array_map(static fn (FavoriteItem $i): int => (int) $i->product_id, $items);
        $products = Product::find()
            ->where(['id' => $productIds])
            ->with([
                'images',
                'categories',
                'sizes.size',
                'featureValues.feature',
                'productGroup.products.featureValues.feature',
            ])
            ->indexBy('id')
            ->all();

        $resultItems = [];
        foreach ($items as $item) {
            $product = $products[$item->product_id] ?? null;
            if ($product === null) {
                continue;
            }
            $resultItems[] = [
                'id' => (int) $item->id,
                'product' => ProductPresenter::detailItem($product),
            ];
        }

        return [
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'items' => $resultItems,
        ];
    }

    public function sync(\app\models\User $user, string $sessionId): array
    {
        if ($sessionId === '') {
            throw new \InvalidArgumentException('session_id is required.');
        }

        $userId = (int) $user->id;
        $guest = GuestSession::findOne(['session_id' => $sessionId]);
        $hasGuestItems = FavoriteItem::find()->where(['session_id' => $sessionId])->exists();
        if ($guest !== null && $guest->favorites_merged_at !== null && !$hasGuestItems) {
            return [
                'merged_count' => 0,
                'result_total' => (int) FavoriteItem::find()->where(['user_id' => $userId])->count(),
            ];
        }

        $guestItems = FavoriteItem::find()->where(['session_id' => $sessionId])->all();
        $merged = 0;

        foreach ($guestItems as $guestItem) {
            $exists = FavoriteItem::find()
                ->where(['user_id' => $userId, 'product_id' => $guestItem->product_id])
                ->exists();
            if ($exists) {
                $guestItem->delete();
                continue;
            }
            $guestItem->user_id = $userId;
            $guestItem->session_id = null;
            $guestItem->save(false);
            $merged++;
        }

        if ($guest !== null && ($merged > 0 || $guestItems !== [])) {
            $guest->favorites_merged_at = time();
            $guest->save(false);
        }

        $total = (int) FavoriteItem::find()->where(['user_id' => $userId])->count();

        return [
            'merged_count' => $merged,
            'result_total' => $total,
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

    private function upsertFavorite(ApiOwnerContext $owner, int $productId): void
    {
        $existing = FavoriteItem::findOne($this->ownerCondition($owner, $productId));
        if ($existing !== null) {
            return;
        }

        $item = new FavoriteItem();
        $item->product_id = $productId;
        if ($owner->userId !== null) {
            $item->user_id = $owner->userId;
        } else {
            $item->session_id = $owner->sessionId;
        }
        $item->created_at = time();
        $item->save(false);
    }

    /** @return array<string, mixed> */
    private function ownerCondition(ApiOwnerContext $owner, ?int $productId = null): array
    {
        $cond = $this->ownerWhere($owner);
        if ($productId !== null) {
            $cond['product_id'] = $productId;
        }

        return $cond;
    }

    /** @return array<string, mixed> */
    private function ownerWhere(ApiOwnerContext $owner): array
    {
        if ($owner->userId !== null) {
            return ['user_id' => $owner->userId];
        }

        return ['session_id' => $owner->sessionId];
    }
}
