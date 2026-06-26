<?php
require_once dirname(__DIR__, 4) . '/config/database.php';
require_once dirname(__DIR__, 4) . '/includes/functions.php';
require_once dirname(__DIR__, 4) . '/includes/language.php';

requireLogin();
requireAdmin();

$error_message = '';
$success_message = '';

// Form gönderildi mi kontrol et
if ($_POST['action'] ?? '' === 'add_rule') {
    verifyCsrf();
    $event_type = $_POST['event_type'] ?? '';
    $site_id = $_POST['site_id'] ?? null;
    $priority = $_POST['priority'] ?? 'medium';
    $email_enabled = isset($_POST['email_enabled']) ? 1 : 0;
    $sms_enabled = isset($_POST['sms_enabled']) ? 1 : 0;
    $telegram_enabled = isset($_POST['telegram_enabled']) ? 1 : 0;
    $webhook_enabled = isset($_POST['webhook_enabled']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if (empty($event_type)) {
        $error_message = 'Olay türü seçilmelidir';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notification_rules 
                (event_type, site_id, priority, email_enabled, sms_enabled, telegram_enabled, webhook_enabled, is_active, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $event_type, 
                $site_id ?: null, 
                $priority, 
                $email_enabled, 
                $sms_enabled, 
                $telegram_enabled, 
                $webhook_enabled, 
                $is_active
            ]);
            
            $success_message = 'Bildirim kuralı başarıyla eklendi';
        } catch (PDOException $e) {
            $error_message = 'Veritabanı hatası: ' . $e->getMessage();
        }
    }
}

// Site listesini getir
$stmt = $pdo->prepare("SELECT id, name FROM sites ORDER BY name");
$stmt->execute();
$sites = $stmt->fetchAll();

// Sayfa başlığı
$page_title = 'Yeni Bildirim Kuralı';
$page_description = 'Site durumu değişikliklerinde bildirim kuralı ekleyin';

// Header'ı dahil et
include dirname(__DIR__, 4) . '/includes/layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Yeni Bildirim Kuralı</h1>
                    <p class="text-muted mb-0">Site durumu değişikliklerinde bildirim kuralı ekleyin</p>
                </div>
                <a href="<?= $base_url ?>admin/notifications/rules" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Geri Dön
                </a>
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

            <!-- Form -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Kural Bilgileri</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
<?= csrfField() ?>
                        <input type="hidden" name="action" value="add_rule">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="event_type" class="form-label">Olay Türü <span class="text-danger">*</span></label>
                                    <select class="form-select" id="event_type" name="event_type" required>
                                        <option value="">Seçiniz...</option>
                                        <option value="up">Site Açıldı (UP)</option>
                                        <option value="down">Site Kapandı (DOWN)</option>
                                        <option value="response_time">Yanıt Süresi Aşımı</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="site_id" class="form-label">Site</label>
                                    <select class="form-select" id="site_id" name="site_id">
                                        <option value="">Tüm Siteler</option>
                                        <?php foreach ($sites as $site): ?>
                                            <option value="<?= $site['id'] ?>"><?= htmlspecialchars($site['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="form-text">Boş bırakılırsa tüm siteler için geçerli olur</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Öncelik</label>
                                    <select class="form-select" id="priority" name="priority">
                                        <option value="low">Düşük</option>
                                        <option value="medium" selected>Orta</option>
                                        <option value="high">Yüksek</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                        <label class="form-check-label" for="is_active">
                                            Kural Aktif
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h6 class="mb-3">Bildirim Kanalları</h6>
                        
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="email_enabled" name="email_enabled" checked>
                                    <label class="form-check-label" for="email_enabled">
                                        <i class="fas fa-envelope text-primary"></i> Email
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="sms_enabled" name="sms_enabled">
                                    <label class="form-check-label" for="sms_enabled">
                                        <i class="fas fa-sms text-success"></i> SMS
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="telegram_enabled" name="telegram_enabled">
                                    <label class="form-check-label" for="telegram_enabled">
                                        <i class="fab fa-telegram text-info"></i> Telegram
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="webhook_enabled" name="webhook_enabled">
                                    <label class="form-check-label" for="webhook_enabled">
                                        <i class="fas fa-webhook text-warning"></i> Webhook
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?= $base_url ?>admin/notifications/rules" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> İptal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Kuralı Kaydet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Footer'ı dahil et
include dirname(__DIR__, 4) . '/includes/layout/footer.php';
?>

