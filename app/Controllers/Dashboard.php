<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $db = db_connect();
        $today = date('Y-m-d');
        $data = [
            'shop' => $db->table('shop_settings')->where('id',1)->get()->getRowArray(),
            'salesToday' => (float)(($db->table('sales')->selectSum('grand_total')->where('DATE(sale_date)',$today)->get()->getRowArray()['grand_total'] ?? 0)),
            'dueTotal' => (float)(($db->table('sales')->selectSum('due_amount')->get()->getRowArray()['due_amount'] ?? 0)),
            'customerCount' => $db->table('customers')->countAllResults(),
            'productCount' => $db->table('products')->where('status','active')->countAllResults(),
            'serializedAvailable' => $db->table('inventory_units')->where('status','available')->countAllResults(),
            'recentSales' => $db->table('sales s')->select('s.*,c.name customer_name,c.phone customer_phone')->join('customers c','c.id=s.customer_id')->orderBy('s.id','DESC')->limit(8)->get()->getResultArray(),
        ];
        $data['title']='Dashboard'; return view('dashboard/index',$data);
    }
}
