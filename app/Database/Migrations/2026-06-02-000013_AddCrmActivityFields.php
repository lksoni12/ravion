<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCrmActivityFields extends Migration
{
    public function up()
    {
        $fields = [];
        if ($this->db->tableExists('sponsorships')) {
            if (! $this->db->fieldExists('activity_type', 'sponsorships')) {
                $fields['activity_type'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'default' => 'Other',
                ];
            }
            if (! $this->db->fieldExists('activity_date', 'sponsorships')) {
                $fields['activity_date'] = [
                    'type' => 'DATE',
                    'null' => true,
                ];
            }
            if (! $this->db->fieldExists('remarks', 'sponsorships')) {
                $fields['remarks'] = [
                    'type' => 'TEXT',
                    'null' => true,
                ];
            }
            if (! $this->db->fieldExists('current_roi', 'sponsorships')) {
                $fields['current_roi'] = [
                    'type' => 'REAL',
                    'default' => 0,
                ];
            }
            if (! $this->db->fieldExists('expected_roi', 'sponsorships')) {
                $fields['expected_roi'] = [
                    'type' => 'REAL',
                    'default' => 0,
                ];
            }

            if (! empty($fields)) {
                $this->forge->addColumn('sponsorships', $fields);
            }
        }
    }

    public function down()
    {
        $dropFields = [];
        if ($this->db->tableExists('sponsorships')) {
            foreach (['activity_type', 'activity_date', 'remarks', 'current_roi', 'expected_roi'] as $col) {
                if ($this->db->fieldExists($col, 'sponsorships')) {
                    $dropFields[] = $col;
                }
            }
            if (! empty($dropFields)) {
                $this->forge->dropColumn('sponsorships', $dropFields);
            }
        }
    }
}
