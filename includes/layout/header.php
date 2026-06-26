<?php
// Ortak Header Dosyası
if (!isset($page_title)) {
    $page_title = __('app_name');
}

if (!isset($page_description)) {
    $page_description = '';
}

$current_lang = getCurrentLanguage();
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(csrfToken(), ENT_QUOTES) ?>">
    <title><?= $page_title ?> - <?= __('app_name') ?></title>
    <?php if ($page_description): ?>
    <meta name="description" content="<?= htmlspecialchars($page_description) ?>">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?php echo $base_url; ?>assets/img/favicon.svg">
    <link rel="alternate icon" href="<?php echo $base_url; ?>assets/img/favicon.svg">
    <link rel="apple-touch-icon" href="<?php echo $base_url; ?>assets/img/favicon.svg">
    
    <link href="<?php echo $base_url; ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/header.css">
    <base href="<?= $base_url ?>">
    <script>const base_url = "<?php echo $base_url; ?>";</script>
    <script>
    // CSRF: aynı kaynağa giden tüm durum değiştiren fetch isteklerine
    // otomatik olarak X-CSRF-Token başlığını ekler.
    (function () {
        var meta = document.querySelector('meta[name="csrf-token"]');
        var token = meta ? meta.getAttribute('content') : '';
        if (!token || !window.fetch) return;
        var origFetch = window.fetch;
        window.fetch = function (input, init) {
            init = init || {};
            var method = (init.method ||
                (typeof input === 'object' && input && input.method) || 'GET').toUpperCase();
            var url = (typeof input === 'string') ? input :
                ((input && input.url) || '');
            var isAbsolute = /^https?:\/\//i.test(url);
            var sameOrigin = !isAbsolute || url.indexOf(window.location.origin) === 0;
            if (method !== 'GET' && method !== 'HEAD' && sameOrigin) {
                var headers = new Headers(init.headers ||
                    (typeof input === 'object' && input ? input.headers : null) || {});
                if (!headers.has('X-CSRF-Token')) {
                    headers.set('X-CSRF-Token', token);
                }
                init.headers = headers;
            }
            return origFetch.call(this, input, init);
        };
    })();
    </script>
</head>
<body>
    <!-- Tema Değiştirme Butonu -->
    <button class="theme-toggle" onclick="toggleTheme()" title="Tema Değiştir" aria-label="Açık/koyu tema değiştir">
        <i class="fas fa-moon" id="themeIcon" aria-hidden="true"></i>
    </button>

    <div class="main-container">
        <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark" aria-label="<?= __('main_navigation') ?? 'Ana menü' ?>">
            <div class="container">
                <a class="navbar-brand" href="<?= $base_url ?>dashboard">
                    <i class="fas fa-heartbeat" aria-hidden="true"></i> <?= __('app_name') ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                        aria-controls="navbarNav" aria-expanded="false" aria-label="<?= __('toggle_menu') ?? 'Menüyü aç/kapat' ?>">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <?php $is_dash = (basename($_SERVER['PHP_SELF']) == 'index.php' && strpos($_SERVER['REQUEST_URI'], '/dashboard') !== false); ?>
                            <a class="nav-link <?= $is_dash ? 'active' : '' ?>" href="<?= $base_url ?>dashboard" <?= $is_dash ? 'aria-current="page"' : '' ?>>
                                <i class="fas fa-tachometer-alt" aria-hidden="true"></i> <?= __('dashboard') ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <?php $is_sites = strpos($_SERVER['REQUEST_URI'], '/sites') !== false; ?>
                            <a class="nav-link <?= $is_sites ? 'active' : '' ?>" href="<?= $base_url ?>sites" <?= $is_sites ? 'aria-current="page"' : '' ?>>
                                <i class="fas fa-globe" aria-hidden="true"></i> <?= __('my_sites') ?>
                            </a>
                        </li>
                        <?php if (isAdmin()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle <?= strpos($_SERVER['REQUEST_URI'], '/admin') !== false ? 'active' : '' ?>" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-cog"></i> <?= __('admin_panel') ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?= $base_url ?>admin"><i class="fas fa-cogs"></i> <?= __('admin_dashboard') ?></a></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>admin/users"><i class="fas fa-users"></i> <?= __('users') ?></a></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>admin/groups"><i class="fas fa-users"></i> <?= __('group_management') ?></a></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>admin/site_logs"><i class="fas fa-list-alt"></i> Tüm Site Logları</a></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>admin/monitor"><i class="fas fa-server"></i> <?= __('monitor_status') ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>admin/notifications"><i class="fas fa-bell"></i> Bildirim Ayarları</a></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>settings/mail"><i class="fas fa-envelope"></i> <?= __('mail_settings') ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= $base_url ?>docs/api"><i class="fas fa-book"></i> API Kılavuzu</a></li>
                            </ul>
                        </li>
                        <?php endif; ?>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user<?= isAdmin() ? '-shield' : '' ?>"></i> <?= htmlspecialchars($_SESSION['user_email']) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= $base_url ?>settings/change_password"><i class="fas fa-lock"></i> <?= __('change_password') ?></a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="logout()"><i class="fas fa-sign-out-alt"></i> <?= __('logout') ?></a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <div class="language-selector">
                                <?= getLanguageSelector() ?>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <?php endif; ?>
