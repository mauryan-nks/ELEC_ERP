<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

class InvoicePdfService
{
    public function generate(int $saleId, bool $forceGstClassic = false): string
    {
        $db=db_connect();
        $sale=$db->table('sales s')->select('s.*,c.name customer_name,c.phone customer_phone,c.email customer_email,c.address customer_address,c.gstin customer_gstin')->join('customers c','c.id=s.customer_id')->where('s.id',$saleId)->get()->getRowArray();
        if(!$sale) throw new RuntimeException('Sale not found for PDF generation.');
        $items=$db->table('sale_items si')->select('si.id,si.qty,si.unit_price,si.discount_amount,si.tax_percent,si.line_total,p.name,p.model,p.hsn_sac,u.imei1,u.imei2,u.serial_no,u.unique_id')->join('products p','p.id=si.product_id')->join('inventory_units u','u.id=si.inventory_unit_id','left')->where('si.sale_id',$saleId)->orderBy('si.id')->get()->getResultArray();
        $shop=$db->table('shop_settings')->where('id',1)->get()->getRowArray() ?? [];
        $currentTerms = $shop['invoice_terms'] ?? null;
        $currentFooter = $shop['invoice_footer'] ?? null;
        $currentAssets = [
            'logo_base64' => $shop['logo_base64'] ?? null, 'logo_mime' => $shop['logo_mime'] ?? null,
            'signature_base64' => $shop['signature_base64'] ?? null, 'signature_mime' => $shop['signature_mime'] ?? null,
        ];
        $cfg=json_decode((string)($sale['invoice_config_json']??''),true); if(!is_array($cfg))$cfg=[];
        $cfg=array_replace(['template'=>'classic','title'=>'TAX INVOICE','gst_enabled'=>(bool)($shop['invoice_default_gst_enabled']??1),'gst_mode'=>$shop['invoice_default_gst_mode']??'inclusive','invoice_color'=>$shop['invoice_color']??'#e87523','show_logo'=>true,'show_signature'=>true,'show_company_phone'=>true,'show_company_email'=>true,'show_company_address'=>true,'show_customer_address'=>true,'show_customer_gstin'=>true,'show_imei'=>true,'show_hsn'=>true,'show_item_discount'=>true,'customer_address_position'=>'left','terms'=>$shop['invoice_terms']??null,'footer'=>$shop['invoice_footer']??null],$cfg);
        if ($forceGstClassic) $cfg['template'] = 'gst_classic';
        if(!empty($sale['company_snapshot_json'])){$snapshot=json_decode((string)$sale['company_snapshot_json'],true);if(is_array($snapshot))$shop=array_replace($shop,$snapshot);}
        foreach ($currentAssets as $key => $value) if ($value !== null && $value !== '') $shop[$key] = $value;
        // Terms and footer are business policies, so use their latest Settings values.
        $cfg['terms'] = $currentTerms;
        $cfg['footer'] = $currentFooter;
        $shop = $this->normalisePdfImages($shop);
        // GST Classic is a dedicated A4 tax-invoice layout. Other themes retain
        // the existing responsive invoice PDF layout.
        $pdfView = ($cfg['template'] ?? 'classic') === 'gst_classic'
            ? 'sales/invoice_pdf_gst_classic'
            : 'sales/invoice_pdf';
        $html=view($pdfView,['sale'=>$sale,'items'=>$items,'shop'=>$shop,'cfg'=>$cfg]);
        $dir=WRITEPATH.'uploads/invoices'; if(!is_dir($dir)&&!mkdir($dir,0750,true)&&!is_dir($dir))throw new RuntimeException('Unable to create invoice PDF directory.');
        $path=$dir.'/'.preg_replace('/[^A-Za-z0-9._-]/','_', $sale['invoice_no']).'.pdf';
        if (($cfg['template'] ?? 'classic') === 'gst_classic') {
            if (! $this->renderWithChrome($html, $path)) {
                throw new RuntimeException('GST Classic PDF could not be rendered by Chrome.');
            }
            return $path;
        }

        $options=new Options(); $options->set('isRemoteEnabled',false); $options->set('defaultFont','DejaVu Sans');
        $paper = ($cfg['template'] ?? 'classic') === 'gst_classic' ? 'letter' : 'A4';
        $dompdf=new Dompdf($options); $dompdf->loadHtml($html,'UTF-8'); $dompdf->setPaper($paper,'portrait'); $dompdf->render();
        file_put_contents($path,$dompdf->output());
        return $path;
    }

    /** Chrome matches the supplied PDF-to-HTML layout, especially its fixed table grid. */
    private function renderWithChrome(string $html, string $outputPath): bool
{
    $chrome = '/usr/bin/google-chrome';

    if (!is_file($chrome) || !is_executable($chrome)) {
        log_message('error', 'GST PDF: Chrome not executable: ' . $chrome);
        return false;
    }

    $workDir = WRITEPATH . 'uploads/chrome_invoice_' . bin2hex(random_bytes(8));

    if (!@mkdir($workDir, 0775, true)) {
        log_message('error', 'GST PDF: Cannot create work directory: ' . $workDir);
        return false;
    }

    $htmlFile = $workDir . '/invoice.html';
    $pdfFile  = $workDir . '/invoice.pdf';
    $profile  = $workDir . '/profile';
    $logFile  = $workDir . '/chrome.log';

    @mkdir($profile, 0775, true);

    if (@file_put_contents($htmlFile, $html) === false) {
        log_message('error', 'GST PDF: Cannot write invoice HTML.');
        return false;
    }

    /*
     * Chrome must be able to read the local HTML file.
     */
    @chmod($htmlFile, 0644);

    $url = 'file://' . $htmlFile;

    $command =
        escapeshellarg($chrome)
        . ' --headless=new'
        . ' --no-sandbox'
        . ' --disable-gpu'
        . ' --disable-dev-shm-usage'
        . ' --disable-software-rasterizer'
        . ' --disable-extensions'
        . ' --disable-background-networking'
        . ' --disable-sync'
        . ' --disable-default-apps'
        . ' --no-first-run'
        . ' --no-pdf-header-footer'
        . ' --user-data-dir=' . escapeshellarg($profile)
        . ' --print-to-pdf=' . escapeshellarg($pdfFile)
        . ' ' . escapeshellarg($url)
        . ' > ' . escapeshellarg($logFile)
        . ' 2>&1';

    /*
     * Run Chrome.
     */
    $output = [];
    $exitCode = 0;

    exec($command, $output, $exitCode);

    /*
     * Save Chrome output to CodeIgniter log.
     */
    $chromeLog = '';

    if (is_file($logFile)) {
        $chromeLog = trim((string) @file_get_contents($logFile));
    }

    if ($exitCode !== 0) {
        log_message(
            'error',
            'GST PDF: Chrome exit code {code}. Output: {output}',
            [
                'code'   => $exitCode,
                'output' => $chromeLog,
            ]
        );

        return false;
    }

    /*
     * Chrome sometimes returns exit code 0 but fails to create the PDF.
     */
    if (!is_file($pdfFile)) {
        log_message(
            'error',
            'GST PDF: Chrome did not create PDF. Output: {output}',
            [
                'output' => $chromeLog,
            ]
        );

        return false;
    }

    $size = filesize($pdfFile);

    if ($size === false || $size < 100) {
        log_message(
            'error',
            'GST PDF: Chrome created an invalid PDF. Size: {size}. Output: {output}',
            [
                'size'   => $size,
                'output' => $chromeLog,
            ]
        );

        return false;
    }

    /*
     * Copy final PDF to invoice storage.
     */
    if (!@copy($pdfFile, $outputPath)) {
        log_message(
            'error',
            'GST PDF: Cannot copy PDF to final path: {path}',
            [
                'path' => $outputPath,
            ]
        );

        return false;
    }

    if (!is_file($outputPath) || filesize($outputPath) < 100) {
        log_message(
            'error',
            'GST PDF: Final PDF is missing or invalid.'
        );

        return false;
    }

    /*
     * Cleanup.
     */
    @unlink($htmlFile);
    @unlink($pdfFile);
    @unlink($logFile);

    return true;
}

    /** Dompdf reliably renders PNG data URIs; normalize settings uploads (including WebP). */
   private function normalisePdfImages(array $shop): array
{
    foreach (['logo', 'signature'] as $asset) {

        $encoded = trim((string) ($shop[$asset . '_base64'] ?? ''));

        if ($encoded === '') {
            continue;
        }

        $encoded = preg_replace(
            '#^data:[^;]+;base64,#i',
            '',
            $encoded
        ) ?? '';

        $binary = base64_decode($encoded, true);

        if ($binary === false || !function_exists('imagecreatefromstring')) {
            continue;
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            continue;
        }

        $width  = imagesx($image);
        $height = imagesy($image);

        /*
         * Create a white canvas.
         * This removes transparent/black background problems
         * when Chrome generates the PDF.
         */
        $canvas = imagecreatetruecolor($width, $height);

        /*
         * White background.
         */
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        /*
         * Preserve the original image including transparency
         * while placing it over the white canvas.
         */
        imagealphablending($canvas, true);
        imagesavealpha($canvas, false);

        imagecopy(
            $canvas,
            $image,
            0,
            0,
            0,
            0,
            $width,
            $height
        );

        ob_start();
        imagepng($canvas);
        $png = ob_get_clean();

        imagedestroy($image);
        imagedestroy($canvas);

        if ($png !== false && $png !== '') {
            $shop[$asset . '_base64'] = base64_encode($png);
            $shop[$asset . '_mime'] = 'image/png';
        }
    }

    return $shop;
}
}
