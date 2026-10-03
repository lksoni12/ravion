<?php

namespace App\Models;

use CodeIgniter\Model;

class VisitModel extends Model
{
    protected $table = 'visits';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'doctor_id',
        'product_id',
        'chemist_id',
        'planned_plan_id',
        'e_detailing_session_id',
        'visit_date',
        'dcr_date',
        'dcr_month',
        'duration_minutes',
        'pob_value',
        'sample_units',
        'action_type',
        'is_deviation',
        'deviation_reason',
        'created_by_user_id',
        'feedback',
        'next_objective',
        'joint_call_with',
        'report_status',
    ];
}
