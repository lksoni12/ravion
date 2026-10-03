<?php

namespace App\Models;

use CodeIgniter\Model;

class ChemistModel extends Model
{
    protected $table = 'chemists';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'name',
        'contact_person',
        'stockist_name',
        'area',
        'contact',
        'territory_id',
        'created_by_user_id',
        'approval_status',
        'approved_by_user_id',
    ];
}
