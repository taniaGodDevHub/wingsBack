<?php

declare(strict_types=1);

namespace app\services;

use app\models\News;

class NewsService
{
    /**
     * @return array{
     *     page: int,
     *     pages: int,
     *     total: int,
     *     items: list<array{id:int,title:string,slug:string,image_url:string}>
     * }
     */
    public function list(int $page, int $pageSize): array
    {
        $page = max(1, $page);
        $pageSize = min(100, max(1, $pageSize));

        $query = News::find()
            ->where(['is_published' => true])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]);

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

        $items = $query
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->all();

        return [
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'items' => array_map(static fn (News $news): array => $news->toApiCard(), $items),
        ];
    }
}
