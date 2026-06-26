<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/language.php';

requireLogin();

// Admin kontrolü
if (!isAdmin()) {
    header('Location: ' . $base_url . 'dashboard');
    exit;
}

$error_message = '';
$success_message = '';

// SMS ayarlarını getir
$stmt = $pdo->prepare("SELECT * FROM sms_settings LIMIT 1");
$stmt->execute();
$sms_settings = $stmt->fetch() ?: ['provider' => 'twilio', 'enabled' => 0];

// Telegram ayarlarını getir
$stmt = $pdo->prepare("SELECT * FROM telegram_settings LIMIT 1");
$stmt->execute();
$telegram_settings = $stmt->fetch() ?: ['enabled' => 0];

// Webhook ayarlarını getir
$stmt = $pdo->prepare("SELECT * FROM webhook_settings ORDER BY created_at DESC");
$stmt->execute();
$webhook_settings = $stmt->fetchAll();

// Bildirim kurallarını getir
$stmt = $pdo->prepare("SELECT nr.*, s.name as site_name FROM notification_rules nr LEFT JOIN sites s ON nr.site_id = s.id ORDER BY nr.created_at DESC");
$stmt->execute();
$notification_rules = $stmt->fetchAll();

// Son bildirimleri getir (performans için son 60 kayıt)
$stmt = $pdo->prepare("
    SELECT 
        nl.*, 
        s.name as site_name,
        s.url as site_url,
        CASE 
            WHEN nl.notification_type = 'email' THEN 'Email'
            WHEN nl.notification_type = 'telegram' THEN 'Telegram'
            WHEN nl.notification_type = 'sms' THEN 'SMS'
            WHEN nl.notification_type = 'webhook' THEN 'Webhook'
            ELSE nl.notification_type
        END as notification_type_display
    FROM notification_logs nl 
    LEFT JOIN sites s ON nl.site_id = s.id 
    ORDER BY nl.sent_at DESC 
    LIMIT 60
");
$stmt->execute();
$recent_notifications = $stmt->fetchAll();

// Sayfa başlığı
$page_title = 'Bildirim Ayarları';
$page_description = 'SMS, Telegram ve Webhook bildirimlerini yönetin';

// Header'ı dahil et
include __DIR__ . '/../../includes/layout/header.php';
?>
<script src="<?php echo $base_url; ?>assets/js/notifications.js?t=<?= time() ?>"></script>
<div class="container p-3">
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

    <div class="row mb-3">
        <div class="col-12">
            <h2 class="section-title"><i class="fas fa-bell"></i> Bildirim Ayarları</h2>
            <p class="text-muted">SMS, Telegram ve Webhook bildirimlerini yönetin</p>
        </div>
    </div>

    <!-- Bildirim İstatistikleri -->
    <div class="row g-2 mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card stat-card h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-sms"></i>
                    </div>
                    <div class="stats-number"><?= $sms_settings['enabled'] ? 'Aktif' : 'Pasif' ?></div>
                    <div class="stats-label">SMS Bildirimleri</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card stat-card h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="stat-icon mb-2">
                        <i class="fab fa-telegram"></i>
                    </div>
                    <div class="stats-number"><?= $telegram_settings['enabled'] ? 'Aktif' : 'Pasif' ?></div>
                    <div class="stats-label">Telegram Bot</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card stat-card h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-link"></i>
                    </div>
                    <div class="stats-number"><?= count($webhook_settings) ?></div>
                    <div class="stats-label">Webhook'lar</div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card stat-card h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-rules"></i>
                    </div>
                    <div class="stats-number"><?= count($notification_rules) ?></div>
                    <div class="stats-label">Bildirim Kuralları</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bildirim Ayarları Kartları -->
    <div class="row g-3 mb-4">
        <!-- SMS Ayarları -->
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-sms"></i> SMS Bildirimleri</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">Twilio üzerinden SMS bildirimleri gönderin</p>
                    <div class="mb-3">
                        <span class="badge bg-<?= $sms_settings['enabled'] ? 'success' : 'secondary' ?>">
                            <?= $sms_settings['enabled'] ? 'Aktif' : 'Pasif' ?>
                        </span>
                    </div>
                    <a href="<?= $base_url ?>admin/notifications/sms" class="btn btn-primary">
                        <i class="fas fa-cog"></i> SMS Ayarları
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Telegram Ayarları -->
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fab fa-telegram"></i> Telegram Bot</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">Telegram bot üzerinden bildirimler gönderin</p>
                    <div class="mb-3">
                        <span class="badge bg-<?= $telegram_settings['enabled'] ? 'success' : 'secondary' ?>">
                            <?= $telegram_settings['enabled'] ? 'Aktif' : 'Pasif' ?>
                        </span>
                    </div>
                    <a href="<?= $base_url ?>admin/notifications/telegram" class="btn btn-info">
                        <i class="fas fa-cog"></i> Telegram Ayarları
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Webhook Ayarları -->
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0"><i class="fas fa-link"></i> Webhook'lar</h5>
                </div>
                <div class="card-body">
                    <p class="card-text text-muted">Slack, Discord ve diğer servislere webhook gönderin</p>
                    <div class="mb-3">
                        <span class="badge bg-info"><?= count($webhook_settings) ?> Webhook</span>
                    </div>
                    <a href="<?= $base_url ?>admin/notifications/webhooks" class="btn btn-warning">
                        <i class="fas fa-cog"></i> Webhook Ayarları
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bildirim Kuralları -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-rules"></i> Bildirim Kuralları</h5>
                    <a href="<?= $base_url ?>admin/notifications/rules" class="btn btn-success btn-sm">
                        <i class="fas fa-plus"></i> Yeni Kural
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($notification_rules)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-rules fa-3x mb-3"></i>
                            <p>Henüz bildirim kuralı tanımlanmamış.</p>
                            <a href="<?= $base_url ?>admin/notifications/rules" class="btn btn-primary">İlk Kuralı Oluştur</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Site</th>
                                        <th>Olay</th>
                                        <th>Öncelik</th>
                                        <th>SMS</th>
                                        <th>Telegram</th>
                                        <th>Webhook</th>
                                        <th>Email</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notification_rules as $rule): ?>
                                        <tr>
                                            <td><?= $rule['site_name'] ?: 'Tüm Siteler' ?></td>
                                            <td>
                                                <span class="badge bg-<?= $rule['event_type'] === 'down' ? 'danger' : ($rule['event_type'] === 'up' ? 'success' : 'warning') ?>">
                                                    <?= ucfirst($rule['event_type']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $rule['priority'] === 'critical' ? 'danger' : ($rule['priority'] === 'high' ? 'warning' : 'info') ?>">
                                                    <?= ucfirst($rule['priority']) ?>
                                                </span>
                                            </td>
                                            <td><?= $rule['sms_enabled'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>' ?></td>
                                            <td><?= $rule['telegram_enabled'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>' ?></td>
                                            <td><?= $rule['webhook_enabled'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>' ?></td>
                                            <td><?= $rule['email_enabled'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>' ?></td>
                                            <td>
                                                <span class="badge bg-<?= $rule['enabled'] ? 'success' : 'secondary' ?>">
                                                    <?= $rule['enabled'] ? 'Aktif' : 'Pasif' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo $base_url; ?>admin/notifications/rules/edit?id=<?= $rule['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteRule(<?= $rule['id'] ?>)" title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                             
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Son Bildirimler -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Son Bildirimler (Son 60 Kayıt)</h5>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-info me-2"><?= count($recent_notifications) ?> Bildirim</span>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshNotifications()">
                            <i class="fas fa-sync-alt"></i> Yenile
                        </button>
                    </div>
                </div>
                 <!-- Bildirim İstatistikleri -->
                 <div class="row mt-3">
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h5><?= count(array_filter($recent_notifications, fn($n) => $n['status'] === 'sent')) ?></h5>
                                        <small>Başarılı</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h5><?= count(array_filter($recent_notifications, fn($n) => $n['status'] === 'failed')) ?></h5>
                                        <small>Başarısız</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-primary text-white">
                                    <div class="card-body text-center">
                                        <h5><?= count(array_filter($recent_notifications, fn($n) => $n['notification_type'] === 'email')) ?></h5>
                                        <small>Email</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h5><?= count(array_filter($recent_notifications, fn($n) => $n['notification_type'] === 'telegram')) ?></h5>
                                        <small>Telegram</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                <div class="card-body">
                    <?php if (empty($recent_notifications)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-bell-slash fa-3x mb-3"></i>
                            <p>Henüz bildirim gönderilmemiş.</p>
                            <small class="text-muted">Site durumu değiştiğinde bildirimler burada görünecek.</small>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Tarih</th>
                                        <th>Site</th>
                                        <th>Olay</th>
                                        <th>Bildirim Tipi</th>
                                        <th>Alıcı</th>
                                        <th>Durum</th>
                                        <th>Mesaj</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_notifications as $notification): ?>
                                        <tr class="<?= $notification['status'] === 'sent' ? 'table-success' : 'table-danger' ?>">
                                            <td>
                                                <small class="text-muted">
                                                    <?= $notification['sent_at'] ? date('d.m.Y H:i:s', strtotime($notification['sent_at'])) : 'Bilinmeyen' ?>
                                                </small>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($notification['site_url'] ?? $notification['site_name'] ?? 'Bilinmeyen Site') ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $notification['event_type'] === 'down' ? 'danger' : 'success' ?>">
                                                    <i class="fas fa-<?= $notification['event_type'] === 'down' ? 'times' : 'check' ?>"></i>
                                                    <?= ucfirst($notification['event_type']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= 
                                                    $notification['notification_type'] === 'email' ? 'primary' : 
                                                    ($notification['notification_type'] === 'telegram' ? 'info' : 
                                                    ($notification['notification_type'] === 'sms' ? 'warning' : 'secondary')) 
                                                ?>">
                                                    <i class="fas fa-<?= 
                                                        $notification['notification_type'] === 'email' ? 'envelope' : 
                                                        ($notification['notification_type'] === 'telegram' ? 'paper-plane' : 
                                                        ($notification['notification_type'] === 'sms' ? 'sms' : 'link')) 
                                                    ?>"></i>
                                                    <?= htmlspecialchars($notification['notification_type_display']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-truncate d-block" style="max-width: 150px;" title="<?= htmlspecialchars($notification['recipient'] ?? '') ?>">
                                                    <?= htmlspecialchars($notification['recipient'] ?? 'Bilinmeyen') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $notification['status'] === 'sent' ? 'success' : 'danger' ?>">
                                                    <i class="fas fa-<?= $notification['status'] === 'sent' ? 'check' : 'times' ?>"></i>
                                                    <?= $notification['status'] === 'sent' ? 'Gönderildi' : 'Başarısız' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-truncate d-block" style="max-width: 200px;" title="<?= htmlspecialchars($notification['message'] ?? '') ?>">
                                                    <?= htmlspecialchars($notification['message'] ?? 'Mesaj yok') ?>
                                                </small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                       
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Footer'ı dahil et
include __DIR__ . '/../../includes/layout/footer.php';
?>

<script>
// Bildirimleri yenile
function refreshNotifications() {
    window.location.reload();
}

// Otomatik yenileme (30 saniyede bir)
setInterval(function() {
    // Sadece sayfa görünürse yenile
    if (!document.hidden) {
        refreshNotifications();
    }
}, 30000);
</script>
