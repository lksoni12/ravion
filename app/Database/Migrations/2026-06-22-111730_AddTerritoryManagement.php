<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTerritoryManagement extends Migration
{
    public function up()
    {
        // 1. Create territories table
        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'type' => ['type' => 'VARCHAR', 'constraint' => 50], // hq, ex-hq, outstation
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('territories');

        // 2. Create user_territories table
        $this->forge->addField([
            'user_id' => ['type' => 'INTEGER'],
            'territory_id' => ['type' => 'INTEGER'],
        ]);
        $this->forge->addKey(['user_id', 'territory_id'], true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('territory_id', 'territories', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_territories');

        // 3. Seed initial default territories
        $db = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');
        $defaultTerritories = [
            ['name' => 'City Center', 'type' => 'hq', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'North Zone', 'type' => 'ex-hq', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'South Zone', 'type' => 'ex-hq', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'East Zone', 'type' => 'ex-hq', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'West Zone', 'type' => 'ex-hq', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Outskirts', 'type' => 'outstation', 'created_at' => $now, 'updated_at' => $now],
        ];
        $db->table('territories')->insertBatch($defaultTerritories);
    }

    public function down()
    {
        $this->forge->dropTable('user_territories', true);
        $this->forge->dropTable('territories', true);
    }
}
