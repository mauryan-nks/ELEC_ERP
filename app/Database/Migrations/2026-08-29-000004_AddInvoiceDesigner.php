<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInvoiceDesigner extends Migration
{
    public function up()
    {
        $shopColumns = [
            'logo_path' => "VARCHAR(255) NULL AFTER `gstin`",
            'signature_path' => "VARCHAR(255) NULL AFTER `logo_path`",
            'invoice_template' => "VARCHAR(40) NOT NULL DEFAULT 'classic' AFTER `currency`",
            'invoice_title' => "VARCHAR(100) NOT NULL DEFAULT 'TAX INVOICE' AFTER `invoice_template`",
            'invoice_terms' => "TEXT NULL AFTER `invoice_title`",
            'invoice_footer' => "TEXT NULL AFTER `invoice_terms`",
            'customer_address_position' => "VARCHAR(20) NOT NULL DEFAULT 'left' AFTER `invoice_footer`",
            'invoice_default_gst_enabled' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `customer_address_position`",
            'invoice_default_discount_enabled' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `invoice_default_gst_enabled`",
            'invoice_show_logo' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `invoice_default_discount_enabled`",
            'invoice_show_signature' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `invoice_show_logo`",
            'invoice_show_company_phone' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `invoice_show_signature`",
            'invoice_show_company_email' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `invoice_show_company_phone`",
            'invoice_show_company_address' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `invoice_show_company_email`",
            'invoice_show_customer_address' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `invoice_show_company_address`",
            'invoice_show_customer_gstin' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `invoice_show_customer_address`",
            'invoice_show_imei' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `invoice_show_customer_gstin`",
            'invoice_show_hsn' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `invoice_show_imei`",
            'invoice_show_item_discount' => "TINYINT(1) NOT NULL DEFAULT 1 AFTER `invoice_show_hsn`",
        ];
        foreach ($shopColumns as $field => $definition) {
            if (! $this->db->fieldExists($field, 'shop_settings')) {
                $this->db->query("ALTER TABLE `shop_settings` ADD `{$field}` {$definition}");
            }
        }

        $saleColumns = [
            'line_discount_total' => "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `discount_total`",
            'overall_discount_amount' => "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER `line_discount_total`",
            'due_date' => "DATE NULL AFTER `payment_status`",
            'invoice_config_json' => "LONGTEXT NULL AFTER `internal_notes`",
            'company_snapshot_json' => "LONGTEXT NULL AFTER `invoice_config_json`",
        ];
        foreach ($saleColumns as $field => $definition) {
            if (! $this->db->fieldExists($field, 'sales')) {
                $this->db->query("ALTER TABLE `sales` ADD `{$field}` {$definition}");
            }
        }
    }

    public function down()
    {
        foreach (['company_snapshot_json','invoice_config_json','due_date','overall_discount_amount','line_discount_total'] as $field) {
            if ($this->db->fieldExists($field, 'sales')) {
                $this->db->query("ALTER TABLE `sales` DROP COLUMN `{$field}`");
            }
        }
        foreach ([
            'invoice_show_item_discount','invoice_show_hsn','invoice_show_imei','invoice_show_customer_gstin',
            'invoice_show_customer_address','invoice_show_company_address','invoice_show_company_email',
            'invoice_show_company_phone','invoice_show_signature','invoice_show_logo','invoice_default_discount_enabled',
            'invoice_default_gst_enabled','customer_address_position','invoice_footer','invoice_terms','invoice_title',
            'invoice_template','signature_path','logo_path'
        ] as $field) {
            if ($this->db->fieldExists($field, 'shop_settings')) {
                $this->db->query("ALTER TABLE `shop_settings` DROP COLUMN `{$field}`");
            }
        }
    }
}
