<?php

declare(strict_types=1);

namespace app\components;

use yii\helpers\Inflector;

final class SlugHelper
{
    public static function fromName(string $name, string $fallback = 'item'): string
    {
        return Inflector::slug($name) ?: $fallback;
    }

    public static function makeUnique(string $base, callable $exists): string
    {
        $slug = $base;
        $suffix = 1;

        while ($exists($slug)) {
            $slug = $base . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }
}
