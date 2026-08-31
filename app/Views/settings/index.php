<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$yes = static fn(string $key, int $default = 1): bool => array_key_exists($key, $shop) ? (bool) $shop[$key] : (bool) $default;
$template = $shop['invoice_template'] ?? 'classic';
$addressPosition = $shop['customer_address_position'] ?? 'left';
?>
<div class="ms-page-head">
    <div>
        <h1>Shop Settings & Invoice Designer</h1>
        <p>Manage the single shop identity and choose how customer invoices look. These are defaults; every sale can override GST, discount, address placement and invoice display options.</p>
    </div>
</div>

<form method="post" action="<?= site_url('settings') ?>" enctype="multipart/form-data" id="invoiceSettingsForm">
<?= csrf_field() ?>

<div class="ms-settings-grid">
    <section class="ms-card">
        <div class="ms-section-head"><div><h2>Company details</h2><p>Printed at the top of the invoice according to the selected layout.</p></div></div>
        <div class="ms-form-grid">
            <div class="ms-field"><label>Company / Shop Name *</label><input class="ms-input" name="name" required value="<?= esc($shop['name'] ?? '') ?>"></div>
            <div class="ms-field"><label>Company Phone</label><input class="ms-input" name="phone" value="<?= esc($shop['phone'] ?? '') ?>"></div>
            <div class="ms-field"><label>Company Email</label><input class="ms-input" type="email" name="email" value="<?= esc($shop['email'] ?? '') ?>"></div>
            <div class="ms-field"><label>GSTIN</label><input class="ms-input" name="gstin" value="<?= esc($shop['gstin'] ?? '') ?>" placeholder="Optional"></div>
            <div class="ms-field ms-full"><label>Company Address</label><textarea class="ms-textarea" name="address" rows="3" placeholder="Full billing address shown on invoice"><?= esc($shop['address'] ?? '') ?></textarea></div>
        </div>
    </section>

    <section class="ms-card">
        <div class="ms-section-head"><div><h2>Brand assets</h2><p>PNG, JPG or WebP, maximum 2 MB each. Images are converted to Base64 and stored directly in the database.</p></div></div>
        <div class="ms-upload-grid">
            <div class="ms-upload-card">
                <div class="ms-upload-preview">
                    <?php if (!empty($shop['logo_base64'])): ?><img src="data:<?= esc($shop['logo_mime'] ?? 'image/png') ?>;base64,<?= esc($shop['logo_base64']) ?>" alt="Current company logo"><?php else: ?><span>LOGO</span><?php endif; ?>
                </div>
                <div class="ms-field"><label>Company Logo</label><input class="ms-file" type="file" name="logo" accept="image/png,image/jpeg,image/webp"></div>
                <?php if (!empty($shop['logo_base64'])): ?><label class="ms-check"><input type="checkbox" name="remove_logo" value="1"><span>Remove current logo</span></label><?php endif; ?>
            </div>
            <div class="ms-upload-card">
                <div class="ms-upload-preview is-signature">
                    <?php if (!empty($shop['signature_base64'])): ?><img src="data:<?= esc($shop['signature_mime'] ?? 'image/png') ?>;base64,<?= esc($shop['signature_base64']) ?>" alt="Current signature"><?php else: ?><span>SIGNATURE</span><?php endif; ?>
                </div>
                <div class="ms-field"><label>Authorized Signature</label><input class="ms-file" type="file" name="signature" accept="image/png,image/jpeg,image/webp"></div>
                <?php if (!empty($shop['signature_base64'])): ?><label class="ms-check"><input type="checkbox" name="remove_signature" value="1"><span>Remove current signature</span></label><?php endif; ?>
            </div>
        </div>
    </section>
</div>

<section class="ms-card ms-spacer-top">
    <div class="ms-section-head">
        <div><h2>Invoice designer</h2><p>Choose one of 11 built-in invoice styles. The selected template becomes the default for new invoices.</p></div>
        <span class="ms-badge is-neutral">11 templates</span>
    </div>
    <div class="ms-invoice-template-grid">
        <?php $n=0; foreach ($invoiceTemplates as $key => $label): $n++; ?>
        <label class="ms-template-option <?= $template === $key ? 'is-selected' : '' ?>" data-invoice-template-card>
            <input type="radio" name="invoice_template" value="<?= esc($key) ?>" <?= $template === $key ? 'checked' : '' ?>>
            <span class="ms-template-preview invoice-theme-<?= esc($key) ?>">
                <span class="tp-head"><i></i><b><?= esc($label) ?></b></span>
                <span class="tp-info"><i></i><i></i></span>
                <span class="tp-table"><i></i><i></i><i></i></span>
                <span class="tp-total"><i></i><i></i></span>
            </span>
            <span class="ms-template-name"><strong><?= $n ?>. <?= esc($label) ?></strong><small><?= esc(ucwords(str_replace('_',' ', $key))) ?> invoice</small></span>
        </label>
        <?php endforeach; ?>
    </div>
</section>

<div class="ms-settings-grid ms-spacer-top">
    <section class="ms-card">
        <div class="ms-section-head"><div><h2>Invoice content defaults</h2><p>Control what is shown. These can be overridden while creating a sale.</p></div></div>
        <div class="ms-form-grid">
            <div class="ms-field"><label>Invoice heading</label><input class="ms-input" name="invoice_title" value="<?= esc($shop['invoice_title'] ?? 'TAX INVOICE') ?>" placeholder="TAX INVOICE"></div>
            <div class="ms-field"><label>Customer address placement</label><select class="ms-select" name="customer_address_position">
                <option value="left" <?= $addressPosition==='left'?'selected':'' ?>>Left under company header</option>
                <option value="right" <?= $addressPosition==='right'?'selected':'' ?>>Right under invoice details</option>
                <option value="full" <?= $addressPosition==='full'?'selected':'' ?>>Full-width billing block</option>
                <option value="hidden" <?= $addressPosition==='hidden'?'selected':'' ?>>Do not show customer address by default</option>
            </select><div class="ms-help">This answers where the customer/user address appears on the invoice.</div></div>
            <div class="ms-field"><label>Invoice accent color</label><input class="ms-input" type="color" name="invoice_color" value="<?= esc($shop['invoice_color'] ?? '#e87523') ?>"></div>
            <div class="ms-field"><label>Invoice Prefix</label><input class="ms-input" name="invoice_prefix" value="<?= esc($shop['invoice_prefix'] ?? 'INV') ?>"></div>
            <div class="ms-field"><label>Purchase Prefix</label><input class="ms-input" name="purchase_prefix" value="<?= esc($shop['purchase_prefix'] ?? 'PUR') ?>"></div>
            <div class="ms-field"><label>Currency</label><input class="ms-input" name="currency" value="<?= esc($shop['currency'] ?? 'INR') ?>"></div>
            <div class="ms-field ms-full"><label>Terms & Conditions</label><textarea class="ms-textarea" name="invoice_terms" rows="4" placeholder="Warranty, returns, payment terms, etc."><?= esc($shop['invoice_terms'] ?? '') ?></textarea></div>
            <div class="ms-field ms-full"><label>Invoice footer text</label><input class="ms-input" name="invoice_footer" value="<?= esc($shop['invoice_footer'] ?? '') ?>" placeholder="Thank you for your business"></div>
        </div>
    </section>

    <section class="ms-card">
        <div class="ms-section-head"><div><h2>Show / hide defaults</h2><p>Use these defaults for new invoices.</p></div></div>
        <div class="ms-toggle-list">
            <label class="ms-switch"><input type="checkbox" name="invoice_default_gst_enabled" value="1" <?= $yes('invoice_default_gst_enabled')?'checked':'' ?>><span></span><b>Enable GST on new invoices</b></label>
            <div class="ms-field"><label>Default GST price mode</label><select class="ms-select" name="invoice_default_gst_mode"><option value="inclusive" <?= ($shop['invoice_default_gst_mode'] ?? 'inclusive')==='inclusive'?'selected':'' ?>>Price includes GST</option><option value="exclusive" <?= ($shop['invoice_default_gst_mode'] ?? 'inclusive')==='exclusive'?'selected':'' ?>>GST added to price</option></select></div>
            <label class="ms-switch"><input type="checkbox" name="invoice_default_discount_enabled" value="1" <?= $yes('invoice_default_discount_enabled')?'checked':'' ?>><span></span><b>Enable discount controls</b></label>
            <label class="ms-switch"><input type="checkbox" name="invoice_show_logo" value="1" <?= $yes('invoice_show_logo')?'checked':'' ?>><span></span><b>Show company logo</b></label>
            <label class="ms-switch"><input type="checkbox" name="invoice_show_signature" value="1" <?= $yes('invoice_show_signature')?'checked':'' ?>><span></span><b>Show authorized signature</b></label>
            <label class="ms-switch"><input type="checkbox" name="invoice_show_company_phone" value="1" <?= $yes('invoice_show_company_phone')?'checked':'' ?>><span></span><b>Show company phone</b></label>
            <label class="ms-switch"><input type="checkbox" name="invoice_show_company_email" value="1" <?= $yes('invoice_show_company_email')?'checked':'' ?>><span></span><b>Show company email</b></label>
            <label class="ms-switch"><input type="checkbox" name="invoice_show_company_address" value="1" <?= $yes('invoice_show_company_address')?'checked':'' ?>><span></span><b>Show company address</b></label>
            <label class="ms-switch"><input type="checkbox" name="invoice_show_customer_address" value="1" <?= $yes('invoice_show_customer_address')?'checked':'' ?>><span></span><b>Show customer address</b></label>
            <label class="ms-switch"><input type="checkbox" name="invoice_show_customer_gstin" value="1" <?= $yes('invoice_show_customer_gstin')?'checked':'' ?>><span></span><b>Show customer GSTIN</b></label>
            <label class="ms-switch"><input type="checkbox" name="invoice_show_imei" value="1" <?= $yes('invoice_show_imei')?'checked':'' ?>><span></span><b>Show IMEI / Serial / Unique ID</b></label>
            <label class="ms-switch"><input type="checkbox" name="invoice_show_hsn" value="1" <?= $yes('invoice_show_hsn')?'checked':'' ?>><span></span><b>Show HSN/SAC</b></label>
            <label class="ms-switch"><input type="checkbox" name="invoice_show_item_discount" value="1" <?= $yes('invoice_show_item_discount')?'checked':'' ?>><span></span><b>Show item discount column</b></label>
        </div>
    </section>
</div>

<section class="ms-card ms-spacer-top">
    <div class="ms-section-head"><div><h2>Email configuration</h2><p>Optional SMTP settings for future invoice/welcome email delivery. The SMTP password is encrypted before it is stored.</p></div></div>
    <div class="ms-form-grid">
        <label class="ms-switch"><input type="checkbox" name="email_enabled" value="1" <?= $yes('email_enabled',0)?'checked':'' ?>><span></span><b>Enable SMTP</b></label>
        <label class="ms-switch"><input type="checkbox" name="email_invoice_enabled" value="1" <?= $yes('email_invoice_enabled',0)?'checked':'' ?>><span></span><b>Enable invoice email</b></label>
        <div class="ms-field"><label>SMTP Host</label><input class="ms-input" name="email_smtp_host" value="<?= esc($shop['email_smtp_host'] ?? '') ?>"></div>
        <div class="ms-field"><label>SMTP Port</label><input class="ms-input" type="number" name="email_smtp_port" value="<?= esc($shop['email_smtp_port'] ?? 587) ?>"></div>
        <div class="ms-field"><label>SMTP Username</label><input class="ms-input" name="email_smtp_user" value="<?= esc($shop['email_smtp_user'] ?? '') ?>"></div>
        <div class="ms-field"><label>SMTP Password</label><input class="ms-input" type="password" name="email_smtp_password" autocomplete="new-password" placeholder="Leave blank to keep existing password"></div>
        <div class="ms-field"><label>Encryption</label><select class="ms-select" name="email_smtp_encryption"><option value="tls" <?= ($shop['email_smtp_encryption'] ?? 'tls')==='tls'?'selected':'' ?>>TLS</option><option value="ssl" <?= ($shop['email_smtp_encryption'] ?? '')==='ssl'?'selected':'' ?>>SSL</option><option value="none" <?= ($shop['email_smtp_encryption'] ?? '')==='none'?'selected':'' ?>>None</option></select></div>
        <div class="ms-field"><label>From Email</label><input class="ms-input" type="email" name="email_from_address" value="<?= esc($shop['email_from_address'] ?? '') ?>"></div>
        <div class="ms-field"><label>From Name</label><input class="ms-input" name="email_from_name" value="<?= esc($shop['email_from_name'] ?? ($shop['name'] ?? '')) ?>"></div>
    </div>
</section>

<div class="ms-sticky-save">
    <div><strong>Invoice designer</strong><span>Save company details, template and defaults.</span></div>
    <button class="ms-btn ms-btn-primary" type="submit">Save Settings & Designer</button>
</div>
</form>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.querySelectorAll('[data-invoice-template-card] input[type="radio"]').forEach(function(input){
    input.addEventListener('change', function(){
        document.querySelectorAll('[data-invoice-template-card]').forEach(function(card){ card.classList.remove('is-selected'); });
        input.closest('[data-invoice-template-card]').classList.add('is-selected');
    });
});
</script>
<?= $this->endSection() ?>
