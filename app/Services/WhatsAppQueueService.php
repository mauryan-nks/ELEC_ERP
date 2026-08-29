<?php

namespace App\Services;

class WhatsAppQueueService
{
    public function processPending(int $limit=10): int
    {
        $db=db_connect(); $bridge=new WhatsAppBridge(); $count=0;
        $rows=$db->table('whatsapp_queue')->where('status','queued')->where('scheduled_at <=',date('Y-m-d H:i:s'))->where('attempts <',3)->orderBy('scheduled_at')->limit($limit)->get()->getResultArray();
        foreach($rows as $row){
            $db->table('whatsapp_queue')->where(['id'=>$row['id'],'status'=>'queued'])->update(['status'=>'processing','attempts'=>(int)$row['attempts']+1,'updated_at'=>date('Y-m-d H:i:s')]);
            try{
                if (($row['message_type'] ?? 'text') === 'media' && $row['event_key'] === 'invoice_sent_pdf' && empty($row['attachment_path']) && !empty($row['sale_id'])) {
                    $pdfPath=(new InvoicePdfService())->generate((int)$row['sale_id']);
                    $db->table('whatsapp_queue')->where('id',$row['id'])->update(['attachment_path'=>$pdfPath,'attachment_mime'=>'application/pdf','attachment_name'=>basename($pdfPath),'updated_at'=>date('Y-m-d H:i:s')]);
                    $row['attachment_path']=$pdfPath; $row['attachment_mime']='application/pdf'; $row['attachment_name']=basename($pdfPath);
                }
                if (($row['message_type'] ?? 'text') === 'media' && !empty($row['attachment_path']) && is_file($row['attachment_path'])) {
                    $bridge->sendMedia($row['phone'],$row['message'],base64_encode((string)file_get_contents($row['attachment_path'])),$row['attachment_mime'] ?: 'application/pdf',$row['attachment_name'] ?: basename($row['attachment_path']));
                } else {
                    $bridge->send($row['phone'],$row['message']);
                }
                $db->table('whatsapp_queue')->where('id',$row['id'])->update(['status'=>'sent','sent_at'=>date('Y-m-d H:i:s'),'last_error'=>null,'updated_at'=>date('Y-m-d H:i:s')]); $count++;
            }catch(\Throwable $e){
                $attempts=(int)$row['attempts']+1; $db->table('whatsapp_queue')->where('id',$row['id'])->update(['status'=>$attempts>=3?'failed':'queued','last_error'=>mb_substr($e->getMessage(),0,2000),'updated_at'=>date('Y-m-d H:i:s')]);
            }
        }
        return $count;
    }
}
