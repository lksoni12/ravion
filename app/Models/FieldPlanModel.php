<?php

namespace App\Models;

use CodeIgniter\Model;

class FieldPlanModel extends Model
{
    protected $table = 'field_plans';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'plan_date',
        'plan_month',
        'day_of_month',
        'activity_type',
        'doctor_id',
        'product_id',
        'chemist_id',
        'objective',
        'pob_amount',
        'joint_call_with',
        'route_name',
        'territory_id',
        'status',
        'created_by_user_id',
        'assigned_user_id',
        'approval_note',
        'is_locked',
    ];
}
