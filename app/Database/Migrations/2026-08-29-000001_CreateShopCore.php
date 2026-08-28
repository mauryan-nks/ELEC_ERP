<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateShopCore extends Migration
{
    public function up()
    {
        $sql = [];

        // This application is intentionally single-shop. There is exactly one
        // settings row (id=1); no tenant/store/workspace relationship exists.
        $sql[] = "CREATE TABLE IF NOT EXISTS shop_settings (
            id TINYINT UNSIGNED NOT NULL PRIMARY KEY DEFAULT 1,
            name VARCHAR(160) NOT NULL,
            phone VARCHAR(25) NULL,
            email VARCHAR(160) NULL,
            address TEXT NULL,
            gstin VARCHAR(32) NULL,
            invoice_prefix VARCHAR(20) NOT NULL DEFAULT 'INV',
            purchase_prefix VARCHAR(20) NOT NULL DEFAULT 'PUR',
            currency VARCHAR(10) NOT NULL DEFAULT 'INR',
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS document_sequences (
            doc_type ENUM('invoice','purchase') NOT NULL PRIMARY KEY,
            next_number BIGINT UNSIGNED NOT NULL DEFAULT 1,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        // Login credentials remain in Shield. This table stores the normal
        // staff profile fields displayed by the ERP Users screen.
        $sql[] = "CREATE TABLE IF NOT EXISTS staff_profiles (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            full_name VARCHAR(160) NULL,
            phone VARCHAR(25) NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS categories (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            type ENUM('device','accessory','service','other') NOT NULL DEFAULT 'other',
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_category_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS brands (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_brand_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS customers (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(160) NOT NULL,
            phone VARCHAR(25) NOT NULL,
            whatsapp_phone VARCHAR(25) NULL,
            email VARCHAR(160) NULL,
            address TEXT NULL,
            gstin VARCHAR(32) NULL,
            notes TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_customer_phone (phone),
            KEY idx_customers_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS suppliers (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(160) NOT NULL,
            phone VARCHAR(25) NULL,
            email VARCHAR(160) NULL,
            address TEXT NULL,
            gstin VARCHAR(32) NULL,
            supplier_type ENUM('vendor','other_store','individual') NOT NULL DEFAULT 'vendor',
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS products (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            category_id BIGINT UNSIGNED NULL,
            brand_id BIGINT UNSIGNED NULL,
            sku VARCHAR(80) NULL,
            name VARCHAR(180) NOT NULL,
            model VARCHAR(160) NULL,
            hsn_sac VARCHAR(30) NULL,
            product_type ENUM('device','accessory','service','other') NOT NULL DEFAULT 'other',
            is_serialized TINYINT(1) NOT NULL DEFAULT 0,
            serial_mode ENUM('none','imei','serial','unique_id','mixed') NOT NULL DEFAULT 'none',
            low_stock_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
            default_sale_price DECIMAL(14,2) NULL,
            tax_percent DECIMAL(6,3) NOT NULL DEFAULT 0,
            status ENUM('active','inactive') NOT NULL DEFAULT 'active',
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_product_sku (sku),
            KEY idx_products_lookup (name,model),
            CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
            CONSTRAINT fk_products_brand FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS purchases (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            supplier_id BIGINT UNSIGNED NULL,
            purchase_no VARCHAR(60) NOT NULL,
            supplier_invoice_no VARCHAR(100) NULL,
            purchase_date DATE NOT NULL,
            subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
            tax_total DECIMAL(14,2) NOT NULL DEFAULT 0,
            grand_total DECIMAL(14,2) NOT NULL DEFAULT 0,
            paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            due_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            notes TEXT NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_purchase_no (purchase_no),
            CONSTRAINT fk_purchases_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS purchase_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            purchase_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            qty DECIMAL(12,3) NOT NULL,
            unit_cost DECIMAL(14,2) NOT NULL,
            tax_percent DECIMAL(6,3) NOT NULL DEFAULT 0,
            line_total DECIMAL(14,2) NOT NULL,
            created_at DATETIME NULL,
            CONSTRAINT fk_purchase_items_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
            CONSTRAINT fk_purchase_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS stock_borrows (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            supplier_id BIGINT UNSIGNED NULL,
            reference_no VARCHAR(80) NULL,
            borrowed_at DATETIME NOT NULL,
            settlement_status ENUM('open','partly_settled','settled','returned') NOT NULL DEFAULT 'open',
            notes TEXT NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            CONSTRAINT fk_borrows_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS stock_lots (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            origin_type ENUM('purchase','borrowed','expense','opening','manual') NOT NULL,
            source_id BIGINT UNSIGNED NULL,
            source_note VARCHAR(255) NULL,
            qty_received DECIMAL(12,3) NOT NULL,
            qty_available DECIMAL(12,3) NOT NULL,
            unit_cost DECIMAL(14,2) NOT NULL DEFAULT 0,
            received_at DATETIME NOT NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NULL,
            KEY idx_stock_lots_available (product_id,qty_available),
            CONSTRAINT fk_stock_lots_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS inventory_units (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            stock_lot_id BIGINT UNSIGNED NOT NULL,
            imei1 VARCHAR(40) NULL,
            imei2 VARCHAR(40) NULL,
            serial_no VARCHAR(100) NULL,
            unique_id VARCHAR(100) NULL,
            color VARCHAR(80) NULL,
            storage_variant VARCHAR(80) NULL,
            status ENUM('available','reserved','sold','returned','damaged','borrow_returned') NOT NULL DEFAULT 'available',
            sold_sale_item_id BIGINT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_imei1 (imei1),
            UNIQUE KEY uq_imei2 (imei2),
            UNIQUE KEY uq_serial (serial_no),
            UNIQUE KEY uq_unique_id (unique_id),
            KEY idx_inventory_available (product_id,status),
            CONSTRAINT fk_inventory_units_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
            CONSTRAINT fk_inventory_units_lot FOREIGN KEY (stock_lot_id) REFERENCES stock_lots(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS stock_movements (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            product_id BIGINT UNSIGNED NOT NULL,
            stock_lot_id BIGINT UNSIGNED NULL,
            inventory_unit_id BIGINT UNSIGNED NULL,
            movement_type ENUM('in','out','adjustment','return') NOT NULL,
            qty DECIMAL(12,3) NOT NULL,
            reference_type VARCHAR(50) NULL,
            reference_id BIGINT UNSIGNED NULL,
            unit_cost DECIMAL(14,2) NOT NULL DEFAULT 0,
            notes VARCHAR(255) NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            KEY idx_stock_movements (product_id,created_at),
            CONSTRAINT fk_stock_movements_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
            CONSTRAINT fk_stock_movements_lot FOREIGN KEY (stock_lot_id) REFERENCES stock_lots(id) ON DELETE SET NULL,
            CONSTRAINT fk_stock_movements_unit FOREIGN KEY (inventory_unit_id) REFERENCES inventory_units(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS sales (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id BIGINT UNSIGNED NOT NULL,
            invoice_no VARCHAR(60) NOT NULL,
            sale_date DATETIME NOT NULL,
            sale_type ENUM('invoice','cash_memo') NOT NULL DEFAULT 'invoice',
            subtotal DECIMAL(14,2) NOT NULL DEFAULT 0,
            discount_total DECIMAL(14,2) NOT NULL DEFAULT 0,
            tax_total DECIMAL(14,2) NOT NULL DEFAULT 0,
            grand_total DECIMAL(14,2) NOT NULL DEFAULT 0,
            paid_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            due_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            payment_status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
            notes TEXT NULL,
            internal_notes TEXT NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_invoice_no (invoice_no),
            KEY idx_sales_customer (customer_id,sale_date),
            CONSTRAINT fk_sales_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS sale_items (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sale_id BIGINT UNSIGNED NOT NULL,
            product_id BIGINT UNSIGNED NOT NULL,
            stock_lot_id BIGINT UNSIGNED NULL,
            inventory_unit_id BIGINT UNSIGNED NULL,
            qty DECIMAL(12,3) NOT NULL,
            unit_price DECIMAL(14,2) NOT NULL,
            unit_cost DECIMAL(14,2) NOT NULL DEFAULT 0,
            discount_amount DECIMAL(14,2) NOT NULL DEFAULT 0,
            tax_percent DECIMAL(6,3) NOT NULL DEFAULT 0,
            line_total DECIMAL(14,2) NOT NULL,
            internal_source ENUM('stock','borrowed','expense','direct') NOT NULL DEFAULT 'stock',
            internal_source_note VARCHAR(255) NULL,
            created_at DATETIME NULL,
            CONSTRAINT fk_sale_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
            CONSTRAINT fk_sale_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
            CONSTRAINT fk_sale_items_lot FOREIGN KEY (stock_lot_id) REFERENCES stock_lots(id) ON DELETE SET NULL,
            CONSTRAINT fk_sale_items_unit FOREIGN KEY (inventory_unit_id) REFERENCES inventory_units(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS payments (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sale_id BIGINT UNSIGNED NULL,
            purchase_id BIGINT UNSIGNED NULL,
            amount DECIMAL(14,2) NOT NULL,
            method ENUM('cash','upi','card','bank','credit','other') NOT NULL DEFAULT 'cash',
            reference_no VARCHAR(120) NULL,
            paid_at DATETIME NOT NULL,
            notes TEXT NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NULL,
            CONSTRAINT fk_payments_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
            CONSTRAINT fk_payments_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS expenses (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            expense_date DATE NOT NULL,
            category VARCHAR(120) NOT NULL,
            amount DECIMAL(14,2) NOT NULL,
            payee VARCHAR(160) NULL,
            reference_no VARCHAR(120) NULL,
            notes TEXT NULL,
            linked_stock_lot_id BIGINT UNSIGNED NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            CONSTRAINT fk_expenses_lot FOREIGN KEY (linked_stock_lot_id) REFERENCES stock_lots(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS whatsapp_templates (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            event_key VARCHAR(80) NOT NULL,
            message TEXT NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            UNIQUE KEY uq_wa_template_name (name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS whatsapp_queue (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id BIGINT UNSIGNED NULL,
            sale_id BIGINT UNSIGNED NULL,
            phone VARCHAR(25) NOT NULL,
            event_key VARCHAR(80) NULL,
            dedupe_key VARCHAR(190) NULL,
            message TEXT NOT NULL,
            scheduled_at DATETIME NOT NULL,
            status ENUM('queued','processing','sent','failed','cancelled') NOT NULL DEFAULT 'queued',
            attempts INT UNSIGNED NOT NULL DEFAULT 0,
            last_error TEXT NULL,
            sent_at DATETIME NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_wa_queue (status,scheduled_at),
            UNIQUE KEY uq_wa_dedupe (dedupe_key),
            CONSTRAINT fk_wa_queue_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
            CONSTRAINT fk_wa_queue_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $sql[] = "CREATE TABLE IF NOT EXISTS audit_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NULL,
            action VARCHAR(120) NOT NULL,
            entity_type VARCHAR(100) NULL,
            entity_id BIGINT UNSIGNED NULL,
            meta_json JSON NULL,
            ip_address VARCHAR(64) NULL,
            created_at DATETIME NOT NULL,
            KEY idx_audit_entity (entity_type,entity_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        foreach ($sql as $query) {
            $this->db->query($query);
        }

        if (! $this->constraintExists('inventory_units', 'fk_inventory_units_sold_item')) {
            $this->db->query(
                'ALTER TABLE `inventory_units` ADD CONSTRAINT `fk_inventory_units_sold_item` '
                . 'FOREIGN KEY (`sold_sale_item_id`) REFERENCES `sale_items` (`id`) ON DELETE SET NULL'
            );
        }
    }

    public function down()
    {
        $this->db->disableForeignKeyChecks();
        foreach ([
            'audit_logs','whatsapp_queue','whatsapp_templates','expenses','payments','sale_items','sales',
            'stock_movements','inventory_units','stock_lots','stock_borrows','purchase_items','purchases',
            'products','suppliers','customers','brands','categories','staff_profiles','document_sequences','shop_settings'
        ] as $table) {
            $this->forge->dropTable($table, true);
        }
        $this->db->enableForeignKeyChecks();
    }

    private function constraintExists(string $table, string $constraint): bool
    {
        $row = $this->db->query(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS '
            . 'WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? LIMIT 1',
            [$this->db->getDatabase(), $table, $constraint]
        )->getRowArray();
        return $row !== null;
    }
}
