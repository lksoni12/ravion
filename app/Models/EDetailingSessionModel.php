<?php

namespace App\Models;

use CodeIgniter\Model;

class EDetailingSessionModel extends Model
{
    protected $table = 'e_detailing_sessions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'e_detailing_doctor_id',
        'doctor_id',
        'field_plan_id',
        'plan_date',
        'started_at',
        'ended_at',
        'status',
    ];

    public function activeForDoctor(int $eDetailingDoctorId): ?array
    {
        $row = $this->where('e_detailing_doctor_id', $eDetailingDoctorId)
            ->where('status', 'active')
            ->orderBy('id', 'DESC')
            ->first();

        return $row ?: null;
    }
}
