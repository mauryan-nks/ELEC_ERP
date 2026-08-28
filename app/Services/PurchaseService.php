<?php

namespace App\Services;

use RuntimeException;

class PurchaseService
{
    public function create(array $data, ?int $userId=null): int
    {
        $db=db_connect(); $inventory=new InventoryService(); $numbers=new DocumentNumberService(); $items=$data['items']??[];
        if($items===[]) throw new RuntimeException('Add at least one purchase item.');
        $db->transBegin();
        try{
            $purchaseNo=$numbers->next('purchase'); $subtotal=0.0; $taxTotal=0.0;
            foreach($items as $item){
                $qty=(float)($item['qty']??0); $cost=(float)($item['unit_cost']??0); $tax=(float)($item['tax_percent']??0);
                if($qty<=0||$cost<0) throw new RuntimeException('Invalid purchase quantity/cost.');
                $base=$qty*$cost; $subtotal+=$base; $taxTotal+=$base*$tax/100;
            }
            $grand=round($subtotal+$taxTotal,2); $paid=min(max((float)($data['paid_amount']??0),0),$grand);
            $db->table('purchases')->insert([
                'supplier_id'=>($data['supplier_id']??null)?:null,'purchase_no'=>$purchaseNo,'supplier_invoice_no'=>($data['supplier_invoice_no']??'')?:null,
                'purchase_date'=>$data['purchase_date']??date('Y-m-d'),'subtotal'=>$subtotal,'tax_total'=>$taxTotal,'grand_total'=>$grand,
                'paid_amount'=>$paid,'due_amount'=>$grand-$paid,'notes'=>($data['notes']??'')?:null,'created_by'=>$userId,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')
            ]);
            $purchaseId=(int)$db->insertID();
            foreach($items as $item){
                $qty=(float)$item['qty']; $cost=(float)$item['unit_cost']; $tax=(float)($item['tax_percent']??0); $line=round(($qty*$cost)*(1+$tax/100),2);
                $db->table('purchase_items')->insert(['purchase_id'=>$purchaseId,'product_id'=>(int)$item['product_id'],'qty'=>$qty,'unit_cost'=>$cost,'tax_percent'=>$tax,'line_total'=>$line,'created_at'=>date('Y-m-d H:i:s')]);
                $inventory->receiveLot((int)$item['product_id'],'purchase',$purchaseId,$qty,$cost,is_array($item['units']??null)?$item['units']:[],$purchaseNo,$userId);
            }
            if($paid>0){
                $db->table('payments')->insert(['purchase_id'=>$purchaseId,'amount'=>$paid,'method'=>$data['payment_method']??'cash','reference_no'=>($data['payment_reference']??'')?:null,'paid_at'=>date('Y-m-d H:i:s'),'created_by'=>$userId,'created_at'=>date('Y-m-d H:i:s')]);
            }
            $db->transCommit(); return $purchaseId;
        }catch(\Throwable $e){ $db->transRollback(); throw $e; }
    }
}
