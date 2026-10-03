<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ModifyProductImagePathToText extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('products', [
            'image_path' => [
                'type' => 'TEXT',
                'null' => true,
            ]
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('products', [
            'image_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ]
        ]);
    }
}
