<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminUserSeed extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $owner = $this->db->table('users')->where('email', 'owner@ravionpharma.com')->get()->getFirstRow('array');
        if (! $owner) {
            $this->db->table('users')->insert([
                'name' => 'Lakesh (Owner)',
                'username' => 'lakesh',
                'email' => 'owner@ravionpharma.com',
                'password_hash' => password_hash('Admin@123', PASSWORD_DEFAULT),
                'role' => 'owner',
                'area' => 'Global',
                'manager_user_id' => null,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $owner = $this->db->table('users')->where('email', 'owner@ravionpharma.com')->get()->getFirstRow('array');
        }

        $managerRecords = [
            ['name' => 'S. K. Sharma (MGR)', 'username' => 'manager_pune', 'email' => 'manager.pune@ravionpharma.com', 'area' => 'Pune'],
            ['name' => 'R. D. Deshmukh (MGR)', 'username' => 'manager_nagpur', 'email' => 'manager.nagpur@ravionpharma.com', 'area' => 'Nagpur'],
        ];

        $managerIds = [];
        foreach ($managerRecords as $record) {
            $existing = $this->db->table('users')->where('email', $record['email'])->get()->getFirstRow('array');
            if (! $existing) {
                $this->db->table('users')->insert([
                    'name' => $record['name'],
                    'username' => $record['username'],
                    'email' => $record['email'],
                    'password_hash' => password_hash('Admin@123', PASSWORD_DEFAULT),
                    'role' => 'manager',
                    'area' => $record['area'],
                    'manager_user_id' => null,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $existing = $this->db->table('users')->where('email', $record['email'])->get()->getFirstRow('array');
            }

            if ($existing) {
                $managerIds[$record['area']] = (int) $existing['id'];
            }
        }

        $mrRecords = [
            ['name' => 'Amit Kumar (MR)', 'username' => 'mr_amit', 'email' => 'mr.amit@ravionpharma.com', 'area' => 'Pune'],
            ['name' => 'Rahul Verma (MR)', 'username' => 'mr_rahul', 'email' => 'mr.rahul@ravionpharma.com', 'area' => 'Nagpur'],
        ];

        foreach ($mrRecords as $record) {
            $existing = $this->db->table('users')->where('email', $record['email'])->get()->getFirstRow('array');
            if (! $existing) {
                $this->db->table('users')->insert([
                    'name' => $record['name'],
                    'username' => $record['username'],
                    'email' => $record['email'],
                    'password_hash' => password_hash('Admin@123', PASSWORD_DEFAULT),
                    'role' => 'mr',
                    'area' => $record['area'],
                    'manager_user_id' => $managerIds[$record['area']] ?? null,
                    'is_active' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $legacyAdmin = $this->db->table('users')->where('email', 'admin@ravionpharma.com')->get()->getFirstRow('array');
        if ($legacyAdmin) {
            $this->db->table('users')->where('id', $legacyAdmin['id'])->update([
                'role' => 'owner',
                'area' => 'Global',
                'username' => $legacyAdmin['username'] ?: 'admin',
                'updated_at' => $now,
            ]);
        }

        if ($this->db->tableExists('app_settings')) {
            $existingLogo = $this->db->table('app_settings')->where('setting_key', 'company_logo')->get()->getFirstRow('array');
            if (! $existingLogo) {
                $this->db->table('app_settings')->insert([
                    'setting_key' => 'company_logo',
                    'setting_value' => '',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if ($this->db->tableExists('settings')) {
            $existingLogo = $this->db->table('settings')->where('key_name', 'company_logo')->get()->getFirstRow('array');
            if (! $existingLogo) {
                $this->db->table('settings')->insert([
                    'key_name' => 'company_logo',
                    'value_text' => '',
                ]);
            }
        }
    }
}
