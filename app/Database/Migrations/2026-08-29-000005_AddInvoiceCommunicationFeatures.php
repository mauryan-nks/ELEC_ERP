<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInvoiceCommunicationFeatures extends Migration
{
    public function up()
    {
        $shopColumns = [
            'logo_base64' => "LONGTEXT NULL AFTER `logo_path`",
            'logo_mime' => "VARCHAR(80) NULL AFTER `logo_base64`",
            'signature_base64' => "LONGTEXT NULL AFTER `signature_path`",
            'signature_mime' => "VARCHAR(80) NULL AFTER `signature_base64`",
            'invoice_color' => "VARCHAR(20) NOT NULL DEFAULT '#e87523' AFTER `invoice_template`",
            'invoice_default_gst_mode' => "ENUM('inclusive','exclusive') NOT NULL DEFAULT 'inclusive' AFTER `invoice_default_gst_enabled`",
            'email_enabled' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER `invoice_default_gst_mode`",
            'email_smtp_host' => "VARCHAR(190) NULL AFTER `email_enabled`",
            'email_smtp_port' => "INT UNSIGNED NULL AFTER `email_smtp_host`",
            'email_smtp_user' => "VARCHAR(190) NULL AFTER `email_smtp_port`",
            'email_smtp_password' => "TEXT NULL AFTER `email_smtp_user`",
            'email_smtp_encryption' => "VARCHAR(10) NULL AFTER `email_smtp_password`",
            'email_from_address' => "VARCHAR(190) NULL AFTER `email_smtp_encryption`",
            'email_from_name' => "VARCHAR(160) NULL AFTER `email_from_address`",
            'email_invoice_enabled' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER `email_from_name`",
        ];
        foreach ($shopColumns as $field => $definition) {
            if (! $this->db->fieldExists($field, 'shop_settings')) {
                $this->db->query("ALTER TABLE `shop_settings` ADD `{$field}` {$definition}");
            }
        }

        $queueColumns = [
            'attachment_path' => "VARCHAR(255) NULL AFTER `message`",
            'attachment_mime' => "VARCHAR(120) NULL AFTER `attachment_path`",
            'attachment_name' => "VARCHAR(190) NULL AFTER `attachment_mime`",
            'message_type' => "ENUM('text','media') NOT NULL DEFAULT 'text' AFTER `attachment_name`",
        ];
        foreach ($queueColumns as $field => $definition) {
            if (! $this->db->fieldExists($field, 'whatsapp_queue')) {
                $this->db->query("ALTER TABLE `whatsapp_queue` ADD `{$field}` {$definition}");
            }
        }


        if ($this->db->tableExists('whatsapp_templates')) {
            $exists=$this->db->table('whatsapp_templates')->where('event_key','customer_welcome')->countAllResults();
            if($exists===0){
                $this->db->table('whatsapp_templates')->insert([
                    'name'=>'Customer Welcome','event_key'=>'customer_welcome',
                    'message'=>'Welcome {customer_name}! Thank you for choosing {store_name}. We are happy to have you with us.',
                    'is_active'=>1,'created_at'=>date('Y-m-d H:i:s'),'updated_at'=>date('Y-m-d H:i:s')
                ]);
            }
        }

        // Existing installations should use GST-inclusive prices by default,
        // matching the new invoice-entry UI.
        if ($this->db->fieldExists('invoice_default_gst_mode', 'shop_settings')) {
            $this->db->query("UPDATE `shop_settings` SET `invoice_default_gst_mode`='inclusive' WHERE `invoice_default_gst_mode` IS NULL OR `invoice_default_gst_mode`=''");
        }
    }

    public function down()
    {
        foreach ([
            'invoice_default_gst_mode','invoice_color','logo_base64','logo_mime','signature_base64','signature_mime',
            'email_enabled','email_smtp_host','email_smtp_port','email_smtp_user','email_smtp_password',
            'email_smtp_encryption','email_from_address','email_from_name','email_invoice_enabled',
        ] as $field) {
            if ($this->db->fieldExists($field, 'shop_settings')) {
                $this->db->query("ALTER TABLE `shop_settings` DROP COLUMN `{$field}`");
            }
        }
        foreach (['attachment_path','attachment_mime','attachment_name','message_type'] as $field) {
            if ($this->db->fieldExists($field, 'whatsapp_queue')) {
                $this->db->query("ALTER TABLE `whatsapp_queue` DROP COLUMN `{$field}`");
            }
        }
    }
}
