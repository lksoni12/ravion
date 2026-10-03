<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSettingsTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('settings')) {
            $this->forge->addField([
                'key_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 120,
                ],
                'value_text' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('key_name', true);
            $this->forge->createTable('settings');
        }

        // Migrate existing data from app_settings if it exists
        if ($this->db->tableExists('app_settings')) {
            $existingSettings = $this->db->table('app_settings')->get()->getResultArray();
            if (!empty($existingSettings)) {
                foreach ($existingSettings as $row) {
                    $key = $row['setting_key'] ?? '';
                    $val = $row['setting_value'] ?? '';
                    
                    // Check if key already exists in settings
                    $exists = $this->db->table('settings')->where('key_name', $key)->countAllResults();
                    if ($exists === 0 && $key !== '') {
                        $this->db->table('settings')->insert([
                            'key_name'   => $key,
                            'value_text' => $val,
                        ]);
                    }
                }
            }
        }

        // Seed default config values if not present
        $defaults = [
            'company_logo'             => '',
            'da_mr_hq'                 => '250',
            'da_mr_ex'                 => '450',
            'da_mr_os'                 => '700',
            'da_manager_hq'            => '250',
            'da_manager_ex'            => '450',
            'da_manager_os'            => '700',
            'monthly_secondary_target' => '1000000',
        ];

        foreach ($defaults as $key => $val) {
            $exists = $this->db->table('settings')->where('key_name', $key)->countAllResults();
            if ($exists === 0) {
                $this->db->table('settings')->insert([
                    'key_name'   => $key,
                    'value_text' => $val,
                ]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('settings')) {
            $this->forge->dropTable('settings', true);
        }
    }
}


