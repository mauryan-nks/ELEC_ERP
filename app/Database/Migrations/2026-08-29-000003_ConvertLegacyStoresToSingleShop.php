<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use RuntimeException;

/**
 * Converts the first multi-store prototype into the intended architecture:
 * one installed shop with many staff logins.
 *
 * The migration refuses to silently discard operational data from secondary
 * stores. On the early starter databases, stores 2/3 were only duplicate seed
 * rows, so those seed-only rows are removed automatically.
 */
class ConvertLegacyStoresToSingleShop extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('stores')) {
            $this->ensureSingleShopTables();
            return;
        }

        $preferred = 1;
        $shop = $this->db->table('stores')->where('id', $preferred)->get()->getRowArray();
        if (! $shop) {
            $shop = $this->db->table('stores')->orderBy('id', 'ASC')->get()->getRowArray();
        }
        if (! $shop) {
            throw new RuntimeException('Legacy stores table exists but has no shop row to convert.');
        }
        $primaryStoreId = (int) $shop['id'];

        // Never destroy real transactional data that might have been entered
        // under another store ID. The admin can merge it manually first.
        $operational = [
            'customers','suppliers','products','purchases','stock_borrows','stock_lots',
            'inventory_units','stock_movements','sales','payments','expenses','whatsapp_queue','audit_logs'
        ];
        $conflicts = [];
        foreach ($operational as $table) {
            if (! $this->db->tableExists($table) || ! $this->db->fieldExists('store_id', $table)) {
                continue;
            }
            $builder = $this->db->table($table)->where('store_id !=', $primaryStoreId);
            if ($table === 'audit_logs') {
                $builder->where('store_id IS NOT NULL', null, false);
            }
            $count = $builder->countAllResults();
            if ($count > 0) {
                $conflicts[$table] = $count;
            }
        }
        if ($conflicts !== []) {
            $details = implode(', ', array_map(static fn($t, $n) => "{$t}={$n}", array_keys($conflicts), $conflicts));
            throw new RuntimeException(
                'Single-shop conversion stopped because secondary store IDs contain operational data: ' . $details
                . '. Move/merge those rows into store ' . $primaryStoreId . ' before rerunning the migration.'
            );
        }

        $this->ensureSingleShopTables();

        $now = date('Y-m-d H:i:s');
        $this->db->query(
            "INSERT INTO shop_settings (id,name,phone,email,address,gstin,invoice_prefix,purchase_prefix,currency,created_at,updated_at)
             VALUES (1,?,?,?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE name=VALUES(name),phone=VALUES(phone),email=VALUES(email),address=VALUES(address),
             gstin=VALUES(gstin),invoice_prefix=VALUES(invoice_prefix),purchase_prefix=VALUES(purchase_prefix),
             currency=VALUES(currency),updated_at=VALUES(updated_at)",
            [
                $shop['name'] ?? 'My Mobile & Electronics Shop', $shop['phone'] ?? null, $shop['email'] ?? null,
                $shop['address'] ?? null, $shop['gstin'] ?? null, $shop['invoice_prefix'] ?? 'INV',
                $shop['purchase_prefix'] ?? 'PUR', $shop['currency'] ?? 'INR', $shop['created_at'] ?? $now, $now
            ]
        );

        if ($this->db->tableExists('store_sequences')) {
            $rows = $this->db->table('store_sequences')->where('store_id', $primaryStoreId)->get()->getResultArray();
            foreach ($rows as $row) {
                $this->db->query(
                    'INSERT INTO document_sequences (doc_type,next_number,updated_at) VALUES (?,?,?) '
                    . 'ON DUPLICATE KEY UPDATE next_number=VALUES(next_number),updated_at=VALUES(updated_at)',
                    [$row['doc_type'], $row['next_number'], $row['updated_at'] ?? $now]
                );
            }
        }

        // The extra stores in the early dump only contain duplicated defaults.
        foreach (['categories','brands','whatsapp_templates'] as $table) {
            if ($this->db->tableExists($table) && $this->db->fieldExists('store_id', $table)) {
                $this->db->table($table)->where('store_id !=', $primaryStoreId)->delete();
            }
        }

        // Profiles are display/business data; credentials stay inside Shield.
        if ($this->db->tableExists('users')) {
            $users = $this->db->table('users')->select('id,username,created_at')->get()->getResultArray();
            foreach ($users as $user) {
                $this->db->query(
                    'INSERT INTO staff_profiles (user_id,full_name,phone,created_at,updated_at) VALUES (?,?,?,?,?) '
                    . 'ON DUPLICATE KEY UPDATE updated_at=VALUES(updated_at)',
                    [(int)$user['id'], $user['username'] ?: null, null, $user['created_at'] ?? $now, $now]
                );
            }
        }

        $storeFks = [
            'categories'         => 'fk_categories_store',
            'brands'             => 'fk_brands_store',
            'customers'          => 'fk_customers_store',
            'suppliers'          => 'fk_suppliers_store',
            'products'           => 'fk_products_store',
            'purchases'          => 'fk_purchases_store',
            'stock_borrows'      => 'fk_borrows_store',
            'stock_lots'         => 'fk_stock_lots_store',
            'inventory_units'    => 'fk_inventory_units_store',
            'stock_movements'    => 'fk_stock_movements_store',
            'sales'              => 'fk_sales_store',
            'payments'           => 'fk_payments_store',
            'expenses'           => 'fk_expenses_store',
            'whatsapp_templates' => 'fk_wa_templates_store',
            'whatsapp_queue'     => 'fk_wa_queue_store',
            'audit_logs'         => 'fk_audit_store',
        ];
        foreach ($storeFks as $table => $constraint) {
            $this->dropForeignIfExists($table, $constraint);
        }

        // Drop old composite indexes before removing store_id.
        $indexes = [
            'categories'         => ['uq_category'],
            'brands'             => ['uq_brand'],
            'customers'          => ['uq_customer_phone','idx_customers_name'],
            'products'           => ['uq_product_sku','idx_products_lookup'],
            'purchases'          => ['uq_purchase_no'],
            'stock_lots'         => ['idx_stock_lots_available'],
            'inventory_units'    => ['uq_imei1','uq_imei2','uq_serial','uq_unique_id','idx_inventory_available'],
            'stock_movements'    => ['idx_stock_movements'],
            'sales'              => ['uq_invoice_no','idx_sales_customer'],
            'whatsapp_templates' => ['uq_wa_template'],
            'audit_logs'         => ['idx_audit_entity'],
        ];
        foreach ($indexes as $table => $names) {
            foreach ($names as $name) {
                $this->dropIndexIfExists($table, $name);
            }
        }

        $tablesWithStoreId = [
            'categories','brands','customers','suppliers','products','purchases','stock_borrows','stock_lots',
            'inventory_units','stock_movements','sales','payments','expenses','whatsapp_templates','whatsapp_queue','audit_logs'
        ];
        foreach ($tablesWithStoreId as $table) {
            if ($this->db->tableExists($table) && $this->db->fieldExists('store_id', $table)) {
                $this->db->query("ALTER TABLE `{$table}` DROP COLUMN `store_id`");
            }
        }

        // Rebuild uniqueness and lookup indexes for a single shop.
        $this->addIndex('categories', 'uq_category_name', '`name`', true);
        $this->addIndex('brands', 'uq_brand_name', '`name`', true);
        $this->addIndex('customers', 'uq_customer_phone', '`phone`', true);
        $this->addIndex('customers', 'idx_customers_name', '`name`');
        $this->addIndex('products', 'uq_product_sku', '`sku`', true);
        $this->addIndex('products', 'idx_products_lookup', '`name`,`model`');
        $this->addIndex('purchases', 'uq_purchase_no', '`purchase_no`', true);
        $this->addIndex('stock_lots', 'idx_stock_lots_available', '`product_id`,`qty_available`');
        $this->addIndex('inventory_units', 'uq_imei1', '`imei1`', true);
        $this->addIndex('inventory_units', 'uq_imei2', '`imei2`', true);
        $this->addIndex('inventory_units', 'uq_serial', '`serial_no`', true);
        $this->addIndex('inventory_units', 'uq_unique_id', '`unique_id`', true);
        $this->addIndex('inventory_units', 'idx_inventory_available', '`product_id`,`status`');
        $this->addIndex('stock_movements', 'idx_stock_movements', '`product_id`,`created_at`');
        $this->addIndex('sales', 'uq_invoice_no', '`invoice_no`', true);
        $this->addIndex('sales', 'idx_sales_customer', '`customer_id`,`sale_date`');
        $this->addIndex('whatsapp_templates', 'uq_wa_template_name', '`name`', true);
        $this->addIndex('audit_logs', 'idx_audit_entity', '`entity_type`,`entity_id`');

        $this->db->disableForeignKeyChecks();
        $this->forge->dropTable('store_users', true);
        $this->forge->dropTable('store_sequences', true);
        $this->forge->dropTable('stores', true);
        $this->db->enableForeignKeyChecks();
    }

    public function down()
    {
        // Deliberately non-destructive: restoring fake tenant IDs would require
        // inventing data and could corrupt a live single-shop installation.
    }

    private function ensureSingleShopTables(): void
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS shop_settings (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS document_sequences (
            doc_type ENUM('invoice','purchase') NOT NULL PRIMARY KEY,
            next_number BIGINT UNSIGNED NOT NULL DEFAULT 1,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->db->query("CREATE TABLE IF NOT EXISTS staff_profiles (
            user_id INT UNSIGNED NOT NULL PRIMARY KEY,
            full_name VARCHAR(160) NULL,
            phone VARCHAR(25) NULL,
            created_by INT UNSIGNED NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        if (! $this->db->tableExists($table)) return;
        $row = $this->db->query(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS '
            . 'WHERE CONSTRAINT_SCHEMA=? AND TABLE_NAME=? AND CONSTRAINT_NAME=? AND CONSTRAINT_TYPE="FOREIGN KEY" LIMIT 1',
            [$this->db->getDatabase(), $table, $constraint]
        )->getRowArray();
        if ($row) $this->db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! $this->db->tableExists($table)) return;
        $row = $this->db->query(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=? LIMIT 1',
            [$this->db->getDatabase(), $table, $index]
        )->getRowArray();
        if ($row) $this->db->query("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
    }

    private function addIndex(string $table, string $name, string $columns, bool $unique = false): void
    {
        if (! $this->db->tableExists($table)) return;
        $row = $this->db->query(
            'SELECT INDEX_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=? LIMIT 1',
            [$this->db->getDatabase(), $table, $name]
        )->getRowArray();
        if (! $row) {
            $kind = $unique ? 'UNIQUE INDEX' : 'INDEX';
            $this->db->query("ALTER TABLE `{$table}` ADD {$kind} `{$name}` ({$columns})");
        }
    }
}
