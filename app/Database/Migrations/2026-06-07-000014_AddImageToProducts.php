<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddImageToProducts extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('image_path', 'products')) {
            $this->forge->addColumn('products', [
                'image_path' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'after'      => 'description'
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('products', 'image_path');
    }
}
