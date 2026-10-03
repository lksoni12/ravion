<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFounderMrEnhancements extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('mobile', 'users')) {
            $this->forge->addColumn('users', [
                'mobile' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'email'],
            ]);
        }

        if (! $this->db->fieldExists('monthly_potential', 'doctors')) {
            $this->forge->addColumn('doctors', [
                'monthly_potential' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true, 'after' => 'category'],
            ]);
        }

        if (! $this->db->fieldExists('area_patch', 'doctors')) {
            $this->forge->addColumn('doctors', [
                'area_patch' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'monthly_potential'],
            ]);
        }

        if (! $this->db->fieldExists('assigned_to_user_id', 'doctors')) {
            $this->forge->addColumn('doctors', [
                'assigned_to_user_id' => ['type' => 'INTEGER', 'null' => true, 'after' => 'approved_by_user_id'],
            ]);
        }

        if (! $this->db->fieldExists('contact_person', 'chemists')) {
            $this->forge->addColumn('chemists', [
                'contact_person' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true, 'after' => 'name'],
            ]);
        }

        if (! $this->db->fieldExists('expense_month', 'expenses')) {
            $this->forge->addColumn('expenses', [
                'expense_month' => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => true, 'after' => 'category'],
            ]);
        }

        if (! $this->db->fieldExists('details_json', 'expenses')) {
            $this->forge->addColumn('expenses', [
                'details_json' => ['type' => 'TEXT', 'null' => true, 'after' => 'approval_note'],
            ]);
        }

        if (! $this->db->fieldExists('rejection_reason', 'expenses')) {
            $this->forge->addColumn('expenses', [
                'rejection_reason' => ['type' => 'TEXT', 'null' => true, 'after' => 'details_json'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('rejection_reason', 'expenses')) {
            $this->forge->dropColumn('expenses', 'rejection_reason');
        }

        if ($this->db->fieldExists('details_json', 'expenses')) {
            $this->forge->dropColumn('expenses', 'details_json');
        }

        if ($this->db->fieldExists('expense_month', 'expenses')) {
            $this->forge->dropColumn('expenses', 'expense_month');
        }

        if ($this->db->fieldExists('contact_person', 'chemists')) {
            $this->forge->dropColumn('chemists', 'contact_person');
        }

        if ($this->db->fieldExists('assigned_to_user_id', 'doctors')) {
            $this->forge->dropColumn('doctors', 'assigned_to_user_id');
        }

        if ($this->db->fieldExists('area_patch', 'doctors')) {
            $this->forge->dropColumn('doctors', 'area_patch');
        }

        if ($this->db->fieldExists('monthly_potential', 'doctors')) {
            $this->forge->dropColumn('doctors', 'monthly_potential');
        }

        if ($this->db->fieldExists('mobile', 'users')) {
            $this->forge->dropColumn('users', 'mobile');
        }
    }
}
