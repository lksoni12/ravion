<?php

namespace App\Models;

use CodeIgniter\Model;

class ExpenseModel extends Model
{
    protected $table = 'expenses';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'submitted_by_user_id',
        'title',
        'category',
        'expense_month',
        'amount',
        'bill_image_path',
        'status',
        'approved_by_user_id',
        'approval_note',
        'details_json',
        'rejection_reason',
    ];
}
