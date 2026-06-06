<?php

use yii\db\Migration;
use yii\db\Query;
use yii\helpers\Inflector;

class m260608_100001_deduplicate_color_feature extends Migration
{
    public function safeUp(): void
    {
        $canonicalId = (new Query())
            ->from('{{%catalog_feature}}')
            ->where(['code' => 'color'])
            ->select('id')
            ->scalar($this->db);

        if ($canonicalId === false) {
            $canonicalId = (new Query())
                ->from('{{%catalog_feature}}')
                ->where(['name_ru' => 'Цвет'])
                ->orderBy(['id' => SORT_ASC])
                ->select('id')
                ->scalar($this->db);
        }

        if ($canonicalId === false) {
            return;
        }

        $canonicalId = (int) $canonicalId;
        $this->update('{{%catalog_feature}}', ['code' => 'color'], ['id' => $canonicalId]);

        $duplicateIds = (new Query())
            ->from('{{%catalog_feature}}')
            ->where(['and', ['name_ru' => 'Цвет'], ['<>', 'id', $canonicalId]])
            ->select('id')
            ->column($this->db);

        $valueSchema = $this->db->getTableSchema('{{%catalog_feature_value}}', true);

        foreach ($duplicateIds as $duplicateId) {
            $duplicateId = (int) $duplicateId;
            $valueMap = [];

            foreach ((new Query())
                ->from('{{%catalog_feature_value}}')
                ->where(['feature_id' => $duplicateId])
                ->all($this->db) as $oldValue) {
                $oldValueId = (int) $oldValue['id'];
                $targetId = (new Query())
                    ->from('{{%catalog_feature_value}}')
                    ->where(['feature_id' => $canonicalId, 'name' => $oldValue['name']])
                    ->select('id')
                    ->scalar($this->db);

                if ($targetId === false) {
                    $valueRow = [
                        'feature_id' => $canonicalId,
                        'name' => $oldValue['name'],
                        'hex' => $oldValue['hex'] ?? null,
                    ];
                    if ($valueSchema !== null && isset($valueSchema->columns['slug'])) {
                        $valueRow['slug'] = $this->uniqueFeatureValueSlug((string) $oldValue['name'], $canonicalId);
                    }
                    $this->insert('{{%catalog_feature_value}}', $valueRow);
                    $targetId = (int) $this->db->getLastInsertID();
                } else {
                    $targetId = (int) $targetId;
                    if (($oldValue['hex'] ?? null) !== null) {
                        $this->update('{{%catalog_feature_value}}', ['hex' => $oldValue['hex']], ['id' => $targetId]);
                    }
                }

                $valueMap[$oldValueId] = $targetId;
            }

            foreach ($valueMap as $oldValueId => $targetId) {
                foreach ((new Query())
                    ->from('{{%product_feature_value}}')
                    ->where(['feature_value_id' => $oldValueId])
                    ->all($this->db) as $link) {
                    $exists = (new Query())
                        ->from('{{%product_feature_value}}')
                        ->where([
                            'product_id' => (int) $link['product_id'],
                            'feature_value_id' => $targetId,
                        ])
                        ->exists($this->db);

                    if ($exists) {
                        $this->delete('{{%product_feature_value}}', [
                            'product_id' => (int) $link['product_id'],
                            'feature_value_id' => $oldValueId,
                        ]);
                        continue;
                    }

                    $this->update('{{%product_feature_value}}', [
                        'feature_value_id' => $targetId,
                    ], [
                        'product_id' => (int) $link['product_id'],
                        'feature_value_id' => $oldValueId,
                    ]);
                }
            }

            $this->delete('{{%catalog_feature_value}}', ['feature_id' => $duplicateId]);
            $this->delete('{{%catalog_feature}}', ['id' => $duplicateId]);
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
        // irreversible data merge
    }
}
