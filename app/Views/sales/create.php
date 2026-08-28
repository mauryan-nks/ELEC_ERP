<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$defaultBool = static fn(string $key, int $fallback=1): bool => array_key_exists($key,$shop) ? (bool)$shop[$key] : (bool)$fallback;
$defaultTemplate=$shop['invoice_template']??'classic';
$defaultAddressPosition=$shop['customer_address_position']??'left';
?>
<div class="ms-page-head">
    <div><h1>New sale / invoice</h1><p>Create the invoice with exact stock/IMEI selection. GST, discounts and invoice appearance can be controlled separately for this sale.</p></div>
    <a class="ms-btn ms-btn-secondary" href="<?= site_url('sales') ?>">Sales history</a>
</div>

<form method="post" action="<?= site_url('sales') ?>" id="saleForm">
<?= csrf_field() ?>
<div class="ms-card">
    <div class="ms-section-head"><div><h2>Customer & payment</h2><p>Customer address is taken from the customer record and can be placed left, right, full-width or hidden on this invoice.</p></div></div>
    <div class="ms-form-grid">
        <div class="ms-field"><label>Customer *</label><div class="ms-actions ms-nowrap"><select class="ms-select" id="customerSelect" name="customer_id"><option value="">Select customer</option><?php foreach($customers as $c): ?><option value="<?= $c['id'] ?>"><?= esc($c['name'].' · '.$c['phone']) ?></option><?php endforeach; ?></select><button type="button" class="ms-btn ms-btn-secondary" data-open-dialog="customerDialog">+ Add</button></div><div class="ms-help">Add a missing customer without leaving this invoice.</div></div>
        <div class="ms-field"><label>Sale type</label><select class="ms-select" name="sale_type"><option value="invoice">Invoice</option><option value="cash_memo">Cash memo</option></select></div>
        <div class="ms-field"><label>Paid now</label><input class="ms-input" name="paid_amount" type="number" min="0" step="0.01" value="0"></div>
        <div class="ms-field"><label>Payment method</label><select class="ms-select" name="payment_method"><option value="cash">Cash</option><option value="upi">UPI</option><option value="card">Card</option><option value="bank">Bank</option><option value="credit">Credit</option><option value="other">Other</option></select></div>
        <div class="ms-field"><label>Payment reference</label><input class="ms-input" name="payment_reference"></div>
        <div class="ms-field"><label>Payment due date</label><input class="ms-input" type="date" name="due_date"></div>
        <div class="ms-field ms-full"><label>Invoice note (customer can see)</label><input class="ms-input" name="notes" placeholder="Optional note printed on invoice"></div>
        <div class="ms-field ms-full"><label>Internal note (never printed)</label><textarea class="ms-textarea" name="internal_notes" placeholder="Staff-only note"></textarea></div>
    </div>
</div>

<div class="ms-card ms-spacer-top">
    <div class="ms-section-head"><div><h2>Tax, discount & invoice options</h2><p>These settings apply only to this invoice and override the defaults from Shop Settings.</p></div><a class="ms-btn ms-btn-secondary is-sm" href="<?= site_url('settings') ?>">Invoice Designer</a></div>
    <div class="ms-invoice-controls">
        <div class="ms-toggle-block">
            <input type="hidden" name="gst_enabled" value="0"><label class="ms-switch"><input id="gstEnabled" type="checkbox" name="gst_enabled" value="1" <?= $defaultBool('invoice_default_gst_enabled')?'checked':'' ?>><span></span><b>Apply GST</b></label>
            <p>When disabled, tax is forced to 0 on the server even if a product has a GST rate.</p>
        </div>
        <div class="ms-toggle-block">
            <input type="hidden" name="discount_enabled" value="0"><label class="ms-switch"><input id="discountEnabled" type="checkbox" name="discount_enabled" value="1" <?= $defaultBool('invoice_default_discount_enabled')?'checked':'' ?>><span></span><b>Allow discounts</b></label>
            <p>Supports line-item discount plus an optional invoice-level amount or percentage.</p>
        </div>
    </div>
    <div class="ms-form-grid ms-spacer-top">
        <div class="ms-field"><label>Invoice template</label><select class="ms-select" name="invoice_template"><?php foreach($invoiceTemplates as $key=>$label): ?><option value="<?= esc($key) ?>" <?= $defaultTemplate===$key?'selected':'' ?>><?= esc($label) ?></option><?php endforeach; ?></select></div>
        <div class="ms-field"><label>Invoice heading</label><input class="ms-input" name="invoice_title" value="<?= esc($shop['invoice_title']??'TAX INVOICE') ?>"></div>
        <div class="ms-field"><label>Customer address placement</label><select class="ms-select" name="customer_address_position"><option value="left" <?= $defaultAddressPosition==='left'?'selected':'' ?>>Left</option><option value="right" <?= $defaultAddressPosition==='right'?'selected':'' ?>>Right</option><option value="full" <?= $defaultAddressPosition==='full'?'selected':'' ?>>Full width</option><option value="hidden" <?= $defaultAddressPosition==='hidden'?'selected':'' ?>>Hidden</option></select></div>
        <div class="ms-field invoice-discount-control"><label>Invoice discount type</label><select class="ms-select" id="overallDiscountType" name="overall_discount_type"><option value="none">No invoice-level discount</option><option value="amount">Fixed amount</option><option value="percent">Percentage</option></select></div>
        <div class="ms-field invoice-discount-control"><label>Invoice discount value</label><input class="ms-input" id="overallDiscountValue" name="overall_discount_value" type="number" min="0" step="0.01" value="0"><div class="ms-help">Percentage is capped at 100%. Fixed discount cannot exceed the taxable item value.</div></div>
        <div class="ms-field ms-full"><details class="ms-option-details"><summary>Invoice display options</summary><div class="ms-toggle-grid">
            <?php
            $toggles=[
                'show_logo'=>['Show company logo','invoice_show_logo'], 'show_signature'=>['Show signature','invoice_show_signature'],
                'show_company_phone'=>['Show company phone','invoice_show_company_phone'], 'show_company_email'=>['Show company email','invoice_show_company_email'],
                'show_company_address'=>['Show company address','invoice_show_company_address'], 'show_customer_address'=>['Show customer address','invoice_show_customer_address'],
                'show_customer_gstin'=>['Show customer GSTIN','invoice_show_customer_gstin'], 'show_imei'=>['Show IMEI / Serial','invoice_show_imei'],
                'show_hsn'=>['Show HSN / SAC','invoice_show_hsn'], 'show_item_discount'=>['Show item discount column','invoice_show_item_discount'],
            ];
            foreach($toggles as $postName=>[$label,$settingKey]): ?>
                <div><input type="hidden" name="<?= esc($postName) ?>" value="0"><label class="ms-check"><input type="checkbox" name="<?= esc($postName) ?>" value="1" <?= $defaultBool($settingKey)?'checked':'' ?>><span><?= esc($label) ?></span></label></div>
            <?php endforeach; ?>
        </div></details></div>
    </div>
</div>

<div class="ms-page-head ms-spacer-top"><div><h2 class="ms-section-title">Items</h2><p>For tracked stock select the exact IMEI/serial being sold.</p></div><button class="ms-btn ms-btn-secondary" type="button" onclick="addSaleRow()">+ Add item</button></div>
<div id="saleRows"></div>

<div class="ms-sale-footer-grid">
    <div class="ms-card ms-sale-summary">
        <h2 class="ms-section-title">Invoice summary</h2>
        <div><span>Subtotal</span><strong id="saleSubtotalPreview">₹0.00</strong></div>
        <div><span>Line discounts</span><strong id="saleLineDiscountPreview">− ₹0.00</strong></div>
        <div><span>Invoice discount</span><strong id="saleOverallDiscountPreview">− ₹0.00</strong></div>
        <div><span>GST</span><strong id="saleTaxPreview">₹0.00</strong></div>
        <div class="is-grand"><span>Estimated total</span><strong id="saleGrandPreview">₹0.00</strong></div>
        <div class="ms-help">Final totals are recalculated and validated on the server when the sale is saved.</div>
    </div>
    <div class="ms-card ms-complete-sale"><div><strong>Ready to invoice?</strong><p class="ms-muted">Stock source and acquisition cost remain internal and never appear on the invoice.</p></div><button class="ms-btn ms-btn-primary" type="submit">Complete sale & create invoice</button></div>
</div>
</form>

<dialog id="customerDialog"><div class="ms-dialog-body"><div class="ms-dialog-head"><h3>Add customer during sale</h3><button type="button" class="ms-btn ms-btn-secondary is-sm" data-close-dialog>Close</button></div><form id="quickCustomer"><div class="ms-form-grid"><div class="ms-field"><label>Name *</label><input class="ms-input" name="name" required></div><div class="ms-field"><label>Phone *</label><input class="ms-input" name="phone" required></div><div class="ms-field"><label>WhatsApp</label><input class="ms-input" name="whatsapp_phone"></div><div class="ms-field"><label>Email</label><input class="ms-input" type="email" name="email"></div><div class="ms-field"><label>GSTIN</label><input class="ms-input" name="gstin"></div><div class="ms-field ms-full"><label>Billing address</label><textarea class="ms-textarea" name="address"></textarea></div><div class="ms-field ms-full"><button class="ms-btn ms-btn-primary">Add customer</button></div></div></form></div></dialog>

<template id="saleRowTemplate"><div class="ms-item-card sale-row"><div class="ms-item-grid"><div class="ms-field"><label>Product *</label><select class="ms-select product-select" required><option value="">Select product</option><?php foreach($products as $p): ?><option value="<?= $p['id'] ?>" data-serialized="<?= (int)$p['is_serialized'] ?>" data-price="<?= esc($p['default_sale_price']??0) ?>" data-tax="<?= esc($p['tax_percent']) ?>" data-stock="<?= esc($p['stock_qty']) ?>"><?= esc(trim(($p['brand_name']??'').' '.$p['name'].' '.($p['model']??''))) ?> · stock <?= number_format((float)$p['stock_qty'],0) ?></option><?php endforeach; ?></select></div><div class="ms-field"><label>Qty</label><input class="ms-input qty" type="number" min="1" step="1" value="1"></div><div class="ms-field"><label>Sale price</label><input class="ms-input price" type="number" min="0" step="0.01"></div><div class="ms-field tax-field"><label>GST %</label><input class="ms-input tax" type="number" min="0" step="0.001"></div><button class="ms-btn is-danger is-sm remove-row" type="button">Remove</button></div><div class="ms-form-grid ms-spacer-top"><div class="ms-field unit-field" hidden><label>Exact IMEI / serialized unit *</label><select class="ms-select unit-select"><option value="">Choose available unit</option></select></div><div class="ms-field line-discount-field"><label>Line discount amount</label><input class="ms-input discount" type="number" min="0" step="0.01" value="0"></div></div><details class="ms-internal"><summary>Internal stock source — never shown on invoice</summary><div class="ms-internal-grid"><div class="ms-field"><label>Source</label><select class="ms-select source"><option value="stock">Existing stock</option><option value="borrowed">Borrowed from another store</option><option value="expense">Bought/paid as expense</option><option value="direct">Direct/manual stock</option></select></div><div class="ms-field"><label>Internal acquisition cost</label><input class="ms-input internal-cost" type="number" min="0" step="0.01" value="0"></div><div class="ms-field"><label>Source note / store name</label><input class="ms-input source-note-input" placeholder="e.g. borrowed from ABC Mobile"></div><div class="ms-field borrowed-source" hidden><label>Known borrowed store</label><select class="ms-select borrowed-supplier"><option value="">Use source note</option><?php foreach($borrowSources as $s): ?><option value="<?= $s['id'] ?>"><?= esc($s['name']) ?></option><?php endforeach; ?></select></div><div class="ms-field direct-id" hidden><label>IMEI 1</label><input class="ms-input imei1"></div><div class="ms-field direct-id" hidden><label>IMEI 2</label><input class="ms-input imei2"></div><div class="ms-field direct-id" hidden><label>Serial no.</label><input class="ms-input serial-no"></div><div class="ms-field direct-id" hidden><label>Unique ID</label><input class="ms-input unique-id"></div></div></details></div></template>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?><script>
let saleIndex=0; const csrfName='<?= csrf_token() ?>'; let csrfHash='<?= csrf_hash() ?>';
const gstEnabled=document.getElementById('gstEnabled'),discountEnabled=document.getElementById('discountEnabled');
function addSaleRow(){const node=document.getElementById('saleRowTemplate').content.firstElementChild.cloneNode(true),i=saleIndex++;const qs=s=>node.querySelector(s);const product=qs('.product-select'),qty=qs('.qty'),price=qs('.price'),tax=qs('.tax'),discount=qs('.discount'),source=qs('.source'),unit=qs('.unit-select');product.name=`items[${i}][product_id]`;qty.name=`items[${i}][qty]`;price.name=`items[${i}][unit_price]`;tax.name=`items[${i}][tax_percent]`;discount.name=`items[${i}][discount_amount]`;source.name=`items[${i}][source_type]`;unit.name=`items[${i}][inventory_unit_id]`;qs('.internal-cost').name=`items[${i}][internal_cost]`;qs('.source-note-input').name=`items[${i}][source_note]`;qs('.borrowed-supplier').name=`items[${i}][borrowed_supplier_id]`;qs('.imei1').name=`items[${i}][units][0][imei1]`;qs('.imei2').name=`items[${i}][units][0][imei2]`;qs('.serial-no').name=`items[${i}][units][0][serial_no]`;qs('.unique-id').name=`items[${i}][units][0][unique_id]`;product.onchange=async()=>{const o=product.selectedOptions[0];price.value=o?.dataset.price||0;tax.value=o?.dataset.tax||0;await refreshUnitUI(node);updateInvoicePreview()};source.onchange=()=>refreshUnitUI(node);qs('.remove-row').onclick=()=>{node.remove();updateInvoicePreview()};node.querySelectorAll('input,select').forEach(el=>el.addEventListener('input',updateInvoicePreview));document.getElementById('saleRows').appendChild(node);syncTaxDiscountUI();updateInvoicePreview()}
async function refreshUnitUI(row){const product=row.querySelector('.product-select'),source=row.querySelector('.source'),unitField=row.querySelector('.unit-field'),unit=row.querySelector('.unit-select'),qty=row.querySelector('.qty'),isSerialized=product.selectedOptions[0]?.dataset.serialized==='1',existing=source.value==='stock';row.querySelector('.borrowed-source').hidden=source.value!=='borrowed';row.querySelectorAll('.direct-id').forEach(el=>{el.hidden=!(isSerialized&&!existing)});if(isSerialized){qty.value=1;qty.readOnly=true}else qty.readOnly=false;if(isSerialized&&existing&&product.value){unitField.hidden=false;unit.innerHTML='<option value="">Loading...</option>';try{const r=await fetch(`<?= site_url('inventory/available') ?>/${product.value}`);const j=await r.json();unit.innerHTML='<option value="">Choose available unit</option>';(j.units||[]).forEach(u=>unit.add(new Option(`${[u.imei1,u.imei2,u.serial_no,u.unique_id].filter(Boolean).join(' / ')} · ${u.color||''} ${u.storage_variant||''}`,u.id)))}catch(e){unit.innerHTML='<option value="">Could not load units</option>'}}else{unitField.hidden=true;unit.innerHTML='<option value="">Choose available unit</option>'}}
function syncTaxDiscountUI(){document.querySelectorAll('.tax-field').forEach(x=>x.hidden=!gstEnabled.checked);document.querySelectorAll('.line-discount-field,.invoice-discount-control').forEach(x=>x.hidden=!discountEnabled.checked);if(!discountEnabled.checked){document.querySelectorAll('.discount').forEach(x=>x.value=0);document.getElementById('overallDiscountType').value='none';document.getElementById('overallDiscountValue').value=0}updateInvoicePreview()}
function num(v){const n=parseFloat(v);return Number.isFinite(n)?n:0}
function money(v){return '₹'+Math.max(0,v).toFixed(2)}
function updateInvoicePreview(){let subtotal=0,lineDiscount=0,net=0;const rows=[...document.querySelectorAll('.sale-row')];rows.forEach(row=>{const q=num(row.querySelector('.qty')?.value),p=num(row.querySelector('.price')?.value),base=q*p,disc=discountEnabled.checked?Math.min(Math.max(0,num(row.querySelector('.discount')?.value)),base):0;subtotal+=base;lineDiscount+=disc;net+=Math.max(0,base-disc)});let overall=0;const type=document.getElementById('overallDiscountType').value,val=Math.max(0,num(document.getElementById('overallDiscountValue').value));if(discountEnabled.checked&&type==='amount')overall=Math.min(val,net);if(discountEnabled.checked&&type==='percent')overall=Math.min(100,val)*net/100;const ratio=net>0?overall/net:0;let tax=0;rows.forEach(row=>{const q=num(row.querySelector('.qty')?.value),p=num(row.querySelector('.price')?.value),base=q*p,disc=discountEnabled.checked?Math.min(Math.max(0,num(row.querySelector('.discount')?.value)),base):0,taxRate=gstEnabled.checked?Math.max(0,num(row.querySelector('.tax')?.value)):0,taxable=Math.max(0,(base-disc)*(1-ratio));tax+=taxable*taxRate/100});const grand=subtotal-lineDiscount-overall+tax;document.getElementById('saleSubtotalPreview').textContent=money(subtotal);document.getElementById('saleLineDiscountPreview').textContent='− '+money(lineDiscount);document.getElementById('saleOverallDiscountPreview').textContent='− '+money(overall);document.getElementById('saleTaxPreview').textContent=money(tax);document.getElementById('saleGrandPreview').textContent=money(grand)}
gstEnabled.addEventListener('change',syncTaxDiscountUI);discountEnabled.addEventListener('change',syncTaxDiscountUI);document.getElementById('overallDiscountType').addEventListener('change',updateInvoicePreview);document.getElementById('overallDiscountValue').addEventListener('input',updateInvoicePreview);
document.getElementById('quickCustomer').addEventListener('submit',async e=>{e.preventDefault();const fd=new FormData(e.target);fd.append(csrfName,csrfHash);const r=await fetch('<?= site_url('customers/quick') ?>',{method:'POST',body:fd});const j=await r.json();if(j.csrfHash){csrfHash=j.csrfHash;document.querySelectorAll(`input[name="${csrfName}"]`).forEach(x=>x.value=csrfHash)}if(!j.ok){shopToast(j.error||'Unable to add customer','error');return}const o=new Option(`${j.customer.name} · ${j.customer.phone}`,j.customer.id,true,true);document.getElementById('customerSelect').add(o);document.getElementById('customerDialog').close();e.target.reset();shopToast('Customer added')});
addSaleRow();syncTaxDiscountUI();
</script><?= $this->endSection() ?>
