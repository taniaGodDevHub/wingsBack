<?php

use yii\db\Migration;
use yii\db\Query;

class m260624_100000_create_size_reference extends Migration
{
    /** @var array<int, array{rus_label: string, size_value: string, default_chest_circumference: string, sort_order: int}> */
    private const SEED_SIZES = [
        ['rus_label' => '40/42', 'size_value' => 'XS', 'default_chest_circumference' => '84/88', 'sort_order' => 1],
        ['rus_label' => '44/46', 'size_value' => 'S', 'default_chest_circumference' => '88/92', 'sort_order' => 2],
        ['rus_label' => '46/48', 'size_value' => 'M', 'default_chest_circumference' => '92/96', 'sort_order' => 3],
        ['rus_label' => '48/50', 'size_value' => 'L', 'default_chest_circumference' => '96/100', 'sort_order' => 4],
    ];

    public function safeUp(): void
    {
        if ($this->db->getTableSchema('{{%size}}', true) === null) {
            $this->createTable('{{%size}}', [
                'id' => $this->primaryKey(),
                'rus_label' => $this->string(16)->notNull(),
                'size_value' => $this->string(16)->notNull(),
                'default_chest_circumference' => $this->string(16)->notNull(),
                'sort_order' => $this->integer()->notNull()->defaultValue(0),
            ]);
            $this->createIndex('idx-size-size_value', '{{%size}}', 'size_value', true);
        }

        foreach (self::SEED_SIZES as $row) {
            $exists = (new Query())
                ->from('{{%size}}')
                ->where(['size_value' => $row['size_value']])
                ->exists($this->db);
            if (!$exists) {
                $this->insert('{{%size}}', $row);
            }
        }

        $sizeValueToId = [];
        foreach ((new Query())->from('{{%size}}')->all($this->db) as $sizeRow) {
            $sizeValueToId[(string) $sizeRow['size_value']] = (int) $sizeRow['id'];
        }

        $productSizeSchema = $this->db->getTableSchema('{{%product_size}}', true);
        if ($productSizeSchema === null) {
            return;
        }

        if (!isset($productSizeSchema->columns['size_id'])) {
            $this->addColumn('{{%product_size}}', 'size_id', $this->integer()->null()->after('product_id'));
        }
        if (!isset($productSizeSchema->columns['chest_circumference'])) {
            $this->addColumn('{{%product_size}}', 'chest_circumference', $this->string(16)->null()->after('size_id'));
        }
        if (!isset($productSizeSchema->columns['is_in_stock'])) {
            $this->addColumn('{{%product_size}}', 'is_in_stock', $this->boolean()->notNull()->defaultValue(false));
        }

        if (isset($productSizeSchema->columns['size_value'])) {
            foreach ((new Query())->from('{{%product_size}}')->all($this->db) as $row) {
                $sizeValue = (string) $row['size_value'];
                if (!isset($sizeValueToId[$sizeValue])) {
                    $this->delete('{{%product_size}}', ['id' => $row['id']]);
                    continue;
                }

                $sizeId = $sizeValueToId[$sizeValue];
                $defaultChest = (new Query())
                    ->from('{{%size}}')
                    ->where(['id' => $sizeId])
                    ->select('default_chest_circumference')
                    ->scalar($this->db);

                $this->update('{{%product_size}}', [
                    'size_id' => $sizeId,
                    'chest_circumference' => $defaultChest !== false ? (string) $defaultChest : null,
                    'is_in_stock' => true,
                ], ['id' => $row['id']]);
            }

            $this->dropColumn('{{%product_size}}', 'size_value');
        }

        $productSizeSchema = $this->db->getTableSchema('{{%product_size}}', true);
        if ($productSizeSchema !== null && isset($productSizeSchema->columns['size_id']) && $productSizeSchema->columns['size_id']->allowNull) {
            $this->alterColumn('{{%product_size}}', 'size_id', $this->integer()->notNull());
        }

        if (!$this->foreignKeyExists('{{%product_size}}', 'fk-product_size-size')) {
            $this->addForeignKey(
                'fk-product_size-size',
                '{{%product_size}}',
                'size_id',
                '{{%size}}',
                'id',
                'CASCADE',
                'CASCADE',
            );
        }

        if (!$this->indexExists('{{%product_size}}', 'idx-product_size-product-size')) {
            $this->createIndex('idx-product_size-product-size', '{{%product_size}}', ['product_id', 'size_id'], true);
        }
    }

    public function safeDown(): void
    {
        $productSizeSchema = $this->db->getTableSchema('{{%product_size}}', true);
        if ($productSizeSchema === null) {
            return;
        }

        if (!isset($productSizeSchema->columns['size_value'])) {
            $this->addColumn('{{%product_size}}', 'size_value', $this->string(16)->null()->after('product_id'));
        }

        foreach ((new Query())->from('{{%product_size}}')->all($this->db) as $row) {
            $sizeValue = (new Query())
                ->from('{{%size}}')
                ->where(['id' => $row['size_id'] ?? 0])
                ->select('size_value')
                ->scalar($this->db);

            if ($sizeValue === false) {
                $this->delete('{{%product_size}}', ['id' => $row['id']]);
                continue;
            }

            $this->update('{{%product_size}}', ['size_value' => (string) $sizeValue], ['id' => $row['id']]);
        }

        if ($this->foreignKeyExists('{{%product_size}}', 'fk-product_size-size')) {
            $this->dropForeignKey('fk-product_size-size', '{{%product_size}}');
        }
        if ($this->indexExists('{{%product_size}}', 'idx-product_size-product-size')) {
            $this->dropIndex('idx-product_size-product-size', '{{%product_size}}');
        }

        $productSizeSchema = $this->db->getTableSchema('{{%product_size}}', true);
        if ($productSizeSchema !== null) {
            if (isset($productSizeSchema->columns['is_in_stock'])) {
                $this->dropColumn('{{%product_size}}', 'is_in_stock');
            }
            if (isset($productSizeSchema->columns['chest_circumference'])) {
                $this->dropColumn('{{%product_size}}', 'chest_circumference');
            }
            if (isset($productSizeSchema->columns['size_id'])) {
                $this->dropColumn('{{%product_size}}', 'size_id');
            }
        }

        if ($this->db->getTableSchema('{{%size}}', true) !== null) {
            $this->dropTable('{{%size}}');
        }
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

    private function foreignKeyExists(string $table, string $name): bool
    {
        $rawTable = $this->db->schema->getRawTableName($table);
        $foreignKeys = $this->db->createCommand(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS '
            . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND CONSTRAINT_NAME = :name AND CONSTRAINT_TYPE = \'FOREIGN KEY\'',
            [':table' => $rawTable, ':name' => $name],
        )->queryAll();

        return $foreignKeys !== [];
    }
}
