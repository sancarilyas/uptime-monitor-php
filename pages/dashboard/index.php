<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/language.php';

requireLogin();

// Çıkış işlemi
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $base_url . 'login');
    exit;
}

$error_message = '';
$success_message = '';

// Site ekleme işlemi
if ($_POST['action'] ?? '' === 'add_site') {
    verifyCsrf();
    $url = trim($_POST['url'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $notification_emails = trim($_POST['notification_emails'] ?? '');
    $monitor_path = trim($_POST['monitor_path'] ?? '');
    $group_id = $_POST['group_id'] ?? null;
    
    // Bildirim ayarları
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $telegram_notifications = isset($_POST['telegram_notifications']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
    $webhook_notifications = isset($_POST['webhook_notifications']) ? 1 : 0;
    $notify_on_down = isset($_POST['notify_on_down']) ? 1 : 0;
    $notify_on_up = isset($_POST['notify_on_up']) ? 1 : 0;
    $notification_priority = $_POST['notification_priority'] ?? 'medium';
    
    if (empty($url) || empty($name)) {
        $error_message = __('site_name_required');
    } else {
        $validated_url = validateUrl($url);
        if (!$validated_url) {
            $error_message = __('valid_url_required');
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO sites 
                (user_id, group_id, url, monitor_path, name, description, notification_emails, 
                 notifications_enabled, email_notifications, telegram_notifications, 
                 sms_notifications, webhook_notifications, notify_on_down, notify_on_up, 
                 notification_priority, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            if ($stmt->execute([
                $_SESSION['user_id'], $group_id, $validated_url, $monitor_path, $name, $description, $notification_emails,
                $notifications_enabled, $email_notifications, $telegram_notifications,
                $sms_notifications, $webhook_notifications, $notify_on_down, $notify_on_up,
                $notification_priority
            ])) {
                $success_message = __('site_added');
            } else {
                $error_message = __('database_error');
            }
        }
    }
}

// Site silme işlemi
if ($_POST['action'] ?? '' === 'delete_site') {
    verifyCsrf();
    $site_id = $_POST['site_id'] ?? 0;

    if ($site_id) {
        $stmt = $pdo->prepare("DELETE FROM sites WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$site_id, $_SESSION['user_id']])) {
            $success_message = __('site_deleted');
        } else {
            $error_message = __('database_error');
        }
    }
}

// Site güncelleme işlemi
if ($_POST['action'] ?? '' === 'update_site') {
    verifyCsrf();
    $site_id = $_POST['site_id'] ?? 0;
    $url = trim($_POST['url'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $notification_emails = trim($_POST['notification_emails'] ?? '');
    $monitor_path = trim($_POST['monitor_path'] ?? '');
    
    // Bildirim ayarları
    $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;
    $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
    $telegram_notifications = isset($_POST['telegram_notifications']) ? 1 : 0;
    $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
    $webhook_notifications = isset($_POST['webhook_notifications']) ? 1 : 0;
    $notify_on_down = isset($_POST['notify_on_down']) ? 1 : 0;
    $notify_on_up = isset($_POST['notify_on_up']) ? 1 : 0;
    $notification_priority = $_POST['notification_priority'] ?? 'medium';
    
    if (empty($url) || empty($name)) {
        $error_message = __('site_name_required');
    } else {
        $validated_url = validateUrl($url);
        if (!$validated_url) {
            $error_message = __('valid_url_required');
        } else {
            $stmt = $pdo->prepare("
                UPDATE sites SET 
                url = ?, monitor_path = ?, name = ?, description = ?, notification_emails = ?,
                notifications_enabled = ?, email_notifications = ?, telegram_notifications = ?,
                sms_notifications = ?, webhook_notifications = ?, notify_on_down = ?, notify_on_up = ?,
                notification_priority = ?
                WHERE id = ? AND user_id = ?
            ");
            if ($stmt->execute([
                $validated_url, $monitor_path, $name, $description, $notification_emails,
                $notifications_enabled, $email_notifications, $telegram_notifications,
                $sms_notifications, $webhook_notifications, $notify_on_down, $notify_on_up,
                $notification_priority, $site_id, $_SESSION['user_id']
            ])) {
                $success_message = __('site_updated');
            } else {
                $error_message = __('database_error');
            }
        }
    }
}

// Kullanıcının sitelerini al - Grup bazlı erişim kontrolü
$user_group_id = null;
$stmt = $pdo->prepare("SELECT group_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if ($user) {
    $user_group_id = $user['group_id'];
}

$stmt = $pdo->prepare("
    SELECT s.*, 
           g.name as group_name
    FROM sites s 
    LEFT JOIN `groups` g ON s.group_id = g.id
    WHERE (s.user_id = ? OR (s.group_id IS NOT NULL AND s.group_id = ?))
    ORDER BY 
        CASE 
            WHEN s.last_status = 'down' THEN 0 
            WHEN s.last_status = 'up' THEN 1 
            ELSE 2 
        END, 
        s.created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $user_group_id]);
$sites = $stmt->fetchAll();

// İstatistikleri hesapla
$total_sites = count($sites);
$active_sites = 0;
$total_uptime = 0;

foreach ($sites as $site) {
    if ($site['last_status'] === 'up') {
        $active_sites++;
    }
    $uptime_24h = calculateUptime($site['id'], 1);
    $total_uptime += $uptime_24h;
}

$avg_uptime = $total_sites > 0 ? $total_uptime / $total_sites : 0;

// Sayfa başlığı
$page_title = __('dashboard');
$page_description = __('dashboard_title');

// Header'ı dahil et
include __DIR__ . '/../../includes/layout/header.php';
?>

<!-- Cache temizleme için meta tag -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">
<meta name="version" content="<?= time() ?>">
<link rel="stylesheet" href="./assets/css/dashboard.css">
 
 

<div class="container p-3">
    <!-- Gerçek Zamanlı Güncelleme Durumu - Sakin Tasarım -->
    <div id="liveUpdateIndicator" class="live-update-indicator" style="display: none;">
        <div class="update-spinner" style="display: none;"></div>
        <span id="updateStatusText">
            <i class="fas fa-satellite-dish"></i> Canlı yayın aktif - Her 10 saniyede otomatik güncelleniyor
        </span>
        <button type="button" class="update-close-btn" onclick="dismissUpdateIndicator()" title="Kapat">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Alert Mesajları -->
    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- İstatistikler -->
    <div class="row g-2 mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card stat-card h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="stats-number"><?= $total_sites ?></div>
                    <div class="stats-label"><?= __('total_sites') ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card stat-card h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stats-number"><?= $active_sites ?></div>
                    <div class="stats-label"><?= __('active_sites') ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card stat-card h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stats-number text-danger"><?= $total_sites - $active_sites ?></div>
                    <div class="stats-label">Kesintili Siteler</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card stat-card h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stats-number">1dk</div>
                    <div class="stats-label"><?= __('last_check') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Site Ekleme Butonu ve Görünüm Seçenekleri -->
    <div class="row mb-3">
        <div class="col-md-2 col-sm-6 mb-2">
            <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addSiteModal">
                <i class="fas fa-plus"></i> <?= __('add_new_site') ?>
            </button>
        </div>
        <div class="col-md-2 col-sm-6 mb-2">
            <button class="btn btn-success btn-sm" id="checkAllSitesBtn" onclick="checkAllSites()">
                <i class="fas fa-play"></i> 
                <span id="checkAllSitesText">Tüm Siteleri Kontrol Et</span>
                <span id="checkAllSitesSpinner" class="spinner-border spinner-border-sm ms-1" style="display: none;"></span>
            </button>
        </div>
        <div class="col-md-4 col-sm-6 mb-2">
            <div class="input-group input-group-sm">
                <span class="input-group-text">
                    <i class="fas fa-search"></i>
                </span>
                <input type="text" 
                       class="form-control" 
                       id="siteSearchInput" 
                       placeholder="Site ara... (isim veya URL)"
                       autocomplete="off">
                <button class="btn btn-outline-secondary" type="button" id="clearSearch" style="display: none;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="col-md-4 col-sm-12 text-md-end mb-2">
            <div class="btn-group" role="group" aria-label="Görünüm Seçenekleri">
                <input type="radio" class="btn-check" name="viewMode" id="cardView" autocomplete="off" checked>
                <label class="btn btn-outline-secondary btn-sm" for="cardView">
                    <i class="fas fa-th-large"></i> <?= __('card_view') ?>
                </label>
                <input type="radio" class="btn-check" name="viewMode" id="tableView" autocomplete="off">
                <label class="btn btn-outline-secondary btn-sm" for="tableView">
                    <i class="fas fa-list"></i> <?= __('table_view') ?>
                </label>
            </div>
        </div>
    </div>
    
    <!-- Arama Sonucu Bilgisi -->
    <div id="searchResultInfo" class="alert alert-info mb-3" style="display: none; padding: 0.5rem 1rem;">
        <i class="fas fa-info-circle"></i> 
        <span id="searchResultText"></span>
        <button type="button" class="btn-close btn-close-sm float-end" onclick="clearSiteSearch()" style="font-size: 0.7rem; padding: 0.25rem;"></button>
    </div>

    <!-- Site Listesi -->
    <?php if (empty($sites)): ?>
        <div class="row">
            <div class="col-12">
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-globe fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted"><?= __('no_sites_yet') ?></h4>
                        <p class="text-muted"><?= __('add_first_site') ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Kart Görünümü -->
        <div id="cardViewContent" class="view-content">
            <div class="row g-3">
                <?php foreach ($sites as $site): ?>
                    <?php 
                    $uptime_24h = calculateUptime($site['id'], 1);
                    $status_class = $site['last_status'] === 'up' ? 'up' : 'down';
                    $status_text = $site['last_status'] === 'up' ? __('up') : __('down');
                    $status_color = $site['last_status'] === 'up' ? 'success' : 'danger';
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card site-card h-100 <?= $status_class ?>" data-site-id="<?= $site['id'] ?>" style="cursor: pointer;" onclick="window.location.href='<?= $base_url ?>sites/detail?id=<?= $site['id'] ?>'">
                            <div class="card-body">
                                <div class="card-title-container mb-2">
                                    <h5 class="card-title mb-0" title="<?= htmlspecialchars($site['name']) ?>"><?= htmlspecialchars($site['name']) ?></h5>
                                    <div class="dropdown" onclick="event.stopPropagation();">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="<?= $base_url ?>sites/detail?id=<?= $site['id'] ?>">
                                                <i class="fas fa-chart-line"></i> Detay Görüntüle
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#" onclick="editSite(<?= htmlspecialchars(json_encode($site)) ?>); event.preventDefault();">
                                                <i class="fas fa-edit"></i> <?= __('edit') ?>
                                            </a></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteSite(<?= $site['id'] ?>); event.preventDefault();">
                                                <i class="fas fa-trash"></i> <?= __('delete') ?>
                                            </a></li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <p class="card-text text-muted small mb-2">
                                    <i class="fas fa-link"></i> <?= htmlspecialchars($site['url']) ?>
                                    <?php if (!empty($site['monitor_path'])): ?>
                                        <br><i class="fas fa-route"></i> <?= htmlspecialchars($site['monitor_path']) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($site['group_name'])): ?>
                                        <br><i class="fas fa-users"></i> <span class="badge bg-info"><?= htmlspecialchars($site['group_name']) ?></span>
                                    <?php endif; ?>
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center">
                                        <span class="status-indicator status-<?= $status_class ?>"></span>
                                        <span class="badge bg-<?= $status_color ?> ms-2"><?= $status_text ?></span>
                                    </div>
                                    <div class="text-end">
                                        <div class="small text-muted"><?= __('uptime_percentage') ?></div>
                                        <div class="fw-bold text-<?= $status_color ?>"><?= formatUptime($uptime_24h) ?></div>
                                    </div>
                                </div>
                                
                                <?php if ($site['last_check']): ?>
                                    <div class="mt-2 text-muted small">
                                        <i class="fas fa-clock"></i> <?= __('last_check') ?>: <?= date('d.m.Y H:i', strtotime($site['last_check'])) ?>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Mini Site Grafiği -->
                                <?php 
                                $site_id = $site['id'];
                                include __DIR__ . '/../../includes/components/mini_site_chart.php'; 
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Tablo Görünümü -->
        <div id="tableViewContent" class="view-content" style="display: none;">
            <div class="table-responsive">
                <table class="table table-hover dashboard-table">
                    <thead class="table-light">
                        <tr>
                            <th><?= __('site_name') ?></th>
                            <th><?= __('url') ?></th>
                            <th><?= __('uptime_24h') ?></th>
                            <th><?= __('status') ?></th>
                            <th><?= __('last_check') ?></th>
                            <th><?= __('actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sites as $site): ?>
                            <?php 
                            $uptime_24h = calculateUptime($site['id'], 1);
                            $status_class = $site['last_status'] === 'up' ? 'up' : 'down';
                            $status_text = $site['last_status'] === 'up' ? __('up') : __('down');
                            $status_color = $site['last_status'] === 'up' ? 'success' : 'danger';
                            ?>
                            <tr class="dashboard-row <?= $status_class ?>" data-site-id="<?= $site['id'] ?>" style="cursor: pointer;" onclick="window.location.href='/uptime/sites/detail?id=<?= $site['id'] ?>'">
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <div class="fw-bold"><?= htmlspecialchars($site['name']) ?></div>
                                            <?php if (!empty($site['monitor_path'])): ?>
                                                <small class="text-muted">
                                                    <i class="fas fa-route"></i> <?= htmlspecialchars($site['monitor_path']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?= htmlspecialchars($site['url']) ?>">
                                        <?= htmlspecialchars($site['url']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="mini-progress me-2">
                                            <div class="progress-bar" style="width: <?= $uptime_24h ?>%; background-color: <?= $site['last_status'] === 'up' ? '#28a745' : '#dc3545' ?>;"></div>
                                        </div>
                                        <span class="fw-bold text-<?= $status_color ?>"><?= formatUptime($uptime_24h) ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $status_color ?>">
                                        <i class="fas fa-circle me-1"></i>
                                        <?= $status_text ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= $site['last_check'] ? date('d.m.Y H:i', strtotime($site['last_check'])) : __('never') ?>
                                    </small>
                                </td>
                                <td onclick="event.stopPropagation();">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="/uptime/sites/detail?id=<?= $site['id'] ?>">
                                                <i class="fas fa-chart-line"></i> Detay Görüntüle
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item" href="#" onclick="editSite(<?= htmlspecialchars(json_encode($site)) ?>); event.preventDefault();">
                                                <i class="fas fa-edit"></i> <?= __('edit') ?>
                                            </a></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="deleteSite(<?= $site['id'] ?>); event.preventDefault();">
                                                <i class="fas fa-trash"></i> <?= __('delete') ?>
                                            </a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Site Ekleme Modal -->
<div class="modal fade" id="addSiteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus"></i> <?= __('add_new_site') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?= csrfField() ?>
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_site">
                    
                    <div class="mb-3">
                        <label for="site_name" class="form-label"><?= __('site_name') ?> *</label>
                        <input type="text" class="form-control" id="site_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="site_url" class="form-label"><?= __('site_url') ?> *</label>
                        <input type="url" class="form-control" id="site_url" name="url" placeholder="https://example.com" required>
                        <div class="form-text">http:// veya https:// ile başlamalı</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="monitor_path" class="form-label"><?= __('monitor_path') ?></label>
                        <input type="text" class="form-control" id="monitor_path" name="monitor_path" placeholder="/api/health">
                        <div class="form-text">Opsiyonel: Belirli bir sayfayı izlemek için</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label"><?= __('description') ?></label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notification_emails" class="form-label"><?= __('notification_emails') ?></label>
                        <input type="text" class="form-control" id="notification_emails" name="notification_emails" placeholder="email1@example.com, email2@example.com">
                        <div class="form-text">Virgülle ayırarak birden fazla e-posta ekleyebilirsiniz</div>
                    </div>
                    
                    <?php if ($user_group_id): ?>
                    <div class="mb-3">
                        <label for="group_id" class="form-label"><?= __('group') ?></label>
                        <select class="form-select" id="group_id" name="group_id">
                            <option value=""><?= __('no_group') ?? 'Grup seçiniz' ?></option>
                            <?php 
                            $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE id = ?");
                            $stmt->execute([$user_group_id]);
                            $user_group = $stmt->fetch();
                            if ($user_group): ?>
                                <option value="<?= $user_group['id'] ?>" selected><?= htmlspecialchars($user_group['name']) ?></option>
                            <?php endif; ?>
                        </select>
                        <div class="form-text">Bu siteyi grubunuzla paylaşabilirsiniz</div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Bildirim Ayarları -->
                    <hr>
                    <h6 class="mb-3">🔔 Bildirim Ayarları</h6>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="notifications_enabled" name="notifications_enabled" checked>
                            <label class="form-check-label" for="notifications_enabled">
                                <strong>Site bildirimlerini aktif et</strong>
                            </label>
                            <div class="form-text">Bu site için tüm bildirimleri açık/kapalı yapar</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify_on_down" name="notify_on_down" checked>
                                    <label class="form-check-label" for="notify_on_down">
                                        Site kapandığında bildirim gönder
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="notify_on_up" name="notify_on_up" checked>
                                    <label class="form-check-label" for="notify_on_up">
                                        Site açıldığında bildirim gönder
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notification_priority" class="form-label">Bildirim Önceliği</label>
                        <select class="form-select" id="notification_priority" name="notification_priority">
                            <option value="low">Düşük</option>
                            <option value="medium" selected>Orta</option>
                            <option value="high">Yüksek</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Bildirim Kanalları</label>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" checked>
                                    <label class="form-check-label" for="email_notifications">
                                        <i class="fas fa-envelope text-primary"></i> Email
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="telegram_notifications" name="telegram_notifications">
                                    <label class="form-check-label" for="telegram_notifications">
                                        <i class="fab fa-telegram text-info"></i> Telegram
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications">
                                    <label class="form-check-label" for="sms_notifications">
                                        <i class="fas fa-sms text-success"></i> SMS
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="webhook_notifications" name="webhook_notifications">
                                    <label class="form-check-label" for="webhook_notifications">
                                        <i class="fas fa-webhook text-warning"></i> Webhook
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                    <button type="submit" class="btn btn-primary"><?= __('add') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Site Düzenleme Modal -->
<div class="modal fade" id="editSiteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> <?= __('edit') ?> <?= __('site_name') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editSiteForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_site">
                    <input type="hidden" name="site_id" id="edit_site_id">
                    
                    <div class="mb-3">
                        <label for="edit_site_name" class="form-label"><?= __('site_name') ?> *</label>
                        <input type="text" class="form-control" id="edit_site_name" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_site_url" class="form-label"><?= __('site_url') ?> *</label>
                        <input type="url" class="form-control" id="edit_site_url" name="url" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_monitor_path" class="form-label"><?= __('monitor_path') ?></label>
                        <input type="text" class="form-control" id="edit_monitor_path" name="monitor_path">
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label"><?= __('description') ?></label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_notification_emails" class="form-label"><?= __('notification_emails') ?></label>
                        <input type="text" class="form-control" id="edit_notification_emails" name="notification_emails">
                    </div>
                    
                    <!-- Bildirim Ayarları -->
                    <hr>
                    <h6 class="mb-3">🔔 Bildirim Ayarları</h6>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_notifications_enabled" name="notifications_enabled">
                            <label class="form-check-label" for="edit_notifications_enabled">
                                <strong>Site bildirimlerini aktif et</strong>
                            </label>
                            <div class="form-text">Bu site için tüm bildirimleri açık/kapalı yapar</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_notify_on_down" name="notify_on_down">
                                    <label class="form-check-label" for="edit_notify_on_down">
                                        Site kapandığında bildirim gönder
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_notify_on_up" name="notify_on_up">
                                    <label class="form-check-label" for="edit_notify_on_up">
                                        Site açıldığında bildirim gönder
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_notification_priority" class="form-label">Bildirim Önceliği</label>
                        <select class="form-select" id="edit_notification_priority" name="notification_priority">
                            <option value="low">Düşük</option>
                            <option value="medium">Orta</option>
                            <option value="high">Yüksek</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Bildirim Kanalları</label>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_email_notifications" name="email_notifications">
                                    <label class="form-check-label" for="edit_email_notifications">
                                        <i class="fas fa-envelope text-primary"></i> Email
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_telegram_notifications" name="telegram_notifications">
                                    <label class="form-check-label" for="edit_telegram_notifications">
                                        <i class="fab fa-telegram text-info"></i> Telegram
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_sms_notifications" name="sms_notifications">
                                    <label class="form-check-label" for="edit_sms_notifications">
                                        <i class="fas fa-sms text-success"></i> SMS
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_webhook_notifications" name="webhook_notifications">
                                    <label class="form-check-label" for="edit_webhook_notifications">
                                        <i class="fas fa-webhook text-warning"></i> Webhook
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                    <button type="submit" class="btn btn-primary"><?= __('save') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Site Silme Modal -->
<div class="modal fade" id="deleteSiteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?= __('delete') ?> <?= __('site_name') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?= __('confirm_delete_site') ?></p>
                <p class="text-muted small"><?= __('delete_warning') ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <form method="POST" style="display: inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete_site">
                    <input type="hidden" name="site_id" id="delete_site_id">
                    <button type="submit" class="btn btn-danger"><?= __('delete') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base_url ?>assets/js/dashboard.js?t=<?= time() ?>"></script>

 

<?php
// Footer'ı dahil et
include __DIR__ . '/../../includes/layout/footer.php';
?>