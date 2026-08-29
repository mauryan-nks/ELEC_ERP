<?php

namespace App\Services;

class CustomerCommunicationService
{
    public function queueWelcome(int $customerId): void
    {
        $db=db_connect();
        $customer=$db->table('customers')->where('id',$customerId)->get()->getRowArray();
        $shop=$db->table('shop_settings')->where('id',1)->get()->getRowArray();
        if(!$customer)return;
        $phone=$customer['whatsapp_phone']?:$customer['phone'];
        if(!$phone)return;
        $tpl=$db->table('whatsapp_templates')->where(['event_key'=>'customer_welcome','is_active'=>1])->get()->getRowArray();
        $message=$tpl?strtr($tpl['message'],['{customer_name}'=>$customer['name'],'{store_name}'=>$shop['name']??'Shop']):('Welcome '.$customer['name'].'! Thank you for choosing '.($shop['name']??'our store').'. We are happy to have you with us.');
        $now=date('Y-m-d H:i:s');
        try{$db->table('whatsapp_queue')->insert(['customer_id'=>$customerId,'phone'=>$phone,'event_key'=>'customer_welcome','dedupe_key'=>'customer_welcome:'.$customerId,'message'=>$message,'message_type'=>'text','scheduled_at'=>$now,'status'=>'queued','created_at'=>$now,'updated_at'=>$now]);}catch(\Throwable $e){}
        try { (new WhatsAppQueueService())->processPending(); } catch(\Throwable $ignored) {}
    }
}
