<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'name',
        'username',
        'email',
        'mobile',
        'password_hash',
        'role',
        'area',
        'manager_user_id',
        'is_active',
        'da_hq',
        'da_ex',
        'da_os',
        'monthly_target',
    ];
}
