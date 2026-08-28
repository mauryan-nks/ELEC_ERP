<?php
namespace App\Models;
use CodeIgniter\Model;
class SaleModel extends Model
{
    protected $table='sales'; protected $primaryKey='id'; protected $returnType='array'; protected $useTimestamps=true;
    protected $allowedFields=['customer_id','invoice_no','sale_date','sale_type','subtotal','discount_total','tax_total','grand_total','paid_amount','due_amount','payment_status','notes','internal_notes','created_by'];
}
