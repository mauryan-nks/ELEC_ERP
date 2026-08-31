<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $canManage = auth()->user()->can('products.create'); ?>
<div class="ms-page-head">
    <div>
        <h1>Inventory & IMEI register</h1>
        <p>Edit physical device identities, manage unit status, and adjust bulk accessories without changing historical sales.</p>
    </div>
    <div class="ms-actions">
        <?php if ($canManage): ?><button class="ms-btn ms-btn-secondary" type="button" data-open-dialog="adjustDialog">± Adjust Bulk Stock</button><?php endif; ?>
        <?php if (auth()->user()->can('purchases.create')): ?><a class="ms-btn ms-btn-primary" href="<?= site_url('purchases/new') ?>">+ Receive Stock</a><?php endif; ?>
    </div>
</div>

<div class="ms-card">
    <div class="ms-section-head"><div><h2>Stock summary</h2><p>Current sellable quantity and inventory value at acquisition cost.</p></div><div class="ms-search"><input class="ms-input" data-table-filter="#stockSummary" placeholder="Search stock summary"></div></div>
    <div class="ms-table-scroll">
        <table class="ms-table" id="stockSummary">
            <thead><tr><th>Product</th><th>Type</th><th>Tracking</th><th>Available</th><th>Low-stock level</th><th>Stock value</th></tr></thead>
            <tbody>
            <?php foreach ($products as $p): $low = (float) $p['stock_qty'] <= (float) $p['low_stock_qty'] && (float) $p['low_stock_qty'] > 0; ?>
                <tr>
                    <td><strong><?= esc(trim(($p['brand_name'] ?? '') . ' ' . $p['name'])) ?></strong><br><span class="ms-muted"><?= esc($p['model'] ?? '') ?></span></td>
                    <td><?= esc(ucfirst($p['product_type'])) ?></td>
                    <td><?= $p['is_serialized'] ? '<span class="ms-badge is-ok">Per unit</span>' : '<span class="ms-badge is-neutral">Bulk</span>' ?></td>
                    <td><strong class="<?= $low ? 'ms-text-danger' : '' ?>"><?= number_format((float) $p['stock_qty'], 0) ?></strong><?= $low ? ' <span class="ms-badge is-danger">Low</span>' : '' ?></td>
                    <td><?= number_format((float) $p['low_stock_qty'], 0) ?></td>
                    <td>₹<?= number_format((float) $p['stock_value'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $products): ?><tr class="ms-no-filter"><td colspan="6" class="ms-empty">No products found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="ms-card ms-spacer-top-lg">
    <div class="ms-section-head"><div><h2>Serialized units</h2><p>IMEI, serial, variant, internal source, unit cost and current status.</p></div><div class="ms-search"><input class="ms-input" data-table-filter="#unitTable" placeholder="Search IMEI, serial, model or source"></div></div>
    <div class="ms-table-scroll">
        <table class="ms-table" id="unitTable">
            <thead><tr><th>Product</th><th>IMEI 1</th><th>IMEI 2</th><th>Serial / Unique</th><th>Variant</th><th>Status</th><th>Internal source</th><th>Cost</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($units as $u): ?>
                <tr>
                    <td><strong><?= esc(trim(($u['brand_name'] ?? '') . ' ' . $u['product_name'])) ?></strong><br><span class="ms-muted"><?= esc($u['model'] ?? '') ?></span></td>
                    <td><?= esc($u['imei1'] ?? '-') ?></td>
                    <td><?= esc($u['imei2'] ?? '-') ?></td>
                    <td><?= esc(($u['serial_no'] ?: $u['unique_id']) ?: '-') ?></td>
                    <td><?= esc(trim(($u['color'] ?? '') . ' ' . ($u['storage_variant'] ?? '')) ?: '-') ?></td>
                    <td><span class="ms-badge <?= $u['status'] === 'available' ? 'is-ok' : ($u['status'] === 'damaged' ? 'is-danger' : ($u['status'] === 'sold' ? 'is-neutral' : 'is-warn')) ?>"><?= esc(ucwords(str_replace('_', ' ', $u['status']))) ?></span></td>
                    <td><span class="ms-badge is-warn"><?= esc(ucfirst($u['origin_type'])) ?></span><?php if ($u['source_note']): ?><br><span class="ms-source-note"><?= esc($u['source_note']) ?></span><?php endif; ?></td>
                    <td>₹<?= number_format((float) $u['unit_cost'], 2) ?></td>
                    <td><?php if ($canManage): ?><button class="ms-btn ms-btn-secondary is-sm" type="button" data-open-dialog="editUnit<?= (int) $u['id'] ?>">Edit</button><?php else: ?><span class="ms-muted">View only</span><?php endif; ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (! $units): ?><tr class="ms-no-filter"><td colspan="9" class="ms-empty">No serialized units yet.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($canManage): ?>
<?php foreach ($units as $u): ?>
<dialog class="is-wide" id="editUnit<?= (int) $u['id'] ?>">
    <div class="ms-dialog-body">
        <div class="ms-dialog-head">
            <div><h3>Edit inventory unit</h3><span class="ms-muted"><?= esc(trim(($u['brand_name'] ?? '') . ' ' . $u['product_name'] . ' ' . ($u['model'] ?? ''))) ?></span></div>
            <button class="ms-btn ms-btn-secondary is-sm" type="button" data-close-dialog>Close</button>
        </div>
        <form method="post" action="<?= site_url('inventory/units/' . $u['id']) ?>">
            <?= csrf_field() ?>
            <div class="ms-form-grid">
                <div class="ms-field"><label>IMEI 1</label><input class="ms-input" name="imei1" value="<?= esc($u['imei1'] ?? '') ?>" inputmode="numeric"></div>
                <div class="ms-field"><label>IMEI 2</label><input class="ms-input" name="imei2" value="<?= esc($u['imei2'] ?? '') ?>" inputmode="numeric"></div>
                <div class="ms-field"><label>Serial number</label><input class="ms-input" name="serial_no" value="<?= esc($u['serial_no'] ?? '') ?>"></div>
                <div class="ms-field"><label>Unique ID</label><input class="ms-input" name="unique_id" value="<?= esc($u['unique_id'] ?? '') ?>"></div>
                <div class="ms-field"><label>Color</label><input class="ms-input" name="color" value="<?= esc($u['color'] ?? '') ?>"></div>
                <div class="ms-field"><label>Storage / Variant</label><input class="ms-input" name="storage_variant" value="<?= esc($u['storage_variant'] ?? '') ?>"></div>
                <div class="ms-field ms-full">
                    <label>Status</label>
                    <?php if ($u['status'] === 'sold'): ?>
                        <input class="ms-input" value="Sold — locked to invoice history" readonly><input type="hidden" name="status" value="sold">
                        <div class="ms-help">You may correct its identity, but a sold device cannot be manually made available again.</div>
                    <?php else: ?>
                        <select class="ms-select" name="status"><?php foreach (['available','reserved','returned','damaged','borrow_returned'] as $s): ?><option value="<?= $s ?>" <?= $u['status'] === $s ? 'selected' : '' ?>><?= esc(ucwords(str_replace('_', ' ', $s))) ?></option><?php endforeach; ?></select>
                    <?php endif; ?>
                </div>
                <div class="ms-field ms-full"><button class="ms-btn ms-btn-primary" type="submit">Save Inventory Unit</button></div>
            </div>
        </form>
    </div>
</dialog>
<?php endforeach; ?>

<dialog id="adjustDialog">
    <div class="ms-dialog-body">
        <div class="ms-dialog-head"><div><h3>Adjust bulk stock</h3><span class="ms-muted">For non-serialized accessories and items only.</span></div><button class="ms-btn ms-btn-secondary is-sm" type="button" data-close-dialog>Close</button></div>
        <form method="post" action="<?= site_url('inventory/adjust') ?>">
            <?= csrf_field() ?>
            <div class="ms-form-grid">
                <div class="ms-field ms-full"><label>Product *</label><select class="ms-select" name="product_id" required><option value="">Select bulk product</option><?php foreach ($products as $p): if ($p['is_serialized']) continue; ?><option value="<?= $p['id'] ?>"><?= esc(trim(($p['brand_name'] ?? '') . ' ' . $p['name'] . ' ' . ($p['model'] ?? ''))) ?> · <?= number_format((float) $p['stock_qty'], 0) ?> available</option><?php endforeach; ?></select></div>
                <div class="ms-field"><label>Adjustment</label><select class="ms-select" name="direction"><option value="add">Add stock</option><option value="remove">Remove stock</option></select></div>
                <div class="ms-field"><label>Quantity *</label><input class="ms-input" name="qty" type="number" min="0.001" step="0.001" required></div>
                <div class="ms-field"><label>Unit cost (when adding)</label><input class="ms-input" name="unit_cost" type="number" min="0" step="0.01" value="0"></div>
                <div class="ms-field"><label>Reason *</label><input class="ms-input" name="note" required placeholder="Physical count / damaged / opening correction"></div>
                <div class="ms-field ms-full"><button class="ms-btn ms-btn-primary" type="submit">Apply Adjustment</button></div>
            </div>
        </form>
    </div>
</dialog>
<?php endif; ?>

<?= $this->endSection() ?>
