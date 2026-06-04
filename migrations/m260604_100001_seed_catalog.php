<?php

use yii\db\Migration;

class m260604_100001_seed_catalog extends Migration
{
    public function safeUp(): void
    {
        $time = time();
        $cdn = 'https://cdn.example.com';

        $this->insert('{{%category}}', ['id' => 1, 'parent_id' => null, 'name' => 'Women', 'slug' => 'women', 'sort_order' => 1, 'is_active' => true]);
        $this->insert('{{%category}}', ['id' => 11, 'parent_id' => 1, 'name' => 'Hoodies', 'slug' => 'hoodies', 'sort_order' => 1, 'is_active' => true]);
        $this->insert('{{%category}}', ['id' => 12, 'parent_id' => 1, 'name' => 'Dresses', 'slug' => 'dresses', 'sort_order' => 2, 'is_active' => true]);

        $this->insert('{{%color}}', ['id' => 1001, 'name' => 'Черный', 'hex' => '#111111']);
        $this->insert('{{%color}}', ['id' => 1002, 'name' => 'Белый', 'hex' => '#F4F4F4']);

        $products = [
            [101, 'oversize-hoodie-black', 'Oversize Hoodie', 5990, 7490, true, true, 1, 1, 'unisex'],
            [102, 'classic-dress-red', 'Classic Dress', 8990, null, false, true, 0, 2, 'female'],
            [103, 'basic-tee-white', 'Basic Tee', 2990, 3990, true, false, 2, 0, 'unisex'],
            [104, 'slim-jeans-blue', 'Slim Jeans', 4990, null, false, true, 0, 3, 'male'],
            [105, 'wool-coat-grey', 'Wool Coat', 12990, 15990, true, true, 3, 4, 'female'],
            [106, 'sport-shorts-black', 'Sport Shorts', 3490, null, true, false, 4, 0, 'male'],
            [107, 'linen-shirt-beige', 'Linen Shirt', 4590, 5590, false, true, 0, 5, 'male'],
            [108, 'puffer-jacket-navy', 'Puffer Jacket', 9990, null, true, true, 5, 6, 'unisex'],
        ];

        foreach ($products as [$id, $slug, $name, $price, $old, $featured, $bestseller, $fSort, $bRank, $gender]) {
            $this->insert('{{%product}}', [
                'id' => $id,
                'slug' => $slug,
                'name' => $name,
                'price' => $price,
                'old_price' => $old,
                'currency' => 'RUB',
                'is_available' => true,
                'is_bestseller' => $bestseller,
                'is_featured_home' => $featured,
                'featured_sort' => $fSort,
                'bestseller_rank' => $bRank,
                'gender' => $gender,
                'search_text' => mb_strtolower($name . ' ' . $slug),
                'created_at' => $time,
                'updated_at' => $time,
            ]);
            $this->insert('{{%product_image}}', [
                'product_id' => $id,
                'image_url' => "{$cdn}/products/{$id}/main.jpg",
                'sort_order' => 1,
            ]);
        }

        $this->insert('{{%product_category}}', ['product_id' => 101, 'category_id' => 11]);
        $this->insert('{{%product_category}}', ['product_id' => 102, 'category_id' => 12]);
        $this->insert('{{%product_category}}', ['product_id' => 103, 'category_id' => 11]);
        $this->insert('{{%product_category}}', ['product_id' => 105, 'category_id' => 12]);

        $this->insert('{{%product_color}}', ['product_id' => 101, 'color_id' => 1001]);
        $this->insert('{{%product_color}}', ['product_id' => 103, 'color_id' => 1002]);

        foreach ([101 => ['S', 'M', 'L'], 102 => ['XS', 'S', 'M'], 103 => ['M', 'L', 'XL']] as $pid => $sizes) {
            foreach ($sizes as $size) {
                $this->insert('{{%product_size}}', ['product_id' => $pid, 'size_value' => $size]);
            }
        }

        $this->insert('{{%home_banner}}', ['image_url' => "{$cdn}/banners/home-1.jpg", 'sort_order' => 1, 'is_active' => true]);
        $this->insert('{{%home_banner}}', ['image_url' => "{$cdn}/banners/home-2.jpg", 'sort_order' => 2, 'is_active' => true]);
    }

    public function safeDown(): void
    {
        $this->delete('{{%home_banner}}');
        $this->delete('{{%product_size}}');
        $this->delete('{{%product_color}}');
        $this->delete('{{%product_category}}');
        $this->delete('{{%product_image}}');
        $this->delete('{{%product}}');
        $this->delete('{{%color}}');
        $this->delete('{{%category}}');
    }
}
