<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMonthlyWorkflowParityFields extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('plan_month', 'field_plans')) {
            $this->forge->addColumn('field_plans', [
                'plan_month' => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => true, 'after' => 'plan_date'],
            ]);
        }
        if (! $this->db->fieldExists('day_of_month', 'field_plans')) {
            $this->forge->addColumn('field_plans', [
                'day_of_month' => ['type' => 'INTEGER', 'null' => true, 'after' => 'plan_month'],
            ]);
        }
        if (! $this->db->fieldExists('route_name', 'field_plans')) {
            $this->forge->addColumn('field_plans', [
                'route_name' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true, 'after' => 'joint_call_with'],
            ]);
        }
        if (! $this->db->fieldExists('is_locked', 'field_plans')) {
            $this->forge->addColumn('field_plans', [
                'is_locked' => ['type' => 'INTEGER', 'default' => 0, 'after' => 'approval_note'],
            ]);
        }

        if (! $this->db->fieldExists('dcr_date', 'visits')) {
            $this->forge->addColumn('visits', [
                'dcr_date' => ['type' => 'DATE', 'null' => true, 'after' => 'visit_date'],
            ]);
        }
        if (! $this->db->fieldExists('dcr_month', 'visits')) {
            $this->forge->addColumn('visits', [
                'dcr_month' => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => true, 'after' => 'dcr_date'],
            ]);
        }
        if (! $this->db->fieldExists('planned_plan_id', 'visits')) {
            $this->forge->addColumn('visits', [
                'planned_plan_id' => ['type' => 'INTEGER', 'null' => true, 'after' => 'chemist_id'],
            ]);
        }
        if (! $this->db->fieldExists('is_deviation', 'visits')) {
            $this->forge->addColumn('visits', [
                'is_deviation' => ['type' => 'INTEGER', 'default' => 0, 'after' => 'action_type'],
            ]);
        }
        if (! $this->db->fieldExists('deviation_reason', 'visits')) {
            $this->forge->addColumn('visits', [
                'deviation_reason' => ['type' => 'TEXT', 'null' => true, 'after' => 'is_deviation'],
            ]);
        }
        if (! $this->db->fieldExists('pob_value', 'visits')) {
            $this->forge->addColumn('visits', [
                'pob_value' => ['type' => 'REAL', 'default' => 0, 'after' => 'duration_minutes'],
            ]);
        }
        if (! $this->db->fieldExists('sample_units', 'visits')) {
            $this->forge->addColumn('visits', [
                'sample_units' => ['type' => 'INTEGER', 'default' => 0, 'after' => 'pob_value'],
            ]);
        }
        if (! $this->db->fieldExists('report_status', 'visits')) {
            $this->forge->addColumn('visits', [
                'report_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'submitted', 'after' => 'joint_call_with'],
            ]);
        }

        if (! $this->db->fieldExists('sales_month', 'sales_entries')) {
            $this->forge->addColumn('sales_entries', [
                'sales_month' => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => true, 'after' => 'entry_date'],
            ]);
        }
        if (! $this->db->fieldExists('matrix_row_key', 'sales_entries')) {
            $this->forge->addColumn('sales_entries', [
                'matrix_row_key' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'stockist_id'],
            ]);
        }
        if (! $this->db->fieldExists('matrix_json', 'sales_entries')) {
            $this->forge->addColumn('sales_entries', [
                'matrix_json' => ['type' => 'TEXT', 'null' => true, 'after' => 'approval_note'],
            ]);
        }
        if (! $this->db->fieldExists('locked_by_manager', 'sales_entries')) {
            $this->forge->addColumn('sales_entries', [
                'locked_by_manager' => ['type' => 'INTEGER', 'default' => 0, 'after' => 'matrix_json'],
            ]);
        }

        $driver = strtolower((string) ($this->db->DBDriver ?? ''));

        if ($driver === 'sqlite3') {
            $this->db->query("UPDATE field_plans SET plan_month = substr(plan_date,1,7), day_of_month = CAST(strftime('%d', plan_date) AS INTEGER) WHERE plan_month IS NULL OR plan_month = ''");
            $this->db->query("UPDATE visits SET dcr_date = DATE(visit_date), dcr_month = substr(visit_date,1,7) WHERE dcr_date IS NULL OR dcr_month IS NULL OR dcr_month = ''");
            $this->db->query("UPDATE sales_entries SET sales_month = substr(entry_date,1,7) WHERE sales_month IS NULL OR sales_month = ''");
        } else {
            $this->db->query("UPDATE field_plans SET plan_month = DATE_FORMAT(plan_date, '%Y-%m'), day_of_month = DAY(plan_date) WHERE plan_month IS NULL OR plan_month = ''");
            $this->db->query("UPDATE visits SET dcr_date = DATE(visit_date), dcr_month = DATE_FORMAT(visit_date, '%Y-%m') WHERE dcr_date IS NULL OR dcr_month IS NULL OR dcr_month = ''");
            $this->db->query("UPDATE sales_entries SET sales_month = DATE_FORMAT(entry_date, '%Y-%m') WHERE sales_month IS NULL OR sales_month = ''");
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('locked_by_manager', 'sales_entries')) {
            $this->forge->dropColumn('sales_entries', 'locked_by_manager');
        }
        if ($this->db->fieldExists('matrix_json', 'sales_entries')) {
            $this->forge->dropColumn('sales_entries', 'matrix_json');
        }
        if ($this->db->fieldExists('matrix_row_key', 'sales_entries')) {
            $this->forge->dropColumn('sales_entries', 'matrix_row_key');
        }
        if ($this->db->fieldExists('sales_month', 'sales_entries')) {
            $this->forge->dropColumn('sales_entries', 'sales_month');
        }

        if ($this->db->fieldExists('report_status', 'visits')) {
            $this->forge->dropColumn('visits', 'report_status');
        }
        if ($this->db->fieldExists('sample_units', 'visits')) {
            $this->forge->dropColumn('visits', 'sample_units');
        }
        if ($this->db->fieldExists('pob_value', 'visits')) {
            $this->forge->dropColumn('visits', 'pob_value');
        }
        if ($this->db->fieldExists('deviation_reason', 'visits')) {
            $this->forge->dropColumn('visits', 'deviation_reason');
        }
        if ($this->db->fieldExists('is_deviation', 'visits')) {
            $this->forge->dropColumn('visits', 'is_deviation');
        }
        if ($this->db->fieldExists('planned_plan_id', 'visits')) {
            $this->forge->dropColumn('visits', 'planned_plan_id');
        }
        if ($this->db->fieldExists('dcr_month', 'visits')) {
            $this->forge->dropColumn('visits', 'dcr_month');
        }
        if ($this->db->fieldExists('dcr_date', 'visits')) {
            $this->forge->dropColumn('visits', 'dcr_date');
        }

        if ($this->db->fieldExists('is_locked', 'field_plans')) {
            $this->forge->dropColumn('field_plans', 'is_locked');
        }
        if ($this->db->fieldExists('route_name', 'field_plans')) {
            $this->forge->dropColumn('field_plans', 'route_name');
        }
        if ($this->db->fieldExists('day_of_month', 'field_plans')) {
            $this->forge->dropColumn('field_plans', 'day_of_month');
        }
        if ($this->db->fieldExists('plan_month', 'field_plans')) {
            $this->forge->dropColumn('field_plans', 'plan_month');
        }
    }
}
