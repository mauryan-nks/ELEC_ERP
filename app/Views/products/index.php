<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<?php $canManage = auth()->user()->can('products.create'); ?>
<div class="ms-page-head">
    <div>
        <h1>Products, categories & brands</h1>
        <p>Create devices and accessories, set pricing/GST, choose bulk or IMEI tracking, and manage opening stock.</p>
    </div>
    <?php if ($canManage): ?><button class="ms-btn ms-btn-primary" type="button" data-open-dialog="productDialog">+ Add Product</button><?php endif; ?>
</div>

<div class="ms-table-tools">
    <div class="ms-search"><input class="ms-input" data-table-filter="#productTable" placeholder="Search product, model, brand or SKU"></div>
    <div class="ms-chip-row">
        <span class="ms-chip"><?= count($products) ?> products</span>
        <span class="ms-chip"><?= count(array_filter($products, static fn($p) => $p['status'] === 'active')) ?> active</span>
    </div>
</div>

<div class="ms-table-scroll">
    <table class="ms-table" id="productTable">
        <thead><tr><th>Product</th><th>Category</th><th>Type</th><th>Tracking</th><th>Stock</th><th>Default price</th><th>Tax</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><strong><?= esc(trim(($p['brand_name'] ?? '') . ' ' . $p['name'])) ?></strong><br><span class="ms-muted"><?= esc($p['model'] ?? '') ?><?= $p['sku'] ? ' · ' . esc($p['sku']) : '' ?></span></td>
                <td><?= esc($p['category_name'] ?? '-') ?></td>
                <td><?= esc(ucfirst($p['product_type'])) ?></td>
                <td><?= $p['is_serialized'] ? '<span class="ms-badge is-ok">' . esc(strtoupper(str_replace('_', ' ', $p['serial_mode']))) . '</span>' : '<span class="ms-badge is-neutral">Bulk qty</span>' ?></td>
                <td><strong><?= number_format((float) $p['stock_qty'], 0) ?></strong></td>
                <td><?= $p['default_sale_price'] !== null ? '₹' . number_format((float) $p['default_sale_price'], 2) : '-' ?></td>
                <td><?= number_format((float) $p['tax_percent'], 2) ?>%</td>
                <td><span class="ms-badge <?= $p['status'] === 'active' ? 'is-ok' : 'is-danger' ?>"><?= esc(ucfirst($p['status'])) ?></span></td>
                <td>
                    <?php if ($canManage): ?>
                    <div class="ms-actions ms-nowrap">
                        <button class="ms-btn ms-btn-secondary is-sm" type="button" data-open-dialog="editProduct<?= (int) $p['id'] ?>">Edit</button>
                        <form method="post" action="<?= site_url('products/' . $p['id'] . '/status') ?>" data-confirm="<?= $p['status'] === 'active' ? 'Deactivate this product?' : 'Activate this product?' ?>">
                            <?= csrf_field() ?><button class="ms-btn ms-btn-ghost is-sm" type="submit"><?= $p['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                    </div>
                    <?php else: ?><span class="ms-muted">View only</span><?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (! $products): ?><tr class="ms-no-filter"><td colspan="9" class="ms-empty">No products yet. Add your first mobile or accessory.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($canManage): ?>
<?php foreach ($products as $p): ?>
<dialog class="is-wide" id="editProduct<?= (int) $p['id'] ?>">
    <div class="ms-dialog-body">
        <div class="ms-dialog-head">
            <div><h3>Edit product</h3><span class="ms-muted"><?= esc(trim(($p['brand_name'] ?? '') . ' ' . $p['name'])) ?></span></div>
            <button class="ms-btn ms-btn-secondary is-sm" type="button" data-close-dialog>Close</button>
        </div>
        <form method="post" action="<?= site_url('products/' . $p['id']) ?>">
            <?= csrf_field() ?>
            <div class="ms-form-grid">
                <div class="ms-field"><label>Product name *</label><input class="ms-input" name="name" required value="<?= esc($p['name']) ?>"></div>
                <div class="ms-field"><label>Model</label><input class="ms-input" name="model" value="<?= esc($p['model'] ?? '') ?>"></div>
                <div class="ms-field"><label>Category</label><select class="ms-select" name="category_id"><option value="">Select category</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= (int) $p['category_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= esc($c['name']) ?></option><?php endforeach; ?></select><input class="ms-input ms-field-gap" name="new_category" placeholder="Or create new category"></div>
                <div class="ms-field"><label>Brand</label><select class="ms-select" name="brand_id"><option value="">Select brand</option><?php foreach ($brands as $b): ?><option value="<?= $b['id'] ?>" <?= (int) $p['brand_id'] === (int) $b['id'] ? 'selected' : '' ?>><?= esc($b['name']) ?></option><?php endforeach; ?></select><input class="ms-input ms-field-gap" name="new_brand" placeholder="Or create new brand"></div>
                <div class="ms-field"><label>Product type</label><select class="ms-select" name="product_type"><?php foreach (['device','accessory','service','other'] as $t): ?><option value="<?= $t ?>" <?= $p['product_type'] === $t ? 'selected' : '' ?>><?= esc(ucfirst($t)) ?></option><?php endforeach; ?></select></div>
                <div class="ms-field"><label>SKU</label><input class="ms-input" name="sku" value="<?= esc($p['sku'] ?? '') ?>"></div>
                <div class="ms-field"><label>HSN/SAC</label><input class="ms-input" name="hsn_sac" value="<?= esc($p['hsn_sac'] ?? '') ?>"></div>
                <div class="ms-field"><label>Default sale price</label><input class="ms-input" type="number" min="0" step="0.01" name="default_sale_price" value="<?= esc($p['default_sale_price'] ?? '') ?>"></div>
                <div class="ms-field"><label>GST/Tax %</label><input class="ms-input" type="number" min="0" step="0.001" name="tax_percent" value="<?= esc($p['tax_percent']) ?>"></div>
                <div class="ms-field"><label>Low-stock alert qty</label><input class="ms-input" type="number" min="0" step="0.001" name="low_stock_qty" value="<?= esc($p['low_stock_qty']) ?>"></div>
                <div class="ms-field"><label>Status</label><select class="ms-select" name="status"><option value="active" <?= $p['status'] === 'active' ? 'selected' : '' ?>>Active</option><option value="inactive" <?= $p['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option></select></div>
                <div class="ms-field">
                    <label>Tracking</label>
                    <label class="ms-checkbox-line"><input type="checkbox" name="is_serialized" value="1" <?= $p['is_serialized'] ? 'checked' : '' ?> data-tracking-checkbox> Track individual units</label>
                    <select class="ms-select ms-field-gap" name="serial_mode" data-tracking-select <?= $p['is_serialized'] ? '' : 'disabled' ?>><?php foreach (['imei','serial','unique_id','mixed'] as $m): ?><option value="<?= $m ?>" <?= $p['serial_mode'] === $m ? 'selected' : '' ?>><?= esc(strtoupper(str_replace('_', ' ', $m))) ?></option><?php endforeach; ?></select>
                </div>
                <div class="ms-field ms-full"><div class="ms-help">Tracking mode cannot be changed after stock is received. That protects IMEI and invoice history.</div></div>
                <div class="ms-field ms-full"><button class="ms-btn ms-btn-primary" type="submit">Save Changes</button></div>
            </div>
        </form>
    </div>
</dialog>
<?php endforeach; ?>

<dialog class="is-wide" id="productDialog">
    <div class="ms-dialog-body">
        <div class="ms-dialog-head"><div><h3>Add product</h3><span class="ms-muted">You can add existing opening stock in the same form.</span></div><button class="ms-btn ms-btn-secondary is-sm" data-close-dialog type="button">Close</button></div>
        <form method="post" action="<?= site_url('products') ?>">
            <?= csrf_field() ?>
            <div class="ms-form-grid">
                <div class="ms-field"><label>Product name *</label><input class="ms-input" name="name" required placeholder="Galaxy S26"></div>
                <div class="ms-field"><label>Model</label><input class="ms-input" name="model" placeholder="SM-S942"></div>
                <div class="ms-field"><label>Category</label><select class="ms-select" name="category_id"><option value="">Select category</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= esc($c['name']) ?></option><?php endforeach; ?></select><input class="ms-input ms-field-gap" name="new_category" placeholder="Or create new category"></div>
                <div class="ms-field"><label>Brand</label><select class="ms-select" name="brand_id"><option value="">Select brand</option><?php foreach ($brands as $b): ?><option value="<?= $b['id'] ?>"><?= esc($b['name']) ?></option><?php endforeach; ?></select><input class="ms-input ms-field-gap" name="new_brand" placeholder="Or create new brand"></div>
                <div class="ms-field"><label>Product type</label><select class="ms-select" name="product_type"><option value="device">Device</option><option value="accessory">Accessory</option><option value="service">Service</option><option value="other">Other</option></select></div>
                <div class="ms-field"><label>SKU</label><input class="ms-input" name="sku"></div>
                <div class="ms-field"><label>HSN/SAC</label><input class="ms-input" name="hsn_sac"></div>
                <div class="ms-field"><label>Default sale price</label><input class="ms-input" type="number" min="0" step="0.01" name="default_sale_price"></div>
                <div class="ms-field"><label>GST/Tax %</label><input class="ms-input" type="number" min="0" step="0.001" name="tax_percent" value="0"></div>
                <div class="ms-field"><label>Low-stock alert qty</label><input class="ms-input" type="number" min="0" step="0.001" name="low_stock_qty" value="0"></div>
                <div class="ms-field"><label>Opening available quantity</label><input class="ms-input" id="openingQty" type="number" min="0" step="1" name="opening_qty" value="0"><div class="ms-help">Optional stock already physically present in your shop.</div></div>
                <div class="ms-field"><label>Opening unit cost</label><input class="ms-input" type="number" min="0" step="0.01" name="opening_unit_cost" value="0"></div>
                <div class="ms-field ms-full">
                    <label class="ms-checkbox-line"><input id="openingSerialized" type="checkbox" name="is_serialized" value="1"> Track each physical unit by IMEI/serial/unique ID</label>
                    <select class="ms-select ms-field-gap" id="serialMode" name="serial_mode" disabled><option value="imei">IMEI</option><option value="serial">Serial number</option><option value="unique_id">Unique ID</option><option value="mixed">Mixed / any identifier</option></select>
                </div>
                <div class="ms-field ms-full" id="openingUnitRows"></div>
                <div class="ms-field ms-full"><button class="ms-btn ms-btn-primary" type="submit">Save Product</button></div>
            </div>
        </form>
    </div>
</dialog>
<?php endif; ?>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
(function(){
    function renderOpeningUnits(){
        const box=document.getElementById('openingUnitRows');
        const tracked=document.getElementById('openingSerialized');
        const qtyInput=document.getElementById('openingQty');
        if(!box||!tracked||!qtyInput)return;
        box.replaceChildren();
        if(!tracked.checked)return;
        const qty=Math.max(0,parseInt(qtyInput.value||'0',10));
        if(!qty)return;
        const wrap=document.createElement('div');
        wrap.className='ms-internal';
        const title=document.createElement('strong'); title.textContent='Opening stock identities'; wrap.appendChild(title);
        const help=document.createElement('div'); help.className='ms-help'; help.textContent='Enter at least one identity for every unit. Dual-SIM devices may use both IMEI fields.'; wrap.appendChild(help);
        for(let i=0;i<qty;i++){
            const row=document.createElement('div'); row.className='serial-row';
            [['imei1','IMEI 1'],['imei2','IMEI 2'],['serial_no','Serial no.'],['unique_id','Unique ID'],['color','Color'],['storage_variant','Variant / storage']].forEach(([key,label])=>{
                const input=document.createElement('input'); input.className='ms-input'; input.name=`opening_units[${i}][${key}]`; input.placeholder=label; row.appendChild(input);
            });
            wrap.appendChild(row);
        }
        box.appendChild(wrap);
    }
    const track=document.getElementById('openingSerialized');
    const qty=document.getElementById('openingQty');
    const mode=document.getElementById('serialMode');
    track?.addEventListener('change',()=>{ mode.disabled=!track.checked; qty.step=track.checked?'1':'0.001'; renderOpeningUnits(); });
    qty?.addEventListener('input',renderOpeningUnits);
    document.querySelectorAll('[data-tracking-checkbox]').forEach(cb=>cb.addEventListener('change',()=>{const field=cb.closest('.ms-field');const select=field?.querySelector('[data-tracking-select]');if(select)select.disabled=!cb.checked;}));
})();
</script>
<?= $this->endSection() ?>
