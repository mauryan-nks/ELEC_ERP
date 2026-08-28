<?php
namespace App\Models;
use CodeIgniter\Model;
class ProductModel extends Model
{
    protected $table='products'; protected $primaryKey='id'; protected $returnType='array'; protected $useTimestamps=true;
    protected $allowedFields=['category_id','brand_id','sku','name','model','hsn_sac','product_type','is_serialized','serial_mode','low_stock_qty','default_sale_price','tax_percent','status'];
}
