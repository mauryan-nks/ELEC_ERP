<?php

namespace App\Services;

use RuntimeException;

class SaleService
{
    public function create(array $data, ?int $userId=null): int
    {
        $db=db_connect();
        $inventory=new InventoryService();
        $numbers=new DocumentNumberService();
        $items=$data['items']??[];
        if($items===[]) throw new RuntimeException('Add at least one sale item.');

        $shop=$db->table('shop_settings')->where('id',1)->get()->getRowArray() ?? [];
        $gstEnabled=!empty($data['gst_enabled']);
        $discountEnabled=!empty($data['discount_enabled']);
        $gstMode=in_array($data['gst_mode']??($shop['invoice_default_gst_mode']??'inclusive'),['inclusive','exclusive'],true)?($data['gst_mode']??($shop['invoice_default_gst_mode']??'inclusive')):'inclusive';

        $db->transBegin();
        try{
            $customerId=(int)($data['customer_id']??0);
            if($customerId<1){
                $customer=$data['customer']??[];
                $name=trim((string)($customer['name']??''));
                $phone=trim((string)($customer['phone']??''));
                if($name===''||$phone==='') throw new RuntimeException('Select a customer or add a new customer with name and phone.');
                $existing=$db->table('customers')->where('phone',$phone)->get()->getRowArray();
                if($existing){
                    $customerId=(int)$existing['id'];
                } else {
                    $db->table('customers')->insert([
                        'name'=>$name,'phone'=>$phone,
                        'whatsapp_phone'=>trim((string)($customer['whatsapp_phone']??$phone)),
                        'email'=>trim((string)($customer['email']??''))?:null,
                        'address'=>trim((string)($customer['address']??''))?:null,
                        'gstin'=>trim((string)($customer['gstin']??''))?:null,
                        'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')
                    ]);
                    $customerId=(int)$db->insertID();
                    $this->queueWelcomeWhatsApp($customerId);
                }
            }

            $normalized=[];
            $subtotal=0.0;
            $lineDiscountTotal=0.0;
            $netBeforeInvoiceDiscount=0.0;
            foreach($items as $index=>$item){
                $qty=(float)($item['qty']??0);
                $price=(float)($item['unit_price']??0);
                if($qty<=0||$price<0) throw new RuntimeException('Invalid sale quantity/price on item '.($index+1).'.');
                $base=round($qty*$price,2);
                $lineDiscount=$discountEnabled ? max(0,(float)($item['discount_amount']??0)) : 0.0;
                $lineDiscount=min($lineDiscount,$base);
                $taxPercent=$gstEnabled ? max(0,(float)($item['tax_percent']??0)) : 0.0;
                $net=max(0,$base-$lineDiscount);
                $subtotal+=$base;
                $lineDiscountTotal+=$lineDiscount;
                $netBeforeInvoiceDiscount+=$net;
                $normalized[]=['raw'=>$item,'qty'=>$qty,'price'=>$price,'base'=>$base,'line_discount'=>$lineDiscount,'tax_percent'=>$taxPercent,'net'=>$net];
            }

            $overallType=$discountEnabled ? (string)($data['overall_discount_type']??'none') : 'none';
            if(!in_array($overallType,['none','amount','percent'],true)) $overallType='none';
            $overallValue=max(0,(float)($data['overall_discount_value']??0));
            $overallDiscount=0.0;
            if($overallType==='amount') $overallDiscount=min($overallValue,$netBeforeInvoiceDiscount);
            if($overallType==='percent') $overallDiscount=min(100,$overallValue)*$netBeforeInvoiceDiscount/100;
            $overallDiscount=round($overallDiscount,2);
            $overallRatio=$netBeforeInvoiceDiscount>0 ? $overallDiscount/$netBeforeInvoiceDiscount : 0.0;

            $taxTotal=0.0;
            foreach($normalized as &$n){
                $allocated=$overallDiscount>0 ? round($n['net']*$overallRatio,2) : 0.0;
                $taxable=max(0,$n['net']-$allocated);
                if (!$gstEnabled || $n['tax_percent'] <= 0) {
                    $tax=0.0;
                    $lineTotal=$taxable;
                } elseif ($gstMode === 'inclusive') {
                    $tax=round($taxable - ($taxable / (1 + ($n['tax_percent'] / 100))),2);
                    $lineTotal=$taxable;
                } else {
                    $tax=round($taxable*$n['tax_percent']/100,2);
                    $lineTotal=round($taxable+$tax,2);
                }
                $n['allocated_discount']=$allocated;
                $n['taxable']=$taxable;
                $n['tax']=$tax;
                $n['line_total']=$lineTotal;
                $taxTotal+=$tax;
            }
            unset($n);
            $taxTotal=round($taxTotal,2);
            $discountTotal=round($lineDiscountTotal+$overallDiscount,2);
            $grand=round($gstMode==='inclusive' ? $subtotal-$discountTotal : $subtotal-$discountTotal+$taxTotal,2);
            if($grand<0) $grand=0;

            $paid=min(max((float)($data['paid_amount']??0),0),$grand);
            $due=round($grand-$paid,2);
            $status=$due<=0?'paid':($paid>0?'partial':'unpaid');
            $invoiceNo=$numbers->next('invoice');

            $template=(string)($data['invoice_template']??($shop['invoice_template']??'classic'));
            $allowedTemplates=['classic','modern','minimal','compact','executive','retail','bold','bordered','elegant','thermal','gst_classic'];
            if(!in_array($template,$allowedTemplates,true)) $template='classic';
            $addressPosition=(string)($data['customer_address_position']??($shop['customer_address_position']??'left'));
            if(!in_array($addressPosition,['left','right','full','hidden'],true)) $addressPosition='left';
            $dueDate=$this->validDate($data['due_date']??null);

            $invoiceConfig=[
                'template'=>$template,
                'title'=>trim((string)($data['invoice_title']??($shop['invoice_title']??'TAX INVOICE'))) ?: 'TAX INVOICE',
                'invoice_color'=>$shop['invoice_color']??'#e87523',
                'gst_enabled'=>$gstEnabled,
                'gst_mode'=>$gstMode,
                'discount_enabled'=>$discountEnabled,
                'overall_discount_type'=>$overallType,
                'overall_discount_value'=>$overallValue,
                'show_logo'=>$this->postedBool($data,'show_logo',$shop['invoice_show_logo']??1),
                'show_signature'=>$this->postedBool($data,'show_signature',$shop['invoice_show_signature']??1),
                'show_company_phone'=>$this->postedBool($data,'show_company_phone',$shop['invoice_show_company_phone']??1),
                'show_company_email'=>$this->postedBool($data,'show_company_email',$shop['invoice_show_company_email']??1),
                'show_company_address'=>$this->postedBool($data,'show_company_address',$shop['invoice_show_company_address']??1),
                'show_customer_address'=>$this->postedBool($data,'show_customer_address',$shop['invoice_show_customer_address']??1),
                'show_customer_gstin'=>$this->postedBool($data,'show_customer_gstin',$shop['invoice_show_customer_gstin']??1),
                'show_imei'=>$this->postedBool($data,'show_imei',$shop['invoice_show_imei']??1),
                'show_hsn'=>$this->postedBool($data,'show_hsn',$shop['invoice_show_hsn']??1),
                'show_item_discount'=>$this->postedBool($data,'show_item_discount',$shop['invoice_show_item_discount']??1),
                'customer_address_position'=>$addressPosition,
                'terms'=>$shop['invoice_terms']??null,
                'footer'=>$shop['invoice_footer']??null,
            ];
            if($addressPosition==='hidden') $invoiceConfig['show_customer_address']=false;

            $companySnapshot=[
                'name'=>$shop['name']??'Shop','phone'=>$shop['phone']??null,'email'=>$shop['email']??null,
                'address'=>$shop['address']??null,'gstin'=>$shop['gstin']??null,'logo_path'=>$shop['logo_path']??null,
                'signature_path'=>$shop['signature_path']??null,'logo_base64'=>$shop['logo_base64']??null,'logo_mime'=>$shop['logo_mime']??null,'signature_base64'=>$shop['signature_base64']??null,'signature_mime'=>$shop['signature_mime']??null,'invoice_color'=>$shop['invoice_color']??'#e87523','currency'=>$shop['currency']??'INR',
                'invoice_title'=>$shop['invoice_title']??'TAX INVOICE','invoice_terms'=>$shop['invoice_terms']??null,'invoice_footer'=>$shop['invoice_footer']??null,
            ];

            $db->table('sales')->insert([
                'customer_id'=>$customerId,'invoice_no'=>$invoiceNo,'sale_date'=>date('Y-m-d H:i:s'),'sale_type'=>$data['sale_type']??'invoice',
                'subtotal'=>round($subtotal,2),'discount_total'=>$discountTotal,'line_discount_total'=>round($lineDiscountTotal,2),
                'overall_discount_amount'=>$overallDiscount,'tax_total'=>$taxTotal,'grand_total'=>$grand,
                'paid_amount'=>$paid,'due_amount'=>$due,'payment_status'=>$status,'due_date'=>$dueDate,
                'notes'=>trim((string)($data['notes']??''))?:null,'internal_notes'=>trim((string)($data['internal_notes']??''))?:null,
                'invoice_config_json'=>json_encode($invoiceConfig,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
                'company_snapshot_json'=>json_encode($companySnapshot,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
                'created_by'=>$userId,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')
            ]);
            $saleId=(int)$db->insertID();

            foreach($normalized as $n){
                $item=$n['raw'];
                $productId=(int)($item['product_id']??0);
                if($productId<1) throw new RuntimeException('A sale item is missing its product.');
                $source=$item['source_type']??'stock';
                $inventoryUnitId=($item['inventory_unit_id']??null)?(int)$item['inventory_unit_id']:null;
                if(in_array($source,['borrowed','expense','direct'],true)){
                    $origin=$source==='borrowed'?'borrowed':($source==='expense'?'expense':'manual');
                    $borrowId=null;
                    if($source==='borrowed'){
                        $db->table('stock_borrows')->insert([
                            'supplier_id'=>($item['borrowed_supplier_id']??null)?:null,'reference_no'=>($item['source_reference']??'')?:null,
                            'borrowed_at'=>date('Y-m-d H:i:s'),'notes'=>($item['source_note']??'')?:null,'created_by'=>$userId,
                            'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')
                        ]);
                        $borrowId=(int)$db->insertID();
                    }
                    $lotId=$inventory->receiveLot($productId,$origin,$borrowId,$n['qty'],(float)($item['internal_cost']??0),is_array($item['units']??null)?$item['units']:[],($item['source_note']??'')?:null,$userId);
                    if(!$inventoryUnitId){
                        $unit=$db->table('inventory_units')->where(['stock_lot_id'=>$lotId,'status'=>'available'])->orderBy('id','ASC')->get()->getRowArray();
                        if($unit) $inventoryUnitId=(int)$unit['id'];
                    }
                }

                $db->table('sale_items')->insert([
                    'sale_id'=>$saleId,'product_id'=>$productId,'qty'=>$n['qty'],'unit_price'=>$n['price'],'unit_cost'=>0,
                    'discount_amount'=>$n['line_discount'],'tax_percent'=>$n['tax_percent'],'line_total'=>$n['line_total'],
                    'internal_source'=>$source,'internal_source_note'=>($item['source_note']??'')?:null,'created_at'=>date('Y-m-d H:i:s')
                ]);
                $saleItemId=(int)$db->insertID();
                $allocation=$inventory->consume($productId,$n['qty'],$inventoryUnitId,$saleItemId,$userId);
                $db->table('sale_items')->where('id',$saleItemId)->update([
                    'stock_lot_id'=>$allocation['lot_id'],'inventory_unit_id'=>$inventoryUnitId,'unit_cost'=>$allocation['unit_cost']
                ]);
            }

            if($paid>0){
                $method=(string)($data['payment_method']??'cash');
                if(!in_array($method,['cash','upi','card','bank','credit','other'],true)) $method='cash';
                $db->table('payments')->insert([
                    'sale_id'=>$saleId,'amount'=>$paid,'method'=>$method,'reference_no'=>trim((string)($data['payment_reference']??''))?:null,
                    'paid_at'=>date('Y-m-d H:i:s'),'created_by'=>$userId,'created_at'=>date('Y-m-d H:i:s')
                ]);
            }
            $this->queueInvoiceWhatsApp($saleId,$customerId);
            $db->transCommit();
            // Attempt immediate delivery when the local bridge is online; the queued rows remain
            // available for the normal cron/CLI worker if the bridge is offline.
            try { (new \App\Services\WhatsAppQueueService())->processPending(); } catch (\Throwable $ignored) {}
            try { (new \App\Services\EmailService())->sendInvoice($saleId); } catch (\Throwable $e) { log_message('error', 'Invoice email failed for sale {saleId}: {message}', ['saleId'=>$saleId,'message'=>$e->getMessage()]); }
            return $saleId;
        }catch(\Throwable $e){
            $db->transRollback();
            throw $e;
        }
    }

    private function postedBool(array $data,string $key,$default=1): bool
    {
        if(array_key_exists($key,$data)) return (bool)$data[$key];
        return (bool)$default;
    }

    private function validDate($value): ?string
    {
        $value=trim((string)$value);
        if($value==='') return null;
        $d=\DateTimeImmutable::createFromFormat('Y-m-d',$value);
        return $d && $d->format('Y-m-d')===$value ? $value : null;
    }

    private function queueInvoiceWhatsApp(int $saleId,int $customerId): void
    {
        $db=db_connect();
        $sale=$db->table('sales')->where('id',$saleId)->get()->getRowArray();
        $customer=$db->table('customers')->where('id',$customerId)->get()->getRowArray();
        $shop=$db->table('shop_settings')->where('id',1)->get()->getRowArray();
        if(!$sale||!$customer)return;
        $phone=$customer['whatsapp_phone']?:$customer['phone'];
        if(!$phone)return;
        $tpl=$db->table('whatsapp_templates')->where(['event_key'=>'invoice_sent','is_active'=>1])->get()->getRowArray();
        $replace=['{customer_name}'=>$customer['name'],'{invoice_no}'=>$sale['invoice_no'],'{grand_total}'=>number_format((float)$sale['grand_total'],2,'.',''),'{due_amount}'=>number_format((float)$sale['due_amount'],2,'.',''),'{paid_amount}'=>number_format((float)$sale['paid_amount'],2,'.',''),'{store_name}'=>$shop['name']??'Shop'];
        $message=$tpl?strtr($tpl['message'],$replace):('Dear '.$customer['name'].', your invoice '.$sale['invoice_no'].' from '.($shop['name']??'our store').' is ready. Total: '.number_format((float)$sale['grand_total'],2).'. Due: '.number_format((float)$sale['due_amount'],2).'.');
        $now=date('Y-m-d H:i:s');
        $db->table('whatsapp_queue')->insert(['customer_id'=>$customerId,'sale_id'=>$saleId,'phone'=>$phone,'event_key'=>'invoice_sent_text','dedupe_key'=>'invoice_sent_text:'.$saleId,'message'=>$message,'message_type'=>'text','scheduled_at'=>$now,'status'=>'queued','created_at'=>$now,'updated_at'=>$now]);
        $db->table('whatsapp_queue')->insert(['customer_id'=>$customerId,'sale_id'=>$saleId,'phone'=>$phone,'event_key'=>'invoice_sent_pdf','dedupe_key'=>'invoice_sent_pdf:'.$saleId,'message'=>'Invoice '.$sale['invoice_no'],'message_type'=>'media','attachment_mime'=>'application/pdf','attachment_name'=>$sale['invoice_no'].'.pdf','scheduled_at'=>$now,'status'=>'queued','created_at'=>$now,'updated_at'=>$now]);
    }

    private function queueWelcomeWhatsApp(int $customerId): void
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
        try {
            $db->table('whatsapp_queue')->insert(['customer_id'=>$customerId,'phone'=>$phone,'event_key'=>'customer_welcome','dedupe_key'=>'customer_welcome:'.$customerId,'message'=>$message,'message_type'=>'text','scheduled_at'=>$now,'status'=>'queued','created_at'=>$now,'updated_at'=>$now]);
        } catch(\Throwable $e) { /* duplicate welcome is intentionally ignored */ }
    }

}
