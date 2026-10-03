<?php

namespace App\Models;

use CodeIgniter\Model;

class SlideModel extends Model
{
    protected $table = 'slides';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'product_id',
        'title',
        'file_path',
        'status',
        'created_by_user_id',
        'slide_order',
    ];
}
