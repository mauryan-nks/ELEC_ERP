<?php

namespace App\Controllers;

class Expenses extends BaseController
{
    public function index(): string{return view('expenses/index',['title'=>'Expenses','expenses'=>db_connect()->table('expenses')->orderBy('expense_date','DESC')->orderBy('id','DESC')->limit(500)->get()->getResultArray()]);}
    public function store(){try{$this->save();return redirect()->to('/expenses')->with('message','Expense added.');}catch(\Throwable $e){return redirect()->back()->withInput()->with('error',$e->getMessage());}}
    public function update(int $id){try{if(!db_connect()->table('expenses')->where('id',$id)->get()->getRowArray())throw new \RuntimeException('Expense not found.');$this->save($id);return redirect()->to('/expenses')->with('message','Expense updated.');}catch(\Throwable $e){return redirect()->to('/expenses')->with('error',$e->getMessage());}}
    private function save(?int $id=null): void
    {
        $amount=(float)$this->request->getPost('amount');if($amount<=0)throw new \RuntimeException('Expense amount must be greater than zero.');
        $row=['expense_date'=>$this->request->getPost('expense_date') ?: date('Y-m-d'),'category'=>trim((string)$this->request->getPost('category')) ?: 'General','amount'=>$amount,'payee'=>trim((string)$this->request->getPost('payee')) ?: null,'reference_no'=>trim((string)$this->request->getPost('reference_no')) ?: null,'notes'=>trim((string)$this->request->getPost('notes')) ?: null,'updated_at'=>date('Y-m-d H:i:s')];
        $db=db_connect();if($id){$db->table('expenses')->where('id',$id)->update($row);}else{$row['created_by']=auth()->id();$row['created_at']=date('Y-m-d H:i:s');$db->table('expenses')->insert($row);}
    }
}
