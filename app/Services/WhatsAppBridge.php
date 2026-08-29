<?php

namespace App\Services;

use RuntimeException;

/**
 * Server-side client for the local WhatsApp Web bridge.
 *
 * Important: this service intentionally only talks to 127.0.0.1. The browser
 * never receives or connects to the Node bridge URL. This keeps the WhatsApp
 * Web session/API private to the web server itself.
 */
class WhatsAppBridge
{
    private string $url;
    private string $key;

    public function __construct()
    {
        $port = (int) env('whatsapp.bridgePort', 3099);
        if ($port < 1 || $port > 65535) {
            $port = 3099;
        }

        // Deliberately hard-coded to loopback. Do not replace with base_url(),
        // the public domain, or a URL supplied by browser JavaScript.
        $this->url = 'http://127.0.0.1:' . $port;
        $this->key = trim((string) env('whatsapp.bridgeKey', ''));
    }

    public function status(): array
    {
        return $this->request('GET', '/status');
    }

    public function qr(): array
    {
        return $this->request('GET', '/qr');
    }

    public function send(string $phone, string $message): array
    {
        return $this->request('POST', '/send', [
            'phone'   => $phone,
            'message' => $message,
        ]);
    }

    public function sendMedia(string $phone, string $message, string $base64, string $mime, string $filename): array
    {
        return $this->request('POST', '/send', [
            'phone' => $phone,
            'message' => $message,
            'media' => ['data' => $base64, 'mimetype' => $mime, 'filename' => $filename],
        ]);
    }

    public function localUrl(): string
    {
        return $this->url;
    }

    private function request(string $method, string $path, ?array $json = null): array
    {
        if ($this->key === '') {
            throw new RuntimeException('WhatsApp local bridge key is not configured in the server .env file.');
        }

        $options = [
            'headers' => [
                'X-Bridge-Key' => $this->key,
                'Accept'       => 'application/json',
            ],
            'connect_timeout' => 3,
            'timeout'         => 15,
            'http_errors'     => false,
        ];

        if ($json !== null) {
            $options['json'] = $json;
        }

        try {
            $res = service('curlrequest')->request($method, $this->url . $path, $options);
        } catch (\Throwable $e) {
            throw new RuntimeException(
                'Local WhatsApp service is not reachable on the server at ' . $this->url
                . '. Start drmi-whatsapp-local.service. Details: ' . $e->getMessage(),
                0,
                $e
            );
        }

        $status = $res->getStatusCode();
        $raw    = (string) $res->getBody();
        $body   = json_decode($raw, true);

        if (! is_array($body)) {
            throw new RuntimeException('Local WhatsApp service returned an invalid response (HTTP ' . $status . ').');
        }

        if ($status >= 400) {
            $message = trim((string) ($body['error'] ?? 'Local WhatsApp bridge request failed.'));
            throw new RuntimeException($message . ' (HTTP ' . $status . ')');
        }

        return $body;
    }
}
