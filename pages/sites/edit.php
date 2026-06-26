<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/language.php';

requireLogin();

$error_message = '';
$success_message = '';

// Site ID kontrolü
$site_id = $_GET['id'] ?? '';
if (empty($site_id) || !is_numeric($site_id)) {
    header('Location: ' . $base_url . 'sites');
    exit;
}

// Kullanıcının grup bilgisini al
$user_group_id = null;
$user_role = null;
$stmt = $pdo->prepare("SELECT group_id, role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if ($user) {
    $user_group_id = $user['group_id'];
    $user_role = $user['role'];
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
    header('Location: ' . $base_url . 'sites');
    exit;
}

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['name'] ?? '');
    $url = trim($_POST['url'] ?? '');
    $monitor_path = trim($_POST['monitor_path'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $check_interval = intval($_POST['check_interval'] ?? 60);
    $notification_emails = trim($_POST['notification_emails'] ?? '');
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
    $is_public = isset($_POST['is_public']) ? 1 : 0;
    $check_keyword = trim($_POST['check_keyword'] ?? '');
    $ssl_monitor = isset($_POST['ssl_monitor']) ? 1 : 0;
    
    // Validasyon
    if (empty($name) || empty($url)) {
        $error_message = 'Site adı ve URL gerekli!';
    } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
        $error_message = 'Geçerli bir URL girin!';
    } elseif ($check_interval < 30) {
        $error_message = 'Kontrol aralığı en az 30 saniye olmalı!';
    } else {
        try {
            // URL'yi normalize et
            $url = rtrim($url, '/');
            
            // Site bilgilerini güncelle - Site sahibi veya grup yöneticisi güncelleyebilir
            $can_edit = false;
            
            // Site sahibi mi kontrol et
            if ($site['user_id'] == $_SESSION['user_id']) {
                $can_edit = true;
            } else {
                // Grup yöneticisi mi kontrol et
                if ($user_role === 'admin' && $site['group_id'] == $user_group_id) {
                    $can_edit = true;
                }
            }
            
            if ($can_edit) {
                $stmt = $pdo->prepare("
                    UPDATE sites SET 
                    name = ?, url = ?, monitor_path = ?, description = ?, check_interval = ?, notification_emails = ?,
                    group_id = ?, notifications_enabled = ?, email_notifications = ?, telegram_notifications = ?,
                    sms_notifications = ?, webhook_notifications = ?, notify_on_down = ?, notify_on_up = ?,
                    notification_priority = ?, is_public = ?, check_keyword = ?, ssl_monitor = ?, updated_at = NOW()
                    WHERE id = ? AND (user_id = ? OR (group_id = ? AND ? = 1))
                ");

                // Admin kontrolü için parametre
                $is_admin = ($user_role === 'admin') ? 1 : 0;

                $stmt->execute([
                    $name, $url, $monitor_path, $description, $check_interval, $notification_emails, $group_id,
                    $notifications_enabled, $email_notifications, $telegram_notifications,
                    $sms_notifications, $webhook_notifications, $notify_on_down, $notify_on_up,
                    $notification_priority, $is_public, $check_keyword, $ssl_monitor, $site_id, $_SESSION['user_id'], $user_group_id, $is_admin
                ]);
            } else {
                throw new Exception('Bu siteyi düzenleme yetkiniz yok');
            }
            
            $success_message = 'Site başarıyla güncellendi!';
            
            // Güncellenmiş bilgileri al
            $stmt = $pdo->prepare("
                SELECT s.*, 
                       g.name as group_name
                FROM sites s 
                LEFT JOIN `groups` g ON s.group_id = g.id
                WHERE s.id = ?
            ");
            $stmt->execute([$site_id]);
            $site = $stmt->fetch();
            
        } catch (Exception $e) {
            $error_message = 'Site güncellenirken hata oluştu: ' . $e->getMessage();
        }
    }
}

// Düzenleme yetkisi kontrolü
$can_edit_site = false;
if ($site['user_id'] == $_SESSION['user_id']) {
    $can_edit_site = true; // Site sahibi
} elseif ($user_role === 'admin' && $site['group_id'] == $user_group_id) {
    $can_edit_site = true; // Grup yöneticisi
}

// Sayfa başlığı
$page_title = 'Site Düzenle - ' . $site['name'];
$page_description = 'Site ayarlarını düzenleyin';

// Header'ı dahil et
include __DIR__ . '/../../includes/layout/header.php';
?>

<div class="container p-3">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $base_url ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $base_url ?>sites">Sitelerim</a></li>
            <li class="breadcrumb-item"><a href="<?= $base_url ?>sites/detail?id=<?= $site_id ?>"><?= htmlspecialchars($site['name']) ?></a></li>
            <li class="breadcrumb-item active">Düzenle</li>
        </ol>
    </nav>

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
            <h2 class="section-title"><i class="fas fa-edit"></i> Site Düzenle</h2>
            <p class="text-muted">Site ayarlarını düzenleyin</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> Site Ayarları</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?= csrfField() ?>
                        <div class="mb-3">
                            <label for="name" class="form-label">Site Adı</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?= htmlspecialchars($site['name']) ?>" 
                                   <?= !$can_edit_site ? 'readonly' : '' ?> required>
                            <div class="form-text">Site için görünen ad</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="url" class="form-label">Site URL</label>
                            <input type="url" class="form-control" id="url" name="url" 
                                   value="<?= htmlspecialchars($site['url']) ?>" 
                                   <?= !$can_edit_site ? 'readonly' : '' ?> required>
                            <div class="form-text">Tam URL (örn: https://example.com)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="monitor_path" class="form-label">Monitor Yolu (Opsiyonel)</label>
                            <input type="text" class="form-control" id="monitor_path" name="monitor_path" 
                                   value="<?= htmlspecialchars($site['monitor_path']) ?>" 
                                   placeholder="api/health, status.php, admin/"
                                   <?= !$can_edit_site ? 'readonly' : '' ?>>
                            <div class="form-text">Belirli bir sayfa veya API endpoint'i kontrol etmek için</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Açıklama (Opsiyonel)</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      <?= !$can_edit_site ? 'readonly' : '' ?>><?= htmlspecialchars($site['description']) ?></textarea>
                            <div class="form-text">Site hakkında notlar</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="check_interval" class="form-label">Kontrol Aralığı (Saniye)</label>
                            <select class="form-select" id="check_interval" name="check_interval" 
                                    <?= !$can_edit_site ? 'disabled' : '' ?>>
                                <option value="30" <?= ($site['check_interval'] ?? 60) == 30 ? 'selected' : '' ?>>30 saniye</option>
                                <option value="60" <?= ($site['check_interval'] ?? 60) == 60 ? 'selected' : '' ?>>1 dakika</option>
                                <option value="300" <?= ($site['check_interval'] ?? 60) == 300 ? 'selected' : '' ?>>5 dakika</option>
                                <option value="600" <?= ($site['check_interval'] ?? 60) == 600 ? 'selected' : '' ?>>10 dakika</option>
                                <option value="1800" <?= ($site['check_interval'] ?? 60) == 1800 ? 'selected' : '' ?>>30 dakika</option>
                            </select>
                            <div class="form-text">Site ne sıklıkla kontrol edilsin</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notification_emails" class="form-label">Bildirim E-postaları</label>
                            <input type="text" class="form-control" id="notification_emails" name="notification_emails" 
                                   value="<?= htmlspecialchars($site['notification_emails']) ?>" 
                                   placeholder="admin@example.com, support@example.com"
                                   <?= !$can_edit_site ? 'readonly' : '' ?>>
                            <div class="form-text">Virgülle ayırarak birden fazla e-posta girebilirsiniz</div>
                        </div>
                        
                        <?php if ($user_group_id): ?>
                        <div class="mb-3">
                            <label for="group_id" class="form-label"><?= __('group') ?></label>
                            <select class="form-select" id="group_id" name="group_id" 
                                    <?= !$can_edit_site ? 'disabled' : '' ?>>
                                <option value=""><?= __('no_group') ?? 'Grup seçiniz' ?></option>
                                <?php 
                                $stmt = $pdo->prepare("SELECT * FROM `groups` WHERE id = ?");
                                $stmt->execute([$user_group_id]);
                                $user_group = $stmt->fetch();
                                if ($user_group): ?>
                                    <option value="<?= $user_group['id'] ?>" <?= ($site['group_id'] == $user_group['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($user_group['name']) ?>
                                    </option>
                                <?php endif; ?>
                            </select>
                            <div class="form-text">Bu siteyi grubunuzla paylaşabilirsiniz</div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Gelişmiş İzleme -->
                        <hr>
                        <h6 class="mb-3">🔎 Gelişmiş İzleme</h6>
                        <div class="mb-3">
                            <label for="check_keyword" class="form-label">İçerik / Keyword Kontrolü</label>
                            <input type="text" class="form-control" id="check_keyword" name="check_keyword"
                                   value="<?= htmlspecialchars($site['check_keyword'] ?? '') ?>"
                                   placeholder="örn. Giriş Yap veya &lt;title&gt;içeriği"
                                   <?= !$can_edit_site ? 'disabled' : '' ?>>
                            <div class="form-text">
                                Doldurulursa: sayfa 200 dönse bile bu metni içermiyorsa site
                                <strong>"kesinti"</strong> sayılır (boş sayfa / hata sayfası tespiti).
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ssl_monitor" name="ssl_monitor"
                                       <?= !empty($site['ssl_monitor']) ? 'checked' : '' ?>
                                       <?= !$can_edit_site ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="ssl_monitor">
                                    <strong>SSL sertifikası son kullanma izleme</strong>
                                </label>
                                <div class="form-text">
                                    HTTPS siteler için sertifika bitişine 14 gün kala bildirim gönderir.
                                    <?php if (!empty($site['ssl_expires_at'])): ?>
                                        <br>Son geçerlilik: <strong><?= date('d.m.Y', strtotime($site['ssl_expires_at'])) ?></strong>
                                        (<?= max(0, (int)floor((strtotime($site['ssl_expires_at']) - time()) / 86400)) ?> gün)
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Public Görünürlük -->
                        <hr>
                        <h6 class="mb-3">🌐 Public Durum Sayfası</h6>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="is_public" name="is_public"
                                       <?= !empty($site['is_public']) ? 'checked' : '' ?>
                                       <?= !$can_edit_site ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="is_public">
                                    <strong>Public status sayfasında göster</strong>
                                </label>
                                <div class="form-text">
                                    Etkinleştirilirse bu site, giriş gerektirmeyen
                                    <a href="<?= $base_url ?>status" target="_blank" rel="noopener">/status</a>
                                    sayfasında herkese açık olarak listelenir.
                                </div>
                            </div>
                        </div>

                        <!-- Bildirim Ayarları -->
                        <hr>
                        <h6 class="mb-3">🔔 Bildirim Ayarları</h6>

                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="notifications_enabled" name="notifications_enabled"
                                       <?= ($site['notifications_enabled'] ?? 1) ? 'checked' : '' ?>
                                       <?= !$can_edit_site ? 'disabled' : '' ?>>
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
                                        <input class="form-check-input" type="checkbox" id="notify_on_down" name="notify_on_down" 
                                               <?= ($site['notify_on_down'] ?? 1) ? 'checked' : '' ?>
                                               <?= !$can_edit_site ? 'disabled' : '' ?>>
                                        <label class="form-check-label" for="notify_on_down">
                                            Site kapandığında bildirim gönder
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="notify_on_up" name="notify_on_up" 
                                               <?= ($site['notify_on_up'] ?? 1) ? 'checked' : '' ?>
                                               <?= !$can_edit_site ? 'disabled' : '' ?>>
                                        <label class="form-check-label" for="notify_on_up">
                                            Site açıldığında bildirim gönder
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notification_priority" class="form-label">Bildirim Önceliği</label>
                            <select class="form-select" id="notification_priority" name="notification_priority" 
                                    <?= !$can_edit_site ? 'disabled' : '' ?>>
                                <option value="low" <?= ($site['notification_priority'] ?? 'medium') === 'low' ? 'selected' : '' ?>>Düşük</option>
                                <option value="medium" <?= ($site['notification_priority'] ?? 'medium') === 'medium' ? 'selected' : '' ?>>Orta</option>
                                <option value="high" <?= ($site['notification_priority'] ?? 'medium') === 'high' ? 'selected' : '' ?>>Yüksek</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Bildirim Kanalları</label>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" 
                                               <?= ($site['email_notifications'] ?? 1) ? 'checked' : '' ?>
                                               <?= !$can_edit_site ? 'disabled' : '' ?>>
                                        <label class="form-check-label" for="email_notifications">
                                            <i class="fas fa-envelope text-primary"></i> Email
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="telegram_notifications" name="telegram_notifications" 
                                               <?= ($site['telegram_notifications'] ?? 0) ? 'checked' : '' ?>
                                               <?= !$can_edit_site ? 'disabled' : '' ?>>
                                        <label class="form-check-label" for="telegram_notifications">
                                            <i class="fab fa-telegram text-info"></i> Telegram
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="sms_notifications" name="sms_notifications" 
                                               <?= ($site['sms_notifications'] ?? 0) ? 'checked' : '' ?>
                                               <?= !$can_edit_site ? 'disabled' : '' ?>>
                                        <label class="form-check-label" for="sms_notifications">
                                            <i class="fas fa-sms text-success"></i> SMS
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="webhook_notifications" name="webhook_notifications" 
                                               <?= ($site['webhook_notifications'] ?? 0) ? 'checked' : '' ?>
                                               <?= !$can_edit_site ? 'disabled' : '' ?>>
                                        <label class="form-check-label" for="webhook_notifications">
                                            <i class="fas fa-webhook text-warning"></i> Webhook
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <?php if ($can_edit_site): ?>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Kaydet
                                </button>
                            <?php else: ?>
                                <div class="alert alert-warning mb-0">
                                    <i class="fas fa-lock"></i> Bu siteyi düzenleme yetkiniz yok. Sadece görüntüleme modundasınız.
                                </div>
                            <?php endif; ?>
                            <a href="<?= $base_url ?>sites/detail?id=<?= $site_id ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> İptal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Bilgi</h5>
                </div>
                <div class="card-body">
                    <h6>Monitor Yolu</h6>
                    <p class="small text-muted">
                        Eğer sadece ana sayfayı değil, belirli bir sayfayı kontrol etmek istiyorsanız bu alanı kullanın.
                    </p>
                    
                    <h6>Örnekler:</h6>
                    <ul class="small text-muted">
                        <li><code>api/health</code> → https://example.com/api/health</li>
                        <li><code>status.php</code> → https://example.com/status.php</li>
                        <li><code>admin/</code> → https://example.com/admin/</li>
                    </ul>
                    
                    <h6>Kontrol Aralığı</h6>
                    <p class="small text-muted">
                        Daha sık kontrol = daha hızlı bildirim, ama daha fazla sunucu yükü.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Footer'ı dahil et
include __DIR__ . '/../../includes/layout/footer.php';
?>
