<?php

use yii\db\Migration;
use yii\db\Query;
use yii\helpers\Inflector;

class m260608_100000_catalog_feature_color extends Migration
{
    public function safeUp(): void
    {
        $featureSchema = $this->db->getTableSchema('{{%catalog_feature}}', true);
        $valueSchema = $this->db->getTableSchema('{{%catalog_feature_value}}', true);

        if ($featureSchema !== null && !isset($featureSchema->columns['code'])) {
            $this->addColumn('{{%catalog_feature}}', 'code', $this->string(32)->null()->after('name_ru'));
        }
        if ($valueSchema !== null && !isset($valueSchema->columns['hex'])) {
            $this->addColumn('{{%catalog_feature_value}}', 'hex', $this->string(7)->null()->after('name'));
        }

        $indexes = $this->db->getSchema()->findUniqueIndexes($featureSchema ?? $this->db->getTableSchema('{{%catalog_feature}}', true));
        $hasCodeIndex = false;
        foreach ($indexes as $indexColumns) {
            if ($indexColumns === ['code']) {
                $hasCodeIndex = true;
                break;
            }
        }
        if (!$hasCodeIndex) {
            $this->createIndex('idx-catalog_feature-code', '{{%catalog_feature}}', 'code', true);
        }

        $colorFeatureId = (new Query())
            ->from('{{%catalog_feature}}')
            ->where(['code' => 'color'])
            ->select('id')
            ->scalar($this->db);

        if ($colorFeatureId === false) {
            $colorFeatureId = (new Query())
                ->from('{{%catalog_feature}}')
                ->where(['name_ru' => 'Цвет'])
                ->orderBy(['id' => SORT_ASC])
                ->select('id')
                ->scalar($this->db);
        }

        if ($colorFeatureId === false) {
            $row = [
                'name_ru' => 'Цвет',
                'code' => 'color',
            ];
            if ($featureSchema !== null && isset($featureSchema->columns['slug'])) {
                $row['slug'] = 'color';
            }
            $this->insert('{{%catalog_feature}}', $row);
            $colorFeatureId = (int) $this->db->getLastInsertID();
        } else {
            $colorFeatureId = (int) $colorFeatureId;
            $this->update('{{%catalog_feature}}', ['code' => 'color'], ['id' => $colorFeatureId]);
        }

        if (!$this->db->getTableSchema('{{%color}}', true)) {
            return;
        }

        $colorMap = [];
        foreach ((new Query())->from('{{%color}}')->all($this->db) as $color) {
            $valueId = (new Query())
                ->from('{{%catalog_feature_value}}')
                ->where(['feature_id' => $colorFeatureId, 'name' => $color['name']])
                ->select('id')
                ->scalar($this->db);

            if ($valueId === false) {
                $valueRow = [
                    'feature_id' => $colorFeatureId,
                    'name' => $color['name'],
                    'hex' => $color['hex'],
                ];
                if ($valueSchema !== null && isset($valueSchema->columns['slug'])) {
                    $valueRow['slug'] = $this->uniqueFeatureValueSlug(
                        (string) $color['name'],
                        $colorFeatureId,
                    );
                }
                $this->insert('{{%catalog_feature_value}}', $valueRow);
                $valueId = (int) $this->db->getLastInsertID();
            } else {
                $valueId = (int) $valueId;
                $this->update('{{%catalog_feature_value}}', ['hex' => $color['hex']], ['id' => $valueId]);
            }

            $colorMap[(int) $color['id']] = $valueId;
        }

        if (!$this->db->getTableSchema('{{%product_color}}', true) || $colorMap === []) {
            return;
        }

        foreach ((new Query())->from('{{%product_color}}')->all($this->db) as $link) {
            $valueId = $colorMap[(int) $link['color_id']] ?? null;
            if ($valueId === null) {
                continue;
            }

            $exists = (new Query())
                ->from('{{%product_feature_value}}')
                ->where([
                    'product_id' => (int) $link['product_id'],
                    'feature_value_id' => $valueId,
                ])
                ->exists($this->db);

            if (!$exists) {
                $this->insert('{{%product_feature_value}}', [
                    'product_id' => (int) $link['product_id'],
                    'feature_value_id' => $valueId,
                ]);
            }
        }
    }

    private function uniqueFeatureValueSlug(string $name, int $featureId): string
    {
        $base = Inflector::slug($name) ?: 'value';
        $slug = $base;
        $suffix = 1;

        while ((new Query())
            ->from('{{%catalog_feature_value}}')
            ->where(['feature_id' => $featureId, 'slug' => $slug])
            ->exists($this->db)) {
            $slug = $base . '-' . $suffix;
            ++$suffix;
        }

        return $slug;
    }

    public function safeDown(): void
    {
        $featureSchema = $this->db->getTableSchema('{{%catalog_feature}}', true);
        if ($featureSchema !== null && isset($featureSchema->columns['code'])) {
            $this->dropIndex('idx-catalog_feature-code', '{{%catalog_feature}}');
            $this->dropColumn('{{%catalog_feature}}', 'code');
        }

        $valueSchema = $this->db->getTableSchema('{{%catalog_feature_value}}', true);
        if ($valueSchema !== null && isset($valueSchema->columns['hex'])) {
            $this->dropColumn('{{%catalog_feature_value}}', 'hex');
        }
    }
}
