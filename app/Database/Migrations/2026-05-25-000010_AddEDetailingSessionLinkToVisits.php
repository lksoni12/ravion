<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEDetailingSessionLinkToVisits extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('e_detailing_session_id', 'visits')) {
            $this->forge->addColumn('visits', [
                'e_detailing_session_id' => ['type' => 'INTEGER', 'null' => true, 'after' => 'planned_plan_id'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('e_detailing_session_id', 'visits')) {
            $this->forge->dropColumn('visits', 'e_detailing_session_id');
        }
    }
}
