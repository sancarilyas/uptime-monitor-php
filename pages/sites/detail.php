<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/language.php';

requireLogin();

// Site ID'sini al
$site_id = $_GET['id'] ?? 0;

if (!$site_id) {
    header('Location: ' . $base_url . 'dashboard');
    exit;
}

// Kullanıcının grup bilgisini al
$user_group_id = null;
$stmt = $pdo->prepare("SELECT group_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if ($user) {
    $user_group_id = $user['group_id'];
}

// Site bilgilerini al - Grup bazlı erişim kontrolü
$stmt = $pdo->prepare("
    SELECT s.*, 
           g.name as group_name
    FROM sites s 
    LEFT JOIN `groups` g ON s.group_id = g.id
    WHERE s.id = ? AND (s.user_id = ? OR (s.group_id IS NOT NULL AND s.group_id = ?))
");
$stmt->execute([$site_id, $_SESSION['user_id'], $user_group_id]);
$site = $stmt->fetch();

if (!$site) {
    header('Location: ' . $base_url . 'dashboard');
    exit;
}

// Zaman aralığı filtresi
$time_range = $_GET['range'] ?? '24h';

// Sayfalama ayarları
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 15;
$per_page = in_array($per_page, [15, 30, 50, 100]) ? $per_page : 15; // Sadece 15, 30, 50, 100 kabul et
$offset = ($page - 1) * $per_page;

switch ($time_range) {
    case '1h':
        $hours = 1;
        $interval_text = 'Son 1 Saat';
        break;
    case '6h':
        $hours = 6;
        $interval_text = 'Son 6 Saat';
        break;
    case '24h':
        $hours = 24;
        $interval_text = 'Son 24 Saat';
        break;
    case '7d':
        $hours = 24 * 7;
        $interval_text = 'Son 7 Gün';
        break;
    case '30d':
        $hours = 24 * 30;
        $interval_text = 'Son 30 Gün';
        break;
    default:
        $hours = 24;
        $interval_text = 'Son 24 Saat';
}

// Toplam log sayısını al
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM uptime_logs 
    WHERE site_id = ? 
    AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
");
$stmt->execute([$site_id, $hours]);
$total_logs = $stmt->fetch()['total'];
$total_pages = ceil($total_logs / $per_page);

// Sayfalanmış logları al
$stmt = $pdo->prepare("
    SELECT 
        status,
        response_time,
        timestamp
    FROM uptime_logs 
    WHERE site_id = :site_id 
    AND timestamp >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
    ORDER BY timestamp DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':site_id', $site_id, PDO::PARAM_INT);
$stmt->bindValue(':hours', $hours, PDO::PARAM_INT);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// İstatistikler (TÜM verilerden hesapla, sadece sayfadakilerden değil)
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_checks,
        SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as up_count,
        SUM(CASE WHEN status = 'down' THEN 1 ELSE 0 END) as down_count,
        AVG(CASE WHEN status = 'up' THEN response_time ELSE NULL END) as avg_response_time
    FROM uptime_logs 
    WHERE site_id = ? 
    AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
");
$stmt->execute([$site_id, $hours]);
$stats = $stmt->fetch();

$total_checks = $stats['total_checks'] ?: 0;
$up_count = $stats['up_count'] ?: 0;
$down_count = $stats['down_count'] ?: 0;
$avg_response_time = $stats['avg_response_time'] ? round($stats['avg_response_time']) : 0;
$uptime_percent = $total_checks > 0 ? ($up_count / $total_checks) * 100 : 0;

// Kesinti periyotları hesaplama (DÜZELTME: En eski log'dan başla)
$downtime_periods = [];
$current_downtime = null;

// Logları eskiden yeniye sırala (reverse)
$sorted_logs = array_reverse($logs);

foreach ($sorted_logs as $log) {
    if ($log['status'] === 'down') {
        if ($current_downtime === null) {
            // Yeni kesinti başladı
            $current_downtime = [
                'start' => $log['timestamp'],
                'end' => $log['timestamp'],
                'duration' => 0
            ];
        } else {
            // Kesinti devam ediyor
            $current_downtime['end'] = $log['timestamp'];
        }
    } else {
        // UP durumu - eğer kesinti varsa bitir
        if ($current_downtime !== null && $current_downtime['start']) {
            $start_time = strtotime($current_downtime['start']);
            $end_time = $current_downtime['end'] ? strtotime($current_downtime['end']) : time();
            $current_downtime['duration'] = $end_time - $start_time;
            $downtime_periods[] = $current_downtime;
            $current_downtime = null;
        }
    }
}

// Eğer hala kesinti devam ediyorsa VE son durum DOWN ise
if ($current_downtime !== null && $site['last_status'] === 'down' && $current_downtime['start']) {
    $start_time = strtotime($current_downtime['start']);
    $end_time = time(); // Şu anki zaman
    $current_downtime['duration'] = $end_time - $start_time;
    $current_downtime['ongoing'] = true;
    $downtime_periods[] = $current_downtime;
}

// Toplam kesinti süresini hesapla
$total_downtime_seconds = 0;
foreach ($downtime_periods as $period) {
    $total_downtime_seconds += $period['duration'];
}

// Kesinti süresini formatlama fonksiyonu
function formatDowntime($seconds) {
    if ($seconds == 0) {
        return '0 saniye';
    }
    
    $days = floor($seconds / 86400);
    $hours = floor(($seconds % 86400) / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;
    
    $parts = [];
    if ($days > 0) $parts[] = $days . ' gün';
    if ($hours > 0) $parts[] = $hours . ' saat';
    if ($minutes > 0) $parts[] = $minutes . ' dk';
    if ($secs > 0 && $days == 0) $parts[] = $secs . ' sn'; // Günler varsa saniye gösterme
    
    return implode(' ', $parts);
}

$formatted_downtime = formatDowntime($total_downtime_seconds);

$page_title = $site['name'] . ' - Detay';
$page_description = $site['url'] . ' için detaylı izleme verileri';

include __DIR__ . '/../../includes/layout/header.php';
?>

<style>
/* Sayfalama boyutunu küçült */
#fullPagination .page-link {
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    line-height: 1.25;
}

#fullPagination .page-item {
    margin: 0 0.125rem;
}

/* Sayfa numaraları için daha küçük boyut */
#fullPagination .page-link:not(.disabled) {
    min-width: 2rem;
    text-align: center;
}

/* Dark mode için site detay sayfası */
body.dark-mode .card {
    background: rgba(45, 55, 72, 0.95) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    color: #e2e8f0 !important;
}

body.dark-mode .card h3,
body.dark-mode .card h4,
body.dark-mode .card h5,
body.dark-mode .card h6 {
    color: #e2e8f0 !important;
}

body.dark-mode .card p,
body.dark-mode .card .text-muted {
    color: #a0aec0 !important;
}

body.dark-mode .card a {
    color: #60a5fa !important;
}

body.dark-mode .card a:hover {
    color: #93c5fd !important;
}

body.dark-mode .card code {
    background: rgba(30, 41, 59, 0.8) !important;
    color: #e2e8f0 !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
}

body.dark-mode .card .badge {
    color: #ffffff !important;
}

body.dark-mode .card .text-success {
    color: #10b981 !important;
}

body.dark-mode .card .text-danger {
    color: #ef4444 !important;
}

/* Dark mode için butonlar */
body.dark-mode .btn-outline-secondary {
    color: #e2e8f0 !important;
    border-color: rgba(255, 255, 255, 0.3) !important;
}

body.dark-mode .btn-outline-secondary:hover {
    background: rgba(255, 255, 255, 0.1) !important;
    border-color: rgba(255, 255, 255, 0.5) !important;
    color: #e2e8f0 !important;
}

body.dark-mode .btn-outline-primary {
    color: #60a5fa !important;
    border-color: #60a5fa !important;
}

body.dark-mode .btn-outline-primary:hover {
    background: #60a5fa !important;
    border-color: #60a5fa !important;
    color: #ffffff !important;
}

body.dark-mode .btn-outline-danger {
    color: #ef4444 !important;
    border-color: #ef4444 !important;
}

body.dark-mode .btn-outline-danger:hover {
    background: #ef4444 !important;
    border-color: #ef4444 !important;
    color: #ffffff !important;
}

/* Dark mode için tablo */
body.dark-mode .table {
    color: #e2e8f0 !important;
}

body.dark-mode .table th {
    background: rgba(30, 41, 59, 0.8) !important;
    color: #e2e8f0 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

body.dark-mode .table td {
    background: rgba(45, 55, 72, 0.95) !important;
    color: #e2e8f0 !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
}

body.dark-mode .table tbody tr:hover td {
    background: rgba(55, 65, 82, 0.95) !important;
}

/* Dark mode için sayfalama */
body.dark-mode .pagination .page-link {
    background: rgba(45, 55, 72, 0.95) !important;
    border-color: rgba(255, 255, 255, 0.1) !important;
    color: #e2e8f0 !important;
}

body.dark-mode .pagination .page-link:hover {
    background: rgba(55, 65, 82, 0.95) !important;
    border-color: rgba(255, 255, 255, 0.2) !important;
    color: #e2e8f0 !important;
}

body.dark-mode .pagination .page-item.active .page-link {
    background: #3b82f6 !important;
    border-color: #3b82f6 !important;
    color: #ffffff !important;
}

/* Dark mode için form elemanları */
body.dark-mode .form-select {
    background: rgba(30, 41, 59, 0.8) !important;
    border: 1px solid rgba(255, 255, 255, 0.2) !important;
    color: #e2e8f0 !important;
}

body.dark-mode .form-select:focus {
    background: rgba(30, 41, 59, 0.9) !important;
    border-color: #60a5fa !important;
    color: #e2e8f0 !important;
}
</style>

<div class="container-fluid p-3">
    <!-- Geri Dön Butonu -->
    <div class="mb-3">
        <a href="<?= $base_url ?>dashboard" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Dashboard'a Dön
        </a>
    </div>

    <!-- Site Başlık Kartı -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h3 class="mb-2">
                        <i class="fas fa-globe"></i> <?= htmlspecialchars($site['name']) ?>
                    </h3>
                    <p class="mb-1">
                        <i class="fas fa-link"></i> 
                        <a href="<?= htmlspecialchars($site['url']) ?>" target="_blank" class="text-decoration-none">
                            <?= htmlspecialchars($site['url']) ?>
                        </a>
                    </p>
                    <?php if (!empty($site['monitor_path'])): ?>
                        <p class="mb-1">
                            <i class="fas fa-route"></i> 
                            <strong>Monitor Yolu:</strong> 
                            <code><?= htmlspecialchars($site['monitor_path']) ?></code>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-external-link-alt"></i> 
                            <strong>Tam URL:</strong> 
                            <a href="<?= htmlspecialchars(rtrim($site['url'], '/') . '/' . ltrim($site['monitor_path'], '/')) ?>" target="_blank" class="text-decoration-none">
                                <?= htmlspecialchars(rtrim($site['url'], '/') . '/' . ltrim($site['monitor_path'], '/')) ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    <?php if (!empty($site['description'])): ?>
                        <p class="text-muted mb-0">
                            <i class="fas fa-info-circle"></i> <?= htmlspecialchars($site['description']) ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <div class="d-flex flex-column align-items-md-end">
                        <span class="badge bg-<?= $site['last_status'] === 'up' ? 'success' : 'danger' ?> mb-2" style="font-size: 1rem; padding: 0.5rem 1rem;">
                            <i class="fas fa-circle"></i> 
                            <?= $site['last_status'] === 'up' ? 'Çalışıyor' : 'Kesinti' ?>
                        </span>
                        <small class="text-muted">
                            Son Kontrol: <?= $site['last_check'] ? date('d.m.Y H:i:s', strtotime($site['last_check'])) : 'Henüz kontrol edilmedi' ?>
                        </small>
                        <?php if ($site['last_status'] === 'up'): ?>
                            <small class="text-success">
                                Yanıt Süresi: <?= $site['last_response_time'] ?>ms
                            </small>
                        <?php endif; ?>
                        
                        <!-- Düzenleme Butonları -->
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-warning btn-sm me-2" onclick="manualCheck(<?= $site['id'] ?>, '<?= htmlspecialchars($site['name']) ?>')" id="manualCheckBtn">
                                <i class="fas fa-sync-alt"></i> Manuel Tetikleme
                            </button>
                            <a href="<?= $base_url ?>sites/edit?id=<?= $site['id'] ?>" class="btn btn-outline-primary btn-sm me-2">
                                <i class="fas fa-edit"></i> Düzenle
                            </a>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteSite(<?= $site['id'] ?>, '<?= htmlspecialchars($site['name']) ?>')">
                                <i class="fas fa-trash"></i> Sil
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Zaman Aralığı Filtreleri -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group" role="group">
                <a href="<?= $base_url ?>sites/detail?id=<?= $site_id ?>&range=1h" class="btn btn-<?= $time_range === '1h' ? 'primary' : 'outline-primary' ?> btn-sm">
                    Son 1 Saat
                </a>
                <a href="<?= $base_url ?>sites/detail?id=<?= $site_id ?>&range=6h" class="btn btn-<?= $time_range === '6h' ? 'primary' : 'outline-primary' ?> btn-sm">
                    Son 6 Saat
                </a>
                <a href="<?= $base_url ?>sites/detail?id=<?= $site_id ?>&range=24h" class="btn btn-<?= $time_range === '24h' ? 'primary' : 'outline-primary' ?> btn-sm">
                    Son 24 Saat
                </a>
                <a href="<?= $base_url ?>sites/detail?id=<?= $site_id ?>&range=7d" class="btn btn-<?= $time_range === '7d' ? 'primary' : 'outline-primary' ?> btn-sm">
                    Son 7 Gün
                </a>
                <a href="<?= $base_url ?>sites/detail?id=<?= $site_id ?>&range=30d" class="btn btn-<?= $time_range === '30d' ? 'primary' : 'outline-primary' ?> btn-sm">
                    Son 30 Gün
                </a>
            </div>
            <span class="ms-3 text-muted">
                <i class="fas fa-clock"></i> Gösterilen: <strong><?= $interval_text ?></strong>
            </span>
        </div>
    </div>

    <!-- İstatistik Kartları -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <h3 class="text-<?= $uptime_percent >= 99 ? 'success' : ($uptime_percent >= 95 ? 'warning' : 'danger') ?>">
                        <?= number_format($uptime_percent, 2) ?>%
                    </h3>
                    <p class="text-muted mb-0">Uptime</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="text-success"><?= $up_count ?></h3>
                    <p class="text-muted mb-0">Başarılı Kontrol</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <h3 class="text-danger"><?= $down_count ?></h3>
                    <p class="text-muted mb-0">Kesinti</p>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="text-<?= $total_downtime_seconds == 0 ? 'success' : 'danger' ?>" style="font-size: 1.3rem;">
                        <?= $formatted_downtime ?>
                    </h3>
                    <p class="text-muted mb-0">Toplam Kesinti Süresi</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Uptime Grafiği (EKG/Nabız Tarzı) -->
    <?php 
    include __DIR__ . '/../../includes/components/detail_uptime_chart.php'; 
    ?>

    <!-- Kesinti Periyotları -->
    <?php if (!empty($downtime_periods)): ?>
    <div class="card mb-4">
        <div class="card-header <?= $site['last_status'] === 'down' ? 'bg-danger' : 'bg-warning' ?> text-white">
            <h5 class="mb-0">
                <i class="fas fa-exclamation-triangle"></i> 
                Kesinti Periyotları (<?= count($downtime_periods) ?> adet)
                <?php 
                $ongoing_count = 0;
                foreach ($downtime_periods as $p) {
                    if (isset($p['ongoing'])) $ongoing_count++;
                }
                if ($ongoing_count > 0): 
                ?>
                    <span class="badge bg-dark ms-2">
                        <i class="fas fa-exclamation"></i> <?= $ongoing_count ?> Aktif Kesinti
                    </span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Başlangıç</th>
                            <th>Bitiş</th>
                            <th>Süre</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($downtime_periods) as $period): ?>
                        <tr>
                            <td><?= $period['start'] ? date('d.m.Y H:i:s', strtotime($period['start'])) : 'Bilinmeyen' ?></td>
                            <td>
                                <?php if (isset($period['ongoing'])): ?>
                                    <span class="badge bg-danger">Devam Ediyor</span>
                                <?php else: ?>
                                    <?= $period['end'] ? date('d.m.Y H:i:s', strtotime($period['end'])) : 'Bilinmeyen' ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $duration = $period['duration'];
                                if ($duration < 60) {
                                    echo $duration . ' saniye';
                                } elseif ($duration < 3600) {
                                    echo floor($duration / 60) . ' dakika';
                                } else {
                                    $hours = floor($duration / 3600);
                                    $mins = floor(($duration % 3600) / 60);
                                    echo $hours . ' saat ' . $mins . ' dakika';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if (isset($period['ongoing'])): ?>
                                    <span class="badge bg-warning">Aktif Kesinti</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Düzeldi</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Log Geçmişi -->
    <div class="card">
        <div class="card-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0">
                        <i class="fas fa-history"></i> 
                        Kontrol Geçmişi (<span id="totalLogsCount"><?= $total_logs ?></span> kayıt)
                    </h5>
                </div>
                <div class="col-md-6 text-md-end mt-2 mt-md-0">
                    <span class="badge bg-secondary me-2">
                        Sayfa <?= $page ?> / <?= max(1, $total_pages) ?>
                    </span>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-<?= $per_page === 15 ? 'primary' : 'outline-primary' ?> btn-sm" 
                                onclick="changePerPage(15)">15</button>
                        <button type="button" class="btn btn-<?= $per_page === 30 ? 'primary' : 'outline-primary' ?> btn-sm" 
                                onclick="changePerPage(30)">30</button>
                        <button type="button" class="btn btn-<?= $per_page === 50 ? 'primary' : 'outline-primary' ?> btn-sm" 
                                onclick="changePerPage(50)">50</button>
                        <button type="button" class="btn btn-<?= $per_page === 100 ? 'primary' : 'outline-primary' ?> btn-sm" 
                                onclick="changePerPage(100)">100</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Zaman</th>
                            <th>Durum</th>
                            <th>Yanıt Süresi</th>
                            <th>Durum Bilgisi</th>
                        </tr>
                    </thead>
                    <tbody id="logTableBody">
                        <?php 
                        $prev_status = null;
                        $prev_time = null;
                        
                        foreach ($logs as $index => $log): 
                            // Bir sonraki log'u al (durum değişikliği kontrolü için)
                            $next_log = $logs[$index + 1] ?? null;
                            
                            // Durum değişikliği var mı?
                            $status_changed = false;
                            $duration_text = '';
                            
                            if ($next_log) {
                                if ($log['status'] !== $next_log['status']) {
                                    $status_changed = true;
                                    
                                    // Süre hesapla
                                    $current_time = $log['timestamp'] ? strtotime($log['timestamp']) : time();
                                    $next_time = $next_log['timestamp'] ? strtotime($next_log['timestamp']) : time();
                                    $duration = $current_time - $next_time;
                                    
                                    // Süreyi formatla
                                    if ($duration < 60) {
                                        $duration_text = $duration . ' saniye';
                                    } elseif ($duration < 3600) {
                                        $mins = floor($duration / 60);
                                        $secs = $duration % 60;
                                        $duration_text = $mins . ' dakika ' . $secs . ' saniye';
                                    } else {
                                        $hours = floor($duration / 3600);
                                        $mins = floor(($duration % 3600) / 60);
                                        $duration_text = $hours . ' saat ' . $mins . ' dakika';
                                    }
                                    
                                    // Durum değişikliği mesajı
                                    if ($log['status'] === 'up' && $next_log['status'] === 'down') {
                                        $duration_text = '<i class="fas fa-arrow-up text-success"></i> <strong>' . $duration_text . '</strong> çalıştı';
                                    } else {
                                        $duration_text = '<i class="fas fa-arrow-down text-danger"></i> <strong>' . $duration_text . '</strong> kesintideydi';
                                    }
                                }
                            } else {
                                // En eski kayıt
                                if ($log['status'] === 'up') {
                                    $duration_text = '<i class="fas fa-history"></i> İlk kontrol - UP';
                                } else {
                                    $duration_text = '<i class="fas fa-history"></i> İlk kontrol - DOWN';
                                }
                            }
                        ?>
                        <tr class="<?= $log['status'] === 'up' ? 'table-success' : 'table-danger' ?> <?= $status_changed ? 'fw-bold' : '' ?>">
                            <td><?= $log['timestamp'] ? date('d.m.Y H:i:s', strtotime($log['timestamp'])) : 'Bilinmeyen' ?></td>
                            <td>
                                <?php if ($log['status'] === 'up'): ?>
                                    <span class="badge bg-success">
                                        <i class="fas fa-check"></i> UP
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger">
                                        <i class="fas fa-times"></i> DOWN
                                    </span>
                                <?php endif; ?>
                                <?php if ($status_changed): ?>
                                    <span class="badge bg-warning text-dark ms-1">
                                        <i class="fas fa-exchange-alt"></i> Değişti
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><?= $log['response_time'] ?>ms</td>
                            <td>
                                <?php if ($duration_text): ?>
                                    <small><?= $duration_text ?></small>
                                <?php else: ?>
                                    <small class="text-muted">-</small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Sayfalama -->
            <?php if ($total_pages > 1): ?>
            <div class="card-footer">
                <nav aria-label="Log sayfaları">
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <!-- Sayfalama (JavaScript ile tamamen dinamik) -->
                        <ul id="fullPagination" class="pagination pagination-sm">
                            <!-- Tüm sayfalama JavaScript ile oluşturulacak -->
                        </ul>
                    </ul>
                    
                    <!-- Sayfa Bilgisi -->
                    <div class="text-center mt-2">
                        <small class="text-muted">
                            Gösterilen: <?= min($offset + 1, $total_logs) ?> - <?= min($offset + $per_page, $total_logs) ?> / <?= $total_logs ?> kayıt
                        </small>
                    </div>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- AJAX Sayfalama Sistemi -->
<script>
// Global değişkenler
let currentPage = <?= $page ?>;
let totalPages = <?= $total_pages ?>;
const siteId = <?= $site_id ?>;
const timeRange = '<?= $time_range ?>';
let perPage = <?= $per_page ?>;
let isLoading = false;

// Per page değiştirme fonksiyonu
function changePerPage(newPerPage) {
    if (newPerPage === perPage) return;
    
    perPage = newPerPage;
    
    // Butonları güncelle
    document.querySelectorAll('.btn-group button').forEach(btn => {
        const btnPerPage = parseInt(btn.textContent);
        if (btnPerPage === newPerPage) {
            btn.className = btn.className.replace('outline-primary', 'primary');
        } else {
            btn.className = btn.className.replace('primary', 'outline-primary');
        }
    });
    
    // İlk sayfaya git ve yükle
    currentPage = 1;
    loadPage(1);
}

// Sayfa yükleme fonksiyonu
async function loadPage(page) {
    if (isLoading || page < 1) return;
    
    isLoading = true;
    
    // Loading göstergesi
    const tbody = document.querySelector('#logTableBody');
    const pagination = document.querySelector('.pagination');
    
    tbody.style.opacity = '0.5';
    tbody.style.pointerEvents = 'none';
    if (pagination) pagination.style.opacity = '0.5';
    
    try {
        const response = await fetch(`<?= $base_url ?>pages/ajax/get_site_logs.php?site_id=${siteId}&range=${timeRange}&per_page=${perPage}&page=${page}`);
        const data = await response.json();
        
        if (data.success) {
            // Tabloyu güncelle
            tbody.innerHTML = data.data.html;
            
            // Sayfalama bilgilerini güncelle
            currentPage = data.data.page;
            totalPages = data.data.total_pages;
            
            
            // Toplam kayıt sayısını güncelle
            const totalLogsElement = document.getElementById('totalLogsCount');
            if (totalLogsElement) {
                totalLogsElement.textContent = data.data.total_logs;
            }
            
            // Sayfalama butonlarını güncelle
            updatePaginationButtons();
            
            // URL'yi güncelle (history API)
            const newUrl = `<?= $base_url ?>sites/detail?id=${siteId}&range=${timeRange}&per_page=${perPage}&page=${page}`;
            history.pushState({page: page}, '', newUrl);
            
            // Smooth scroll to table
            document.querySelector('#logTableBody').closest('.card').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'nearest' 
            });
            
            // Flash efekti
            tbody.style.transition = 'opacity 0.3s';
            tbody.style.opacity = '1';
            if (pagination) pagination.style.opacity = '1';
        }
    } catch (error) {
        console.error('Sayfa yükleme hatası:', error);
        alert('Veriler yüklenirken bir hata oluştu.');
    } finally {
        isLoading = false;
        tbody.style.pointerEvents = 'auto';
    }
}

// Tam sayfalama oluştur (İlk, Önceki, Sayfa Numaraları, Sonraki, Son)
function generateFullPagination() {
    const fullPaginationUl = document.getElementById('fullPagination');
    if (!fullPaginationUl) return;
    
    let html = '';
    
    // İlk Sayfa
    html += `<li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="<?= $base_url ?>sites/detail?id=${siteId}&range=${timeRange}&per_page=${perPage}&page=1">
                    <i class="fas fa-angle-double-left"></i>
                </a>
             </li>`;
    
    // Önceki Sayfa
    html += `<li class="page-item ${currentPage <= 1 ? 'disabled' : ''}">
                <a class="page-link" href="<?= $base_url ?>sites/detail?id=${siteId}&range=${timeRange}&per_page=${perPage}&page=${Math.max(1, currentPage - 1)}">
                    <i class="fas fa-angle-left"></i> Önceki
                </a>
             </li>`;
    
    // Sayfa Numaraları
    const startPage = Math.max(1, currentPage - 4);
    const endPage = Math.min(totalPages, currentPage + 5);
    
    // İlk sayfa her zaman göster
    if (startPage > 1) {
        html += `<li class="page-item ${1 === currentPage ? 'active' : ''}">
                    <a class="page-link" href="<?= $base_url ?>sites/detail?id=${siteId}&range=${timeRange}&per_page=${perPage}&page=1">1</a>
                 </li>`;
        if (startPage > 2) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Ana sayfa aralığı
    for (let i = startPage; i <= endPage; i++) {
        html += `<li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="<?= $base_url ?>sites/detail?id=${siteId}&range=${timeRange}&per_page=${perPage}&page=${i}">${i}</a>
                 </li>`;
    }
    
    // Son sayfa her zaman göster
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        html += `<li class="page-item ${totalPages === currentPage ? 'active' : ''}">
                    <a class="page-link" href="<?= $base_url ?>sites/detail?id=${siteId}&range=${timeRange}&per_page=${perPage}&page=${totalPages}">${totalPages}</a>
                 </li>`;
    }
    
    // Sonraki Sayfa
    html += `<li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
                <a class="page-link" href="<?= $base_url ?>sites/detail?id=${siteId}&range=${timeRange}&per_page=${perPage}&page=${Math.min(totalPages, currentPage + 1)}">
                    Sonraki <i class="fas fa-angle-right"></i>
                </a>
             </li>`;
    
    // Son Sayfa
    html += `<li class="page-item ${currentPage >= totalPages ? 'disabled' : ''}">
                <a class="page-link" href="<?= $base_url ?>sites/detail?id=${siteId}&range=${timeRange}&per_page=${perPage}&page=${totalPages}">
                    <i class="fas fa-angle-double-right"></i>
                </a>
             </li>`;
    
    fullPaginationUl.innerHTML = html;
}

// Sayfalama butonlarını güncelle
function updatePaginationButtons() {
    // Tam sayfalama oluştur
    generateFullPagination();
    
    // Sayfa bilgisini güncelle
    const pageInfo = document.querySelector('.pagination + .text-center small');
    if (pageInfo) {
        // AJAX'ten gelen total_logs değerini kullan
        const totalLogsElement = document.getElementById('totalLogsCount');
        const totalLogs = totalLogsElement ? parseInt(totalLogsElement.textContent) : (totalPages * perPage);
        const start = Math.min((currentPage - 1) * perPage + 1, totalLogs);
        const end = Math.min(currentPage * perPage, totalLogs);
        pageInfo.innerHTML = `Gösterilen: ${start} - ${end} / ${totalLogs} kayıt`;
    }
}

// Event Delegation - Sayfalama butonları için
document.addEventListener('click', function(e) {
    const link = e.target.closest('.pagination .page-link');
    if (!link) return;
    
    e.preventDefault();
    
    const item = link.closest('.page-item');
    if (item && item.classList.contains('disabled')) return;
    
    const href = link.getAttribute('href');
    if (!href) return;
    
    // Page parametresini al
    const urlParams = new URLSearchParams(href.split('?')[1]);
    const page = parseInt(urlParams.get('page'));
    
    if (page && page !== currentPage && page >= 1 && page <= totalPages) {
        loadPage(page);
    }
});

// Klavye Navigasyonu
document.addEventListener('keydown', function(e) {
    // Sol ok - Önceki sayfa
    if (e.key === 'ArrowLeft' && currentPage > 1) {
        e.preventDefault();
        loadPage(currentPage - 1);
    }
    
    // Sağ ok - Sonraki sayfa
    if (e.key === 'ArrowRight' && currentPage < totalPages) {
        e.preventDefault();
        loadPage(currentPage + 1);
    }
    
    // Home - İlk sayfa
    if (e.key === 'Home' && e.ctrlKey && currentPage > 1) {
        e.preventDefault();
        loadPage(1);
    }
    
    // End - Son sayfa
    if (e.key === 'End' && e.ctrlKey && currentPage < totalPages) {
        e.preventDefault();
        loadPage(totalPages);
    }
});

// Browser geri/ileri butonları
window.addEventListener('popstate', function(e) {
    if (e.state && e.state.page) {
        currentPage = e.state.page;
        loadPage(currentPage);
    }
});

// Manuel kontrol fonksiyonu
async function manualCheck(siteId, siteName) {
    const btn = document.getElementById('manualCheckBtn');
    const originalText = btn.innerHTML;
    
    // Butonu devre dışı bırak ve loading göster
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kontrol Ediliyor...';
    
    try {
        // Debug: URL'yi kontrol et
        const url = base_url + 'pages/ajax/manual_check.php';
        console.log('İstek URL:', url);
        console.log('Site ID:', siteId);
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                site_id: siteId
            })
        });
        
        console.log('Response status:', response.status);
        console.log('Response ok:', response.ok);
        
        // Response'u text olarak oku (JSON parse hatası için)
        const responseText = await response.text();
        console.log('Response text:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse hatası:', parseError);
            console.error('Response text:', responseText);
            throw new Error('Sunucudan geçersiz yanıt alındı: ' + responseText.substring(0, 100));
        }
        
        if (data.success) {
            // Başarılı kontrol - sayfayı yenile
            const result = data.data;
            
            // Başarı mesajı göster
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${result.status_color} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                <i class="fas fa-${result.status === 'up' ? 'check-circle' : 'exclamation-triangle'}"></i>
                <strong>${result.status_text}</strong><br>
                <small>Yanıt Süresi: ${result.response_time}ms</small><br>
                <small>${result.message}</small>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // 3 saniye sonra sayfayı yenile
            setTimeout(() => {
                window.location.reload();
            }, 3000);
            
        } else {
            // Hata mesajı
            alert('Manuel kontrol başarısız: ' + (data.message || 'Bilinmeyen hata'));
        }
        
    } catch (error) {
        console.error('Manuel kontrol hatası:', error);
        console.error('Error stack:', error.stack);
        alert('Bağlantı hatası: ' + error.message);
    } finally {
        // Butonu eski haline getir
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// Site silme fonksiyonu
function deleteSite(siteId, siteName) {
    if (confirm('"' + siteName + '" sitesini silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!')) {
        // AJAX ile site silme
        fetch('<?= $base_url ?>pages/ajax/delete_site.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                site_id: siteId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Site başarıyla silindi!');
                window.location.href = '<?= $base_url ?>sites';
            } else {
                alert('Site silinirken hata oluştu: ' + data.message);
            }
        })
        .catch(error => {
            alert('Bağlantı hatası: ' + error.message);
        });
    }
}

// Sayfa yüklendiğinde ilk sayfalama oluştur
generateFullPagination();

// Sayfa yüklendiğinde bilgilendirme göster
<?php if ($total_pages > 1): ?>
console.log('📄 AJAX Sayfalama Aktif: ← → ok tuşları ile gezin, Ctrl+Home/End ile ilk/son sayfa');
<?php endif; ?>
</script>

<?php
include __DIR__ . '/../../includes/layout/footer.php';
?>
