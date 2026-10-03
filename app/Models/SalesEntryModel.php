<?php

namespace App\Models;

use CodeIgniter\Model;

class SalesEntryModel extends Model
{
    protected $table = 'sales_entries';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'entry_date',
        'sales_month',
        'product_id',
        'stockist_id',
        'matrix_row_key',
        'area',
        'opening_value',
        'primary_value',
        'secondary_value',
        'closing_value',
        'status',
        'created_by_user_id',
        'approved_by_user_id',
        'approval_note',
        'matrix_json',
        'locked_by_manager',
        'aiocd_sales',
        'mr_sales',
        'late_sales',
        'late_stock',
    ];
}
