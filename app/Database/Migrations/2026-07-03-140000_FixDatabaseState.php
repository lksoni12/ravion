<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixDatabaseState extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // 1. Ensure territories table has parent_id column to prevent 'Unknown column' error
        if ($db->tableExists('territories') && !$db->fieldExists('parent_id', 'territories')) {
            $this->forge->addColumn('territories', [
                'parent_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'after'      => 'type'
                ]
            ]);
            
            // Add foreign key constraint if supported (avoiding issues in SQLite for dynamic column addition)
            $driver = strtolower((string) ($db->DBDriver ?? ''));
            if ($driver !== 'sqlite3') {
                $db->query("ALTER TABLE territories ADD CONSTRAINT fk_territories_parent FOREIGN KEY (parent_id) REFERENCES territories(id) ON DELETE CASCADE");
            }
        }

        // 2. Ensure products table has a default product with ID 1 to prevent foreign key errors on visits
        if ($db->tableExists('products')) {
            $existing = $db->table('products')->where('id', 1)->get()->getRow();
            if (!$existing) {
                // If ID 1 does not exist, check if table is completely empty to maintain auto_increment
                $anyProduct = $db->table('products')->get()->getRow();
                
                $data = [
                    'id'          => 1,
                    'name'        => 'PEDIVION DROPS 15 ML',
                    'description' => 'Default product (auto-created by migration)',
                    'pts_price'   => 0,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ];
                
                $db->table('products')->insert($data);
                
                // If it wasn't empty and we just force-inserted ID 1, we should be fine.
                // If it was empty, auto-increment will just continue from 2.
            }
        }
    }

    public function down()
    {
        // Down migration can be left empty as it's fixing state, or we could remove the default product 
        // but it's safer to leave data intact.
    }
}
