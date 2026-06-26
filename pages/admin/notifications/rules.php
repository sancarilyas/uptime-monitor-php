<?php
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';
require_once dirname(__DIR__, 3) . '/includes/language.php';

requireLogin();
requireAdmin();

// Sayfa başlığı
$page_title = 'Bildirim Kuralları';
$page_description = 'Site durumu değişikliklerinde bildirim kurallarını yönetin';

// Header'ı dahil et
include dirname(__DIR__, 3) . '/includes/layout/header.php';

// Bildirim kurallarını getir
$stmt = $pdo->prepare("SELECT * FROM notification_rules ORDER BY event_type");
$stmt->execute();
$rules = $stmt->fetchAll();

// Site listesini getir
$stmt = $pdo->prepare("SELECT id, name FROM sites ORDER BY name");
$stmt->execute();
$sites = $stmt->fetchAll();
?>
<script src="<?php echo $base_url; ?>assets/js/notifications.js?t=<?= time() ?>"></script>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">Bildirim Kuralları</h1>
                    <p class="text-muted mb-0">Site durumu değişikliklerinde bildirim kurallarını yönetin</p>
                </div>
                <a href="<?= $base_url ?>admin/notifications/rules/add" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Yeni Kural Ekle
                </a>
            </div>

            <!-- Bildirim Kuralları Tablosu -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Mevcut Kurallar</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($rules)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Henüz bildirim kuralı tanımlanmamış</h5>
                            <p class="text-muted">Site durumu değişikliklerinde bildirim almak için kural ekleyin.</p>
                            <a href="<?= $base_url ?>admin/notifications/rules/add" class="btn btn-primary">
                                <i class="fas fa-plus"></i> İlk Kuralı Ekle
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Olay Türü</th>
                                        <th>Site</th>
                                        <th>Email</th>
                                        <th>SMS</th>
                                        <th>Telegram</th>
                                        <th>Webhook</th>
                                        <th>Öncelik</th>
                                        <th>Durum</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rules as $rule): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?= $rule['event_type'] === 'up' ? 'success' : ($rule['event_type'] === 'down' ? 'danger' : 'warning') ?>">
                                                    <?php
                                                    switch($rule['event_type']) {
                                                        case 'up': echo 'Site Açıldı'; break;
                                                        case 'down': echo 'Site Kapandı'; break;
                                                        case 'response_time': echo 'Yanıt Süresi Aşımı'; break;
                                                        default: echo ucfirst($rule['event_type']);
                                                    }
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($rule['site_id']): ?>
                                                    <?php 
                                                    $site = array_filter($sites, function($s) use ($rule) { 
                                                        return $s['id'] == $rule['site_id']; 
                                                    });
                                                    $site = reset($site);
                                                    ?>
                                                    <?= $site ? htmlspecialchars($site['name']) : 'Site Bulunamadı' ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Tüm Siteler</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $rule['email_enabled'] ? 'success' : 'secondary' ?>">
                                                    <?= $rule['email_enabled'] ? 'Aktif' : 'Pasif' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $rule['sms_enabled'] ? 'success' : 'secondary' ?>">
                                                    <?= $rule['sms_enabled'] ? 'Aktif' : 'Pasif' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $rule['telegram_enabled'] ? 'success' : 'secondary' ?>">
                                                    <?= $rule['telegram_enabled'] ? 'Aktif' : 'Pasif' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $rule['webhook_enabled'] ? 'success' : 'secondary' ?>">
                                                    <?= $rule['webhook_enabled'] ? 'Aktif' : 'Pasif' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $rule['priority'] === 'high' ? 'danger' : ($rule['priority'] === 'medium' ? 'warning' : 'info') ?>">
                                                    <?= ucfirst($rule['priority']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $rule['is_active'] ? 'success' : 'secondary' ?>">
                                                    <?= $rule['is_active'] ? 'Aktif' : 'Pasif' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="<?php echo $base_url; ?>admin/notifications/rules/edit?id=<?= $rule['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Düzenle">
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
</div>

 


<?php
// Footer'ı dahil et
include dirname(__DIR__, 3) . '/includes/layout/footer.php';
?>

