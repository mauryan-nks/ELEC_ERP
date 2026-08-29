<?php

namespace App\Controllers;

use App\Services\InventoryService;
use App\Services\InvoicePdfService;
use App\Services\SaleService;

class Sales extends BaseController
{
    public function index(): string
    {
        $db=db_connect();
        $rows=$db->table('sales s')->select('s.*,c.name customer_name,c.phone customer_phone')->join('customers c','c.id=s.customer_id')->orderBy('s.id','DESC')->limit(250)->get()->getResultArray();
        return view('sales/index',['title'=>'Sales & Invoices','rows'=>$rows]);
    }

    public function create(): string
    {
        $db=db_connect();
        return view('sales/create',['title'=>'New Sale / Invoice',
            'customers'=>$db->table('customers')->orderBy('name')->get()->getResultArray(),
            'products'=>$db->table('products p')->select('p.*,b.name brand_name,COALESCE(SUM(l.qty_available),0) stock_qty')
                ->join('brands b','b.id=p.brand_id','left')->join('stock_lots l','l.product_id=p.id','left')->where('p.status','active')->groupBy('p.id')->orderBy('p.name')->get()->getResultArray(),
            'borrowSources'=>$db->table('suppliers')->where('supplier_type','other_store')->orderBy('name')->get()->getResultArray(),
            'shop'=>$db->table('shop_settings')->where('id',1)->get()->getRowArray() ?? [],
            'invoiceTemplates'=>[
                'classic'=>'Classic','modern'=>'Modern','minimal'=>'Minimal','compact'=>'Compact','executive'=>'Executive',
                'retail'=>'Retail Pro','bold'=>'Bold Header','bordered'=>'Clean Border','elegant'=>'Elegant','thermal'=>'Thermal / Narrow','gst_classic'=>'GST Classic',
            ],
        ]);
    }

    public function store()
    {
        try {
            $id=(new SaleService())->create($this->request->getPost(),auth()->id());
            return redirect()->to('/sales/'.$id.'/invoice')->with('message','Sale completed.');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error',$e->getMessage());
        }
    }

    public function availableUnits(int $productId)
    {
        return $this->response->setJSON(['ok'=>true,'units'=>(new InventoryService())->availableUnits($productId)]);
    }

    public function addPayment(int $saleId)
    {
        $db=db_connect(); $db->transBegin();
        try{
            $sale=$db->query('SELECT * FROM sales WHERE id=? FOR UPDATE',[$saleId])->getRowArray();
            if(!$sale) throw new \RuntimeException('Sale not found.');
            $amount=(float)$this->request->getPost('amount');
            if($amount<=0) throw new \RuntimeException('Payment amount must be greater than zero.');
            if($amount>(float)$sale['due_amount']+0.00001) throw new \RuntimeException('Payment cannot be greater than the outstanding due amount.');
            $method=(string)$this->request->getPost('method'); if(!in_array($method,['cash','upi','card','bank','credit','other'],true)) $method='cash';
            $db->table('payments')->insert(['sale_id'=>$saleId,'amount'=>$amount,'method'=>$method,'reference_no'=>trim((string)$this->request->getPost('reference_no')) ?: null,'paid_at'=>date('Y-m-d H:i:s'),'notes'=>trim((string)$this->request->getPost('notes')) ?: null,'created_by'=>auth()->id(),'created_at'=>date('Y-m-d H:i:s')]);
            $paid=round((float)$sale['paid_amount']+$amount,2);$due=max(0,round((float)$sale['grand_total']-$paid,2));$status=$due<=0?'paid':'partial';
            $db->table('sales')->where('id',$saleId)->update(['paid_amount'=>$paid,'due_amount'=>$due,'payment_status'=>$status,'updated_at'=>date('Y-m-d H:i:s')]);
            $this->queuePaymentWhatsApp($saleId,$amount);
            $db->transCommit(); return redirect()->to('/sales/'.$saleId.'/invoice')->with('message','Payment recorded.');
        }catch(\Throwable $e){$db->transRollback();return redirect()->to('/sales/'.$saleId.'/invoice')->with('error',$e->getMessage());}
    }

    private function queuePaymentWhatsApp(int $saleId,float $amount): void
    {
        $db=db_connect();$sale=$db->table('sales')->where('id',$saleId)->get()->getRowArray();if(!$sale)return;$customer=$db->table('customers')->where('id',$sale['customer_id'])->get()->getRowArray();$tpl=$db->table('whatsapp_templates')->where(['event_key'=>'payment_received','is_active'=>1])->get()->getRowArray();if(!$customer||!$tpl)return;$phone=$customer['whatsapp_phone']?:$customer['phone'];if(!$phone)return;$shop=$db->table('shop_settings')->where('id',1)->get()->getRowArray();
        $msg=strtr($tpl['message'],['{customer_name}'=>$customer['name'],'{invoice_no}'=>$sale['invoice_no'],'{paid_amount}'=>number_format($amount,2,'.',''),'{due_amount}'=>number_format((float)$sale['due_amount'],2,'.',''),'{grand_total}'=>number_format((float)$sale['grand_total'],2,'.',''),'{store_name}'=>$shop['name']??'Shop']);
        $db->table('whatsapp_queue')->insert(['customer_id'=>$customer['id'],'sale_id'=>$saleId,'phone'=>$phone,'event_key'=>'payment_received','dedupe_key'=>'payment_received:'.$saleId.':'.time(),'message'=>$msg,'scheduled_at'=>date('Y-m-d H:i:s'),'status'=>'queued','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);
    }

    public function invoice(int $saleId): string
    {
        $db=db_connect();
        $sale=$db->table('sales s')->select('s.*,c.name customer_name,c.phone customer_phone,c.email customer_email,c.address customer_address,c.gstin customer_gstin')
            ->join('customers c','c.id=s.customer_id')->where('s.id',$saleId)->get()->getRowArray();
        if(!$sale) throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        $items=$db->table('sale_items si')->select('si.id,si.qty,si.unit_price,si.discount_amount,si.tax_percent,si.line_total,p.name,p.model,p.hsn_sac,u.imei1,u.imei2,u.serial_no,u.unique_id')
            ->join('products p','p.id=si.product_id')->join('inventory_units u','u.id=si.inventory_unit_id','left')->where('si.sale_id',$saleId)->orderBy('si.id')->get()->getResultArray();
        $currentShop=$db->table('shop_settings')->where('id',1)->get()->getRowArray() ?? [];
        $shop=$currentShop;
        if(!empty($sale['company_snapshot_json'])){
            $snapshot=json_decode((string)$sale['company_snapshot_json'],true);
            if(is_array($snapshot)) $shop=array_replace($currentShop,$snapshot);
        }
        $invoiceConfig=[];
        if(!empty($sale['invoice_config_json'])){
            $decoded=json_decode((string)$sale['invoice_config_json'],true);
            if(is_array($decoded)) $invoiceConfig=$decoded;
        }
        $invoiceConfig=array_replace([
            'template'=>$shop['invoice_template']??'classic','title'=>$shop['invoice_title']??'TAX INVOICE','gst_enabled'=>(bool)($shop['invoice_default_gst_enabled']??1),'gst_mode'=>$shop['invoice_default_gst_mode']??'inclusive','invoice_color'=>$shop['invoice_color']??'#e87523',
            'show_logo'=>(bool)($shop['invoice_show_logo']??1),'show_signature'=>(bool)($shop['invoice_show_signature']??1),'show_company_phone'=>(bool)($shop['invoice_show_company_phone']??1),
            'show_company_email'=>(bool)($shop['invoice_show_company_email']??1),'show_company_address'=>(bool)($shop['invoice_show_company_address']??1),
            'show_customer_address'=>(bool)($shop['invoice_show_customer_address']??1),'show_customer_gstin'=>(bool)($shop['invoice_show_customer_gstin']??1),
            'show_imei'=>(bool)($shop['invoice_show_imei']??1),'show_hsn'=>(bool)($shop['invoice_show_hsn']??1),'show_item_discount'=>(bool)($shop['invoice_show_item_discount']??1),
            'customer_address_position'=>$shop['customer_address_position']??'left','terms'=>$shop['invoice_terms']??null,'footer'=>$shop['invoice_footer']??null,
        ],$invoiceConfig);
        $payments=$db->table('payments')->where('sale_id',$saleId)->orderBy('paid_at','ASC')->get()->getResultArray();
        return view('sales/invoice',['title'=>'Invoice '.$sale['invoice_no'],'sale'=>$sale,'items'=>$items,'shop'=>$shop,'payments'=>$payments,'invoiceConfig'=>$invoiceConfig]);
    }

    /** Serve the dedicated invoice renderer instead of browser-printing the app UI. */
    public function pdf(int $saleId)
    {
        try {
            $path = (new InvoicePdfService())->generate($saleId);
            if (! is_file($path)) {
                throw new \RuntimeException('Invoice PDF could not be generated.');
            }

            return $this->response
                ->setHeader('Content-Type', 'application/pdf')
                ->setHeader('Content-Disposition', 'inline; filename="'.basename($path).'"')
                ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->setHeader('Pragma', 'no-cache')
                ->setBody((string) file_get_contents($path));
        } catch (\Throwable $e) {
            log_message('error', 'Invoice PDF generation failed for sale {saleId}: {message}', [
                'saleId' => $saleId,
                'message' => $e->getMessage(),
            ]);

            return redirect()->to('/sales/'.$saleId.'/invoice')
                ->with('error', 'Unable to generate the invoice PDF. Please try again.'.$e);
        }
    }
}
