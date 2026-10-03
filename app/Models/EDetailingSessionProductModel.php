<?php

namespace App\Models;

use CodeIgniter\Model;

class EDetailingSessionProductModel extends Model
{
    protected $table = 'e_detailing_session_products';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'session_id',
        'product_id',
        'sequence_no',
    ];

    public function productsForSession(int $sessionId): array
    {
        return $this->select('e_detailing_session_products.*, products.name as product_name, products.detail_pdf as detail_pdf, products.image_path as image_path')
            ->join('products', 'products.id = e_detailing_session_products.product_id', 'inner')
            ->where('e_detailing_session_products.session_id', $sessionId)
            ->orderBy('e_detailing_session_products.sequence_no', 'ASC')
            ->findAll();
    }
}
