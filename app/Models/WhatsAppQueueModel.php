<?php
namespace App\Models;
use CodeIgniter\Model;
class WhatsAppQueueModel extends Model
{
    protected $table='whatsapp_queue'; protected $primaryKey='id'; protected $returnType='array'; protected $useTimestamps=true;
    protected $allowedFields=['customer_id','sale_id','phone','event_key','dedupe_key','message','scheduled_at','status','attempts','last_error','sent_at'];
}
