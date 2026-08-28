<?php

namespace App\Controllers;

use App\Models\CustomerModel;

class Customers extends BaseController
{
    public function index(): string
    {
        $db=db_connect();
        $rows=$db->table('customers c')->select('c.*,COUNT(s.id) invoice_count,COALESCE(SUM(s.due_amount),0) due_total')
            ->join('sales s','s.customer_id=c.id','left')->groupBy('c.id')->orderBy('c.id','DESC')->limit(500)->get()->getResultArray();
        return view('customers/index',['title'=>'Customers','customers'=>$rows]);
    }

    public function store(){try{$this->saveCustomer();return redirect()->to('/customers')->with('message','Customer saved.');}catch(\Throwable $e){return redirect()->back()->withInput()->with('error',$e->getMessage());}}

    public function update(int $id)
    {
        try{
            $model=new CustomerModel(); $customer=$model->find($id); if(!$customer) throw new \RuntimeException('Customer not found.');
            $name=trim((string)$this->request->getPost('name'));$phone=trim((string)$this->request->getPost('phone'));if($name===''||$phone==='')throw new \RuntimeException('Name and phone are required.');
            $dup=$model->where('phone',$phone)->where('id !=',$id)->first();if($dup)throw new \RuntimeException('Another customer already uses this phone number.');
            $model->update($id,['name'=>$name,'phone'=>$phone,'whatsapp_phone'=>trim((string)$this->request->getPost('whatsapp_phone')) ?: $phone,'email'=>trim((string)$this->request->getPost('email')) ?: null,'address'=>trim((string)$this->request->getPost('address')) ?: null,'gstin'=>trim((string)$this->request->getPost('gstin')) ?: null,'notes'=>trim((string)$this->request->getPost('notes')) ?: null]);
            return redirect()->to('/customers')->with('message','Customer updated.');
        }catch(\Throwable $e){return redirect()->to('/customers')->with('error',$e->getMessage());}
    }

    public function quickCreate()
    {
        try {$id=$this->saveCustomer();return $this->response->setJSON(['ok'=>true,'customer'=>(new CustomerModel())->find($id),'csrfToken'=>csrf_token(),'csrfHash'=>csrf_hash()]);}
        catch (\Throwable $e) {return $this->response->setStatusCode(422)->setJSON(['ok'=>false,'error'=>$e->getMessage(),'csrfToken'=>csrf_token(),'csrfHash'=>csrf_hash()]);}
    }

    private function saveCustomer(): int
    {
        $name=trim((string)$this->request->getPost('name')); $phone=trim((string)$this->request->getPost('phone'));
        if($name===''||$phone==='') throw new \RuntimeException('Name and phone are required.');
        $model=new CustomerModel(); $existing=$model->where('phone',$phone)->first(); if($existing) return (int)$existing['id'];
        $model->insert(['name'=>$name,'phone'=>$phone,'whatsapp_phone'=>trim((string)$this->request->getPost('whatsapp_phone')) ?: $phone,'email'=>trim((string)$this->request->getPost('email')) ?: null,'address'=>trim((string)$this->request->getPost('address')) ?: null,'gstin'=>trim((string)$this->request->getPost('gstin')) ?: null,'notes'=>trim((string)$this->request->getPost('notes')) ?: null]);
        return (int)$model->getInsertID();
    }
}
