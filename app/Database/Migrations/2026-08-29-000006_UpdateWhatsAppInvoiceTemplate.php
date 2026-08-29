<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UpdateWhatsAppInvoiceTemplate extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('whatsapp_templates')) return;
        $staff=$this->db->table('whatsapp_templates')->where('event_key','staff_welcome')->countAllResults();
        if($staff===0){ $this->db->table('whatsapp_templates')->insert(['name'=>'Staff Welcome','event_key'=>'staff_welcome','message'=>'Welcome {user_name}! Your account at {store_name} has been created.','is_active'=>1,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]); }
        $row=$this->db->table('whatsapp_templates')->where('event_key','invoice_sent')->get()->getRowArray();
        if ($row && !str_contains((string)$row['message'],'{items}')) {
            $message=(string)$row['message'];
            $message .= "\n\nItems:\n{items}\n\nPaid: ₹{paid_amount}\nBalance due: ₹{due_amount}";
            $this->db->table('whatsapp_templates')->where('id',$row['id'])->update(['message'=>$message,'updated_at'=>date('Y-m-d H:i:s')]);
        }
    }

    public function down() {}
}
