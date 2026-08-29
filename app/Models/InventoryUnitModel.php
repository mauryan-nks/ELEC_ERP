<?php
namespace App\Models;
use CodeIgniter\Model;
class InventoryUnitModel extends Model
{
    protected $table='inventory_units'; protected $primaryKey='id'; protected $returnType='array'; protected $useTimestamps=true;
    protected $allowedFields=['product_id','stock_lot_id','imei1','imei2','serial_no','unique_id','color','storage_variant','status','sold_sale_item_id'];
}
