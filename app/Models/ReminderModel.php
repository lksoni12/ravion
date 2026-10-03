<?php

namespace App\Models;

use CodeIgniter\Model;

class ReminderModel extends Model
{
    protected $table = 'reminders';
    protected $primaryKey = 'id';
    
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'user_id', 
        'person_name', 
        'event_type', 
        'event_date', 
        'notes'
    ];
    
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
