<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ShopSeeder extends Seeder
{
    public function run()
    {
        $now=date('Y-m-d H:i:s');
        $this->db->query(
            "INSERT INTO shop_settings (id,name,invoice_prefix,purchase_prefix,currency,created_at,updated_at)
             VALUES (1,'My Mobile & Electronics Shop','INV','PUR','INR',?,?)
             ON DUPLICATE KEY UPDATE updated_at=VALUES(updated_at)",[$now,$now]
        );

        foreach([
            ['Mobile Phones','device'],['Tablets','device'],['Laptops','device'],['Chargers','accessory'],['Cables','accessory'],
            ['Earphones','accessory'],['Covers & Cases','accessory'],['Screen Protectors','accessory']
        ] as [$name,$type]){
            $existing=$this->db->table('categories')->where('name',$name)->get()->getRowArray();
            if($existing){$this->db->table('categories')->where('id',$existing['id'])->update(['type'=>$type,'status'=>'active','updated_at'=>$now]);}
            else{$this->db->table('categories')->insert(['name'=>$name,'type'=>$type,'status'=>'active','created_at'=>$now,'updated_at'=>$now]);}
        }
        foreach(['Apple','Samsung','Xiaomi','OnePlus','Realme','Vivo','Oppo','Motorola','Nothing','boAt'] as $name){
            $existing=$this->db->table('brands')->where('name',$name)->get()->getRowArray();
            if($existing){$this->db->table('brands')->where('id',$existing['id'])->update(['status'=>'active','updated_at'=>$now]);}
            else{$this->db->table('brands')->insert(['name'=>$name,'status'=>'active','created_at'=>$now,'updated_at'=>$now]);}
        }
        foreach([
            ['Invoice sent','invoice_sent','Hello {customer_name}, your invoice {invoice_no} for ₹{grand_total} has been created. Thank you for shopping with {store_name}.'],
            ['Payment due','payment_due','Hello {customer_name}, ₹{due_amount} is pending against invoice {invoice_no}. Please ignore if already paid. - {store_name}'],
            ['Payment received','payment_received','Thank you {customer_name}. We received ₹{paid_amount} for invoice {invoice_no}. - {store_name}']
        ] as [$name,$event,$message]){
            $existing=$this->db->table('whatsapp_templates')->where('name',$name)->get()->getRowArray();
            if($existing){$this->db->table('whatsapp_templates')->where('id',$existing['id'])->update(['event_key'=>$event,'message'=>$message,'is_active'=>1,'updated_at'=>$now]);}
            else{$this->db->table('whatsapp_templates')->insert(['name'=>$name,'event_key'=>$event,'message'=>$message,'is_active'=>1,'created_at'=>$now,'updated_at'=>$now]);}
        }
    }
}
