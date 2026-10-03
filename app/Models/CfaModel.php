<?php

namespace App\Models;

use CodeIgniter\Model;

class CfaModel extends Model
{
    protected $table = 'cfas';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'name',
        'state',
        'territory_id',
        'contact_person',
        'mobile',
        'email',
        'gst',
        'address',
        'created_by_user_id',
    ];
}
