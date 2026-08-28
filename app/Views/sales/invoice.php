<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php
$cfg=$invoiceConfig??[];
$template=in_array($cfg['template']??'classic',['classic','modern','minimal','compact','executive','retail','bold','bordered','elegant','thermal'],true)?$cfg['template']:'classic';
$gstEnabled=(bool)($cfg['gst_enabled']??false);
$showLogo=(bool)($cfg['show_logo']??true) && !empty($shop['logo_path']);
$showSignature=(bool)($cfg['show_signature']??true) && !empty($shop['signature_path']);
$showCompanyPhone=(bool)($cfg['show_company_phone']??true) && !empty($shop['phone']);
$showCompanyEmail=(bool)($cfg['show_company_email']??true) && !empty($shop['email']);
$showCompanyAddress=(bool)($cfg['show_company_address']??true) && !empty($shop['address']);
$showCustomerAddress=(bool)($cfg['show_customer_address']??true) && !empty($sale['customer_address']);
$showCustomerGstin=$gstEnabled && (bool)($cfg['show_customer_gstin']??true) && !empty($sale['customer_gstin']);
$showImei=(bool)($cfg['show_imei']??true);
$showHsn=(bool)($cfg['show_hsn']??true);
$showItemDiscount=(bool)($cfg['show_item_discount']??true);
$addressPosition=$cfg['customer_address_position']??'left';
if(!in_array($addressPosition,['left','right','full','hidden'],true))$addressPosition='left';
if($addressPosition==='hidden')$showCustomerAddress=false;
$currency=$shop['currency']??'INR';
$moneyPrefix=$currency==='INR'?'₹':esc($currency).' ';
$lineDiscount=(float)($sale['line_discount_total']??$sale['discount_total']??0);
$overallDiscount=(float)($sale['overall_discount_amount']??0);
?>
<div class="ms-no-print ms-page-head"><div><h1><?= esc($sale['invoice_no']) ?></h1><p><?= esc($cfg['title']??'Invoice') ?> · <?= esc(ucfirst($template)) ?> template · <?= $gstEnabled?'GST enabled':'GST not applied' ?></p></div><div class="ms-actions"><a class="ms-btn ms-btn-secondary" href="<?= site_url('sales') ?>">Back</a><?php if((float)$sale['due_amount']>0): ?><button class="ms-btn ms-btn-secondary" type="button" data-open-dialog="paymentDialog">+ Record Payment</button><?php endif; ?><button class="ms-btn ms-btn-primary" type="button" onclick="window.print()">Print / Save PDF</button></div></div>

<article class="ms-invoice invoice-theme-<?= esc($template) ?>" id="printInvoice">
    <header class="inv-header">
        <div class="inv-company">
            <?php if($showLogo): ?><div class="inv-logo"><img src="<?= base_url($shop['logo_path']) ?>" alt="<?= esc($shop['name']??'Company') ?> logo"></div><?php endif; ?>
            <div class="inv-company-copy">
                <h2><?= esc($shop['name']??'Shop') ?></h2>
                <?php if($showCompanyAddress): ?><div class="inv-company-address"><?= nl2br(esc($shop['address'])) ?></div><?php endif; ?>
                <div class="inv-company-contact">
                    <?php if($showCompanyPhone): ?><span><?= esc($shop['phone']) ?></span><?php endif; ?>
                    <?php if($showCompanyEmail): ?><span><?= esc($shop['email']) ?></span><?php endif; ?>
                    <?php if($gstEnabled && !empty($shop['gstin'])): ?><span>GSTIN: <?= esc($shop['gstin']) ?></span><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="inv-document">
            <div class="inv-title"><?= esc($cfg['title']??($sale['sale_type']==='cash_memo'?'CASH MEMO':'INVOICE')) ?></div>
            <div class="inv-number"><?= esc($sale['invoice_no']) ?></div>
            <dl><div><dt>Date</dt><dd><?= esc(date('d M Y',strtotime($sale['sale_date']))) ?></dd></div><?php if(!empty($sale['due_date'])): ?><div><dt>Due date</dt><dd><?= esc(date('d M Y',strtotime($sale['due_date']))) ?></dd></div><?php endif; ?><div><dt>Status</dt><dd><?= esc(ucfirst($sale['payment_status'])) ?></dd></div></dl>
        </div>
    </header>

    <?php if($showCustomerAddress || !empty($sale['customer_name'])): ?>
    <section class="inv-party inv-address-<?= esc($addressPosition) ?>">
        <div class="inv-billto">
            <span class="inv-label">Bill to</span>
            <strong><?= esc($sale['customer_name']) ?></strong>
            <?php if(!empty($sale['customer_phone'])): ?><span><?= esc($sale['customer_phone']) ?></span><?php endif; ?>
            <?php if(!empty($sale['customer_email'])): ?><span><?= esc($sale['customer_email']) ?></span><?php endif; ?>
            <?php if($showCustomerAddress): ?><address><?= nl2br(esc($sale['customer_address'])) ?></address><?php endif; ?>
            <?php if($showCustomerGstin): ?><span>GSTIN: <?= esc($sale['customer_gstin']) ?></span><?php endif; ?>
        </div>
        <div class="inv-reference">
            <span class="inv-label">Invoice details</span>
            <span>Invoice: <strong><?= esc($sale['invoice_no']) ?></strong></span>
            <span>Payment: <strong><?= esc(ucfirst($sale['payment_status'])) ?></strong></span>
            <?php if(!empty($sale['due_date'])): ?><span>Due: <strong><?= esc(date('d M Y',strtotime($sale['due_date']))) ?></strong></span><?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <div class="inv-table-wrap">
        <table class="inv-table">
            <thead><tr><th class="inv-col-no">#</th><th>Item</th><?php if($showImei): ?><th>IMEI / Serial</th><?php endif; ?><th class="num">Qty</th><th class="num">Rate</th><?php if($showItemDiscount): ?><th class="num">Discount</th><?php endif; ?><?php if($gstEnabled): ?><th class="num">GST</th><?php endif; ?><th class="num">Amount</th></tr></thead>
            <tbody><?php foreach($items as $idx=>$it): ?>
                <tr>
                    <td class="inv-col-no"><?= $idx+1 ?></td>
                    <td><strong><?= esc($it['name']) ?></strong><?php if($it['model']): ?><small><?= esc($it['model']) ?></small><?php endif; ?><?php if($showHsn && $it['hsn_sac']): ?><small>HSN/SAC: <?= esc($it['hsn_sac']) ?></small><?php endif; ?></td>
                    <?php if($showImei): ?><td><?php $ids=array_filter([$it['imei1'],$it['imei2'],$it['serial_no'],$it['unique_id']]); ?><?= $ids?esc(implode(' / ',$ids)):'—' ?></td><?php endif; ?>
                    <td class="num"><?= rtrim(rtrim(number_format((float)$it['qty'],3,'.',''),'0'),'.') ?></td>
                    <td class="num"><?= $moneyPrefix.number_format((float)$it['unit_price'],2) ?></td>
                    <?php if($showItemDiscount): ?><td class="num"><?= (float)$it['discount_amount']>0?'− '.$moneyPrefix.number_format((float)$it['discount_amount'],2):'—' ?></td><?php endif; ?>
                    <?php if($gstEnabled): ?><td class="num"><?= number_format((float)$it['tax_percent'],2) ?>%</td><?php endif; ?>
                    <td class="num"><strong><?= $moneyPrefix.number_format((float)$it['line_total'],2) ?></strong></td>
                </tr>
            <?php endforeach; ?></tbody>
        </table>
    </div>

    <section class="inv-bottom">
        <div class="inv-notes">
            <?php if(!empty($sale['notes'])): ?><div><span class="inv-label">Note</span><p><?= nl2br(esc($sale['notes'])) ?></p></div><?php endif; ?>
            <?php if(!empty($cfg['terms'])): ?><div><span class="inv-label">Terms & conditions</span><p><?= nl2br(esc($cfg['terms'])) ?></p></div><?php endif; ?>
        </div>
        <div class="inv-totals">
            <div><span>Subtotal</span><strong><?= $moneyPrefix.number_format((float)$sale['subtotal'],2) ?></strong></div>
            <?php if($lineDiscount>0): ?><div><span>Line discounts</span><strong>− <?= $moneyPrefix.number_format($lineDiscount,2) ?></strong></div><?php endif; ?>
            <?php if($overallDiscount>0): ?><div><span>Invoice discount</span><strong>− <?= $moneyPrefix.number_format($overallDiscount,2) ?></strong></div><?php endif; ?>
            <?php if($gstEnabled): ?><div><span>GST</span><strong><?= $moneyPrefix.number_format((float)$sale['tax_total'],2) ?></strong></div><?php endif; ?>
            <div class="grand"><span>Grand total</span><strong><?= $moneyPrefix.number_format((float)$sale['grand_total'],2) ?></strong></div>
            <div><span>Paid</span><strong><?= $moneyPrefix.number_format((float)$sale['paid_amount'],2) ?></strong></div>
            <div class="due"><span>Balance due</span><strong><?= $moneyPrefix.number_format((float)$sale['due_amount'],2) ?></strong></div>
        </div>
    </section>

    <footer class="inv-footer">
        <div class="inv-footer-message"><?php if(!empty($cfg['footer'])): ?><?= esc($cfg['footer']) ?><?php else: ?>Thank you for your business.<?php endif; ?></div>
        <?php if($showSignature): ?><div class="inv-signature"><img src="<?= base_url($shop['signature_path']) ?>" alt="Authorized signature"><span>Authorized Signatory</span></div><?php endif; ?>
    </footer>
</article>

<?php if($payments): ?><div class="ms-no-print ms-card"><h2 class="ms-section-title">Payment history</h2><div class="ms-table-scroll"><table class="ms-table"><thead><tr><th>Date</th><th>Method</th><th>Reference</th><th>Notes</th><th>Amount</th></tr></thead><tbody><?php foreach($payments as $p): ?><tr><td><?= esc($p['paid_at']) ?></td><td><?= esc(strtoupper($p['method'])) ?></td><td><?= esc($p['reference_no']??'-') ?></td><td><?= esc($p['notes']??'-') ?></td><td><strong><?= $moneyPrefix.number_format((float)$p['amount'],2) ?></strong></td></tr><?php endforeach; ?></tbody></table></div></div><?php endif; ?>

<?php if((float)$sale['due_amount']>0): ?><dialog id="paymentDialog"><div class="ms-dialog-body"><div class="ms-dialog-head"><div><h3>Record payment</h3><span class="ms-muted">Outstanding: <?= $moneyPrefix.number_format((float)$sale['due_amount'],2) ?></span></div><button class="ms-btn ms-btn-secondary is-sm" type="button" data-close-dialog>Close</button></div><form method="post" action="<?= site_url('sales/'.$sale['id'].'/payments') ?>"><?= csrf_field() ?><div class="ms-form-grid"><div class="ms-field"><label>Amount *</label><input class="ms-input" type="number" name="amount" min="0.01" max="<?= esc($sale['due_amount']) ?>" step="0.01" value="<?= esc($sale['due_amount']) ?>" required></div><div class="ms-field"><label>Method</label><select class="ms-select" name="method"><option value="cash">Cash</option><option value="upi">UPI</option><option value="card">Card</option><option value="bank">Bank</option><option value="credit">Credit</option><option value="other">Other</option></select></div><div class="ms-field"><label>Reference</label><input class="ms-input" name="reference_no"></div><div class="ms-field ms-full"><label>Notes</label><textarea class="ms-textarea" name="notes"></textarea></div><div class="ms-field ms-full"><button class="ms-btn ms-btn-primary">Save Payment</button></div></div></form></div></dialog><?php endif; ?>
<?= $this->endSection() ?>
