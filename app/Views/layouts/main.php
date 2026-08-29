<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#f3f6fb" id="themeColorMeta">
    <title><?= esc($title ?? 'MobileShop ERP') ?></title>
    <script>
    (function(){
        try {
            var theme = localStorage.getItem('shop-theme');
            if (theme !== 'light' && theme !== 'dark') {
                theme = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.dataset.theme = theme;
        } catch (e) {}
    })();
    </script>
    <?php $cssPath = FCPATH . 'assets/css/shop.css'; $jsPath = FCPATH . 'assets/js/shop.js'; ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/shop.css') ?>?v=<?= is_file($cssPath) ? (string) filemtime($cssPath) : '1' ?>">
</head>
<body>
<?php
$path = trim(uri_string(), '/');
$active = static fn(string $prefix): string => ($path === $prefix || str_starts_with($path, $prefix . '/')) ? 'is-active' : '';
$me = auth()->user();
?>
<div class="ms-app">
    <aside class="ms-sidebar" id="shopSidebar" aria-label="Primary navigation">
        <div class="ms-brand">
            <div class="ms-brand-mark" aria-hidden="true">MS</div>
            <div class="ms-brand-copy">
                <strong>MobileShop ERP</strong>
                <span>Sales · Stock · Billing</span>
            </div>
            <button class="ms-sidebar-close" type="button" data-close-sidebar aria-label="Close navigation">×</button>
        </div>

        <nav class="ms-nav">
            <div class="ms-nav-heading">Overview</div>
            <a class="ms-nav-link <?= $active('dashboard') ?>" href="<?= site_url('dashboard') ?>"><span class="ms-nav-icon">DB</span><span>Dashboard</span></a>

            <?php if ($me->can('sales.view') || $me->can('customers.view')): ?><div class="ms-nav-heading">Sales</div><?php endif; ?>
            <?php if ($me->can('sales.create')): ?><a class="ms-nav-link <?= $active('sales/new') ?>" href="<?= site_url('sales/new') ?>"><span class="ms-nav-icon">+</span><span>New Sale / Invoice</span></a><?php endif; ?>
            <?php if ($me->can('sales.view')): ?><a class="ms-nav-link <?= ($path === 'sales' || preg_match('#^sales/\d+#', $path)) ? 'is-active' : '' ?>" href="<?= site_url('sales') ?>"><span class="ms-nav-icon">IN</span><span>Sales & Invoices</span></a><?php endif; ?>
            <?php if ($me->can('customers.view')): ?><a class="ms-nav-link <?= $active('customers') ?>" href="<?= site_url('customers') ?>"><span class="ms-nav-icon">CU</span><span>Customers</span></a><?php endif; ?>

            <?php if ($me->can('purchases.view') || $me->can('products.view') || $me->can('borrow.manage')): ?><div class="ms-nav-heading">Stock & Buying</div><?php endif; ?>
            <?php if ($me->can('purchases.view')): ?><a class="ms-nav-link <?= $active('purchases') ?>" href="<?= site_url('purchases') ?>"><span class="ms-nav-icon">PU</span><span>Purchases</span></a><?php endif; ?>
            <?php if ($me->can('purchases.create')): ?><a class="ms-nav-link <?= $active('suppliers') ?>" href="<?= site_url('suppliers') ?>"><span class="ms-nav-icon">SU</span><span>Suppliers / Stores</span></a><?php endif; ?>
            <?php if ($me->can('products.view')): ?><a class="ms-nav-link <?= $active('inventory') ?>" href="<?= site_url('inventory') ?>"><span class="ms-nav-icon">ST</span><span>Inventory / IMEI</span></a><?php endif; ?>
            <?php if ($me->can('products.view')): ?><a class="ms-nav-link <?= $active('products') ?>" href="<?= site_url('products') ?>"><span class="ms-nav-icon">PR</span><span>Products / Brands</span></a><?php endif; ?>
            <?php if ($me->can('borrow.manage')): ?><a class="ms-nav-link <?= $active('borrowed-stock') ?>" href="<?= site_url('borrowed-stock') ?>"><span class="ms-nav-icon">BR</span><span>Borrowed Stock</span></a><?php endif; ?>

            <?php if ($me->can('expenses.manage') || $me->can('reports.view')): ?><div class="ms-nav-heading">Accounts</div><?php endif; ?>
            <?php if ($me->can('expenses.manage')): ?><a class="ms-nav-link <?= $active('expenses') ?>" href="<?= site_url('expenses') ?>"><span class="ms-nav-icon">EX</span><span>Expenses</span></a><?php endif; ?>
            <?php if ($me->can('reports.view')): ?><a class="ms-nav-link <?= $active('reports') ?>" href="<?= site_url('reports') ?>"><span class="ms-nav-icon">RP</span><span>Reports</span></a><?php endif; ?>

            <?php if ($me->can('whatsapp.manage')): ?><div class="ms-nav-heading">Automation</div><a class="ms-nav-link <?= $active('whatsapp') ?>" href="<?= site_url('whatsapp') ?>"><span class="ms-nav-icon">WA</span><span>WhatsApp</span></a><?php endif; ?>

            <?php if ($me->can('users.manage') || $me->can('settings.manage')): ?><div class="ms-nav-heading">Administration</div><?php endif; ?>
            <?php if ($me->can('users.manage')): ?><a class="ms-nav-link <?= $active('users') ?>" href="<?= site_url('users') ?>"><span class="ms-nav-icon">US</span><span>Users & Roles</span></a><?php endif; ?>
            <?php if ($me->can('settings.manage')): ?><a class="ms-nav-link <?= $active('settings') ?>" href="<?= site_url('settings') ?>"><span class="ms-nav-icon">SE</span><span>Shop Settings</span></a><?php endif; ?>
        </nav>
    </aside>

    <button class="ms-scrim" id="shopScrim" type="button" data-close-sidebar aria-label="Close navigation"></button>

    <div class="ms-main">
        <header class="ms-topbar">
            <div class="ms-topbar-start">
                <button class="ms-menu-btn" id="shopMenuButton" type="button" aria-label="Open navigation" aria-controls="shopSidebar" aria-expanded="false">☰</button>
                <div class="ms-topbar-title-wrap">
                    <strong class="ms-topbar-title"><?= esc($title ?? 'MobileShop ERP') ?></strong>
                    <span class="ms-topbar-subtitle">Single shop management</span>
                </div>
            </div>
            <div class="ms-topbar-end">
                <button class="ms-theme-btn" type="button" data-theme-toggle aria-label="Switch color theme"><span data-theme-icon aria-hidden="true">☾</span><span data-theme-label>Dark</span></button>
                <div class="ms-account-chip"><span class="ms-account-avatar" aria-hidden="true"><?= esc(strtoupper(substr((string) ($me->username ?? 'U'), 0, 1))) ?></span><span class="ms-user-email"><?= esc($me->email ?? $me->username ?? 'User') ?></span></div>
                <a class="ms-btn ms-btn-secondary is-sm" href="<?= site_url('logout') ?>">Logout</a>
            </div>
        </header>

        <main class="ms-content" id="mainContent">
            <?php if (session('message')): ?><div class="ms-alert is-success" role="status"><?= esc(session('message')) ?></div><?php endif; ?>
            <?php if (session('error')): ?><div class="ms-alert is-error" role="alert"><?= esc(session('error')) ?></div><?php endif; ?>
            <?= $this->renderSection('content') ?>
        </main>
    </div>
</div>
<div id="shopToastStack" class="ms-toast-stack" aria-live="polite" aria-atomic="true"></div>
<script src="<?= base_url('assets/js/shop.js') ?>?v=<?= is_file($jsPath) ? (string) filemtime($jsPath) : '1' ?>"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
