<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRoleWorkflowAndExpenses extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('username', 'users')) {
            $this->forge->addColumn('users', [
                'username' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true, 'after' => 'name'],
            ]);
            $this->forge->addUniqueKey('username');
        }

        if (! $this->db->fieldExists('area', 'users')) {
            $this->forge->addColumn('users', [
                'area' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'role'],
            ]);
        }

        if (! $this->db->fieldExists('manager_user_id', 'users')) {
            $this->forge->addColumn('users', [
                'manager_user_id' => ['type' => 'INTEGER', 'null' => true, 'after' => 'area'],
            ]);
        }

        if (! $this->db->fieldExists('created_by_user_id', 'field_plans')) {
            $this->forge->addColumn('field_plans', [
                'created_by_user_id' => ['type' => 'INTEGER', 'null' => true, 'after' => 'status'],
            ]);
        }

        if (! $this->db->fieldExists('created_by_user_id', 'visits')) {
            $this->forge->addColumn('visits', [
                'created_by_user_id' => ['type' => 'INTEGER', 'null' => true, 'after' => 'action_type'],
            ]);
        }

        if (! $this->db->fieldExists('duration_minutes', 'visits')) {
            $this->forge->addColumn('visits', [
                'duration_minutes' => ['type' => 'INTEGER', 'default' => 0, 'after' => 'visit_date'],
            ]);
        }

        if (! $this->db->fieldExists('created_by_user_id', 'doctors')) {
            $this->forge->addColumn('doctors', [
                'created_by_user_id' => ['type' => 'INTEGER', 'null' => true, 'after' => 'category'],
            ]);
        }

        if (! $this->db->fieldExists('approval_status', 'doctors')) {
            $this->forge->addColumn('doctors', [
                'approval_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'approved', 'after' => 'created_by_user_id'],
            ]);
        }

        if (! $this->db->fieldExists('approved_by_user_id', 'doctors')) {
            $this->forge->addColumn('doctors', [
                'approved_by_user_id' => ['type' => 'INTEGER', 'null' => true, 'after' => 'approval_status'],
            ]);
        }

        if (! $this->db->fieldExists('created_by_user_id', 'chemists')) {
            $this->forge->addColumn('chemists', [
                'created_by_user_id' => ['type' => 'INTEGER', 'null' => true, 'after' => 'contact'],
            ]);
        }

        if (! $this->db->fieldExists('approval_status', 'chemists')) {
            $this->forge->addColumn('chemists', [
                'approval_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'approved', 'after' => 'created_by_user_id'],
            ]);
        }

        if (! $this->db->fieldExists('approved_by_user_id', 'chemists')) {
            $this->forge->addColumn('chemists', [
                'approved_by_user_id' => ['type' => 'INTEGER', 'null' => true, 'after' => 'approval_status'],
            ]);
        }

        if (! $this->db->fieldExists('pts_price', 'products')) {
            $this->forge->addColumn('products', [
                'pts_price' => ['type' => 'REAL', 'default' => 0, 'after' => 'name'],
            ]);
        }

        if (! $this->db->tableExists('expenses')) {
            $this->forge->addField([
                'id' => ['type' => 'INTEGER', 'auto_increment' => true],
                'submitted_by_user_id' => ['type' => 'INTEGER'],
                'title' => ['type' => 'VARCHAR', 'constraint' => 180],
                'category' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                'amount' => ['type' => 'REAL', 'default' => 0],
                'bill_image_path' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'submitted'],
                'approved_by_user_id' => ['type' => 'INTEGER', 'null' => true],
                'approval_note' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addForeignKey('submitted_by_user_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('approved_by_user_id', 'users', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('expenses');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('expenses')) {
            $this->forge->dropTable('expenses', true);
        }

        if ($this->db->fieldExists('pts_price', 'products')) {
            $this->forge->dropColumn('products', 'pts_price');
        }

        if ($this->db->fieldExists('approved_by_user_id', 'chemists')) {
            $this->forge->dropColumn('chemists', 'approved_by_user_id');
        }
        if ($this->db->fieldExists('approval_status', 'chemists')) {
            $this->forge->dropColumn('chemists', 'approval_status');
        }
        if ($this->db->fieldExists('created_by_user_id', 'chemists')) {
            $this->forge->dropColumn('chemists', 'created_by_user_id');
        }

        if ($this->db->fieldExists('approved_by_user_id', 'doctors')) {
            $this->forge->dropColumn('doctors', 'approved_by_user_id');
        }
        if ($this->db->fieldExists('approval_status', 'doctors')) {
            $this->forge->dropColumn('doctors', 'approval_status');
        }
        if ($this->db->fieldExists('created_by_user_id', 'doctors')) {
            $this->forge->dropColumn('doctors', 'created_by_user_id');
        }

        if ($this->db->fieldExists('duration_minutes', 'visits')) {
            $this->forge->dropColumn('visits', 'duration_minutes');
        }
        if ($this->db->fieldExists('created_by_user_id', 'visits')) {
            $this->forge->dropColumn('visits', 'created_by_user_id');
        }

        if ($this->db->fieldExists('created_by_user_id', 'field_plans')) {
            $this->forge->dropColumn('field_plans', 'created_by_user_id');
        }

        if ($this->db->fieldExists('manager_user_id', 'users')) {
            $this->forge->dropColumn('users', 'manager_user_id');
        }
        if ($this->db->fieldExists('area', 'users')) {
            $this->forge->dropColumn('users', 'area');
        }
        if ($this->db->fieldExists('username', 'users')) {
            $this->forge->dropColumn('users', 'username');
        }
    }
}
