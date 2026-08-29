<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

class InvoicePdfService
{
    public function generate(int $saleId): string
    {
        $db=db_connect();
        $sale=$db->table('sales s')->select('s.*,c.name customer_name,c.phone customer_phone,c.email customer_email,c.address customer_address,c.gstin customer_gstin')->join('customers c','c.id=s.customer_id')->where('s.id',$saleId)->get()->getRowArray();
        if(!$sale) throw new RuntimeException('Sale not found for PDF generation.');
        $items=$db->table('sale_items si')->select('si.id,si.qty,si.unit_price,si.discount_amount,si.tax_percent,si.line_total,p.name,p.model,p.hsn_sac,u.imei1,u.imei2,u.serial_no,u.unique_id')->join('products p','p.id=si.product_id')->join('inventory_units u','u.id=si.inventory_unit_id','left')->where('si.sale_id',$saleId)->orderBy('si.id')->get()->getResultArray();
        $shop=$db->table('shop_settings')->where('id',1)->get()->getRowArray() ?? [];
        $cfg=json_decode((string)($sale['invoice_config_json']??''),true); if(!is_array($cfg))$cfg=[];
        $cfg=array_replace(['template'=>'classic','title'=>'TAX INVOICE','gst_enabled'=>(bool)($shop['invoice_default_gst_enabled']??1),'gst_mode'=>$shop['invoice_default_gst_mode']??'inclusive','invoice_color'=>$shop['invoice_color']??'#e87523','show_logo'=>true,'show_signature'=>true,'show_company_phone'=>true,'show_company_email'=>true,'show_company_address'=>true,'show_customer_address'=>true,'show_customer_gstin'=>true,'show_imei'=>true,'show_hsn'=>true,'show_item_discount'=>true,'customer_address_position'=>'left','terms'=>$shop['invoice_terms']??null,'footer'=>$shop['invoice_footer']??null],$cfg);
        if(!empty($sale['company_snapshot_json'])){$snapshot=json_decode((string)$sale['company_snapshot_json'],true);if(is_array($snapshot))$shop=array_replace($shop,$snapshot);}
        $html=view('sales/invoice_pdf',['sale'=>$sale,'items'=>$items,'shop'=>$shop,'cfg'=>$cfg]);
        $options=new Options(); $options->set('isRemoteEnabled',false); $options->set('defaultFont','DejaVu Sans');
        $dompdf=new Dompdf($options); $dompdf->loadHtml($html,'UTF-8'); $dompdf->setPaper('A4','portrait'); $dompdf->render();
        $dir=WRITEPATH.'uploads/invoices'; if(!is_dir($dir)&&!mkdir($dir,0750,true)&&!is_dir($dir))throw new RuntimeException('Unable to create invoice PDF directory.');
        $path=$dir.'/'.preg_replace('/[^A-Za-z0-9._-]/','_', $sale['invoice_no']).'.pdf'; file_put_contents($path,$dompdf->output());
        return $path;
    }
}
