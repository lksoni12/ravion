<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOperationsModules extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('cfas')) {
            $this->forge->addField([
                'id' => ['type' => 'INTEGER', 'auto_increment' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 180],
                'state' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'created_by_user_id' => ['type' => 'INTEGER', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('created_by_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('cfas');
        }

        if (! $this->db->tableExists('stockists')) {
            $this->forge->addField([
                'id' => ['type' => 'INTEGER', 'auto_increment' => true],
                'name' => ['type' => 'VARCHAR', 'constraint' => 180],
                'area' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'cfa_id' => ['type' => 'INTEGER', 'null' => true],
                'created_by_user_id' => ['type' => 'INTEGER', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('cfa_id', 'cfas', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('created_by_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('stockists');
        }

        if (! $this->db->tableExists('sponsorships')) {
            $this->forge->addField([
                'id' => ['type' => 'INTEGER', 'auto_increment' => true],
                'doctor_id' => ['type' => 'INTEGER'],
                'amount' => ['type' => 'REAL', 'default' => 0],
                'purpose' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'submitted'],
                'created_by_user_id' => ['type' => 'INTEGER'],
                'approved_by_user_id' => ['type' => 'INTEGER', 'null' => true],
                'approval_note' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('doctor_id', 'doctors', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('created_by_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('approved_by_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('sponsorships');
        }

        if (! $this->db->tableExists('sales_entries')) {
            $this->forge->addField([
                'id' => ['type' => 'INTEGER', 'auto_increment' => true],
                'entry_date' => ['type' => 'DATE'],
                'product_id' => ['type' => 'INTEGER', 'null' => true],
                'stockist_id' => ['type' => 'INTEGER', 'null' => true],
                'area' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                'opening_value' => ['type' => 'REAL', 'default' => 0],
                'primary_value' => ['type' => 'REAL', 'default' => 0],
                'secondary_value' => ['type' => 'REAL', 'default' => 0],
                'closing_value' => ['type' => 'REAL', 'default' => 0],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'submitted'],
                'created_by_user_id' => ['type' => 'INTEGER'],
                'approved_by_user_id' => ['type' => 'INTEGER', 'null' => true],
                'approval_note' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('product_id', 'products', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('stockist_id', 'stockists', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('created_by_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('approved_by_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('sales_entries');
        }

        if (! $this->db->tableExists('slides')) {
            $this->forge->addField([
                'id' => ['type' => 'INTEGER', 'auto_increment' => true],
                'product_id' => ['type' => 'INTEGER', 'null' => true],
                'title' => ['type' => 'VARCHAR', 'constraint' => 180],
                'file_path' => ['type' => 'VARCHAR', 'constraint' => 255],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
                'created_by_user_id' => ['type' => 'INTEGER'],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('product_id', 'products', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('created_by_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('slides');
        }

        if (! $this->db->fieldExists('assigned_user_id', 'field_plans')) {
            $this->forge->addColumn('field_plans', [
                'assigned_user_id' => ['type' => 'INTEGER', 'null' => true, 'after' => 'created_by_user_id'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('assigned_user_id', 'field_plans')) {
            $this->forge->dropColumn('field_plans', 'assigned_user_id');
        }

        if ($this->db->tableExists('slides')) {
            $this->forge->dropTable('slides', true);
        }
        if ($this->db->tableExists('sales_entries')) {
            $this->forge->dropTable('sales_entries', true);
        }
        if ($this->db->tableExists('sponsorships')) {
            $this->forge->dropTable('sponsorships', true);
        }
        if ($this->db->tableExists('stockists')) {
            $this->forge->dropTable('stockists', true);
        }
        if ($this->db->tableExists('cfas')) {
            $this->forge->dropTable('cfas', true);
        }
    }
}
