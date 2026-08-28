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
            $logoPath = $this->processImage('logo', $current['logo_path'] ?? null, 'logo');
            $signaturePath = $this->processImage('signature', $current['signature_path'] ?? null, 'signature');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        if ($this->request->getPost('remove_logo')) {
            // Keep the old file on disk because historical invoices may reference it.
            $logoPath = null;
        }
        if ($this->request->getPost('remove_signature')) {
            // Keep the old file on disk because historical invoices may reference it.
            $signaturePath = null;
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
            'logo_path' => $logoPath,
            'signature_path' => $signaturePath,
            'invoice_prefix' => trim((string) $this->request->getPost('invoice_prefix')) ?: 'INV',
            'purchase_prefix' => trim((string) $this->request->getPost('purchase_prefix')) ?: 'PUR',
            'currency' => trim((string) $this->request->getPost('currency')) ?: 'INR',
            'invoice_template' => $template,
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
            'updated_at' => $now,
        ];

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

    private function processImage(string $field, ?string $oldPath, string $prefix): ?string
    {
        $file = $this->request->getFile($field);
        if (! $file instanceof UploadedFile || $file->getError() === UPLOAD_ERR_NO_FILE) return $oldPath;
        if (! $file->isValid()) throw new \RuntimeException(ucfirst($field) . ' upload failed.');
        if ($file->getSize() > 2 * 1024 * 1024) throw new \RuntimeException(ucfirst($field) . ' must be 2 MB or smaller.');

        $mime = strtolower((string) $file->getMimeType());
        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp'];
        if (! isset($allowed[$mime])) throw new \RuntimeException(ucfirst($field) . ' must be PNG, JPG, or WebP.');

        $dir = FCPATH . 'uploads/shop';
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Could not create the shop upload directory.');
        }
        $name = $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $allowed[$mime];
        $file->move($dir, $name, true);
        return 'uploads/shop/' . $name;
    }

    private function deletePublicFile(?string $relativePath): void
    {
        if (! $relativePath || ! str_starts_with($relativePath, 'uploads/shop/')) return;
        $path = FCPATH . ltrim($relativePath, '/');
        if (is_file($path)) @unlink($path);
    }
}
