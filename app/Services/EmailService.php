<?php

namespace App\Services;

use RuntimeException;

class EmailService
{
    public function sendInvoice(int $saleId): bool
    {
        $db=db_connect();
        $shop=$db->table('shop_settings')->where('id',1)->get()->getRowArray() ?? [];
        if(empty($shop['email_enabled']) || empty($shop['email_invoice_enabled'])) return false;
        $sale=$db->table('sales s')->select('s.*,c.name customer_name,c.email customer_email')->join('customers c','c.id=s.customer_id')->where('s.id',$saleId)->get()->getRowArray();
        if(!$sale || empty($sale['customer_email'])) return false;
        $host=trim((string)($shop['email_smtp_host']??'')); $user=trim((string)($shop['email_smtp_user']??''));
        $from=trim((string)($shop['email_from_address']??$shop['email']??''));
        if($host===''||$from==='') throw new RuntimeException('SMTP host and From Email are required.');
        $password='';
        if(!empty($shop['email_smtp_password'])) $password=(string)service('encrypter')->decrypt($shop['email_smtp_password']);
        $crypto=($shop['email_smtp_encryption']??'tls'); $crypto=$crypto==='none'?'':$crypto;
        $email=service('email');
        $email->initialize([
            'protocol'=>'smtp','SMTPHost'=>$host,'SMTPPort'=>(int)($shop['email_smtp_port']??587),'SMTPUser'=>$user,'SMTPPass'=>$password,
            'SMTPAuthMethod'=>'login','SMTPCrypto'=>$crypto,'mailType'=>'html','charset'=>'UTF-8','newline'=>"\r\n",'CRLF'=>"\r\n",
        ]);
        $email->setFrom($from,$shop['email_from_name']??$shop['name']??'Shop');
        $email->setTo($sale['customer_email']);
        $email->setSubject('Invoice '.$sale['invoice_no'].' - '.($shop['name']??'Shop'));
        $email->setMessage('<p>Dear '.esc($sale['customer_name']).',</p><p>Please find your invoice <strong>'.esc($sale['invoice_no']).'</strong> attached.</p><p>Total: '.esc($shop['currency']??'INR').' '.number_format((float)$sale['grand_total'],2).'</p><p>Thank you for your business.</p>');
        $pdf=(new InvoicePdfService())->generate($saleId);
        $email->attach($pdf,'attachment',basename($pdf),'application/pdf');
        if(!$email->send()) throw new RuntimeException('Invoice email could not be sent: '.$email->printDebugger(['headers']));
        return true;
    }
}
