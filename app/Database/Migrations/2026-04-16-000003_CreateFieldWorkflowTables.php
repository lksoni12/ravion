<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFieldWorkflowTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 180],
            'stockist_name' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'area' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'contact' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('chemists');

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'plan_date' => ['type' => 'DATE'],
            'activity_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'tp'],
            'doctor_id' => ['type' => 'INTEGER', 'null' => true],
            'product_id' => ['type' => 'INTEGER', 'null' => true],
            'chemist_id' => ['type' => 'INTEGER', 'null' => true],
            'objective' => ['type' => 'TEXT', 'null' => true],
            'pob_amount' => ['type' => 'REAL', 'null' => true],
            'joint_call_with' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'planned'],
            'approval_note' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('doctor_id', 'doctors', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('chemist_id', 'chemists', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('field_plans');
    }

    public function down()
    {
        $this->forge->dropTable('field_plans', true);
        $this->forge->dropTable('chemists', true);
    }
}
