<?php

namespace App\Controllers;

class BorrowedStock extends BaseController
{
    public function index(): string
    {
        $db=db_connect();$rows=$db->table('stock_borrows b')->select('b.*,s.name source_name,COUNT(l.id) lot_count,COALESCE(SUM(l.qty_received),0) qty_received,COALESCE(SUM(l.qty_available),0) qty_available,COALESCE(SUM(l.qty_received*l.unit_cost),0) cost_value')->join('suppliers s','s.id=b.supplier_id','left')->join('stock_lots l','l.origin_type="borrowed" AND l.source_id=b.id','left')->groupBy('b.id')->orderBy('b.id','DESC')->get()->getResultArray();
        return view('borrowed/index',['title'=>'Borrowed Stock','rows'=>$rows]);
    }
    public function update(int $id)
    {
        $status=(string)$this->request->getPost('settlement_status');if(!in_array($status,['open','partly_settled','settled','returned'],true))return redirect()->to('/borrowed-stock')->with('error','Invalid settlement status.');
        $db=db_connect();if(!$db->table('stock_borrows')->where('id',$id)->get()->getRowArray())return redirect()->to('/borrowed-stock')->with('error','Borrow record not found.');
        $db->table('stock_borrows')->where('id',$id)->update(['settlement_status'=>$status,'notes'=>trim((string)$this->request->getPost('notes')) ?: null,'updated_at'=>date('Y-m-d H:i:s')]);return redirect()->to('/borrowed-stock')->with('message','Borrowed stock record updated.');
    }
}
