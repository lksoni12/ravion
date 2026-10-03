<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOrderingToProductsAndSlides extends Migration
{
    public function up()
    {
        // Add display_order to products if not exists
        if (!$this->db->fieldExists('display_order', 'products')) {
            $this->forge->addColumn('products', [
                'display_order' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                    'after'      => 'packing'
                ]
            ]);
        }

        // Add slide_order to slides if not exists
        if (!$this->db->fieldExists('slide_order', 'slides')) {
            $this->forge->addColumn('slides', [
                'slide_order' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                    'after'      => 'status'
                ]
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('display_order', 'products')) {
            $this->forge->dropColumn('products', 'display_order');
        }
        if ($this->db->fieldExists('slide_order', 'slides')) {
            $this->forge->dropColumn('slides', 'slide_order');
        }
    }
}
