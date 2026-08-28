<?php

namespace App\Controllers;

class Reports extends BaseController
{
    public function index(): string
    {
        $db=db_connect(); $from=$this->request->getGet('from') ?: date('Y-m-01'); $to=$this->request->getGet('to') ?: date('Y-m-d');
        $sales=$db->table('sales')->selectSum('grand_total')->selectSum('paid_amount')->selectSum('due_amount')->where('DATE(sale_date) >=',$from)->where('DATE(sale_date) <=',$to)->get()->getRowArray();
        $costRow=$db->table('sale_items si')->select('SUM(si.unit_cost*si.qty) v')->join('sales s','s.id=si.sale_id')->where('DATE(s.sale_date) >=',$from)->where('DATE(s.sale_date) <=',$to)->get()->getRowArray();
        $expenseRow=$db->table('expenses')->selectSum('amount')->where('expense_date >=',$from)->where('expense_date <=',$to)->get()->getRowArray();
        $revenue=(float)($sales['grand_total']??0); $cost=(float)($costRow['v']??0); $expenses=(float)($expenseRow['amount']??0);
        return view('reports/index',['title'=>'Business Reports','from'=>$from,'to'=>$to,'sales'=>$sales,'revenue'=>$revenue,'cost'=>$cost,'expenses'=>$expenses,'grossProfit'=>$revenue-$cost,'netProfit'=>$revenue-$cost-$expenses]);
    }
}
