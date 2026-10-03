<?php

namespace App\Controllers;

use Config\Database;
use Throwable;

class DbSyncController extends BaseController
{
    public function sync()
    {
        $providedToken = (string) ($this->request->getHeaderLine('X-DB-SYNC-TOKEN') ?: $this->request->getPost('token') ?: $this->request->getGet('token'));
        $expectedToken = (string) (getenv('db.sync.token') ?: getenv('DB_SYNC_TOKEN') ?: '');

        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            return $this->response->setStatusCode(401)->setJSON([
                'ok' => false,
                'message' => 'Unauthorized. Invalid or missing sync token.',
            ]);
        }

        try {
            $config = new \Config\Database();
            $default = $config->default;

            if (($default['DBDriver'] ?? '') !== 'MySQLi') {
                return $this->response->setStatusCode(400)->setJSON([
                    'ok' => false,
                    'message' => 'DB sync API currently supports MySQLi only.',
                ]);
            }

            $this->createDatabaseIfMissing($default);

            $db = Database::connect();
            
            // Generate Backup before sync
            $backupFile = $this->backupDatabase($default);
            
            $migrationService = \Config\Services::migrations();
            $migrationService->setGroup('default');
            $migrationResult = $migrationService->latest();

            $repairReport = $this->repairSchema($db);

            if ((string) ($this->request->getPost('seed') ?: $this->request->getGet('seed')) === '1') {
                $seeder = \Config\Database::seeder();
                $seeder->call('InitialPharmaSeed');
                $seeder->call('AdminUserSeed');
            }

            return $this->response->setJSON([
                'ok' => true,
                'message' => 'Database sync completed.',
                'backup_file' => basename($backupFile ?? ''),
                'migrationResult' => $migrationResult,
                'repairReport' => $repairReport,
            ]);
        } catch (Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'DB sync failed: ' . $e->getMessage(),
            ]);
        }
    }

    public function clear()
    {
        return $this->response->setStatusCode(403)->setJSON([
            'ok' => false,
            'message' => 'Data deletion feature is disabled for the live environment.',
        ]);
    }
    
    public function listBackups()
    {
        $this->verifyToken();
        
        $backupDir = WRITEPATH . 'backups';
        if (!is_dir($backupDir)) {
            return $this->response->setJSON(['ok' => true, 'backups' => []]);
        }
        
        $files = array_diff(scandir($backupDir), ['.', '..']);
        $backups = [];
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                $backups[] = [
                    'filename' => $file,
                    'size' => filesize($backupDir . DIRECTORY_SEPARATOR . $file),
                    'created_at' => filemtime($backupDir . DIRECTORY_SEPARATOR . $file),
                ];
            }
        }
        
        usort($backups, fn($a, $b) => $b['created_at'] <=> $a['created_at']);
        
        return $this->response->setJSON(['ok' => true, 'backups' => $backups]);
    }

    public function backup()
    {
        $this->verifyToken();
        
        $config = new \Config\Database();
        $default = $config->default;
        
        $backupFile = $this->backupDatabase($default);
        
        if (empty($backupFile)) {
            return $this->response->setStatusCode(500)->setJSON([
                'ok' => false,
                'message' => 'Manual database backup failed. Please check logs.'
            ]);
        }
        
        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Manual database backup completed successfully.',
            'backup_file' => basename($backupFile)
        ]);
    }

    public function restore()
    {
        $this->verifyToken();
        
        $filename = $this->request->getPost('filename') ?: $this->request->getGet('filename');
        if (empty($filename)) {
            return $this->response->setStatusCode(400)->setJSON(['ok' => false, 'message' => 'Filename is required.']);
        }
        
        $backupPath = WRITEPATH . 'backups' . DIRECTORY_SEPARATOR . basename((string) $filename);
        if (!file_exists($backupPath)) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'Backup file not found.']);
        }
        
        $config = new \Config\Database();
        $default = $config->default;
        
        $host = escapeshellarg($default['hostname']);
        $user = escapeshellarg($default['username']);
        $pass = !empty($default['password']) ? '-p' . escapeshellarg($default['password']) : '';
        $port = escapeshellarg($default['port'] ?? 3306);
        $dbName = escapeshellarg($default['database']);
        
        $command = "mysql -h {$host} -P {$port} -u {$user} {$pass} {$dbName} < " . escapeshellarg($backupPath);
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            log_message('warning', "mysql import failed. Attempting PHP fallback. Output: " . implode("\n", $output));
            // PHP Fallback
            try {
                $db = Database::connect();
                $sql = file_get_contents($backupPath);
                
                // Extremely basic parsing for PHP fallback. Works best if backup generated by PHP fallback.
                $queries = explode(";\n", $sql);
                $db->transStart();
                $db->query('SET FOREIGN_KEY_CHECKS = 0');
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        $db->query($query);
                    }
                }
                $db->query('SET FOREIGN_KEY_CHECKS = 1');
                $db->transComplete();
                
                if ($db->transStatus() === false) {
                    return $this->response->setStatusCode(500)->setJSON([
                        'ok' => false,
                        'message' => 'Restore failed during PHP fallback execution.'
                    ]);
                }
            } catch (\Exception $e) {
                return $this->response->setStatusCode(500)->setJSON([
                    'ok' => false,
                    'message' => 'Restore failed: ' . $e->getMessage(),
                    'output' => $output
                ]);
            }
        }
        
        return $this->response->setJSON(['ok' => true, 'message' => "Successfully restored backup: {$filename}"]);
    }
    
    private function verifyToken()
    {
        $providedToken = (string) ($this->request->getHeaderLine('X-DB-SYNC-TOKEN') ?: $this->request->getPost('token') ?: $this->request->getGet('token'));
        $expectedToken = (string) (getenv('db.sync.token') ?: getenv('DB_SYNC_TOKEN') ?: '');

        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(['ok' => false, 'message' => 'Unauthorized. Invalid or missing sync token.']);
            exit;
        }
    }
    
    private function backupDatabase(array $default): string
    {
        $backupDir = WRITEPATH . 'backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }
        
        $filename = 'db_backup_' . date('Y_m_d_H_i_s') . '.sql';
        $filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;
        
        $host = escapeshellarg($default['hostname']);
        $user = escapeshellarg($default['username']);
        $pass = !empty($default['password']) ? '-p' . escapeshellarg($default['password']) : '';
        $port = escapeshellarg($default['port'] ?? 3306);
        $dbName = escapeshellarg($default['database']);
        
        $command = "mysqldump -h {$host} -P {$port} -u {$user} {$pass} {$dbName} > " . escapeshellarg($filepath);
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0) {
            log_message('warning', "mysqldump failed. Attempting PHP fallback. Output: " . implode("\n", $output));
            // PHP Fallback
            try {
                $db = Database::connect();
                $tables = $db->listTables();
                $sql = "SET FOREIGN_KEY_CHECKS=0;\n\n";

                foreach ($tables as $table) {
                    $create = $db->query("SHOW CREATE TABLE `$table`")->getRowArray();
                    $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                    $sql .= $create['Create Table'] . ";\n\n";

                    $rows = $db->table($table)->get()->getResultArray();
                    if (count($rows) > 0) {
                        foreach ($rows as $row) {
                            $values = array_map(function($val) use ($db) {
                                return $val === null ? 'NULL' : $db->escape($val);
                            }, array_values($row));
                            $sql .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                        }
                        $sql .= "\n\n";
                    }
                }
                $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
                
                file_put_contents($filepath, $sql);
            } catch (\Exception $e) {
                log_message('error', "PHP fallback backup failed: " . $e->getMessage());
                return '';
            }
        }
        
        return $filepath;
    }

    private function createDatabaseIfMissing(array $dbConfig): void
    {
        $host = (string) ($dbConfig['hostname'] ?? '127.0.0.1');
        $user = (string) ($dbConfig['username'] ?? 'root');
        $pass = (string) ($dbConfig['password'] ?? '');
        $name = (string) ($dbConfig['database'] ?? '');
        $port = (int) ($dbConfig['port'] ?? 3306);

        if ($name === '') {
            throw new \RuntimeException('Database name is empty in configuration.');
        }

        $mysqli = @new \mysqli($host, $user, $pass, '', $port);
        if ($mysqli->connect_errno) {
            throw new \RuntimeException('MySQL server connection failed: ' . $mysqli->connect_error);
        }

        $dbName = str_replace('`', '``', $name);
        $query = "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
        if (! $mysqli->query($query)) {
            $error = $mysqli->error;
            $mysqli->close();
            throw new \RuntimeException('Unable to create/check database: ' . $error);
        }

        $mysqli->close();
    }

    private function repairSchema($db): array
    {
        $forge = Database::forge($db);

        $tableDefinitions = [
            'doctors' => [
                'fields' => [
                    'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'name' => ['type' => 'VARCHAR', 'constraint' => 150],
                    'speciality' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                    'clinic_address' => ['type' => 'TEXT', 'null' => true],
                    'contact' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                    'category' => ['type' => 'VARCHAR', 'constraint' => 1, 'default' => 'C'],
                    'monthly_potential' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
                    'area_patch' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                    'territory_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                    'created_by_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                    'approval_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
                    'approved_by_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                    'assigned_to_user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                    'birthday' => ['type' => 'DATE', 'null' => true],
                    'anniversary' => ['type' => 'DATE', 'null' => true],
                    'created_at' => ['type' => 'DATETIME', 'null' => true],
                    'updated_at' => ['type' => 'DATETIME', 'null' => true],
                ],
                'primary' => 'id',
            ],
            'products' => [
                'fields' => [
                    'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'name' => ['type' => 'VARCHAR', 'constraint' => 180],
                    'pts_price' => ['type' => 'REAL', 'default' => 0],
                    'description' => ['type' => 'TEXT', 'null' => true],
                    'image_path' => ['type' => 'TEXT', 'null' => true],
                    'detail_pdf' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                    'packing' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
                    'display_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                    'created_at' => ['type' => 'DATETIME', 'null' => true],
                    'updated_at' => ['type' => 'DATETIME', 'null' => true],
                ],
                'primary' => 'id',
            ],
            'visits' => [
                'fields' => [
                    'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'doctor_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                    'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                    'chemist_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                    'planned_plan_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                    'e_detailing_session_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                    'visit_date' => ['type' => 'DATETIME'],
                    'dcr_date' => ['type' => 'DATE', 'null' => true],
                    'dcr_month' => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => true],
                    'duration_minutes' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                    'pob_value' => ['type' => 'REAL', 'default' => 0],
                    'sample_units' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                    'action_type' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'detailing'],
                    'is_deviation' => ['type' => 'INT', 'constraint' => 1, 'default' => 0],
                    'deviation_reason' => ['type' => 'TEXT', 'null' => true],
                    'created_by_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                    'feedback' => ['type' => 'TEXT', 'null' => true],
                    'next_objective' => ['type' => 'TEXT', 'null' => true],
                    'joint_call_with' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                    'report_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'submitted'],
                    'created_at' => ['type' => 'DATETIME', 'null' => true],
                    'updated_at' => ['type' => 'DATETIME', 'null' => true],
                ],
                'primary' => 'id',
            ],
            'users' => [
                'fields' => [
                    'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'name' => ['type' => 'VARCHAR', 'constraint' => 120],
                    'username' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
                    'email' => ['type' => 'VARCHAR', 'constraint' => 160],
                    'mobile' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
                    'password_hash' => ['type' => 'VARCHAR', 'constraint' => 255],
                    'role' => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'admin'],
                    'area' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                    'manager_user_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                    'is_active' => ['type' => 'INT', 'constraint' => 1, 'default' => 1],
                    'da_hq' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                    'da_ex' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                    'da_os' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                    'monthly_target' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                    'created_at' => ['type' => 'DATETIME', 'null' => true],
                    'updated_at' => ['type' => 'DATETIME', 'null' => true],
                ],
                'primary' => 'id',
            ],
            'app_settings' => [
                'fields' => [
                    'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'setting_key' => ['type' => 'VARCHAR', 'constraint' => 120],
                    'setting_value' => ['type' => 'TEXT', 'null' => true],
                    'created_at' => ['type' => 'DATETIME', 'null' => true],
                    'updated_at' => ['type' => 'DATETIME', 'null' => true],
                ],
                'primary' => 'id',
            ],
            'settings' => [
                'fields' => [
                    'key_name' => ['type' => 'VARCHAR', 'constraint' => 120],
                    'value_text' => ['type' => 'TEXT', 'null' => true],
                    'created_at' => ['type' => 'DATETIME', 'null' => true],
                    'updated_at' => ['type' => 'DATETIME', 'null' => true],
                ],
                'primary' => 'key_name',
            ],
            'chemists' => [
                'fields' => [
                    'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'name' => ['type' => 'VARCHAR', 'constraint' => 180],
                    'contact_person' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
                    'stockist_name' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
                    'area' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                    'contact' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
                    'territory_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                    'created_by_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                    'approval_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
                    'approved_by_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                    'created_at' => ['type' => 'DATETIME', 'null' => true],
                    'updated_at' => ['type' => 'DATETIME', 'null' => true],
                ],
                'primary' => 'id',
            ],
            'field_plans' => [
                'fields' => [
                    'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'plan_date' => ['type' => 'DATE'],
                    'plan_month' => ['type' => 'VARCHAR', 'constraint' => 7, 'null' => true],
                    'day_of_month' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                    'activity_type' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'tp'],
                    'doctor_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                    'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                    'chemist_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                    'objective' => ['type' => 'TEXT', 'null' => true],
                    'pob_amount' => ['type' => 'DOUBLE', 'null' => true],
                    'joint_call_with' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
                    'route_name' => ['type' => 'VARCHAR', 'constraint' => 180, 'null' => true],
                    'territory_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                    'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'planned'],
                    'created_by_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                    'assigned_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                    'approval_note' => ['type' => 'TEXT', 'null' => true],
                    'is_locked' => ['type' => 'INT', 'constraint' => 1, 'default' => 0],
                    'created_at' => ['type' => 'DATETIME', 'null' => true],
                    'updated_at' => ['type' => 'DATETIME', 'null' => true],
                ],
                'primary' => 'id',
            ],
            'slides' => [
                'fields' => [
                    'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                    'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                    'title' => ['type' => 'VARCHAR', 'constraint' => 180],
                    'file_path' => ['type' => 'VARCHAR', 'constraint' => 255],
                    'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'active'],
                    'created_by_user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                    'slide_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
                    'created_at' => ['type' => 'DATETIME', 'null' => true],
                    'updated_at' => ['type' => 'DATETIME', 'null' => true],
                ],
                'primary' => 'id',
            ],
        ];

        $createdTables = [];
        $addedColumns = [];

        foreach ($tableDefinitions as $table => $definition) {
            if (! $db->tableExists($table)) {
                $forge->addField($definition['fields']);
                $forge->addKey($definition['primary'], true);
                $forge->createTable($table, true);
                $createdTables[] = $table;
                continue;
            }

            foreach ($definition['fields'] as $column => $columnDefinition) {
                if (! $db->fieldExists($column, $table)) {
                    $forge->addColumn($table, [$column => $columnDefinition]);
                    $addedColumns[] = $table . '.' . $column;
                }
            }
        }

        return [
            'createdTables' => $createdTables,
            'addedColumns' => $addedColumns,
        ];
    }
}
