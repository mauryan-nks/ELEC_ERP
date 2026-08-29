<?php

namespace App\Commands;

use App\Services\WhatsAppQueueService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class ProcessWhatsAppQueue extends BaseCommand
{
    protected $group='Shop'; protected $name='whatsapp:process'; protected $description='Send queued WhatsApp messages through the local WhatsApp Web bridge.';
    public function run(array $params)
    {
        $count=(new WhatsAppQueueService())->processPending(25);
        CLI::write('Processed '.$count.' WhatsApp message(s).','green');
    }
}
