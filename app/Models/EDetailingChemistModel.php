<?php

namespace App\Models;

use CodeIgniter\Model;

class EDetailingChemistModel extends Model
{
    protected $table = 'e_detailing_chemists';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'plan_date',
        'chemist_id',
        'source',
        'status',
    ];

    public function activeByDate(string $planDate): array
    {
        return $this->select('e_detailing_chemists.*, chemists.name as chemist_name, chemists.area as chemist_area')
            ->join('chemists', 'chemists.id = e_detailing_chemists.chemist_id', 'inner')
            ->where('e_detailing_chemists.plan_date', $planDate)
            ->where('e_detailing_chemists.status', 'active')
            ->orderBy('chemists.name', 'ASC')
            ->findAll();
    }
}
