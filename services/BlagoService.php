<?php

declare(strict_types=1);

namespace app\services;

use app\models\HomeBlago;

final class BlagoService
{
    /** @return array{title: string, collection_start_at: int, collection_end_at: int, amount: float, image_url: string}|null */
    public function getForApi(): ?array
    {
        $blago = HomeBlago::findOne(1);
        if (
            $blago === null
            || $blago->title === ''
            || $blago->getImagePublicUrl() === null
            || (int) $blago->collection_start_at <= 0
            || (int) $blago->collection_end_at <= 0
        ) {
            return null;
        }

        return $blago->toApiArray();
    }
}
