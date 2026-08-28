<?php
$active = function ($name) use ($section) { return $section === $name ? 'active' : ''; };
if (!function_exists('dashboard_badge_class')) {
    function dashboard_badge_class($status) {
        $status = strtolower((string)$status);
        if (in_array($status, ['active','paid','completed','verified','payment_received'], true)) return 'bg-success-subtle text-success';
        if (in_array($status, ['pending','processing','quote_requested','payment_pending','not_required'], true)) return 'bg-warning-subtle text-warning';
        if (in_array($status, ['suspended','cancelled','refunded','failed','expired'], true)) return 'bg-danger-subtle text-danger';
        return 'bg-primary-subtle text-primary';
    }
}
if (!function_exists('dashboard_first_name')) {
    function dashboard_first_name($name) {
        $name = trim((string)$name);
        return $name !== '' ? explode(' ', $name)[0] : 'User';
    }
}
if (!function_exists('dashboard_profile_image')) {
    function dashboard_profile_image(array $user) {
        $profile = json_decode((string)($user['profile'] ?? ''), true);
        if (is_array($profile)) {
            foreach (['google','facebook'] as $provider) {
                if (empty($profile[$provider]) || !is_array($profile[$provider])) continue;
                foreach (['photo','image'] as $key) {
                    $url = trim((string)($profile[$provider][$key] ?? ''));
                    if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL) && in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http','https'], true)) {
                        return $url;
                    }
                }
            }
        }
        return base_url('assets/dashboard/images/profile/user-1.jpg');
    }
}
$header_orders = isset($header_orders) && is_array($header_orders) ? $header_orders : [];
$header_projects = isset($header_projects) && is_array($header_projects) ? $header_projects : [];
$header_stats = isset($header_stats) && is_array($header_stats) ? $header_stats : [];
$header_activity_count = min(9, count($header_orders) + count($header_projects));
$profile_image = dashboard_profile_image($user);
$branding = isset($branding) && is_array($branding) ? $branding : ['brand_name'=>'Vismrit Tech','logo_url'=>'','loader_url'=>''];
$brand_name = trim((string)($branding['brand_name'] ?? 'Vismrit Tech')) ?: 'Vismrit Tech';
$brand_logo = trim((string)($branding['logo_url'] ?? ''));
$brand_loader = trim((string)($branding['loader_url'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en" dir="ltr" data-bs-theme="light" data-color-theme="Blue_Theme" data-layout="vertical">
<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" type="image/png" href="https://cdn.vismrit.tech/cdn?p=logo/vismritsinhcoms-icon.jpg" />
  <script>
    try {
      const savedTheme = localStorage.getItem('vismrit-dashboard-theme');
      if (savedTheme === 'dark' || savedTheme === 'light') document.documentElement.setAttribute('data-bs-theme', savedTheme);
    } catch (e) {}
  </script>
  <link rel="stylesheet" href="<?=base_url('assets/dashboard/css/styles.css')?>" />
  <link rel="stylesheet" href="<?=base_url('assets/dashboard/css/business-ui.css')?>?v=24.0" />
  <?php if(($section ?? '')==='tools'): ?><link rel="stylesheet" href="<?=base_url('assets/dashboard/css/tools.css')?>?v=25.2" /><?php endif; ?>
  <title><?=html_escape($page_title)?> · <?=html_escape($brand_name)?></title>
  <style>
    .license-key-box{word-break:break-all}.project-update{border-left:3px solid #e52b3d;padding-left:1rem}.table>tbody>tr:last-child>td{border-bottom:0}.market-icon{width:46px;height:46px}.stat-icon{width:46px;height:46px}.body-wrapper .container-fluid{max-width:1540px}.cursor-pointer{cursor:pointer}.notification-message{max-width:240px}.topbar .content-dd{min-width:360px}.topbar .profile-dropdown{min-width:300px}
    @media(max-width:575.98px){.topbar .content-dd{min-width:calc(100vw - 28px);max-width:calc(100vw - 28px)}.topbar .profile-dropdown{min-width:280px}}
    @media(min-width:992px){
      a.navbar-toggler.d-lg-none[data-bs-target="#navbarNav"]{
        display:none!important;visibility:hidden!important;opacity:0!important;pointer-events:none!important
      }
    }
    @media(min-width:1200px){
      .topbar a.nav-link.nav-icon-hover-bg.rounded-circle.vt-sidebar-toggle{
        display:none!important;visibility:hidden!important;opacity:0!important;pointer-events:none!important
      }
    }
    /* Single dashboard sidebar. No MatDash mini sidebar / sidebarmenu offset. */
    #main-wrapper{
      min-height:100vh!important;
      position:relative!important;
      display:block!important;
    }

    .vt-dashboard-sidebar{
      position:fixed!important;
      inset:0 auto 0 0!important;
      left:0!important;
      top:0!important;
      width:280px!important;
      min-width:280px!important;
      max-width:280px!important;
      height:100vh!important;
      height:100dvh!important;
      margin:0!important;
      padding:0!important;
      transform:none!important;
      z-index:1050!important;
      overflow:hidden!important;
      background:var(--bs-body-bg)!important;
    }

    .vt-dashboard-sidebar-inner{
      position:relative!important;
      width:100%!important;
      height:100%!important;
      min-height:0!important;
      margin:0!important;
      padding:0!important;
      display:flex!important;
      flex-direction:column!important;
      overflow:hidden!important;
    }

    .vt-dashboard-sidebar .sidebar-nav{
      position:relative!important;
      inset:auto!important;
      left:auto!important;
      right:auto!important;
      top:auto!important;
      width:100%!important;
      flex:1 1 auto!important;
      min-height:0!important;
      height:100%!important;
      max-height:100%!important;
      margin:0!important;
      padding:0 0 28px 0!important;
      overflow-y:auto!important;
      overflow-x:hidden!important;
      overscroll-behavior:contain;
      -webkit-overflow-scrolling:touch;
      scrollbar-width:thin;
      box-sizing:border-box!important;
    }

    .vt-sidebar-nav-logo{
      width:100%!important;
      min-height:103px!important;
      margin:0!important;
      padding:8px 0!important;
      display:flex!important;
      align-items:center!important;
      justify-content:center!important;
      position:sticky!important;
      top:0!important;
      left:0!important;
      z-index:10!important;
      background:var(--bs-body-bg)!important;
      border:0!important;
      box-sizing:border-box!important;
    }

    .vt-sidebar-nav-logo-link{
      display:block!important;
      width:130px!important;
      height:87px!important;
      min-width:130px!important;
      min-height:87px!important;
      max-width:130px!important;
      max-height:87px!important;
      margin:0!important;
      padding:0!important;
      line-height:0!important;
      background:transparent!important;
      border:0!important;
      position:static!important;
      transform:none!important;
    }

    .vt-sidebar-nav-logo-img{
      display:block!important;
      width:130px!important;
      height:87px!important;
      min-width:130px!important;
      min-height:87px!important;
      max-width:130px!important;
      max-height:87px!important;
      margin:0!important;
      padding:0!important;
      object-fit:contain!important;
      object-position:center center!important;
      background:transparent!important;
      border:0!important;
      position:static!important;
      transform:none!important;
    }

    .vt-dashboard-sidebar .sidebar-menu{
      width:100%!important;
      margin:0!important;
      padding-top:0!important;
      padding-bottom:32px!important;
    }

    .page-wrapper{
      position:relative!important;
      width:calc(100% - 280px)!important;
      max-width:none!important;
      margin-left:280px!important;
      margin-inline-start:280px!important;
      padding-left:0!important;
      left:0!important;
      transform:none!important;
    }

    .vt-sidebar-backdrop{
      display:none;
    }

    @media(max-width:1199.98px){
      .vt-dashboard-sidebar{
        width:min(88vw,280px)!important;
        min-width:min(88vw,280px)!important;
        max-width:min(88vw,280px)!important;
        transform:translateX(-100%)!important;
        transition:transform .22s ease!important;
      }

      body.vt-sidebar-open .vt-dashboard-sidebar{
        transform:translateX(0)!important;
      }

      .page-wrapper{
        width:100%!important;
        margin-left:0!important;
        margin-inline-start:0!important;
      }

      .vt-sidebar-backdrop{
        display:block;
        position:fixed;
        inset:0;
        z-index:1040;
        background:rgba(0,0,0,.56);
        opacity:0;
        visibility:hidden;
        pointer-events:none;
        transition:.2s ease;
      }

      body.vt-sidebar-open .vt-sidebar-backdrop{
        opacity:1;
        visibility:visible;
        pointer-events:auto;
      }
    }
    @media print{.no-print,.side-mini-panel,.topbar,.customizer-btn,.customizer{display:none!important}.page-wrapper{margin-left:0!important}.body-wrapper{padding-top:0!important}.container-fluid{max-width:none!important}}
  </style>
</head>
<body class="neo-business-dashboard">
  <div class="preloader">
    <?php if($brand_loader!==''): ?>
      <img src="<?=html_escape($brand_loader)?>" alt="Loading" style="max-width:180px;max-height:110px;object-fit:contain">
    <?php else: ?>
      <div class="vismrit-preloader-mark" aria-label="<?=html_escape($brand_name)?>"><?=html_escape(strtoupper(substr($brand_name,0,1)))?></div>
    <?php endif; ?>
  </div>
  <div id="main-wrapper">
    <aside class="vt-dashboard-sidebar" id="vtDashboardSidebar">
      <div class="vt-dashboard-sidebar-inner">
            <nav class="sidebar-nav" id="dashboardSidebarNav">
              <div class="vt-sidebar-nav-logo">
                <a href="<?=base_url()?>" class="vt-sidebar-nav-logo-link" aria-label="<?=html_escape($brand_name)?> dashboard">
                  <?php if($brand_logo!==''): ?>
                    <img
                      class="vt-sidebar-nav-logo-img"
                      src="<?=html_escape($brand_logo)?>"
                      width="130"
                      height="87"
                      alt="<?=html_escape($brand_name)?>"
                    />
                  <?php else: ?>
                    <img
                      class="vt-sidebar-nav-logo-img"
                      src="https://cdn.vismrit.tech/cdn?p=logo/vismrittech.webp&width=130&height=87"
                      width="130"
                      height="87"
                      alt="<?=html_escape($brand_name)?>"
                    />
                  <?php endif; ?>
                </a>
              </div>
              <ul class="sidebar-menu" id="sidebarnav">
                <li class="nav-small-cap"><span class="hide-menu">Workspace</span></li>
                <li class="sidebar-item"><a class="sidebar-link <?=$active('dashboard')?>" href="<?=base_url()?>"><iconify-icon icon="solar:home-2-line-duotone"></iconify-icon><span class="hide-menu">Overview</span></a></li>
                <li class="sidebar-item"><a class="sidebar-link <?=$active('marketplace')?>" href="<?=base_url('marketplace')?>"><iconify-icon icon="solar:shop-2-line-duotone"></iconify-icon><span class="hide-menu">Marketplace</span></a></li>
                <li><span class="sidebar-divider"></span></li>
                <li class="nav-small-cap"><span class="hide-menu">Products & Services</span></li>
                <li class="sidebar-item"><a class="sidebar-link <?=$active('licenses')?>" href="<?=base_url('licenses')?>"><iconify-icon icon="solar:key-square-2-line-duotone"></iconify-icon><span class="hide-menu">My Licenses</span></a></li>
                <li class="sidebar-item"><a class="sidebar-link <?=$active('api')?>" href="<?=base_url('api-access')?>"><iconify-icon icon="solar:code-square-line-duotone"></iconify-icon><span class="hide-menu">API Access</span></a></li>
                <li class="sidebar-item"><a class="sidebar-link <?=$active('digital')?>" href="<?=base_url('digital-products')?>"><iconify-icon icon="solar:box-minimalistic-line-duotone"></iconify-icon><span class="hide-menu">My Digital Products</span></a></li>
                <li class="sidebar-item"><a class="sidebar-link <?=$active('projects')?>" href="<?=base_url('projects')?>"><iconify-icon icon="solar:case-round-line-duotone"></iconify-icon><span class="hide-menu">Projects</span></a></li>
                <li class="sidebar-item"><a class="sidebar-link <?=$active('orders')?>" href="<?=base_url('orders')?>"><iconify-icon icon="solar:bill-list-line-duotone"></iconify-icon><span class="hide-menu">Orders & Payments</span></a></li>
                <li><span class="sidebar-divider"></span></li>
                <li class="nav-small-cap"><span class="hide-menu">Tools</span></li>
                <li class="sidebar-item"><a class="sidebar-link <?=($section==='tools' && !in_array(($tool_page??''),['seo','vapt','app_operation'],true))?'active':''?>" href="<?=base_url('tools')?>"><iconify-icon icon="solar:widget-add-line-duotone"></iconify-icon><span class="hide-menu">Free Tools</span><span class="badge bg-success-subtle text-success ms-auto vt-tools-free-badge">Free</span></a></li>
                <li class="sidebar-item"><a class="sidebar-link <?=($section==='tools' && ($tool_page??'')==='vapt')?'active':''?>" href="<?=base_url('tools/vapt')?>"><iconify-icon icon="solar:shield-check-line-duotone"></iconify-icon><span class="hide-menu">VAPT</span><span class="badge bg-primary-subtle text-primary ms-auto">Security</span></a></li>
                <li class="sidebar-item"><a class="sidebar-link <?=($section==='tools' && ($tool_page??'')==='app_operation')?'active':''?>" href="<?=base_url('tools/app-operation')?>"><iconify-icon icon="solar:smartphone-2-line-duotone"></iconify-icon><span class="hide-menu">App Operation</span><span class="badge bg-danger-subtle text-danger ms-auto">Paid</span></a></li>
                <li class="sidebar-item"><a class="sidebar-link <?=($section==='tools' && ($tool_page??'')==='seo')?'active':''?>" href="<?=base_url('tools/seo')?>"><iconify-icon icon="solar:graph-up-line-duotone"></iconify-icon><span class="hide-menu">SEO Suite</span><span class="badge bg-danger-subtle text-danger ms-auto">Free + Pro</span></a></li>
                <li><span class="sidebar-divider"></span></li>
                <li class="nav-small-cap"><span class="hide-menu">Account</span></li>
                <li class="sidebar-item"><a class="sidebar-link <?=$active('account')?>" href="<?=base_url('account')?>"><iconify-icon icon="solar:user-id-line-duotone"></iconify-icon><span class="hide-menu">Account Settings</span></a></li>
                <li class="sidebar-item"><a class="sidebar-link" href="https://vismrit.tech"><iconify-icon icon="solar:global-line-duotone"></iconify-icon><span class="hide-menu">Vismrit.tech</span></a></li>
                <li class="sidebar-item"><a class="sidebar-link" href="https://auth.vismrit.tech/logout"><iconify-icon icon="solar:logout-2-line-duotone"></iconify-icon><span class="hide-menu">Sign Out</span></a></li>
              </ul>
            </nav>
      </div>
    </aside>

    <div class="page-wrapper">
      <header class="topbar">
        <div class="with-vertical">
          <nav class="navbar navbar-expand-lg p-0">
            <ul class="navbar-nav align-items-center">
              <li class="nav-item d-flex d-xl-none">
                <a class="nav-link nav-icon-hover-bg rounded-circle vt-sidebar-toggle" href="javascript:void(0)" aria-label="Toggle sidebar"><iconify-icon icon="solar:hamburger-menu-line-duotone" class="fs-6"></iconify-icon></a>
              </li>
              <li class="nav-item d-none d-md-flex align-items-center ms-2">
                <div><h6 class="mb-0 fw-semibold"><?=html_escape($page_title)?></h6><span class="fs-2 text-muted">Vismrit business workspace</span></div>
              </li>
            </ul>

            <div class="d-flex d-lg-none py-3 ms-2"><span class="fw-semibold"><?=html_escape($page_title)?></span></div>
            <a class="navbar-toggler p-0 border-0 nav-icon-hover-bg rounded-circle ms-auto d-lg-none" href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle top navigation">
              <iconify-icon icon="solar:menu-dots-bold-duotone" class="fs-6"></iconify-icon>
            </a>

            <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
              <div class="d-flex align-items-center justify-content-end ms-auto">
                <ul class="navbar-nav flex-row ms-auto align-items-center vt-topbar-actions" aria-label="Dashboard controls">

                  <!-- Theme: one icon only. Light = moon, dark = sun. -->
                  <li class="nav-item vt-topbar-item">
                    <a class="nav-link vt-header-btn" href="javascript:void(0)" id="dashboardThemeToggle" aria-label="Switch to dark mode" title="Dark mode">
                      <iconify-icon id="dashboardThemeIcon" icon="solar:moon-line-duotone" class="fs-6" aria-hidden="true"></iconify-icon>
                    </a>
                  </li>

                  <!-- Appearance settings -->
                  <li class="nav-item vt-topbar-item">
                    <a class="nav-link vt-header-btn" href="javascript:void(0)" id="dashboardSettingsButton" data-bs-toggle="offcanvas" data-bs-target="#vismritCustomizer" aria-controls="vismritCustomizer" aria-label="Appearance settings" title="Appearance settings">
                      <iconify-icon icon="solar:settings-linear" class="fs-6" aria-hidden="true"></iconify-icon>
                    </a>
                  </li>

                  <!-- Notifications -->
                  <li class="nav-item dropdown vt-topbar-item">
                    <a class="nav-link vt-header-btn position-relative" href="javascript:void(0)" id="dashboardNotifications" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Recent activity" title="Recent activity">
                      <iconify-icon icon="solar:bell-bing-line-duotone" class="fs-6" aria-hidden="true"></iconify-icon>
                      <?php if($header_activity_count > 0): ?><span class="notification-dot" aria-hidden="true"></span><?php endif; ?>
                    </a>
                    <div class="dropdown-menu content-dd dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="dashboardNotifications">
                      <div class="d-flex align-items-center justify-content-between py-3 px-4 border-bottom">
                        <div><h5 class="mb-0 fs-5 fw-semibold">Recent activity</h5><small class="text-muted">Orders and project updates</small></div>
                        <span class="badge bg-primary-subtle text-primary"><?=number_format($header_activity_count)?></span>
                      </div>
                      <div class="message-body p-2" data-simplebar style="max-height:350px">
                        <?php foreach(array_slice($header_orders,0,3) as $activityOrder): ?>
                          <a href="<?=base_url('orders/'.(int)$activityOrder['id'])?>" class="py-3 px-3 d-flex align-items-center dropdown-item gap-3">
                            <span class="flex-shrink-0 bg-primary-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-primary"><iconify-icon icon="solar:bill-list-line-duotone"></iconify-icon></span>
                            <div class="w-75 notification-message"><div class="d-flex align-items-center justify-content-between gap-2"><h6 class="mb-1 fw-semibold text-truncate"><?=html_escape($activityOrder['title'])?></h6><span class="badge <?=dashboard_badge_class($activityOrder['payment_status'])?>"><?=html_escape(ucwords(str_replace('_',' ',$activityOrder['payment_status'])))?></span></div><span class="d-block text-truncate fs-2 text-muted"><?=html_escape($activityOrder['order_no'])?> · <?=date('d M',strtotime($activityOrder['created_at']))?></span></div>
                          </a>
                        <?php endforeach; ?>
                        <?php foreach(array_slice($header_projects,0,2) as $activityProject): ?>
                          <a href="<?=base_url('projects/'.(int)$activityProject['id'])?>" class="py-3 px-3 d-flex align-items-center dropdown-item gap-3">
                            <span class="flex-shrink-0 bg-success-subtle rounded-circle round d-flex align-items-center justify-content-center fs-6 text-success"><iconify-icon icon="solar:case-round-line-duotone"></iconify-icon></span>
                            <div class="w-75 notification-message"><div class="d-flex align-items-center justify-content-between gap-2"><h6 class="mb-1 fw-semibold text-truncate"><?=html_escape($activityProject['title'])?></h6><span class="badge <?=dashboard_badge_class($activityProject['status'])?>"><?=html_escape(ucwords(str_replace('_',' ',$activityProject['status'])))?></span></div><span class="d-block text-truncate fs-2 text-muted"><?=html_escape($activityProject['project_no'])?> · <?=intval($activityProject['progress'])?>% complete</span></div>
                          </a>
                        <?php endforeach; ?>
                        <?php if(!$header_orders && !$header_projects): ?>
                          <div class="text-center py-5 px-3"><iconify-icon icon="solar:bell-off-line-duotone" class="fs-9 text-muted"></iconify-icon><p class="text-muted mt-2 mb-0">No recent activity yet.</p></div>
                        <?php endif; ?>
                      </div>
                      <div class="p-3 border-top d-grid"><a class="btn btn-light" href="<?=base_url('orders')?>">View orders</a></div>
                    </div>
                  </li>

                  <!-- Language -->
                  <li class="nav-item dropdown vt-topbar-item vt-language-item">
                    <a class="nav-link vt-header-btn" href="javascript:void(0)" id="dashboardLanguage" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Language" title="Language">
                      <img src="<?=base_url('assets/dashboard/images/flag/icon-flag-en.svg')?>" width="20" height="20" alt="English" class="rounded-circle object-fit-cover" style="width:20px!important;height:20px!important;max-width:20px!important;" />
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-animate-up p-2" aria-labelledby="dashboardLanguage" style="min-width:210px">
                      <div class="px-2 py-2"><small class="text-muted fw-semibold text-uppercase">Interface language</small></div>
                      <a href="javascript:void(0)" class="dropdown-item d-flex align-items-center gap-2 active"><img src="<?=base_url('assets/dashboard/images/flag/icon-flag-en.svg')?>" width="22" height="22" class="rounded-circle object-fit-cover" alt="English"> English <iconify-icon icon="solar:check-circle-bold" class="ms-auto"></iconify-icon></a>
                      <div class="dropdown-divider"></div>
                      <span class="dropdown-item d-flex align-items-center gap-2 text-muted"><img src="<?=base_url('assets/dashboard/fonts/flag-icon-css/flags/in.svg')?>" width="22" height="16" alt="India"> Hindi <small class="ms-auto">Soon</small></span>
                    </div>
                  </li>

                  <!-- Profile: use the original MatDash profile structure to avoid layout conflicts. -->
                  <li class="nav-item dropdown vt-profile-item">
                    <a class="nav-link vt-profile-link" href="javascript:void(0)" id="dashboardProfile" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Open account menu">
                      <div class="d-flex align-items-center gap-2 lh-base">
                        <img src="<?=html_escape($profile_image)?>" class="rounded-circle vt-profile-avatar" width="36" height="36" alt="<?=html_escape($user['name'])?>" style="width:36px!important;height:36px!important;min-width:36px!important;max-width:36px!important;object-fit:cover!important;flex:0 0 36px!important;" />
                        <iconify-icon icon="solar:alt-arrow-down-bold" class="fs-2 vt-profile-chevron"></iconify-icon>
                      </div>
                    </a>
                    <div class="dropdown-menu profile-dropdown dropdown-menu-end dropdown-menu-animate-up" aria-labelledby="dashboardProfile">
                      <div class="position-relative px-4 pt-3 pb-2">
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom gap-3">
                          <img src="<?=html_escape($profile_image)?>" class="rounded-circle" width="56" height="56" alt="<?=html_escape($user['name'])?>" style="width:56px!important;height:56px!important;min-width:56px!important;max-width:56px!important;object-fit:cover!important;" />
                          <div class="min-w-0"><h6 class="mb-1 fw-semibold text-truncate"><?=html_escape($user['name'])?></h6><p class="mb-0 fs-2 text-muted text-truncate"><?=html_escape($user['email'])?></p></div>
                        </div>
                        <div class="message-body">
                          <a href="<?=base_url('account')?>" class="p-2 dropdown-item h6 rounded-1 d-flex align-items-center gap-2"><iconify-icon icon="solar:user-id-line-duotone"></iconify-icon>Account Settings</a>
                          <a href="<?=base_url('orders')?>" class="p-2 dropdown-item h6 rounded-1 d-flex align-items-center gap-2"><iconify-icon icon="solar:bill-list-line-duotone"></iconify-icon>Orders & Payments</a>
                          <a href="<?=base_url('marketplace')?>" class="p-2 dropdown-item h6 rounded-1 d-flex align-items-center gap-2"><iconify-icon icon="solar:shop-2-line-duotone"></iconify-icon>Marketplace</a>
                          <a href="https://auth.vismrit.tech/logout" class="p-2 dropdown-item h6 rounded-1 d-flex align-items-center gap-2 text-danger"><iconify-icon icon="solar:logout-2-line-duotone"></iconify-icon>Sign Out</a>
                        </div>
                      </div>
                    </div>
                  </li>
                </ul>
              </div>
            </div>
          </nav>
        </div>
      </header>

      <div class="body-wrapper">
        <div class="container-fluid">
          <?php if($this->session->flashdata('success')): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><div class="d-flex align-items-center gap-2"><iconify-icon icon="solar:check-circle-bold-duotone" class="fs-5"></iconify-icon><div><?=html_escape($this->session->flashdata('success'))?></div></div><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
          <?php if($this->session->flashdata('error')): ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><div class="d-flex align-items-center gap-2"><iconify-icon icon="solar:danger-triangle-bold-duotone" class="fs-5"></iconify-icon><div><?=html_escape($this->session->flashdata('error'))?></div></div><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
