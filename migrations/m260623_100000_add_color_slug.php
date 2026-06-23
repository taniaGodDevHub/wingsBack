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

        foreach ((new Query())->from('{{%color}}')->all($this->db) as $color) {
            $valueId = (new Query())
                ->from('{{%catalog_feature_value}}')
                ->where(['feature_id' => $colorFeatureId, 'name' => $color['name']])
                ->select('id')
                ->scalar($this->db);

            if ($valueId === false) {
                continue;
            }

            $this->update('{{%catalog_feature_value}}', ['slug' => $color['slug']], ['id' => (int) $valueId]);
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
}
