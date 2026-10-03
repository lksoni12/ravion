<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCfaStockistFields extends Migration
{
    public function up()
    {
        $fields = [
            'contact_person' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'mobile'         => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'email'          => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
            'gst'            => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'address'        => ['type' => 'TEXT', 'null' => true],
        ];

        $this->forge->addColumn('cfas', $fields);
        $this->forge->addColumn('stockists', $fields);
    }

    public function down()
    {
        $fields = ['contact_person', 'mobile', 'email', 'gst', 'address'];
        $this->forge->dropColumn('cfas', $fields);
        $this->forge->dropColumn('stockists', $fields);
    }
}
