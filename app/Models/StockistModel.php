<?php

namespace App\Models;

use CodeIgniter\Model;

class StockistModel extends Model
{
    protected $table = 'stockists';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'name',
        'area',
        'territory_id',
        'cfa_id',
        'contact_person',
        'mobile',
        'email',
        'gst',
        'city',
        'address',
        'created_by_user_id',
    ];
}
