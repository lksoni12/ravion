<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddChemistToVisits extends Migration
{
    public function up()
    {
        $fields = [
            'chemist_id' => [
                'type' => 'INTEGER',
                'null' => true,
            ],
        ];

        $this->forge->addColumn('visits', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('visits', 'chemist_id');
    }
}
