<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSalesEntryUFields extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('stockists') && ! $this->db->fieldExists('city', 'stockists')) {
            $this->forge->addColumn('stockists', [
                'city' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            ]);
        }

        if ($this->db->tableExists('products') && ! $this->db->fieldExists('packing', 'products')) {
            $this->forge->addColumn('products', [
                'packing' => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            ]);
        }

        $salesFields = [];
        if ($this->db->tableExists('sales_entries')) {
            if (! $this->db->fieldExists('aiocd_sales', 'sales_entries')) {
                $salesFields['aiocd_sales'] = ['type' => 'REAL', 'default' => 0];
            }
            if (! $this->db->fieldExists('mr_sales', 'sales_entries')) {
                $salesFields['mr_sales'] = ['type' => 'REAL', 'default' => 0];
            }
            if (! $this->db->fieldExists('late_sales', 'sales_entries')) {
                $salesFields['late_sales'] = ['type' => 'REAL', 'default' => 0];
            }
            if (! $this->db->fieldExists('late_stock', 'sales_entries')) {
                $salesFields['late_stock'] = ['type' => 'REAL', 'default' => 0];
            }
            if (! empty($salesFields)) {
                $this->forge->addColumn('sales_entries', $salesFields);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('stockists') && $this->db->fieldExists('city', 'stockists')) {
            $this->forge->dropColumn('stockists', 'city');
        }

        if ($this->db->tableExists('products') && $this->db->fieldExists('packing', 'products')) {
            $this->forge->dropColumn('products', 'packing');
        }

        $dropSales = [];
        if ($this->db->tableExists('sales_entries')) {
            foreach (['aiocd_sales', 'mr_sales', 'late_sales', 'late_stock'] as $col) {
                if ($this->db->fieldExists($col, 'sales_entries')) {
                    $dropSales[] = $col;
                }
            }
            if (! empty($dropSales)) {
                $this->forge->dropColumn('sales_entries', $dropSales);
            }
        }
    }
}
