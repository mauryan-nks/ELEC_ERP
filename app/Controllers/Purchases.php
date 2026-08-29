<?php

namespace App\Controllers;

use App\Services\PurchaseService;

class Purchases extends BaseController
{
    public function index(): string
    {
        $db=db_connect();$rows=$db->table('purchases p')->select('p.*,s.name supplier_name')->join('suppliers s','s.id=p.supplier_id','left')->orderBy('p.id','DESC')->limit(500)->get()->getResultArray();
        return view('purchases/index',['title'=>'Purchases','rows'=>$rows]);
    }
    public function create(): string
    {
        $db=db_connect();return view('purchases/create',['title'=>'Receive Purchase','products'=>$db->table('products p')->select('p.*,b.name brand_name')->join('brands b','b.id=p.brand_id','left')->where('p.status','active')->orderBy('p.name')->get()->getResultArray(),'suppliers'=>$db->table('suppliers')->orderBy('name')->get()->getResultArray()]);
    }
    public function store(){try{$id=(new PurchaseService())->create($this->request->getPost(),auth()->id());return redirect()->to('/purchases/'.$id)->with('message','Purchase received into stock.');}catch(\Throwable $e){return redirect()->back()->withInput()->with('error',$e->getMessage());}}
    public function addPayment(int $id)
    {
        $db=db_connect(); $db->transBegin();
        try{
            $purchase=$db->query('SELECT * FROM purchases WHERE id=? FOR UPDATE',[$id])->getRowArray(); if(!$purchase) throw new \RuntimeException('Purchase not found.');
            $amount=(float)$this->request->getPost('amount'); if($amount<=0) throw new \RuntimeException('Payment amount must be greater than zero.');
            if($amount>(float)$purchase['due_amount']+0.00001) throw new \RuntimeException('Payment cannot exceed the supplier due amount.');
            $method=(string)$this->request->getPost('method'); if(!in_array($method,['cash','upi','card','bank','credit','other'],true))$method='cash';
            $db->table('payments')->insert(['purchase_id'=>$id,'amount'=>$amount,'method'=>$method,'reference_no'=>trim((string)$this->request->getPost('reference_no')) ?: null,'paid_at'=>date('Y-m-d H:i:s'),'notes'=>trim((string)$this->request->getPost('notes')) ?: null,'created_by'=>auth()->id(),'created_at'=>date('Y-m-d H:i:s')]);
            $paid=round((float)$purchase['paid_amount']+$amount,2);$due=max(0,round((float)$purchase['grand_total']-$paid,2));$db->table('purchases')->where('id',$id)->update(['paid_amount'=>$paid,'due_amount'=>$due,'updated_at'=>date('Y-m-d H:i:s')]);
            $db->transCommit();return redirect()->to('/purchases/'.$id)->with('message','Supplier payment recorded.');
        }catch(\Throwable $e){$db->transRollback();return redirect()->to('/purchases/'.$id)->with('error',$e->getMessage());}
    }

    public function show(int $id): string
    {
        $db=db_connect();$purchase=$db->table('purchases p')->select('p.*,s.name supplier_name,s.phone supplier_phone,s.gstin supplier_gstin')->join('suppliers s','s.id=p.supplier_id','left')->where('p.id',$id)->get()->getRowArray();if(!$purchase)throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        $items=$db->table('purchase_items pi')->select('pi.*,p.name,p.model,b.name brand_name')->join('products p','p.id=pi.product_id')->join('brands b','b.id=p.brand_id','left')->where('pi.purchase_id',$id)->get()->getResultArray();
        $units=$db->table('inventory_units u')->select('u.*,l.product_id')->join('stock_lots l','l.id=u.stock_lot_id')->where(['l.origin_type'=>'purchase','l.source_id'=>$id])->orderBy('u.id')->get()->getResultArray();$unitMap=[];foreach($units as $u)$unitMap[(int)$u['product_id']][]=$u;
        $payments=$db->table('payments')->where('purchase_id',$id)->orderBy('paid_at')->get()->getResultArray();return view('purchases/show',['title'=>'Purchase '.$purchase['purchase_no'],'purchase'=>$purchase,'items'=>$items,'unitMap'=>$unitMap,'payments'=>$payments]);
    }
}
