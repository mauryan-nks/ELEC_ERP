<?php

namespace App\Services;

use RuntimeException;

class InventoryService
{
    /**
     * Check whether an identifier already exists in inventory.
     *
     * Returns the matching inventory row, or null.
     *
     * Checks all identifier columns so an IMEI cannot be
     * stored as another unit's serial/unique ID either.
     */
    public function findDuplicateIdentifier(string $identifier): ?array
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        $db = db_connect();

        $row = $db->table('inventory_units')
            ->groupStart()
                ->where('imei1', $identifier)
                ->orWhere('imei2', $identifier)
                ->orWhere('serial_no', $identifier)
                ->orWhere('unique_id', $identifier)
            ->groupEnd()
            ->orderBy('id', 'ASC')
            ->get()
            ->getRowArray();

        return $row ?: null;
    }

    /**
     * Return a friendly duplicate message or null.
     */
    public function duplicateMessage(string $identifier): ?string
    {
        $identifier = trim($identifier);

        if ($identifier === '') {
            return null;
        }

        $duplicate = $this->findDuplicateIdentifier($identifier);

        if (!$duplicate) {
            return null;
        }

        $productName = '';

        if (!empty($duplicate['product_id'])) {
            $product = db_connect()
                ->table('products')
                ->select('name,model')
                ->where('id', (int)$duplicate['product_id'])
                ->get()
                ->getRowArray();

            if ($product) {
                $productName = trim(
                    ($product['name'] ?? '') .
                    ' ' .
                    ($product['model'] ?? '')
                );
            }
        }

        if ($productName !== '') {
            return 'Identifier "' . $identifier .
                '" already exists in inventory for "' .
                $productName . '".';
        }

        return 'Identifier "' . $identifier .
            '" is already assigned to another inventory unit.';
    }

    /**
     * Validate a list of submitted units before receiving them.
     *
     * Also catches duplicates inside the SAME purchase.
     */
    public function validateIncomingUnits(array $units): void
    {
        $seen = [];

        foreach ($units as $unitIndex => $unit) {

            if (!is_array($unit)) {
                throw new RuntimeException(
                    'Invalid inventory unit data.'
                );
            }

            $identifiers = [
                'IMEI 1' => trim((string)($unit['imei1'] ?? '')),
                'IMEI 2' => trim((string)($unit['imei2'] ?? '')),
                'Serial No' => trim((string)($unit['serial_no'] ?? '')),
                'Unique ID' => trim((string)($unit['unique_id'] ?? '')),
            ];

            $identifiers = array_filter(
                $identifiers,
                static fn($value) => $value !== ''
            );

            if ($identifiers === []) {
                throw new RuntimeException(
                    'Unit ' . ((int)$unitIndex + 1) .
                    ' needs at least one IMEI, serial number, or unique ID.'
                );
            }

            /*
             * Same physical unit cannot contain the same
             * identifier in multiple fields.
             */
            $values = array_values($identifiers);

            if (count($values) !== count(array_unique($values))) {
                throw new RuntimeException(
                    'The same identifier cannot be repeated on one physical unit.'
                );
            }

            foreach ($identifiers as $fieldName => $identifier) {

                /*
                 * Duplicate inside this purchase.
                 */
                $key = mb_strtolower($identifier);

                if (isset($seen[$key])) {
                    throw new RuntimeException(
                        $fieldName . ' "' . $identifier .
                        '" is repeated in this purchase.'
                    );
                }

                $seen[$key] = true;

                /*
                 * Duplicate against existing inventory.
                 */
                $duplicate = $this->findDuplicateIdentifier(
                    $identifier
                );

                if ($duplicate) {
                    $message = $this->duplicateMessage(
                        $identifier
                    );

                    throw new RuntimeException(
                        $message ??
                        'Identifier "' . $identifier .
                        '" is already assigned to another inventory unit.'
                    );
                }
            }
        }
    }

    public function receiveLot(
        int $productId,
        string $originType,
        ?int $sourceId,
        float $qty,
        float $unitCost,
        array $units = [],
        ?string $sourceNote = null,
        ?int $userId = null
    ): int {
        $db = db_connect();

        $product = $db->table('products')
            ->where('id', $productId)
            ->get()
            ->getRowArray();

        if (!$product) {
            throw new RuntimeException(
                'Product not found.'
            );
        }

        if ($qty <= 0) {
            throw new RuntimeException(
                'Received quantity must be greater than zero.'
            );
        }

        if ((int)$product['is_serialized'] === 1) {

            if (floor($qty) !== $qty) {
                throw new RuntimeException(
                    'Serialized product quantity must be a whole number.'
                );
            }

            if (count($units) !== (int)$qty) {
                throw new RuntimeException(
                    'This product needs one IMEI/serial/unique ID row per received unit.'
                );
            }

            /*
             * Validate every submitted unit BEFORE creating
             * the stock lot.
             */
            $this->validateIncomingUnits($units);
        }

        $db->table('stock_lots')->insert([
            'product_id'    => $productId,
            'origin_type'   => $originType,
            'source_id'     => $sourceId,
            'source_note'   => $sourceNote,
            'qty_received'  => $qty,
            'qty_available' => $qty,
            'unit_cost'     => $unitCost,
            'received_at'   => date('Y-m-d H:i:s'),
            'created_by'    => $userId,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        $lotId = (int)$db->insertID();

        foreach ($units as $unit) {

            $clean = [
                'imei1' => trim(
                    (string)($unit['imei1'] ?? '')
                ),
                'imei2' => trim(
                    (string)($unit['imei2'] ?? '')
                ),
                'serial_no' => trim(
                    (string)($unit['serial_no'] ?? '')
                ),
                'unique_id' => trim(
                    (string)($unit['unique_id'] ?? '')
                ),
                'color' => trim(
                    (string)($unit['color'] ?? '')
                ),
                'storage_variant' => trim(
                    (string)($unit['storage_variant'] ?? '')
                ),
            ];

            $identifiers = array_values(
                array_filter([
                    $clean['imei1'],
                    $clean['imei2'],
                    $clean['serial_no'],
                    $clean['unique_id'],
                ])
            );

            if ($identifiers === []) {
                throw new RuntimeException(
                    'Every serialized unit needs at least one identifier.'
                );
            }

            if (
                count(array_unique($identifiers)) !==
                count($identifiers)
            ) {
                throw new RuntimeException(
                    'The same identifier cannot be repeated on one physical unit.'
                );
            }

            /*
             * Final duplicate check immediately before INSERT.
             *
             * This remains intentionally in receiveLot even though
             * validateIncomingUnits() already checked everything.
             *
             * It protects this method when called from another part
             * of the application in the future.
             */
            foreach ($identifiers as $identifier) {

                $duplicate = $this->findDuplicateIdentifier(
                    $identifier
                );

                if ($duplicate) {
                    throw new RuntimeException(
                        $this->duplicateMessage($identifier)
                        ??
                        'Identifier "' . $identifier .
                        '" is already assigned to another inventory unit.'
                    );
                }
            }

            $db->table('inventory_units')->insert([
                'product_id' => $productId,
                'stock_lot_id' => $lotId,
                'imei1' => $clean['imei1'] ?: null,
                'imei2' => $clean['imei2'] ?: null,
                'serial_no' => $clean['serial_no'] ?: null,
                'unique_id' => $clean['unique_id'] ?: null,
                'color' => $clean['color'] ?: null,
                'storage_variant' => $clean['storage_variant'] ?: null,
                'status' => 'available',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            $this->movement(
                $productId,
                $lotId,
                (int)$db->insertID(),
                'in',
                1,
                $originType,
                $sourceId,
                $unitCost,
                $sourceNote,
                $userId
            );
        }

        if ((int)$product['is_serialized'] !== 1) {

            $this->movement(
                $productId,
                $lotId,
                null,
                'in',
                $qty,
                $originType,
                $sourceId,
                $unitCost,
                $sourceNote,
                $userId
            );
        }

        return $lotId;
    }

    public function availableUnits(int $productId): array
    {
        return db_connect()
            ->table('inventory_units u')
            ->select(
                'u.*,l.unit_cost,l.origin_type,l.source_note'
            )
            ->join(
                'stock_lots l',
                'l.id=u.stock_lot_id'
            )
            ->where([
                'u.product_id' => $productId,
                'u.status' => 'available',
            ])
            ->orderBy('u.id', 'ASC')
            ->get()
            ->getResultArray();
    }

    public function consume(
        int $productId,
        float $qty,
        ?int $inventoryUnitId,
        int $saleItemId,
        ?int $userId = null
    ): array {
        $db = db_connect();

        $product = $db->table('products')
            ->where('id', $productId)
            ->get()
            ->getRowArray();

        if (!$product) {
            throw new RuntimeException(
                'Product not found.'
            );
        }

        if ((int)$product['is_serialized'] === 1) {

            if ($qty !== 1.0 && $qty !== 1) {
                throw new RuntimeException(
                    'A serialized sale row must have quantity 1.'
                );
            }

            if (!$inventoryUnitId) {
                throw new RuntimeException(
                    'Select the IMEI/serial unit being sold.'
                );
            }

            $unit = $db->query(
                'SELECT * FROM inventory_units WHERE id=? AND product_id=? FOR UPDATE',
                [
                    $inventoryUnitId,
                    $productId,
                ]
            )->getRowArray();

            if (
                !$unit ||
                $unit['status'] !== 'available'
            ) {
                throw new RuntimeException(
                    'Selected IMEI/serial unit is no longer available.'
                );
            }

            $lot = $db->query(
                'SELECT * FROM stock_lots WHERE id=? FOR UPDATE',
                [
                    $unit['stock_lot_id'],
                ]
            )->getRowArray();

            $db->table('inventory_units')
                ->where('id', $unit['id'])
                ->update([
                    'status' => 'sold',
                    'sold_sale_item_id' => $saleItemId,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            $db->table('stock_lots')
                ->where('id', $lot['id'])
                ->set(
                    'qty_available',
                    'qty_available - 1',
                    false
                )
                ->update();

            $this->movement(
                $productId,
                (int)$lot['id'],
                (int)$unit['id'],
                'out',
                1,
                'sale_item',
                $saleItemId,
                (float)$lot['unit_cost'],
                null,
                $userId
            );

            return [
                'lot_id' =>
                    (int)$lot['id'],
                'unit_cost' =>
                    (float)$lot['unit_cost'],
            ];
        }

        $remaining = $qty;
        $costTotal = 0.0;
        $firstLotId = null;

        $lots = $db->query(
            'SELECT * FROM stock_lots
             WHERE product_id=?
             AND qty_available>0
             ORDER BY received_at,id
             FOR UPDATE',
            [
                $productId,
            ]
        )->getResultArray();

        foreach ($lots as $lot) {

            if ($remaining <= 0) {
                break;
            }

            $take = min(
                $remaining,
                (float)$lot['qty_available']
            );

            if ($take <= 0) {
                continue;
            }

            $firstLotId ??= (int)$lot['id'];

            $db->table('stock_lots')
                ->where('id', $lot['id'])
                ->set(
                    'qty_available',
                    'qty_available - ' . (float)$take,
                    false
                )
                ->update();

            $costTotal +=
                $take * (float)$lot['unit_cost'];

            $remaining -= $take;

            $this->movement(
                $productId,
                (int)$lot['id'],
                null,
                'out',
                $take,
                'sale_item',
                $saleItemId,
                (float)$lot['unit_cost'],
                null,
                $userId
            );
        }

        if ($remaining > 0.00001) {
            throw new RuntimeException(
                'Insufficient stock for this product.'
            );
        }

        return [
            'lot_id' => $firstLotId,
            'unit_cost' =>
                $qty > 0
                    ? $costTotal / $qty
                    : 0.0,
        ];
    }

    private function movement(
        int $productId,
        ?int $lotId,
        ?int $unitId,
        string $type,
        float $qty,
        ?string $refType,
        ?int $refId,
        float $unitCost,
        ?string $notes,
        ?int $userId
    ): void {
        db_connect()
            ->table('stock_movements')
            ->insert([
                'product_id' => $productId,
                'stock_lot_id' => $lotId,
                'inventory_unit_id' => $unitId,
                'movement_type' => $type,
                'qty' => $qty,
                'reference_type' => $refType,
                'reference_id' => $refId,
                'unit_cost' => $unitCost,
                'notes' => $notes,
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
    }
}