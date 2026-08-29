<?php

namespace App\Controllers;

use CodeIgniter\HTTP\Files\UploadedFile;

class Settings extends BaseController
{
    private const TEMPLATES = [
        'classic' => 'Classic',
        'modern' => 'Modern',
        'minimal' => 'Minimal',
        'compact' => 'Compact',
        'executive' => 'Executive',
        'retail' => 'Retail Pro',
        'bold' => 'Bold Header',
        'bordered' => 'Clean Border',
        'elegant' => 'Elegant',
        'thermal' => 'Thermal / Narrow',
    ];

    public function index(): string
    {
        $shop = db_connect()->table('shop_settings')->where('id', 1)->get()->getRowArray() ?? [];
        return view('settings/index', [
            'title' => 'Shop Settings & Invoice Designer',
            'shop' => $shop,
            'invoiceTemplates' => self::TEMPLATES,
        ]);
    }

    public function update()
    {
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->back()->withInput()->with('error', 'Company name is required.');
        }

        $db = db_connect();
        $current = $db->table('shop_settings')->where('id', 1)->get()->getRowArray() ?? [];

        try {
            $logo = $this->processImage('logo', $current['logo_base64'] ?? null);
            $signature = $this->processImage('signature', $current['signature_base64'] ?? null);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        if ($this->request->getPost('remove_logo')) {
            // Images are stored only in the database as Base64; no filesystem copy is retained.
            $logo = ['base64' => null, 'mime' => null];
        }
        if ($this->request->getPost('remove_signature')) {
            // Images are stored only in the database as Base64; no filesystem copy is retained.
            $signature = ['base64' => null, 'mime' => null];
        }

        $template = (string) $this->request->getPost('invoice_template');
        if (! isset(self::TEMPLATES[$template])) $template = 'classic';

        $addressPosition = (string) $this->request->getPost('customer_address_position');
        if (! in_array($addressPosition, ['left', 'right', 'full', 'hidden'], true)) $addressPosition = 'left';

        $now = date('Y-m-d H:i:s');
        $data = [
            'name' => $name,
            'phone' => trim((string) $this->request->getPost('phone')) ?: null,
            'email' => trim((string) $this->request->getPost('email')) ?: null,
            'address' => trim((string) $this->request->getPost('address')) ?: null,
            'gstin' => trim((string) $this->request->getPost('gstin')) ?: null,
            'logo_path' => null,
            'logo_base64' => $logo['base64'],
            'logo_mime' => $logo['mime'],
            'signature_path' => null,
            'signature_base64' => $signature['base64'],
            'signature_mime' => $signature['mime'],
            'invoice_prefix' => trim((string) $this->request->getPost('invoice_prefix')) ?: 'INV',
            'purchase_prefix' => trim((string) $this->request->getPost('purchase_prefix')) ?: 'PUR',
            'currency' => trim((string) $this->request->getPost('currency')) ?: 'INR',
            'invoice_template' => $template,
            'invoice_color' => $this->validColor($this->request->getPost('invoice_color'), $current['invoice_color'] ?? '#e87523'),
            'invoice_default_gst_mode' => in_array($this->request->getPost('invoice_default_gst_mode'), ['inclusive','exclusive'], true) ? $this->request->getPost('invoice_default_gst_mode') : 'inclusive',
            'invoice_title' => trim((string) $this->request->getPost('invoice_title')) ?: 'TAX INVOICE',
            'invoice_terms' => trim((string) $this->request->getPost('invoice_terms')) ?: null,
            'invoice_footer' => trim((string) $this->request->getPost('invoice_footer')) ?: null,
            'customer_address_position' => $addressPosition,
            'invoice_default_gst_enabled' => $this->boolPost('invoice_default_gst_enabled'),
            'invoice_default_discount_enabled' => $this->boolPost('invoice_default_discount_enabled'),
            'invoice_show_logo' => $this->boolPost('invoice_show_logo'),
            'invoice_show_signature' => $this->boolPost('invoice_show_signature'),
            'invoice_show_company_phone' => $this->boolPost('invoice_show_company_phone'),
            'invoice_show_company_email' => $this->boolPost('invoice_show_company_email'),
            'invoice_show_company_address' => $this->boolPost('invoice_show_company_address'),
            'invoice_show_customer_address' => $this->boolPost('invoice_show_customer_address'),
            'invoice_show_customer_gstin' => $this->boolPost('invoice_show_customer_gstin'),
            'invoice_show_imei' => $this->boolPost('invoice_show_imei'),
            'invoice_show_hsn' => $this->boolPost('invoice_show_hsn'),
            'invoice_show_item_discount' => $this->boolPost('invoice_show_item_discount'),
            'email_enabled' => $this->boolPost('email_enabled'),
            'email_smtp_host' => trim((string) $this->request->getPost('email_smtp_host')) ?: null,
            'email_smtp_port' => max(1, (int) ($this->request->getPost('email_smtp_port') ?: 587)),
            'email_smtp_user' => trim((string) $this->request->getPost('email_smtp_user')) ?: null,
            'email_smtp_encryption' => in_array($this->request->getPost('email_smtp_encryption'), ['tls','ssl','none'], true) ? $this->request->getPost('email_smtp_encryption') : 'tls',
            'email_from_address' => trim((string) $this->request->getPost('email_from_address')) ?: null,
            'email_from_name' => trim((string) $this->request->getPost('email_from_name')) ?: $name,
            'email_invoice_enabled' => $this->boolPost('email_invoice_enabled'),
            'updated_at' => $now,
        ];

        $smtpPassword = (string) $this->request->getPost('email_smtp_password');
        if ($smtpPassword !== '') {
            $data['email_smtp_password'] = service('encrypter')->encrypt($smtpPassword);
        } elseif ($current && array_key_exists('email_smtp_password', $current)) {
            $data['email_smtp_password'] = $current['email_smtp_password'];
        }

        if ($current) {
            $db->table('shop_settings')->where('id', 1)->update($data);
        } else {
            $data['id'] = 1;
            $data['created_at'] = $now;
            $db->table('shop_settings')->insert($data);
        }

        return redirect()->to('/settings')->with('message', 'Shop and invoice designer settings updated.');
    }

    private function boolPost(string $name): int
    {
        return $this->request->getPost($name) ? 1 : 0;
    }

    private function processImage(string $field, ?string $oldBase64): array
    {
        $file = $this->request->getFile($field);
        if (! $file instanceof UploadedFile || $file->getError() === UPLOAD_ERR_NO_FILE) {
            $row = db_connect()->table('shop_settings')->where('id', 1)->get()->getRowArray() ?? [];
            return [
                'base64' => $oldBase64 ?: ($row[$field . '_base64'] ?? null),
                'mime' => $row[$field . '_mime'] ?? null,
            ];
        }
        if (! $file->isValid()) throw new \RuntimeException(ucfirst($field) . ' upload failed.');
        if ($file->getSize() > 2 * 1024 * 1024) throw new \RuntimeException(ucfirst($field) . ' must be 2 MB or smaller.');
        $mime = strtolower((string) $file->getMimeType());
        if (! in_array($mime, ['image/png','image/jpeg','image/webp'], true)) {
            throw new \RuntimeException(ucfirst($field) . ' must be PNG, JPG, or WebP.');
        }
        $raw = file_get_contents($file->getTempName());
        if ($raw === false || $raw === '') throw new \RuntimeException('Unable to read ' . $field . ' upload.');
        return ['base64' => base64_encode($raw), 'mime' => $mime];
    }

    private function validColor($value, string $fallback): string
    {
        $value = trim((string) $value);
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $fallback;
    }

    private function deletePublicFile(?string $relativePath): void
    {
        if (! $relativePath || ! str_starts_with($relativePath, 'uploads/shop/')) return;
        $path = FCPATH . ltrim($relativePath, '/');
        if (is_file($path)) @unlink($path);
    }
}
