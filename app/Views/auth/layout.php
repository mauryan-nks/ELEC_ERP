<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <title><?= $this->renderSection('title') ?: 'Sign in' ?> · MobileShop ERP</title>
    <script>
    (function(){try{var t=localStorage.getItem('shop-theme');if(t!=='light'&&t!=='dark')t=matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';document.documentElement.dataset.theme=t}catch(e){}})();
    </script>
    <?php $cssPath = FCPATH . 'assets/css/shop.css'; $jsPath = FCPATH . 'assets/js/shop.js'; ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/shop.css') ?>?v=<?= is_file($cssPath) ? (string) filemtime($cssPath) : '1' ?>">
</head>
<body class="ms-auth-body">
    <button class="ms-theme-btn ms-auth-theme" type="button" data-theme-toggle aria-label="Switch color theme"><span data-theme-icon aria-hidden="true">☾</span><span data-theme-label>Dark</span></button>
    <div class="ms-auth-shell">
        <section class="ms-auth-brand-panel" aria-hidden="true">
            <div class="ms-auth-brand-mark">MS</div>
            <h1>MobileShop ERP</h1>
            <p>Invoices, sales, purchases, IMEI inventory, customer dues and staff access in one shop system.</p>
            <div class="ms-auth-feature-grid"><span>Invoice</span><span>Inventory</span><span>IMEI</span><span>WhatsApp</span></div>
        </section>
        <main class="ms-auth-card">
            <?= $this->renderSection('main') ?>
        </main>
    </div>
    <script src="<?= base_url('assets/js/shop.js') ?>?v=<?= is_file($jsPath) ? (string) filemtime($jsPath) : '1' ?>"></script>
</body>
</html>
