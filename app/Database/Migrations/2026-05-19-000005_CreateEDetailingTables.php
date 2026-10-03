<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEDetailingTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'plan_date' => ['type' => 'DATE'],
            'doctor_id' => ['type' => 'INTEGER'],
            'field_plan_id' => ['type' => 'INTEGER', 'null' => true],
            'source' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'tour_plan'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['plan_date', 'doctor_id']);
        $this->forge->addForeignKey('doctor_id', 'doctors', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('field_plan_id', 'field_plans', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('e_detailing_doctors');

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'plan_date' => ['type' => 'DATE'],
            'chemist_id' => ['type' => 'INTEGER'],
            'source' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'manual'],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['plan_date', 'chemist_id']);
        $this->forge->addForeignKey('chemist_id', 'chemists', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('e_detailing_chemists');

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'e_detailing_doctor_id' => ['type' => 'INTEGER'],
            'doctor_id' => ['type' => 'INTEGER'],
            'field_plan_id' => ['type' => 'INTEGER', 'null' => true],
            'plan_date' => ['type' => 'DATE'],
            'started_at' => ['type' => 'DATETIME'],
            'ended_at' => ['type' => 'DATETIME', 'null' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('e_detailing_doctor_id', 'e_detailing_doctors', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('doctor_id', 'doctors', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('field_plan_id', 'field_plans', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('e_detailing_sessions');

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'session_id' => ['type' => 'INTEGER'],
            'product_id' => ['type' => 'INTEGER'],
            'sequence_no' => ['type' => 'INTEGER', 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('session_id', 'e_detailing_sessions', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('e_detailing_session_products');
    }

    public function down()
    {
        $this->forge->dropTable('e_detailing_session_products', true);
        $this->forge->dropTable('e_detailing_sessions', true);
        $this->forge->dropTable('e_detailing_chemists', true);
        $this->forge->dropTable('e_detailing_doctors', true);
    }
}
