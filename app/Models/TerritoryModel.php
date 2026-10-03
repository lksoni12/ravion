<?php

namespace App\Models;

use CodeIgniter\Model;

class TerritoryModel extends Model
{
    protected $table = 'territories';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'name',
        'type',
        'parent_id',
    ];
}
