<?php

namespace App\Models;

use CodeIgniter\Model;

class SponsorshipModel extends Model
{
    protected $table = 'sponsorships';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'doctor_id',
        'amount',
        'purpose',
        'status',
        'created_by_user_id',
        'approved_by_user_id',
        'approval_note',
        'activity_type',
        'activity_date',
        'remarks',
        'current_roi',
        'expected_roi',
    ];
}
