<?php

namespace App\Models;

use CodeIgniter\Model;

class UserTerritoryModel extends Model
{
    protected $table = 'user_territories';
    protected $primaryKey = 'user_id'; // Note: composite key, but CI Model uses one primary key or none.
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'user_id',
        'territory_id',
    ];
}
