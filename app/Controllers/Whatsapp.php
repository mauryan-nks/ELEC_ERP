<?php

namespace App\Controllers;

use App\Services\WhatsAppBridge;

class Whatsapp extends BaseController
{
    public function index(): string
    {
        return view('whatsapp/index', ['title' => 'WhatsApp']);
    }

    /**
     * Browser calls this same-origin CI route. CI then calls 127.0.0.1 on the
     * server. An offline local service is represented as JSON, not HTTP 503,
     * so the browser console is not flooded by expected connection errors.
     */
    public function status()
    {
        $bridge = new WhatsAppBridge();

        try {
            $data = $bridge->status();
            $data['bridgeAvailable'] = true;
            $data['transport'] = 'server-local';
            return $this->response->setJSON($data);
        } catch (\Throwable $e) {
            return $this->response->setJSON([
                'ok'              => false,
                'ready'           => false,
                'bridgeAvailable' => false,
                'transport'       => 'server-local',
                'error'           => $e->getMessage(),
            ]);
        }
    }

    public function qr()
    {
        $bridge = new WhatsAppBridge();

        try {
            $data = $bridge->qr();
            $data['bridgeAvailable'] = true;
            return $this->response->setJSON($data);
        } catch (\Throwable $e) {
            // Keep this same-origin endpoint non-503 as well. The UI only calls
            // it after status() confirms the local bridge is reachable.
            return $this->response->setJSON([
                'ok'              => false,
                'ready'           => false,
                'bridgeAvailable' => false,
                'qrDataUrl'       => null,
                'error'           => $e->getMessage(),
            ]);
        }
    }

    public function sendTest()
    {
        try {
            $r = (new WhatsAppBridge())->send(
                (string) $this->request->getPost('phone'),
                (string) $this->request->getPost('message')
            );
            $r['csrfHash'] = csrf_hash();
            return $this->response->setJSON($r);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(422)->setJSON([
                'ok'       => false,
                'error'    => $e->getMessage(),
                'csrfHash' => csrf_hash(),
            ]);
        }
    }
}
