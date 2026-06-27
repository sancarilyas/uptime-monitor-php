<?php
/**
 * Public Status Sayfası — giriş gerektirmez.
 * Yalnızca is_public = 1 olan aktif siteleri gösterir.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/language.php';

$app_name = __('app_name') ?: 'Uptime Monitor';
$sites = getPublicSites();

// Genel durum hesapla
$total = count($sites);
$down = 0;
foreach ($sites as $s) {
    if (($s['last_status'] ?? 'up') === 'down') {
        $down++;
    }
}

if ($total === 0) {
    $overall = ['key' => 'none', 'class' => 'neutral', 'icon' => 'fa-circle-info', 'text' => 'Henüz yayınlanmış servis yok'];
} elseif ($down === 0) {
    $overall = ['key' => 'ok', 'class' => 'ok', 'icon' => 'fa-circle-check', 'text' => 'Tüm sistemler çalışıyor'];
} elseif ($down >= ceil($total / 2)) {
    $overall = ['key' => 'major', 'class' => 'major', 'icon' => 'fa-triangle-exclamation', 'text' => 'Büyük çaplı kesinti yaşanıyor'];
} else {
    $overall = ['key' => 'partial', 'class' => 'partial', 'icon' => 'fa-circle-exclamation', 'text' => 'Bazı servislerde kesinti var'];
}

/** Günlük uptime yüzdesine göre bar seviyesi */
function stripLevel($pct) {
    if ($pct === null) return 'none';
    if ($pct >= 99.5) return 'ok';
    if ($pct >= 90)   return 'warn';
    return 'down';
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($current_lang ?? 'tr') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Durumu · <?= htmlspecialchars($app_name) ?></title>
    <meta name="description" content="<?= htmlspecialchars($app_name) ?> servislerinin canlı çalışma durumu.">
    <meta name="robots" content="noindex">
    <meta http-equiv="refresh" content="60">
    <link rel="icon" type="image/svg+xml" href="<?= $base_url ?>assets/img/favicon.svg">
    <link href="<?= $base_url ?>assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/header.css">
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/status.css">
    <base href="<?= $base_url ?>">
    <script>
        // Tema yansımasını boyamadan önce uygula (flash önleme)
        (function () {
            try {
                if (localStorage.getItem('theme') === 'dark') {
                    document.documentElement.classList.add('preload-dark');
                }
            } catch (e) {}
        })();
    </script>
</head>
<body class="status-page">
    <header class="status-topbar">
        <div class="status-container status-topbar-inner">
            <div class="status-brand">
                <i class="fas fa-heartbeat"></i>
                <span><?= htmlspecialchars($app_name) ?></span>
            </div>
            <div class="status-topbar-actions">
                <button type="button" class="status-theme-toggle" id="statusThemeToggle" aria-label="Karanlık moda geç">
                    <i class="fas fa-moon"></i>
                </button>
                <a class="status-login-link" href="<?= $base_url ?>login">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                    <span>Panele Giriş</span>
                </a>
            </div>
        </div>
    </header>

    <main class="status-container status-main">
        <!-- Genel durum bandı -->
        <section class="status-hero status-hero-<?= $overall['class'] ?>" role="status" aria-live="polite">
            <div class="status-hero-icon">
                <i class="fas <?= $overall['icon'] ?>"></i>
            </div>
            <div class="status-hero-text">
                <h1><?= htmlspecialchars($overall['text']) ?></h1>
                <p>Son güncelleme:
                    <time datetime="<?= date('c') ?>"><?= date('d.m.Y H:i') ?></time>
                    · <span class="status-auto-refresh"><i class="fas fa-rotate"></i> her 60 sn'de yenilenir</span>
                </p>
            </div>
            <?php if ($total > 0): ?>
            <div class="status-hero-count">
                <span class="status-count-up"><?= $total - $down ?></span> / <?= $total ?>
                <small>servis çalışıyor</small>
            </div>
            <?php endif; ?>
        </section>

        <?php if ($total === 0): ?>
            <!-- Boş durum -->
            <div class="status-empty">
                <i class="fas fa-globe"></i>
                <h2>Yayınlanmış servis yok</h2>
                <p>Bir servisi burada göstermek için yönetim panelinde ilgili sitenin
                   ayarlarından <strong>“Public status sayfasında göster”</strong> seçeneğini etkinleştirin.</p>
            </div>
        <?php else: ?>
            <!-- PERFORMANS: 1/7/30 günlük uptime'ı tüm siteler için 3 sorguda (N*3 yerine 3) hesapla -->
            <?php
            $status_site_ids = array_column($sites, 'id');
            $uptime_map_1  = calculateUptimeForSites($status_site_ids, 1);
            $uptime_map_7  = calculateUptimeForSites($status_site_ids, 7);
            $uptime_map_30 = calculateUptimeForSites($status_site_ids, 30);
            ?>
            <!-- Servis listesi -->
            <section class="status-list" aria-label="Servis durumları">
                <?php foreach ($sites as $site): ?>
                    <?php
                    $is_up   = ($site['last_status'] ?? 'up') !== 'down';
                    $u24 = $uptime_map_1[$site['id']] ?? 0.0;
                    $u7  = $uptime_map_7[$site['id']] ?? 0.0;
                    $u30 = $uptime_map_30[$site['id']] ?? 0.0;
                    $strip = getDailyUptimeStrip($site['id'], 90);
                    $host = parse_url($site['url'], PHP_URL_HOST) ?: $site['url'];
                    ?>
                    <article class="status-item">
                        <div class="status-item-head">
                            <div class="status-item-name">
                                <span class="status-dot status-dot-<?= $is_up ? 'up' : 'down' ?>"
                                      aria-hidden="true"></span>
                                <div>
                                    <h3><?= htmlspecialchars($site['name']) ?></h3>
                                    <span class="status-item-host"><?= htmlspecialchars($host) ?></span>
                                </div>
                            </div>
                            <div class="status-item-badge">
                                <span class="status-pill status-pill-<?= $is_up ? 'up' : 'down' ?>">
                                    <i class="fas <?= $is_up ? 'fa-check' : 'fa-xmark' ?>"></i>
                                    <?= $is_up ? 'Çalışıyor' : 'Kesinti' ?>
                                </span>
                                <span class="status-uptime-30"><?= number_format($u30, 2) ?>%
                                    <small>30g</small></span>
                            </div>
                        </div>

                        <!-- 90 günlük şerit -->
                        <div class="status-strip" role="img"
                             aria-label="Son 90 günlük çalışma geçmişi: %<?= number_format($u30, 1) ?>">
                            <?php foreach ($strip as $day => $pct): ?>
                                <span class="status-bar status-bar-<?= stripLevel($pct) ?>"
                                      title="<?= date('d.m.Y', strtotime($day)) ?> — <?= $pct === null ? 'veri yok' : '%' . number_format($pct, 1) ?>"></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="status-strip-legend">
                            <span>90 gün önce</span>
                            <span class="status-metrics">
                                <span title="Son 24 saat">24s: <strong><?= number_format($u24, 1) ?>%</strong></span>
                                <span title="Son 7 gün">7g: <strong><?= number_format($u7, 1) ?>%</strong></span>
                            </span>
                            <span>bugün</span>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>

        <footer class="status-footer">
            <span><i class="fas fa-shield-halved"></i> <?= htmlspecialchars($app_name) ?> tarafından izleniyor</span>
        </footer>
    </main>

    <script>
        // Tema geçişi (uygulamanın geri kalanıyla aynı localStorage anahtarı: 'theme')
        (function () {
            var body = document.body;
            var html = document.documentElement;
            var btn = document.getElementById('statusThemeToggle');
            html.classList.remove('preload-dark');

            function apply(theme) {
                var dark = theme === 'dark';
                body.classList.toggle('dark-mode', dark);
                btn.querySelector('i').className = dark ? 'fas fa-sun' : 'fas fa-moon';
                btn.setAttribute('aria-label', dark ? 'Aydınlık moda geç' : 'Karanlık moda geç');
            }

            var saved = 'light';
            try { saved = localStorage.getItem('theme') || 'light'; } catch (e) {}
            apply(saved);

            btn.addEventListener('click', function () {
                var next = body.classList.contains('dark-mode') ? 'light' : 'dark';
                try { localStorage.setItem('theme', next); } catch (e) {}
                apply(next);
            });
        })();
    </script>
</body>
</html>
