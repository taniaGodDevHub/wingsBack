<?php

use yii\db\Migration;
use yii\db\Query;
use yii\helpers\Inflector;

class m260623_100000_add_color_slug extends Migration
{
    public function safeUp(): void
    {
        $schema = $this->db->getTableSchema('{{%color}}', true);
        if ($schema === null) {
            return;
        }

        if (!isset($schema->columns['slug'])) {
            $this->addColumn('{{%color}}', 'slug', $this->string(255)->null()->after('name'));
        }

        foreach ((new Query())->from('{{%color}}')->all($this->db) as $row) {
            if (!empty($row['slug'])) {
                continue;
            }

            $slug = $this->uniqueColorSlug(Inflector::slug((string) $row['name']) ?: 'color', (int) $row['id']);
            $this->update('{{%color}}', ['slug' => $slug], ['id' => $row['id']]);
        }

        $schema = $this->db->getTableSchema('{{%color}}', true);
        if ($schema !== null && isset($schema->columns['slug']) && $schema->columns['slug']->allowNull) {
            $this->alterColumn('{{%color}}', 'slug', $this->string(255)->notNull());
        }

        if (!$this->indexExists('{{%color}}', 'idx-color-slug')) {
            $this->createIndex('idx-color-slug', '{{%color}}', 'slug', true);
        }

        $colorFeatureId = (new Query())
            ->from('{{%catalog_feature}}')
            ->where(['code' => 'color'])
            ->select('id')
            ->scalar($this->db);

        if ($colorFeatureId === false) {
            return;
        }

        $colorFeatureId = (int) $colorFeatureId;
        $valueSchema = $this->db->getTableSchema('{{%catalog_feature_value}}', true);
        if ($valueSchema === null || !isset($valueSchema->columns['slug'])) {
            return;
        }

        $this->deduplicateColorFeatureValuesByName($colorFeatureId);

        foreach ((new Query())->from('{{%color}}')->all($this->db) as $color) {
            $valueId = $this->findColorFeatureValueId($colorFeatureId, $color);
            if ($valueId === null) {
                continue;
            }

            $desiredSlug = (string) $color['slug'];
            $slug = $this->uniqueFeatureValueSlug($desiredSlug, $colorFeatureId, $valueId);
            $currentSlug = (new Query())
                ->from('{{%catalog_feature_value}}')
                ->where(['id' => $valueId])
                ->select('slug')
                ->scalar($this->db);

            if ($currentSlug === $slug) {
                continue;
            }

            $this->update('{{%catalog_feature_value}}', ['slug' => $slug], ['id' => $valueId]);
        }
    }

    public function safeDown(): void
    {
        $schema = $this->db->getTableSchema('{{%color}}', true);
        if ($schema === null || !isset($schema->columns['slug'])) {
            return;
        }

        if ($this->indexExists('{{%color}}', 'idx-color-slug')) {
            $this->dropIndex('idx-color-slug', '{{%color}}');
        }
        $this->dropColumn('{{%color}}', 'slug');
    }

    private function deduplicateColorFeatureValuesByName(int $featureId): void
    {
        $rows = (new Query())
            ->from('{{%catalog_feature_value}}')
            ->where(['feature_id' => $featureId])
            ->orderBy(['id' => SORT_ASC])
            ->all($this->db);

        $byName = [];
        foreach ($rows as $row) {
            $byName[(string) $row['name']][] = $row;
        }

        foreach ($byName as $group) {
            if (count($group) <= 1) {
                continue;
            }

            $canonicalId = (int) $group[0]['id'];
            for ($i = 1, $count = count($group); $i < $count; ++$i) {
                $this->mergeFeatureValueLinks($canonicalId, (int) $group[$i]['id']);
                $this->delete('{{%catalog_feature_value}}', ['id' => (int) $group[$i]['id']]);
            }
        }
    }

    private function mergeFeatureValueLinks(int $targetId, int $sourceId): void
    {
        foreach ((new Query())
            ->from('{{%product_feature_value}}')
            ->where(['feature_value_id' => $sourceId])
            ->all($this->db) as $link) {
            $productId = (int) $link['product_id'];
            $exists = (new Query())
                ->from('{{%product_feature_value}}')
                ->where([
                    'product_id' => $productId,
                    'feature_value_id' => $targetId,
                ])
                ->exists($this->db);

            if ($exists) {
                $this->delete('{{%product_feature_value}}', [
                    'product_id' => $productId,
                    'feature_value_id' => $sourceId,
                ]);
                continue;
            }

            $this->update('{{%product_feature_value}}', [
                'feature_value_id' => $targetId,
            ], [
                'product_id' => $productId,
                'feature_value_id' => $sourceId,
            ]);
        }
    }

    /** @param array<string, mixed> $color */
    private function findColorFeatureValueId(int $featureId, array $color): ?int
    {
        $query = (new Query())
            ->from('{{%catalog_feature_value}}')
            ->where(['feature_id' => $featureId, 'name' => $color['name']])
            ->orderBy(['id' => SORT_ASC]);

        if (!empty($color['hex'])) {
            $valueId = (clone $query)
                ->andWhere(['hex' => $color['hex']])
                ->select('id')
                ->scalar($this->db);
            if ($valueId !== false) {
                return (int) $valueId;
            }
        }

        $valueId = $query->select('id')->scalar($this->db);

        return $valueId === false ? null : (int) $valueId;
    }

    private function indexExists(string $table, string $name): bool
    {
        $rawTable = $this->db->schema->getRawTableName($table);
        $indexes = $this->db->createCommand('SHOW INDEX FROM ' . $this->db->quoteTableName($rawTable))
            ->queryAll();

        foreach ($indexes as $index) {
            if (($index['Key_name'] ?? '') === $name) {
                return true;
            }
        }

        return false;
    }

    private function uniqueColorSlug(string $base, int $excludeId): string
    {
        $slug = $base;
        $suffix = 1;

        while ((new Query())
            ->from('{{%color}}')
            ->where(['slug' => $slug])
            ->andWhere(['<>', 'id', $excludeId])
            ->exists($this->db)) {
            $slug = $base . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }

    private function uniqueFeatureValueSlug(string $base, int $featureId, int $excludeId): string
    {
        $slug = $base !== '' ? $base : 'value';
        $suffix = 1;

        while ((new Query())
            ->from('{{%catalog_feature_value}}')
            ->where(['feature_id' => $featureId, 'slug' => $slug])
            ->andWhere(['<>', 'id', $excludeId])
            ->exists($this->db)) {
            $slug = $base . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }
}
