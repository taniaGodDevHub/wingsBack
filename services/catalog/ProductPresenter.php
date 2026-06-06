<?php

declare(strict_types=1);

namespace app\services\catalog;

use app\models\Product;

final class ProductPresenter
{
    /**
     * @param Product[] $products
     */
    public static function showcaseItems(array $products): array
    {
        return array_map([self::class, 'showcaseItem'], $products);
    }

    public static function showcaseItem(Product $product): array
    {
        $item = self::baseItem($product);
        $item['images'] = self::imagesDetailed($product);
        $item['is_bestseller'] = (bool) $product->is_bestseller;
        $item['is_featured_home'] = (bool) $product->is_featured_home;

        return $item;
    }

    /**
     * @param Product[] $products
     */
    public static function searchItems(array $products): array
    {
        return array_map(static function (Product $product): array {
            $item = self::baseItem($product);
            $item['images'] = self::imagesDetailed($product);
            $item['sizes'] = $product->getSizeValues();
            $item['colors'] = $product->getColorsData();
            $item['gender'] = $product->gender;

            return $item;
        }, $products);
    }

    public static function cartProductInfo(Product $product): array
    {
        $images = [];
        foreach ($product->images as $image) {
            $images[] = ['url' => $image->publicUrl];
        }

        return [
            'id' => (int) $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'brand' => $product->brand,
            'images' => $images,
        ];
    }

    public static function compact(Product $product): array
    {
        return [
            'id' => (int) $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'images' => self::imagesUrls($product),
            'price' => (float) $product->price,
            'currency' => 'RUB',
        ];
    }

    public static function universalProduct(Product $product): array
    {
        return [
            'id' => (int) $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'images' => self::imagesUrls($product),
            'categories' => self::categories($product),
            'price' => (float) $product->price,
            'currency' => 'RUB',
            'is_available' => (bool) $product->is_available,
        ];
    }

    private static function baseItem(Product $product): array
    {
        $price = (float) $product->price;
        $oldPrice = $product->old_price !== null ? (float) $product->old_price : null;
        $discount = null;
        if ($oldPrice !== null && $oldPrice > $price && $oldPrice > 0) {
            $discount = (int) round((1 - $price / $oldPrice) * 100);
        }

        return [
            'id' => (int) $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'price' => $price,
            'old_price' => $oldPrice,
            'discount_percent' => $discount,
            'currency' => 'RUB',
            'is_available' => (bool) $product->is_available,
            'categories' => self::categories($product),
        ];
    }

    private static function categories(Product $product): array
    {
        $result = [];
        foreach ($product->categories as $category) {
            $result[] = [
                'id' => (int) $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ];
        }

        return $result;
    }

    private static function imagesDetailed(Product $product): array
    {
        $images = [];
        foreach ($product->images as $image) {
            $images[] = [
                'id' => (int) $image->id,
                'image_url' => $image->publicUrl,
                'sort_order' => (int) $image->sort_order,
            ];
        }

        return $images;
    }

    private static function imagesUrls(Product $product): array
    {
        $urls = [];
        foreach ($product->images as $image) {
            $urls[] = $image->publicUrl;
        }

        return $urls;
    }
}
