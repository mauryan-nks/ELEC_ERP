<?php

namespace App\Commands;

use App\Services\WhatsAppBridge;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ProcessWhatsAppQueue extends BaseCommand
{
    protected $group='Shop'; protected $name='whatsapp:process'; protected $description='Send queued WhatsApp messages through the local WhatsApp Web bridge.';

    public function run(array $params)
    {
        $db=db_connect(); $bridge=new WhatsAppBridge();
        $rows=$db->table('whatsapp_queue')->where('status','queued')->where('scheduled_at <=',date('Y-m-d H:i:s'))->where('attempts <',3)->orderBy('scheduled_at')->limit(25)->get()->getResultArray();
        foreach($rows as $row){
            $db->table('whatsapp_queue')->where(['id'=>$row['id'],'status'=>'queued'])->update(['status'=>'processing','attempts'=>(int)$row['attempts']+1,'updated_at'=>date('Y-m-d H:i:s')]);
            try{
                $bridge->send($row['phone'],$row['message']);
                $db->table('whatsapp_queue')->where('id',$row['id'])->update(['status'=>'sent','sent_at'=>date('Y-m-d H:i:s'),'last_error'=>null,'updated_at'=>date('Y-m-d H:i:s')]);
                CLI::write('Sent queue #'.$row['id'],'green');
            }catch(\Throwable $e){
                $attempts=(int)$row['attempts']+1;
                $db->table('whatsapp_queue')->where('id',$row['id'])->update(['status'=>$attempts>=3?'failed':'queued','last_error'=>mb_substr($e->getMessage(),0,2000),'updated_at'=>date('Y-m-d H:i:s')]);
                CLI::error('Queue #'.$row['id'].': '.$e->getMessage());
            }
        }
        CLI::write('Processed '.count($rows).' queued message(s).');
    }
}
