<?php

namespace App\Models;

use CodeIgniter\Model;

class EDetailingDoctorModel extends Model
{
    protected $table = 'e_detailing_doctors';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'plan_date',
        'doctor_id',
        'field_plan_id',
        'source',
        'status',
    ];

    public function activeByDate(string $planDate): array
    {
        return $this->select('e_detailing_doctors.*, doctors.name as doctor_name, doctors.speciality as doctor_speciality, doctors.category as doctor_category')
            ->join('doctors', 'doctors.id = e_detailing_doctors.doctor_id', 'inner')
            ->where('e_detailing_doctors.plan_date', $planDate)
            ->where('e_detailing_doctors.status', 'active')
            ->orderBy('doctors.name', 'ASC')
            ->findAll();
    }

    public function syncFromTourPlan(string $planDate, array $plans): void
    {
        foreach ($plans as $plan) {
            $doctorId = (int) ($plan['doctor_id'] ?? 0);
            if ($doctorId <= 0) {
                continue;
            }

            $existing = $this->where('plan_date', $planDate)
                ->where('doctor_id', $doctorId)
                ->first();

            if (! $existing) {
                $this->insert([
                    'plan_date' => $planDate,
                    'doctor_id' => $doctorId,
                    'field_plan_id' => (int) ($plan['id'] ?? 0) ?: null,
                    'source' => 'tour_plan',
                    'status' => 'active',
                ]);
                continue;
            }

            if (($existing['status'] ?? '') === 'active') {
                continue;
            }

            if (($existing['source'] ?? '') === 'manual') {
                $this->update((int) $existing['id'], [
                    'status' => 'active',
                    'field_plan_id' => (int) ($plan['id'] ?? 0) ?: null,
                ]);
            }
        }
    }
}
