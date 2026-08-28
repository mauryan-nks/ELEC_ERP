<?php

namespace App\Services;

class DocumentNumberService
{
    public function next(string $type): string
    {
        if(!in_array($type,['invoice','purchase'],true)) throw new \InvalidArgumentException('Invalid document type.');
        $db=db_connect();
        $db->query(
            "INSERT INTO document_sequences (doc_type,next_number,updated_at) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE updated_at=VALUES(updated_at)",
            [$type]
        );
        $row=$db->query('SELECT next_number FROM document_sequences WHERE doc_type=? FOR UPDATE',[$type])->getRowArray();
        $n=(int)$row['next_number'];
        $db->query('UPDATE document_sequences SET next_number=?,updated_at=NOW() WHERE doc_type=?',[$n+1,$type]);
        $shop=$db->table('shop_settings')->select('invoice_prefix,purchase_prefix')->where('id',1)->get()->getRowArray();
        $prefix=$type==='invoice'?($shop['invoice_prefix']??'INV'):($shop['purchase_prefix']??'PUR');
        return sprintf('%s-%s-%06d',$prefix,date('Ym'),$n);
    }
}
