<?php

declare(strict_types=1);

namespace app\components;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
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

    /**
     * @param callable(ActiveQuery): void|null $scopeExists
     */
    public static function assignUniqueSlug(
        ActiveRecord $model,
        string $nameAttribute,
        string $slugAttribute,
        string $fallback,
        ?callable $scopeExists = null,
    ): void {
        if (!$model->hasAttribute($slugAttribute)) {
            return;
        }

        $current = $model->{$slugAttribute};
        if ($current !== '' && $current !== null) {
            return;
        }

        $base = self::fromName((string) $model->{$nameAttribute}, $fallback);
        $model->{$slugAttribute} = self::makeUnique($base, function (string $slug) use ($model, $slugAttribute, $scopeExists): bool {
            $query = $model::find()->where([$slugAttribute => $slug]);
            if (!$model->isNewRecord) {
                $query->andWhere(['<>', 'id', $model->id]);
            }
            if ($scopeExists !== null) {
                $scopeExists($query);
            }

            return $query->exists();
        });
    }
}
