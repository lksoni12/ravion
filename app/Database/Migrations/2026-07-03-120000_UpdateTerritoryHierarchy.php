<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateTerritoryHierarchy extends Migration
{
    public function up()
    {
        // 1. Add parent_id to territories table
        if (!$this->db->fieldExists('parent_id', 'territories')) {
            $this->forge->addColumn('territories', [
                'parent_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => true,
                    'after'      => 'type'
                ]
            ]);
            
            // Add foreign key constraint if supported (avoiding issues in SQLite for dynamic column addition)
            $driver = strtolower((string) ($this->db->DBDriver ?? ''));
            if ($driver !== 'sqlite3') {
                $this->db->query("ALTER TABLE territories ADD CONSTRAINT fk_territories_parent FOREIGN KEY (parent_id) REFERENCES territories(id) ON DELETE CASCADE");
            }
        }

        // Helper to add territory_id to other tables
        $tablesToUpdate = ['doctors', 'chemists', 'stockists', 'cfas', 'field_plans'];
        foreach ($tablesToUpdate as $table) {
            if (!$this->db->fieldExists('territory_id', $table)) {
                $this->forge->addColumn($table, [
                    'territory_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'null'       => true
                    ]
                ]);
                
                $driver = strtolower((string) ($this->db->DBDriver ?? ''));
                if ($driver !== 'sqlite3') {
                    $this->db->query("ALTER TABLE `{$table}` ADD CONSTRAINT `fk_{$table}_territory` FOREIGN KEY (territory_id) REFERENCES territories(id) ON DELETE SET NULL");
                }
            }
        }

        // 2. Perform Data Migration - Map existing string values to territory_id
        $db = \Config\Database::connect();

        // Migrate CFAs (mapped to state type territories)
        $cfas = $db->table('cfas')->get()->getResultArray();
        foreach ($cfas as $cfa) {
            $stateName = trim((string)($cfa['state'] ?? ''));
            if ($stateName !== '') {
                $t = $db->table('territories')->where('name', $stateName)->get()->getRowArray();
                if ($t) {
                    $db->table('cfas')->where('id', $cfa['id'])->update(['territory_id' => $t['id']]);
                }
            }
        }

        // Migrate Stockists
        $stockists = $db->table('stockists')->get()->getResultArray();
        foreach ($stockists as $st) {
            $areaName = trim((string)($st['area'] ?? ''));
            if ($areaName !== '') {
                $t = $db->table('territories')->where('name', $areaName)->get()->getRowArray();
                if ($t) {
                    $db->table('stockists')->where('id', $st['id'])->update(['territory_id' => $t['id']]);
                }
            }
        }

        // Migrate Doctors
        $doctors = $db->table('doctors')->get()->getResultArray();
        foreach ($doctors as $dr) {
            $areaName = trim((string)($dr['area_patch'] ?? ''));
            if ($areaName !== '') {
                $t = $db->table('territories')->where('name', $areaName)->get()->getRowArray();
                if ($t) {
                    $db->table('doctors')->where('id', $dr['id'])->update(['territory_id' => $t['id']]);
                }
            }
        }

        // Migrate Chemists
        $chemists = $db->table('chemists')->get()->getResultArray();
        foreach ($chemists as $ch) {
            $areaName = trim((string)($ch['area'] ?? ''));
            if ($areaName !== '') {
                $t = $db->table('territories')->where('name', $areaName)->get()->getRowArray();
                if ($t) {
                    $db->table('chemists')->where('id', $ch['id'])->update(['territory_id' => $t['id']]);
                }
            }
        }

        // Migrate Field Plans
        $plans = $db->table('field_plans')->get()->getResultArray();
        foreach ($plans as $pl) {
            $routeName = trim((string)($pl['route_name'] ?? ''));
            if ($routeName !== '') {
                $t = $db->table('territories')->where('name', $routeName)->get()->getRowArray();
                if ($t) {
                    $db->table('field_plans')->where('id', $pl['id'])->update(['territory_id' => $t['id']]);
                }
            }
        }
    }

    public function down()
    {
        $tablesToDrop = ['doctors', 'chemists', 'stockists', 'cfas', 'field_plans'];
        $driver = strtolower((string) ($this->db->DBDriver ?? ''));

        foreach ($tablesToDrop as $table) {
            if ($this->db->fieldExists('territory_id', $table)) {
                if ($driver !== 'sqlite3') {
                    $this->db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `fk_{$table}_territory`");
                }
                $this->forge->dropColumn($table, 'territory_id');
            }
        }

        if ($this->db->fieldExists('parent_id', 'territories')) {
            if ($driver !== 'sqlite3') {
                $this->db->query("ALTER TABLE territories DROP FOREIGN KEY fk_territories_parent");
            }
            $this->forge->dropColumn('territories', 'parent_id');
        }
    }
}
