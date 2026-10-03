<?php

namespace App\Controllers;

use Config\Database;
use App\Models\UserModel;
use Throwable;

class AuthController extends BaseController
{
    private UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function login()
    {
        $initError = $this->ensureDatabaseReady();
        if ($initError !== null) {
            return view('auth/login', [
                'title' => 'Login',
                'error' => 'Database initialization failed: ' . $initError,
            ]);
        }

        if (session()->get('is_logged_in')) {
            return redirect()->to('/phadmin/dashboard');
        }

        return view('auth/login', ['title' => 'Login']);
    }

    public function authenticate()
    {
        $initError = $this->ensureDatabaseReady();
        if ($initError !== null) {
            return redirect()->to('/phadmin/login')->with('error', 'Database initialization failed: ' . $initError);
        }

        $loginId = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        $user = $this->userModel
            ->groupStart()
                ->where('email', $loginId)
                ->orWhere('username', $loginId)
            ->groupEnd()
            ->first();

        if (! $user || (int) $user['is_active'] !== 1 || ! password_verify($password, $user['password_hash'])) {
            return redirect()->to('/phadmin/login')->with('error', 'Invalid credentials.');
        }

        session()->set([
            'is_logged_in' => true,
            'user_id' => $user['id'],
            'user_name' => $user['name'],
            'user_email' => $user['email'],
            'user_role' => $user['role'],
            'user_area' => $user['area'] ?? '',
            'manager_user_id' => $user['manager_user_id'] ?? null,
        ]);

        return redirect()->to('/phadmin/dashboard')->with('success', 'Welcome back, ' . $user['name'] . '!');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/phadmin/login')->with('success', 'Logged out successfully.');
    }

    private function ensureDatabaseReady(): ?string
    {
        try {
            $db = Database::connect();

            if ($db->tableExists('users')) {
                return null;
            }

            $migrationService = \Config\Services::migrations();
            $migrationService->setGroup('default');
            $migrationService->latest();

            if (! $db->tableExists('users')) {
                return 'Users table not found after migration.';
            }

            $userCount = (int) $db->table('users')->countAllResults();
            if ($userCount === 0) {
                $seeder = \Config\Database::seeder();
                $seeder->call('InitialPharmaSeed');
                $seeder->call('AdminUserSeed');
            }

            return null;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }
}
