<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddDaFieldsToUsers extends Migration
{
    public function up()
    {
        $fields = [
            'da_hq' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'is_active'
            ],
            'da_ex' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'da_hq'
            ],
            'da_os' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'da_ex'
            ],
            'monthly_target' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
                'after'      => 'da_os'
            ]
        ];

        foreach ($fields as $field => $config) {
            if (!$this->db->fieldExists($field, 'users')) {
                $this->forge->addColumn('users', [$field => $config]);
            }
        }
    }

    public function down()
    {
        $fields = ['da_hq', 'da_ex', 'da_os', 'monthly_target'];
        foreach ($fields as $field) {
            if ($this->db->fieldExists($field, 'users')) {
                $this->forge->dropColumn('users', $field);
            }
        }
    }
}
