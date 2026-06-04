<?php

declare(strict_types=1);

namespace app\docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Catalog", description="Catalog and search")
 * @OA\Tag(name="Favorites", description="Favorites")
 * @OA\Tag(name="Cart", description="Shopping cart")
 *
 * @OA\Get(path="/api/catalog/showcase", tags={"Catalog"}, summary="Home showcase",
 *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
 *     @OA\Parameter(name="page_size", in="query", @OA\Schema(type="integer"))
 * )
 * @OA\Get(path="/api/catalog/search/universal", tags={"Catalog"}, summary="Universal search",
 *     @OA\Parameter(name="search", in="query", required=true, @OA\Schema(type="string"))
 * )
 * @OA\Get(path="/api/catalog/categories/simple-tree", tags={"Catalog"}, summary="Category tree")
 * @OA\Get(path="/api/catalog/search", tags={"Catalog"}, summary="Catalog search with filters")
 * @OA\Get(path="/api/catalog/search/category/{slug}", tags={"Catalog"}, summary="Category page search",
 *     @OA\Parameter(name="slug", in="path", required=true, @OA\Schema(type="string"))
 * )
 */
class CatalogApiDoc
{
}
