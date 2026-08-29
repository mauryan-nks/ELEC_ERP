<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class QueueDueReminders extends BaseCommand
{
    protected $group='Shop'; protected $name='reminders:queue'; protected $description='Queue one WhatsApp payment reminder per unpaid invoice per day.';
    public function run(array $params)
    {
        $db=db_connect(); $today=date('Y-m-d'); $shop=$db->table('shop_settings')->where('id',1)->get()->getRowArray();
        $sales=$db->table('sales s')->select('s.*,c.name customer_name,c.phone,c.whatsapp_phone')->join('customers c','c.id=s.customer_id')
            ->where('s.due_amount >',0)->whereIn('s.payment_status',['unpaid','partial'])
            ->groupStart()->where('s.due_date',null)->orWhere('s.due_date <=',$today)->groupEnd()->get()->getResultArray();
        $count=0;
        foreach($sales as $sale){
            $tpl=$db->table('whatsapp_templates')->where(['event_key'=>'payment_due','is_active'=>1])->get()->getRowArray(); if(!$tpl)continue;
            $phone=$sale['whatsapp_phone']?:$sale['phone']; if(!$phone)continue; $dedupe='payment_due:'.$sale['id'].':'.$today;
            $msg=strtr($tpl['message'],['{customer_name}'=>$sale['customer_name'],'{invoice_no}'=>$sale['invoice_no'],'{grand_total}'=>number_format((float)$sale['grand_total'],2,'.',''),'{paid_amount}'=>number_format((float)$sale['paid_amount'],2,'.',''),'{due_amount}'=>number_format((float)$sale['due_amount'],2,'.',''),'{due_date}'=>$sale['due_date']??'','{store_name}'=>$shop['name']??'Shop']);
            try{$db->table('whatsapp_queue')->insert(['customer_id'=>$sale['customer_id'],'sale_id'=>$sale['id'],'phone'=>$phone,'event_key'=>'payment_due','dedupe_key'=>$dedupe,'message'=>$msg,'scheduled_at'=>date('Y-m-d H:i:s'),'status'=>'queued','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);$count++;}catch(\Throwable $e){/* duplicate today */}
        }
        CLI::write("Queued {$count} due reminder(s).",'green');
    }
}
