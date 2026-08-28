<?php

namespace App\Commands;

use App\Services\WhatsAppBridge;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckWhatsAppBridge extends BaseCommand
{
    protected $group = 'Shop';
    protected $name = 'whatsapp:check';
    protected $description = 'Check the server-local WhatsApp Web bridge on 127.0.0.1.';

    public function run(array $params)
    {
        $bridge = new WhatsAppBridge();
        CLI::write('Checking ' . $bridge->localUrl() . ' from the PHP server process...');

        try {
            $status = $bridge->status();
            CLI::write('Local bridge reachable: yes', 'green');
            CLI::write('WhatsApp ready: ' . (! empty($status['ready']) ? 'yes' : 'no'), ! empty($status['ready']) ? 'green' : 'yellow');
            if (! empty($status['info']['pushname'])) {
                CLI::write('Account: ' . $status['info']['pushname']);
            }
            if (! empty($status['lastError'])) {
                CLI::error('Last bridge error: ' . $status['lastError']);
            }
        } catch (\Throwable $e) {
            CLI::error($e->getMessage());
            CLI::write('Check: sudo systemctl status drmi-whatsapp-local --no-pager');
            CLI::write('Logs : sudo journalctl -u drmi-whatsapp-local -n 100 --no-pager');
        }
    }
}
