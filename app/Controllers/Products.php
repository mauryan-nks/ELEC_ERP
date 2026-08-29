<?php

namespace App\Controllers;

use App\Models\ProductModel;
use App\Services\InventoryService;

class Products extends BaseController
{
    public function index(): string
    {
        $db=db_connect();
        $products=$db->table('products p')->select('p.*,c.name category_name,b.name brand_name,COALESCE(SUM(l.qty_available),0) stock_qty')
            ->join('categories c','c.id=p.category_id','left')->join('brands b','b.id=p.brand_id','left')->join('stock_lots l','l.product_id=p.id','left')
            ->groupBy('p.id')->orderBy('p.id','DESC')->get()->getResultArray();
        return view('products/index',[
            'title'=>'Products & Brands','products'=>$products,'categories'=>$db->table('categories')->where('status','active')->orderBy('name')->get()->getResultArray(),
            'brands'=>$db->table('brands')->where('status','active')->orderBy('name')->get()->getResultArray(),
        ]);
    }

    public function store()
    {
        $db=db_connect(); $db->transBegin();
        try{
            $data=$this->productPayload(); $model=new ProductModel(); $model->insert($data); $productId=(int)$model->getInsertID();
            $openingQty=(float)$this->request->getPost('opening_qty');
            if($openingQty>0){
                $units=$this->request->getPost('opening_units');
                (new InventoryService())->receiveLot($productId,'opening',null,$openingQty,max(0,(float)$this->request->getPost('opening_unit_cost')),is_array($units)?$units:[],'Opening stock',auth()->id());
            }
            $db->transCommit(); return redirect()->to('/products')->with('message','Product added'.($openingQty>0?' with opening stock.':'.'));
        }catch(\Throwable $e){ $db->transRollback(); return redirect()->back()->withInput()->with('error',$this->friendly($e)); }
    }

    public function update(int $id)
    {
        try{
            $model=new ProductModel(); $product=$model->find($id); if(!$product) throw new \RuntimeException('Product not found.');
            $data=$this->productPayload();
            if((int)$data['is_serialized'] !== (int)$product['is_serialized']){
                $hasStock=db_connect()->table('stock_lots')->where('product_id',$id)->countAllResults()>0;
                if($hasStock) throw new \RuntimeException('Tracking type cannot be changed after stock has been received. Create a new product instead.');
            }
            $model->update($id,$data);
            return redirect()->to('/products')->with('message','Product updated.');
        }catch(\Throwable $e){ return redirect()->to('/products')->with('error',$this->friendly($e)); }
    }

    public function status(int $id)
    {
        $model=new ProductModel(); $product=$model->find($id); if(!$product) return redirect()->to('/products')->with('error','Product not found.');
        $status=$product['status']==='active'?'inactive':'active'; $model->update($id,['status'=>$status]);
        return redirect()->to('/products')->with('message','Product '.$status.'.');
    }

    public function quickMaster()
    {
        $type=(string)$this->request->getPost('type'); $name=trim((string)$this->request->getPost('name'));
        if(!in_array($type,['category','brand'],true)||$name==='') return $this->response->setStatusCode(422)->setJSON(['ok'=>false]);
        $table=$type==='category'?'categories':'brands'; $db=db_connect(); $existing=$db->table($table)->where('name',$name)->get()->getRowArray();
        if($existing) return $this->response->setJSON(['ok'=>true,'id'=>(int)$existing['id'],'name'=>$existing['name']]);
        $row=['name'=>$name,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')];
        if($type==='category') $row['type']=$this->request->getPost('product_type') ?: 'other';
        $db->table($table)->insert($row);
        return $this->response->setJSON(['ok'=>true,'id'=>(int)$db->insertID(),'name'=>$name]);
    }

    private function productPayload(): array
    {
        $name=trim((string)$this->request->getPost('name')); if($name==='') throw new \RuntimeException('Product name is required.');
        $db=db_connect(); $categoryId=$this->request->getPost('category_id') ?: null; $brandId=$this->request->getPost('brand_id') ?: null;
        $newCategory=trim((string)$this->request->getPost('new_category')); $newBrand=trim((string)$this->request->getPost('new_brand'));
        if(!$categoryId && $newCategory!==''){
            $found=$db->table('categories')->where('name',$newCategory)->get()->getRowArray();
            if($found){$categoryId=$found['id'];}else{$db->table('categories')->insert(['name'=>$newCategory,'type'=>$this->request->getPost('product_type') ?: 'other','created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);$categoryId=$db->insertID();}
        }
        if(!$brandId && $newBrand!==''){
            $found=$db->table('brands')->where('name',$newBrand)->get()->getRowArray();
            if($found){$brandId=$found['id'];}else{$db->table('brands')->insert(['name'=>$newBrand,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')]);$brandId=$db->insertID();}
        }
        $serialized=$this->request->getPost('is_serialized')?1:0;
        return [
            'category_id'=>$categoryId,'brand_id'=>$brandId,'sku'=>trim((string)$this->request->getPost('sku')) ?: null,
            'name'=>$name,'model'=>trim((string)$this->request->getPost('model')) ?: null,'hsn_sac'=>trim((string)$this->request->getPost('hsn_sac')) ?: null,
            'product_type'=>in_array($this->request->getPost('product_type'),['device','accessory','service','other'],true)?$this->request->getPost('product_type'):'other',
            'is_serialized'=>$serialized,'serial_mode'=>$serialized?($this->request->getPost('serial_mode') ?: 'mixed'):'none',
            'low_stock_qty'=>max(0,(float)$this->request->getPost('low_stock_qty')),'default_sale_price'=>$this->request->getPost('default_sale_price')!==''?(float)$this->request->getPost('default_sale_price'):null,
            'tax_percent'=>max(0,(float)$this->request->getPost('tax_percent')),'status'=>$this->request->getPost('status')==='inactive'?'inactive':'active'
        ];
    }

    private function friendly(\Throwable $e): string
    {
        $m=$e->getMessage(); return str_contains(strtolower($m),'duplicate')?'SKU or another unique value is already in use.':$m;
    }
}
