<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRemindersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INTEGER',
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'       => 'INTEGER',
            ],
            'person_name' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
            ],
            'event_type' => [
                'type'       => 'VARCHAR',
                'constraint' => '50',
                'default'    => 'birthday', // or 'anniversary'
            ],
            'event_date' => [
                'type' => 'DATE',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('reminders');
    }

    public function down()
    {
        $this->forge->dropTable('reminders');
    }
}
