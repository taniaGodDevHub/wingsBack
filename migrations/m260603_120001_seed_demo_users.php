<?php

use yii\db\Migration;

class m260603_120001_seed_demo_users extends Migration
{
    public function safeUp(): void
    {
        $time = time();
        $this->insert('{{%user}}', [
            'id' => 100,
            'username' => 'admin',
            'auth_key' => 'test100key',
            'password_hash' => '$2y$13$gYAywKSkhfZDq9FLNdm7buKnvlRxDexf5xipSMAxQPDUxpaptmZJu',
            'status' => 10,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        $this->insert('{{%user}}', [
            'id' => 101,
            'username' => 'demo',
            'auth_key' => 'test101key',
            'password_hash' => '$2y$13$alRLq1PGVMlGYwS/Y3iy3ewQns1Z8ol8Iq6Zb5k7ZwEhblA1aL29y',
            'status' => 10,
            'created_at' => $time,
            'updated_at' => $time,
        ]);
        $this->insert('{{%user_profile}}', [
            'user_id' => 100,
            'email' => 'admin@example.com',
            'name' => 'Admin',
            'f' => 'Админов',
            'i' => 'Админ',
        ]);
        $this->insert('{{%user_profile}}', [
            'user_id' => 101,
            'email' => 'demo@example.com',
            'name' => 'Demo',
            'f' => 'Демов',
            'i' => 'Демо',
        ]);
    }

    public function safeDown(): void
    {
        $this->delete('{{%user_profile}}', ['user_id' => [100, 101]]);
        $this->delete('{{%user}}', ['id' => [100, 101]]);
    }
}
