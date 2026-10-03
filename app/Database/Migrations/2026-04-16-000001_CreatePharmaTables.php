<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePharmaTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'speciality' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'clinic_address' => ['type' => 'TEXT', 'null' => true],
            'contact' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'category' => ['type' => 'VARCHAR', 'constraint' => 1, 'default' => 'C'],
            'birthday' => ['type' => 'DATE', 'null' => true],
            'anniversary' => ['type' => 'DATE', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('doctors');

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 180],
            'description' => ['type' => 'TEXT', 'null' => true],
            'detail_pdf' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('products');

        $this->forge->addField([
            'id' => ['type' => 'INTEGER', 'auto_increment' => true],
            'doctor_id' => ['type' => 'INTEGER'],
            'product_id' => ['type' => 'INTEGER'],
            'visit_date' => ['type' => 'DATETIME'],
            'action_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'detailing'],
            'feedback' => ['type' => 'TEXT', 'null' => true],
            'next_objective' => ['type' => 'TEXT', 'null' => true],
            'joint_call_with' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('doctor_id', 'doctors', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('visits');
    }

    public function down()
    {
        $this->forge->dropTable('visits', true);
        $this->forge->dropTable('products', true);
        $this->forge->dropTable('doctors', true);
    }
}
