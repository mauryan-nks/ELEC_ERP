<?php

namespace App\Controllers;

class Inventory extends BaseController
{
    public function index(): string
    {
        $db=db_connect();
        $products=$db->table('products p')->select('p.id,p.name,p.model,p.product_type,p.is_serialized,p.low_stock_qty,b.name brand_name,COALESCE(SUM(l.qty_available),0) stock_qty,COALESCE(SUM(l.qty_available*l.unit_cost),0) stock_value')
            ->join('brands b','b.id=p.brand_id','left')->join('stock_lots l','l.product_id=p.id','left')->where('p.status','active')
            ->groupBy('p.id')->orderBy('p.name')->get()->getResultArray();
        $units=$db->table('inventory_units u')->select('u.*,p.name product_name,p.model,b.name brand_name,l.origin_type,l.unit_cost,l.source_note')
            ->join('products p','p.id=u.product_id')->join('brands b','b.id=p.brand_id','left')->join('stock_lots l','l.id=u.stock_lot_id')
            ->orderBy('u.id','DESC')->limit(500)->get()->getResultArray();
        return view('inventory/index',['title'=>'Inventory & IMEI','products'=>$products,'units'=>$units]);
    }

    public function updateUnit(int $id)
    {
        $db=db_connect(); $db->transBegin();
        try{
            $unit=$db->query('SELECT * FROM inventory_units WHERE id=? FOR UPDATE',[$id])->getRowArray(); if(!$unit) throw new \RuntimeException('Inventory unit not found.');
            $lot=$db->query('SELECT * FROM stock_lots WHERE id=? FOR UPDATE',[$unit['stock_lot_id']])->getRowArray(); if(!$lot) throw new \RuntimeException('Stock lot not found.');
            $newStatus=(string)$this->request->getPost('status'); $allowed=['available','reserved','returned','damaged','borrow_returned'];
            if($unit['status']==='sold') $newStatus='sold'; elseif(!in_array($newStatus,$allowed,true)) $newStatus=$unit['status'];
            $data=[]; foreach(['imei1','imei2','serial_no','unique_id','color','storage_variant'] as $f){$v=trim((string)$this->request->getPost($f));$data[$f]=$v!==''?$v:null;} $data['status']=$newStatus; $data['updated_at']=date('Y-m-d H:i:s');
            if(array_filter([$data['imei1'],$data['imei2'],$data['serial_no'],$data['unique_id']])===[]) throw new \RuntimeException('Keep at least one IMEI, serial number or unique ID.');
            $ids=array_values(array_filter([$data['imei1'],$data['imei2'],$data['serial_no'],$data['unique_id']]));if(count(array_unique($ids))!==count($ids))throw new \RuntimeException('The same identifier cannot be repeated on one physical unit.');foreach($ids as $identifier){$dup=$db->table('inventory_units')->groupStart()->where('imei1',$identifier)->orWhere('imei2',$identifier)->orWhere('serial_no',$identifier)->orWhere('unique_id',$identifier)->groupEnd()->where('id !=',$id)->get()->getRowArray();if($dup)throw new \RuntimeException('Identifier '.$identifier.' is already assigned to another unit.');}
            $oldAvailable=$unit['status']==='available'; $newAvailable=$newStatus==='available';
            if($oldAvailable!==$newAvailable){
                if($newAvailable){$db->table('stock_lots')->where('id',$lot['id'])->set('qty_available','qty_available + 1',false)->update();$qty=1;}
                else{$db->table('stock_lots')->where('id',$lot['id'])->set('qty_available','GREATEST(qty_available - 1,0)',false)->update();$qty=-1;}
                $db->table('stock_movements')->insert(['product_id'=>$unit['product_id'],'stock_lot_id'=>$lot['id'],'inventory_unit_id'=>$id,'movement_type'=>'adjustment','qty'=>$qty,'reference_type'=>'inventory_edit','unit_cost'=>$lot['unit_cost'],'notes'=>'Status '.$unit['status'].' → '.$newStatus,'created_by'=>auth()->id(),'created_at'=>date('Y-m-d H:i:s')]);
            }
            $db->table('inventory_units')->where('id',$id)->update($data); $db->transCommit();
            return redirect()->to('/inventory')->with('message','Inventory unit updated.');
        }catch(\Throwable $e){$db->transRollback();return redirect()->to('/inventory')->with('error',$e->getMessage());}
    }

    public function adjust()
    {
        $db=db_connect(); $db->transBegin();
        try{
            $productId=(int)$this->request->getPost('product_id'); $direction=(string)$this->request->getPost('direction'); $qty=(float)$this->request->getPost('qty');
            $cost=max(0,(float)$this->request->getPost('unit_cost')); $note=trim((string)$this->request->getPost('note')) ?: 'Manual inventory adjustment';
            $product=$db->table('products')->where('id',$productId)->get()->getRowArray(); if(!$product) throw new \RuntimeException('Product not found.');
            if((int)$product['is_serialized']===1) throw new \RuntimeException('Serialized stock must be received with its IMEI/serial. Use Receive Stock for new units, or Edit on an existing unit.');
            if($qty<=0) throw new \RuntimeException('Adjustment quantity must be greater than zero.');
            if($direction==='add'){
                $db->table('stock_lots')->insert(['product_id'=>$productId,'origin_type'=>'manual','source_note'=>$note,'qty_received'=>$qty,'qty_available'=>$qty,'unit_cost'=>$cost,'received_at'=>date('Y-m-d H:i:s'),'created_by'=>auth()->id(),'created_at'=>date('Y-m-d H:i:s')]);
                $lotId=(int)$db->insertID();$db->table('stock_movements')->insert(['product_id'=>$productId,'stock_lot_id'=>$lotId,'movement_type'=>'adjustment','qty'=>$qty,'reference_type'=>'manual_adjustment','unit_cost'=>$cost,'notes'=>$note,'created_by'=>auth()->id(),'created_at'=>date('Y-m-d H:i:s')]);
            }else{
                $remaining=$qty;$lots=$db->query('SELECT * FROM stock_lots WHERE product_id=? AND qty_available>0 ORDER BY received_at,id FOR UPDATE',[$productId])->getResultArray();
                foreach($lots as $lot){if($remaining<=0)break;$take=min($remaining,(float)$lot['qty_available']);if($take<=0)continue;$db->table('stock_lots')->where('id',$lot['id'])->set('qty_available','qty_available - '.(float)$take,false)->update();$db->table('stock_movements')->insert(['product_id'=>$productId,'stock_lot_id'=>$lot['id'],'movement_type'=>'adjustment','qty'=>-$take,'reference_type'=>'manual_adjustment','unit_cost'=>$lot['unit_cost'],'notes'=>$note,'created_by'=>auth()->id(),'created_at'=>date('Y-m-d H:i:s')]);$remaining-=$take;}
                if($remaining>0.00001) throw new \RuntimeException('Cannot remove more than the available stock.');
            }
            $db->transCommit();return redirect()->to('/inventory')->with('message','Stock adjusted.');
        }catch(\Throwable $e){$db->transRollback();return redirect()->to('/inventory')->with('error',$e->getMessage());}
    }
}
