<?php

namespace App\Models;

use CodeIgniter\Model;

class DoctorModel extends Model
{
    protected $table = 'doctors';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'name',
        'speciality',
        'clinic_address',
        'contact',
        'category',
        'monthly_potential',
        'area_patch',
        'territory_id',
        'created_by_user_id',
        'approval_status',
        'approved_by_user_id',
        'assigned_to_user_id',
        'birthday',
        'anniversary',
    ];
}
