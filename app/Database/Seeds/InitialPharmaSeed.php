<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class InitialPharmaSeed extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $products = [
            [
                'name' => 'PEDIVION DROPS 15 ML',
                'description' => 'Pediatric drops - initial product master from provided PDF.',
                'detail_pdf' => 'uploads/products/pedivion-drops-15ml.pdf',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'PRIME DHA 15ML WITH DROPPER GLASS BOTTLE',
                'description' => 'DHA drops with glass dropper - initial product master from provided PDF.',
                'detail_pdf' => 'uploads/products/prime-dha-15ml-dropper.pdf',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $doctors = [
            [
                'name' => 'Dr. Demo Pediatrician',
                'speciality' => 'Pediatrics',
                'clinic_address' => 'Demo Clinic, City',
                'contact' => '9999999999',
                'category' => 'A',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $chemists = [
            [
                'name' => 'RAVION Care Chemist',
                'stockist_name' => 'Demo Stockist',
                'area' => 'City Center',
                'contact' => '9888888888',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->db->table('products')->insertBatch($products);
        $this->db->table('doctors')->insertBatch($doctors);
        if ($this->db->tableExists('chemists')) {
            $this->db->table('chemists')->insertBatch($chemists);
        }
    }
}
