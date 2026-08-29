<?php
/**
 * GST CLASSIC — TMW-style A4 invoice.
 * Designed to closely reproduce the supplied reference invoice:
 * - thin black outer border
 * - logo at upper-left
 * - centered TAX INVOICE / company header
 * - Original Copy at upper-right
 * - two-column Party Details / invoice metadata block
 * - 11-column GST item table
 * - large blank item area
 * - Grand Total row
 * - compact tax summary
 * - amount in words
 * - Terms & Conditions / Receiver's Signature / Authorised Signatory
 */

$sale  = is_array($sale ?? null) ? $sale : [];
$items = is_array($items ?? null) ? $items : [];
$shop  = is_array($shop ?? null) ? $shop : [];
$cfg   = is_array($cfg ?? null) ? $cfg : [];

$e = static fn($v): string => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$num = static fn($v, int $d = 2): string => number_format((float)$v, $d, '.', ',');

$currency = (string)($shop['currency'] ?? 'INR');
$currencySymbol = $currency === 'INR' ? '₹' : $currency . ' ';

$grandTotal = (float)($sale['grand_total'] ?? 0);
$taxTotal   = (float)($sale['tax_total'] ?? 0);
$gstEnabled = !empty($cfg['gst_enabled']);
$gstMode    = (string)($cfg['gst_mode'] ?? 'inclusive');

$cgstTotal = round($taxTotal / 2, 2);
$sgstTotal = round($taxTotal - $cgstTotal, 2);

/* Number to words in Indian numbering format. */
$numberToWords = static function (int $number) use (&$numberToWords): string {
    $ones = ['', 'One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
    $tens = ['', '', 'Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];

    if ($number === 0) return 'Zero';
    if ($number < 20) return $ones[$number];
    if ($number < 100) return $tens[intdiv($number,10)] . ($number % 10 ? ' ' . $ones[$number % 10] : '');
    if ($number < 1000) return $ones[intdiv($number,100)] . ' Hundred' . ($number % 100 ? ' ' . $numberToWords($number % 100) : '');
    if ($number < 100000) return $numberToWords(intdiv($number,1000)) . ' Thousand' . ($number % 1000 ? ' ' . $numberToWords($number % 1000) : '');
    if ($number < 10000000) return $numberToWords(intdiv($number,100000)) . ' Lakh' . ($number % 100000 ? ' ' . $numberToWords($number % 100000) : '');
    return $numberToWords(intdiv($number,10000000)) . ' Crore' . ($number % 10000000 ? ' ' . $numberToWords($number % 10000000) : '');
};

$whole = (int)floor(abs($grandTotal));
$paise = (int)round((abs($grandTotal) - $whole) * 100);
$amountWords = $numberToWords($whole);
if ($paise > 0) $amountWords .= ' and ' . $numberToWords($paise) . ' Paise';
$amountWords .= ' Only';

$invoiceTimestamp = !empty($sale['sale_date']) ? strtotime((string)$sale['sale_date']) : false;
$invoiceDate = $invoiceTimestamp ? date('d-m-Y', $invoiceTimestamp) : date('d-m-Y');

$companyName    = trim((string)($shop['name'] ?? 'Shop'));
$companyAddress = trim((string)($shop['address'] ?? ''));
$companyPhone   = trim((string)($shop['phone'] ?? ''));
$companyEmail   = trim((string)($shop['email'] ?? ''));
$companyWebsite = trim((string)($shop['website'] ?? $shop['web'] ?? ''));
$companyGstin   = trim((string)($shop['gstin'] ?? ''));

$customerName    = trim((string)($sale['customer_name'] ?? ''));
$customerPhone   = trim((string)($sale['customer_phone'] ?? ''));
$customerAddress = trim((string)($sale['customer_address'] ?? ''));
$customerGstin   = trim((string)($sale['customer_gstin'] ?? ''));

$placeOfSupply = trim((string)($sale['place_of_supply'] ?? $shop['place_of_supply'] ?? ''));
$reverseCharge = trim((string)($sale['reverse_charge'] ?? 'N')) ?: 'N';
$grrrNo        = trim((string)($sale['gr_rr_no'] ?? $sale['grrr_no'] ?? ''));
$transport     = trim((string)($sale['transport'] ?? ''));
$vehicleNo     = trim((string)($sale['vehicle_no'] ?? ''));
$station       = trim((string)($sale['station'] ?? ''));

$terms = trim((string)($cfg['terms'] ?? $shop['invoice_terms'] ?? ''));
$footer = trim((string)($cfg['footer'] ?? $shop['invoice_footer'] ?? ''));

/* Image snapshots are preferred because Chromium may be running in a service sandbox. */
$makeDataUri = static function (string $base64, string $mime): string {
    if ($base64 === '') return '';
    return 'data:' . ($mime !== '' ? $mime : 'image/png') . ';base64,' . $base64;
};

$logoSrc = $makeDataUri(trim((string)($shop['logo_base64'] ?? '')), trim((string)($shop['logo_mime'] ?? '')));
$signatureSrc = $makeDataUri(trim((string)($shop['signature_base64'] ?? '')), trim((string)($shop['signature_mime'] ?? '')));

if ($logoSrc === '' && !empty($shop['logo_path']) && is_file((string)$shop['logo_path'])) {
    $p = (string)$shop['logo_path']; $b = @file_get_contents($p);
    if ($b !== false) $logoSrc = 'data:' . (function_exists('mime_content_type') ? (@mime_content_type($p) ?: 'image/png') : 'image/png') . ';base64,' . base64_encode($b);
}
if ($signatureSrc === '' && !empty($shop['signature_path']) && is_file((string)$shop['signature_path'])) {
    $p = (string)$shop['signature_path']; $b = @file_get_contents($p);
    if ($b !== false) $signatureSrc = 'data:' . (function_exists('mime_content_type') ? (@mime_content_type($p) ?: 'image/png') : 'image/png') . ';base64,' . base64_encode($b);
}

/* Build tax summary from the same line values used by the sale. */
$taxSummary = [];
$totalQty = 0.0;

foreach ($items as $item) {
    $qty = (float)($item['qty'] ?? 0);
    $unitPrice = (float)($item['unit_price'] ?? 0);
    $lineTotal = (float)($item['line_total'] ?? 0);
    $rate = (float)($item['tax_percent'] ?? 0);
    $totalQty += $qty;

    if ($gstEnabled && $rate > 0) {
        if ($gstMode === 'inclusive') {
            $taxable = round($lineTotal / (1 + $rate / 100), 2);
            $lineTax = round($lineTotal - $taxable, 2);
        } else {
            $taxable = round($lineTotal, 2);
            $lineTax = round($taxable * $rate / 100, 2);
        }
    } else {
        $taxable = round($lineTotal, 2);
        $lineTax = 0.0;
    }

    $cgst = round($lineTax / 2, 2);
    $sgst = round($lineTax - $cgst, 2);
    $key = number_format($rate, 2, '.', '');

    if (!isset($taxSummary[$key])) {
        $taxSummary[$key] = ['rate'=>$rate, 'taxable'=>0.0, 'cgst'=>0.0, 'sgst'=>0.0, 'tax'=>0.0];
    }
    $taxSummary[$key]['taxable'] += $taxable;
    $taxSummary[$key]['cgst'] += $cgst;
    $taxSummary[$key]['sgst'] += $sgst;
    $taxSummary[$key]['tax'] += $lineTax;
}
foreach ($taxSummary as &$s) {
    $s['taxable'] = round($s['taxable'], 2);
    $s['cgst'] = round($s['cgst'], 2);
    $s['sgst'] = round($s['sgst'], 2);
    $s['tax'] = round($s['tax'], 2);
}
unset($s);

/* Terms: preserve configured text, but render newline-separated lines as the reference does. */
$termLines = [];
if ($terms !== '') {
    foreach (preg_split('/\R+/', $terms) ?: [] as $line) {
        $line = trim($line);
        if ($line !== '') $termLines[] = preg_replace('/^\d+[.)]\s*/', '', $line);
    }
}
if ($termLines === []) {
    $termLines = [
        'Goods once sold will not be taken back.',
        'Interest @ 18% p.a. will be charged if the payment is not made within the stipulated time.',
        'Subject to jurisdiction of the place of supply only.'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $e($cfg['title'] ?? 'TAX INVOICE') ?> - <?= $e($sale['invoice_no'] ?? '') ?></title>
<style>
@page { size: A4 portrait; margin: 5mm; }
* { box-sizing: border-box; }
html, body { margin:0; padding:0; background:#fff; color:#111; }
body { font-family: Arial, Helvetica, sans-serif; font-size:9px; }
.invoice { width:100%; border:1px solid #111; }

/* HEADER — reference is compact, centered, with logo occupying the left block. */
.header { height:40mm; position:relative; border-bottom:1px solid #111; overflow:hidden; }
.copy { position:absolute; top:2mm; right:2.5mm; font-size:11px; font-style:italic; }
.logo-wrap { position:absolute; left:1.5mm; top:1.5mm; width:38mm; height:31mm; display:flex; align-items:center; justify-content:center; }
.logo { max-width:100%; max-height:100%; object-fit:contain; }
.head-center { position:absolute; left:39mm; right:17mm; top:3.5mm; text-align:center; }
.title { font-size:15px; font-weight:700; text-decoration:underline; line-height:18px; }
.company-name { margin-top:1mm; font-size:25px; line-height:27px; font-weight:700; text-transform:uppercase; }
.address { margin-top:0.5mm; font-size:12px; line-height:15px; }
.gstin { margin-top:0.6mm; font-size:12px; font-weight:700; }
.contact { margin-top:0.4mm; font-size:10px; font-weight:700; line-height:13px; }
.website { font-size:11px; font-weight:700; line-height:14px; }

/* PARTY / INVOICE DETAILS */
.details { width:100%; border-collapse:collapse; table-layout:fixed; height:34mm; }
.details td { border-right:1px solid #111; border-bottom:0; padding:1.2mm 1.5mm; vertical-align:top; font-size:10.5px; line-height:13px; }
.details td:last-child { border-right:0; }
.party { width:50%; position:relative; }
.meta-label { width:15%; white-space:nowrap; }
.meta-value { width:35%; }
.party-title { font-weight:700; font-style:italic; font-size:12px; }
.party-name { font-size:11px; }
.party-bottom { position:absolute; left:1.5mm; bottom:2mm; }
.meta-row { height:4.45mm; }

/* ITEMS — same 11 columns and proportions as supplied reference. */
.items { width:100%; border-collapse:collapse; table-layout:fixed; }
.items th, .items td { border-right:1px solid #111; border-bottom:1px solid #111; }
.items th:last-child, .items td:last-child { border-right:0; }
.items th { height:12mm; padding:1.2mm 0.7mm; text-align:center; font-size:9px; line-height:12px; font-weight:700; }
.items td { padding:1.6mm 1mm; vertical-align:top; font-size:9px; line-height:12px; }
.items .c { text-align:center; }
.items .r { text-align:right; }
.items .sn { width:4%; }
.items .desc { width:24%; }
.items .hsn { width:8%; }
.items .qty { width:7%; }
.items .unit { width:7%; }
.items .price { width:10%; }
.items .rate { width:6.5%; }
.items .taxamt { width:8%; }
.items .amount { width:11%; }
.item-area td { height:89mm; }
.item-name { font-size:9.5px; }
.imei { margin-top:2mm; font-size:8.5px; font-style:italic; }
.grand td { height:10mm; vertical-align:middle; font-weight:700; font-size:10px; }
.grand-label { text-align:right; }

/* TAX SUMMARY */
.tax-section { height:22mm; padding:2mm 1.5mm 0; }
.tax-summary { border-collapse:collapse; width:48%; }
.tax-summary th, .tax-summary td { padding:0.7mm 1mm; font-size:9px; text-align:right; }
.tax-summary th { text-decoration:underline; text-align:center; }
.tax-summary td:first-child { text-align:left; }

/* AMOUNT WORDS */
.words { min-height:20mm; border-bottom:1px solid #111; padding:2mm 1.5mm; font-size:10px; }
.words strong { font-size:12px; }
.words-value { font-size:10px; margin-top:0.5mm; }

/* FINAL BLOCK */
.bottom { display:table; width:100%; table-layout:fixed; height:36mm; }
.bottom-left, .bottom-right { display:table-cell; vertical-align:top; }
.bottom-left { width:46%; border-right:1px solid #111; }
.bottom-right { width:54%; position:relative; }
.terms { padding:1.5mm; font-size:9px; line-height:13px; }
.terms-title { font-size:10px; font-weight:700; text-decoration:underline; margin-bottom:1mm; }
.terms ol { margin:0; padding-left:5mm; }
.terms li { padding-left:0.5mm; }
.eoe { margin-top:1mm; }
.receiver { padding:2mm 1.5mm; font-weight:700; }
.receiver-line { margin-top:9mm; }
.auth { position:absolute; left:0; right:0; bottom:1.5mm; text-align:right; padding-right:4mm; font-size:12px; font-weight:700; }
.auth img { display:block; width:34mm; height:18mm; object-fit:contain; margin:0 2mm 0 auto; }
.auth-name { margin-top:0.5mm; }

@media print { body { -webkit-print-color-adjust: exact; print-color-adjust: exact; } }
</style>
</head>
<body>
<div class="invoice">

    <div class="header">
        <div class="copy">Original Copy</div>
        <?php if ($logoSrc !== '' && !empty($cfg['show_logo'])): ?>
            <div class="logo-wrap"><img class="logo" src="<?= $e($logoSrc) ?>" alt="Logo"></div>
        <?php endif; ?>

        <div class="head-center">
            <div class="title"><?= $e($cfg['title'] ?? 'TAX INVOICE') ?></div>
            <div class="company-name"><?= $e($companyName) ?></div>
            <?php if (!empty($cfg['show_company_address']) && $companyAddress !== ''): ?>
                <div class="address"><?= nl2br($e($companyAddress)) ?></div>
            <?php endif; ?>
            <?php if ($companyGstin !== ''): ?><div class="gstin">GSTIN : <?= $e($companyGstin) ?></div><?php endif; ?>
            <div class="contact">
                <?php if (!empty($cfg['show_company_phone']) && $companyPhone !== ''): ?>Tel. : <?= $e($companyPhone) ?><?php endif; ?>
                <?php if (!empty($cfg['show_company_email']) && $companyEmail !== ''): ?> &nbsp;&nbsp; email : <?= $e($companyEmail) ?><?php endif; ?>
            </div>
            <?php if ($companyWebsite !== ''): ?><div class="website">Website : <?= $e($companyWebsite) ?></div><?php endif; ?>
        </div>
    </div>

    <table class="details">
        <tr>
            <td class="party" rowspan="7">
                <div class="party-title">Party Details :</div>
                <div class="party-name"><?= $e($customerName) ?></div>
                <?php if (!empty($cfg['show_customer_address']) && $customerAddress !== ''): ?>
                    <div><?= nl2br($e($customerAddress)) ?></div>
                <?php endif; ?>
                <div class="party-bottom">
                    <div>Party Mobile No&nbsp;&nbsp;&nbsp; : &nbsp;<?= $e($customerPhone) ?></div>
                    <div>GSTIN / UIN&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; : &nbsp;<?= $e($customerGstin) ?></div>
                </div>
            </td>
            <td class="meta-label">Invoice No.</td><td class="meta-value">: &nbsp;<?= $e($sale['invoice_no'] ?? '') ?></td>
        </tr>
        <tr class="meta-row"><td class="meta-label">Dated</td><td class="meta-value">: &nbsp;<?= $e($invoiceDate) ?></td></tr>
        <tr class="meta-row"><td class="meta-label">Place of Supply</td><td class="meta-value">: &nbsp;<?= $e($placeOfSupply) ?></td></tr>
        <tr class="meta-row"><td class="meta-label">Reverse Charge</td><td class="meta-value">: &nbsp;<?= $e($reverseCharge) ?></td></tr>
        <tr class="meta-row"><td class="meta-label">GR/RR No.</td><td class="meta-value">: &nbsp;<?= $e($grrrNo) ?></td></tr>
        <tr class="meta-row"><td class="meta-label">Transport</td><td class="meta-value">: &nbsp;<?= $e($transport) ?></td></tr>
        <tr class="meta-row"><td class="meta-label">Vehicle No.</td><td class="meta-value">: &nbsp;<?= $e($vehicleNo) ?></td></tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th class="sn">S.N.</th>
                <th class="desc">Description of Goods</th>
                <th class="hsn">HSN/SAC<br>Code</th>
                <th class="qty">Qty.</th>
                <th class="unit">Unit</th>
                <th class="price">Price</th>
                <th class="rate">CGST<br>Rate</th>
                <th class="taxamt">CGST<br>Amount</th>
                <th class="rate">SGST<br>Rate</th>
                <th class="taxamt">SGST<br>Amount</th>
                <th class="amount">Amount<br>(<?= $e($currencySymbol) ?>)</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($items === []): ?>
            <tr class="item-area"><td colspan="11" class="c">No items</td></tr>
        <?php else: ?>
            <?php foreach ($items as $index => $item):
                $qty = (float)($item['qty'] ?? 0);
                $unitPrice = (float)($item['unit_price'] ?? 0);
                $lineTotal = (float)($item['line_total'] ?? 0);
                $rate = (float)($item['tax_percent'] ?? 0);
                if ($gstEnabled && $rate > 0) {
                    if ($gstMode === 'inclusive') { $taxable = $lineTotal / (1 + $rate / 100); $lineTax = $lineTotal - $taxable; }
                    else { $taxable = $lineTotal; $lineTax = $taxable * $rate / 100; }
                } else { $lineTax = 0; }
                $cgst = round($lineTax / 2, 2); $sgst = round($lineTax - $cgst, 2);
                $productName = trim((string)($item['name'] ?? ''));
                $model = trim((string)($item['model'] ?? ''));
                $hsn = trim((string)($item['hsn_sac'] ?? $item['hsn'] ?? ''));
                $imei1 = trim((string)($item['imei1'] ?? '')); $imei2 = trim((string)($item['imei2'] ?? ''));
                $serial = trim((string)($item['serial_no'] ?? '')); $uniqueId = trim((string)($item['unique_id'] ?? ''));
            ?>
            <tr class="item-area">
                <td class="c"><?= $index + 1 ?>.</td>
                <td>
                    <div class="item-name"><?= $e($productName) ?><?= $model !== '' ? ' ' . $e($model) : '' ?></div>
                    <?php if (!empty($cfg['show_imei']) && ($imei1 || $imei2 || $serial || $uniqueId)): ?>
                        <div class="imei">
                            <?= $e($imei1) ?>
                            <?php if ($imei2): ?><br><?= $e($imei2) ?><?php endif; ?>
                            <?php if ($serial): ?><br><?= $e($serial) ?><?php endif; ?>
                            <?php if ($uniqueId): ?><br><?= $e($uniqueId) ?><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </td>
                <td class="c"><?= !empty($cfg['show_hsn']) ? $e($hsn) : '' ?></td>
                <td class="c"><?= $num($qty, 2) ?></td>
                <td class="c">Pcs.</td>
                <td class="r"><?= $num($unitPrice, 2) ?></td>
                <td class="c"><?= ($gstEnabled && $rate > 0) ? $num($rate/2, 2) . ' %' : '-' ?></td>
                <td class="r"><?= ($gstEnabled && $lineTax > 0) ? $num($cgst, 2) : '-' ?></td>
                <td class="c"><?= ($gstEnabled && $rate > 0) ? $num($rate/2, 2) . ' %' : '-' ?></td>
                <td class="r"><?= ($gstEnabled && $lineTax > 0) ? $num($sgst, 2) : '-' ?></td>
                <td class="r"><?= $num($lineTotal, 2) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
        <tfoot>
            <tr class="grand">
                <td colspan="3" class="grand-label">Grand Total</td>
                <td class="c"><?= $num($totalQty, 2) ?></td>
                <td class="c">Pcs.</td>
                <td></td><td></td>
                <td class="r"><?= $num($cgstTotal, 2) ?></td>
                <td></td>
                <td class="r"><?= $num($sgstTotal, 2) ?></td>
                <td class="r"><?= $num($grandTotal, 2) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="tax-section">
        <table class="tax-summary">
            <thead><tr><th>Tax Rate</th><th>Taxable Amt.</th><th>CGST Amt.</th><th>SGST Amt.</th><th>Total Tax</th></tr></thead>
            <tbody>
            <?php if ($taxSummary): foreach ($taxSummary as $s): ?>
                <tr><td><?= $num($s['rate'], 0) ?>%</td><td><?= $num($s['taxable']) ?></td><td><?= $num($s['cgst']) ?></td><td><?= $num($s['sgst']) ?></td><td><?= $num($s['tax']) ?></td></tr>
            <?php endforeach; else: ?>
                <tr><td colspan="5" style="text-align:left">No GST</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="words">
        <strong><?= $e($amountWords) ?></strong>
        <div class="words-value">- <?= $currency === 'INR' ? $currencySymbol : $currency . ' ' ?><?= $num($grandTotal) ?></div>
    </div>

    <div class="bottom">
        <div class="bottom-left">
            <div class="terms">
                <div class="terms-title">Terms &amp; Conditions</div>
                <div>E. &amp; O.E.</div>
                <ol>
                    <?php foreach ($termLines as $line): ?><li><?= $e($line) ?></li><?php endforeach; ?>
                </ol>
                <?php if ($footer !== ''): ?><div style="margin-top:1mm"><?= nl2br($e($footer)) ?></div><?php endif; ?>
            </div>
        </div>
        <div class="bottom-right">
            <div class="receiver">Receiver's Signature &nbsp;&nbsp; :</div>
            <div class="auth">
                <?php if (!empty($cfg['show_signature']) && $signatureSrc !== ''): ?><img src="<?= $e($signatureSrc) ?>" alt="Signature"><?php endif; ?>
                <div>for <?= $e($companyName) ?></div>
                <div class="auth-name">Authorised Signatory</div>
            </div>
        </div>
    </div>

</div>
</body>
</html>
