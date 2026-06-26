<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/language.php';

requireLogin();

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
        // Sadece kendi sitelerini silebilir
        $stmt = $pdo->prepare("DELETE FROM sites WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$site_id, $_SESSION['user_id']])) {
            $success_message = __('site_deleted');
        } else {
            $error_message = __('database_error');
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
           ul.last_status,
           ul.last_check_time,
           ul.response_time,
           g.name as group_name
    FROM sites s 
    LEFT JOIN (
        SELECT site_id, 
               status as last_status,
               timestamp as last_check_time,
               response_time,
               ROW_NUMBER() OVER (PARTITION BY site_id ORDER BY timestamp DESC) as rn
        FROM uptime_logs
    ) ul ON s.id = ul.site_id AND ul.rn = 1
    LEFT JOIN `groups` g ON s.group_id = g.id
    WHERE (s.user_id = ? OR (s.group_id IS NOT NULL AND s.group_id = ?))
    ORDER BY 
        CASE WHEN ul.last_status = 'down' THEN 0 ELSE 1 END,
        CASE WHEN ul.last_status = 'up' THEN 0 ELSE 1 END,
        s.created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $user_group_id]);
$sites = $stmt->fetchAll();

// Sayfa başlığı
$page_title = __('my_sites');
$page_description = __('site_management');

// Header'ı dahil et
include __DIR__ . '/../../includes/layout/header.php';
?>
<link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/sites.css">
<div class="container p-3">
    <!-- Alert Mesajları -->
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-3">
        <div class="col-12">
            <h2 class="section-title"><i class="fas fa-globe"></i> <?= __('my_sites') ?></h2>
            <p class="text-muted"><?= __('site_management') ?> - Down olan siteler önce gösteriliyor</p>
        </div>
    </div>

  

    <!-- Site Ekleme Butonu, Arama ve Görünüm Seçenekleri -->
    <div class="row mb-3">
        <div class="col-md-4 col-sm-6 mb-2">
            <button class="btn btn-primary btn-sm btn-block" data-bs-toggle="modal" data-bs-target="#addSiteModal">
                <i class="fas fa-plus"></i> <?= __('add_new_site') ?>
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
                       placeholder="<?= __('search_sites') ?>"
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
        <div class="col-12">
            <div class="card text-center py-5">
                <div class="card-body">
                    <i class="fas fa-globe fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted"><?= __('no_sites_yet') ?></h4>
                    <p class="text-muted"><?= __('add_first_site') ?></p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Kart Görünümü -->
        <div id="cardViewContainer" class="row">
            <?php foreach ($sites as $site): ?>
                <?php 
                $uptime_24h = calculateUptime($site['id'], 1);
                $status_class = $site['last_status'] === 'up' ? 'up' : 'down';
                $status_text = $site['last_status'] === 'up' ? __('up') : __('down');
                $status_color = $site['last_status'] === 'up' ? 'success' : 'danger';
                ?>
                <div class="col-lg-4 col-md-6 mb-3">
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
                                            <i class="fas fa-chart-line"></i> <?= __('detail') ?>
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

        <!-- Tablo Görünümü -->
        <div id="tableViewContainer" class="d-none">
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="20%"><?= __('site_name') ?></th>
                                    <th width="30%"><?= __('url') ?></th>
                                    <th width="15%"><?= __('status') ?></th>
                                    <th width="15%"><?= __('uptime_24h') ?></th>
                                    <th width="15%"><?= __('last_check') ?></th>
                                    <th width="5%" class="text-center"><?= __('actions') ?></th>
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
                                    <tr class="<?= $status_class ?>-row" data-site-id="<?= $site['id'] ?>" style="cursor: pointer;" onclick="window.location.href='<?= $base_url ?>sites/detail?id=<?= $site['id'] ?>'">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="status-indicator status-<?= $status_class ?> me-2"></span>
                                                <strong><?= htmlspecialchars($site['name']) ?></strong>
                                            </div>
                                            <?php if (!empty($site['monitor_path'])): ?>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-route"></i> <?= htmlspecialchars($site['monitor_path']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?= htmlspecialchars($site['url']) ?>" target="_blank" class="text-decoration-none">
                                                <i class="fas fa-external-link-alt me-1"></i>
                                                <?= htmlspecialchars($site['url']) ?>
                                            </a>
                                            <?php if (!empty($site['group_name'])): ?>
                                                <br><small class="text-info"><i class="fas fa-users"></i> <?= htmlspecialchars($site['group_name']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $status_color ?>"><?= $status_text ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="fw-bold text-<?= $status_color ?> me-2"><?= formatUptime($uptime_24h) ?></span>
                                                <!-- Mini Progress Bar -->
                                                <div class="mini-progress" style="width: 60px; height: 6px; background: #e9ecef; border-radius: 3px; overflow: hidden;">
                                                    <div class="mini-progress-fill" style="width: <?= $uptime_24h ?>%; height: 100%; background: <?= $status_color === 'success' ? '#28a745' : '#dc3545' ?>; transition: width 0.3s ease;"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($site['last_check']): ?>
                                                <small class="text-muted">
                                                    <?= date('d.m.Y H:i', strtotime($site['last_check'])) ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">-</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center" onclick="event.stopPropagation();">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                    <i class="fas fa-ellipsis-v"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    <li><a class="dropdown-item" href="<?= $base_url ?>sites/detail?id=<?= $site['id'] ?>">
                                                        <i class="fas fa-chart-line"></i> <?= __('detail') ?>
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
                        <div class="form-text">Virgülle ayırarak birden fazla e-posta ekleyebilirsiniz</div>
                    </div>
                    
                    <!-- Bildirim Ayarları -->
                    <hr>
                    <h6 class="text-primary mb-3"><i class="fas fa-bell"></i> Bildirim Ayarları</h6>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_notifications_enabled" name="notifications_enabled">
                            <label class="form-check-label" for="edit_notifications_enabled">
                                <strong>Bildirimleri Etkinleştir</strong>
                            </label>
                            <div class="form-text">Bu site için tüm bildirimleri açık/kapalı yapar</div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_notification_priority" class="form-label">Bildirim Önceliği</label>
                        <select class="form-select" id="edit_notification_priority" name="notification_priority">
                            <option value="low">Düşük</option>
                            <option value="medium" selected>Orta</option>
                            <option value="high">Yüksek</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_notify_on_down" name="notify_on_down">
                            <label class="form-check-label" for="edit_notify_on_down">
                                <strong>Site Kapandığında Bildirim Gönder</strong>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_notify_on_up" name="notify_on_up">
                            <label class="form-check-label" for="edit_notify_on_up">
                                <strong>Site Açıldığında Bildirim Gönder</strong>
                            </label>
                        </div>
                    </div>
                    
                    <h6 class="text-info mb-3"><i class="fas fa-paper-plane"></i> Bildirim Kanalları</h6>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_email_notifications" name="email_notifications">
                            <label class="form-check-label" for="edit_email_notifications">
                                <strong>E-posta Bildirimleri</strong>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_telegram_notifications" name="telegram_notifications">
                            <label class="form-check-label" for="edit_telegram_notifications">
                                <strong>Telegram Bildirimleri</strong>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_sms_notifications" name="sms_notifications">
                            <label class="form-check-label" for="edit_sms_notifications">
                                <strong>SMS Bildirimleri</strong>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_webhook_notifications" name="webhook_notifications">
                            <label class="form-check-label" for="edit_webhook_notifications">
                                <strong>Webhook Bildirimleri</strong>
                            </label>
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
<script src="<?= $base_url ?>assets/js/sites.js?t=<?= time() ?>"></script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../../includes/layout/footer.php';
?>