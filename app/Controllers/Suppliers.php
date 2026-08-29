<?php

namespace App\Controllers;

class Suppliers extends BaseController
{
    public function index(): string{return view('suppliers/index',['title'=>'Suppliers & Source Stores','rows'=>db_connect()->table('suppliers')->orderBy('name')->get()->getResultArray()]);}
    public function store(){try{$this->save();return redirect()->to('/suppliers')->with('message','Supplier/source saved.');}catch(\Throwable $e){return redirect()->back()->withInput()->with('error',$e->getMessage());}}
    public function update(int $id){try{$this->save($id);return redirect()->to('/suppliers')->with('message','Supplier/source updated.');}catch(\Throwable $e){return redirect()->to('/suppliers')->with('error',$e->getMessage());}}
    public function quickCreate(){try{$id=$this->save();$row=db_connect()->table('suppliers')->where('id',$id)->get()->getRowArray();return $this->response->setJSON(['ok'=>true,'supplier'=>$row,'csrfHash'=>csrf_hash()]);}catch(\Throwable $e){return $this->response->setStatusCode(422)->setJSON(['ok'=>false,'error'=>$e->getMessage(),'csrfHash'=>csrf_hash()]);}}
    private function save(?int $id=null): int
    {
        $name=trim((string)$this->request->getPost('name'));if($name==='')throw new \RuntimeException('Name is required.');$type=(string)$this->request->getPost('supplier_type');if(!in_array($type,['vendor','other_store','individual'],true))$type='vendor';
        $row=['name'=>$name,'phone'=>trim((string)$this->request->getPost('phone')) ?: null,'email'=>trim((string)$this->request->getPost('email')) ?: null,'address'=>trim((string)$this->request->getPost('address')) ?: null,'gstin'=>trim((string)$this->request->getPost('gstin')) ?: null,'supplier_type'=>$type,'updated_at'=>date('Y-m-d H:i:s')];$db=db_connect();
        if($id){if(!$db->table('suppliers')->where('id',$id)->get()->getRowArray())throw new \RuntimeException('Supplier/source not found.');$db->table('suppliers')->where('id',$id)->update($row);return $id;}
        $row['created_at']=date('Y-m-d H:i:s');$db->table('suppliers')->insert($row);return (int)$db->insertID();
    }
}
