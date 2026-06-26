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

// Çıkış işlemi
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $base_url . 'login');
    exit;
}

$error_message = '';
$success_message = '';

// İstatistikleri hesapla
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
$stmt->execute();
$user_count = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM sites");
$stmt->execute();
$site_count = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM sites WHERE last_status = 'up'");
$stmt->execute();
$active_sites = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM sites WHERE last_status = 'down'");
$stmt->execute();
$down_sites = $stmt->fetch()['total'];

// Sayfa başlığı
$page_title = __('admin_panel');
$page_description = __('system_management');

// Header'ı dahil et
include __DIR__ . '/../../includes/layout/header.php';
?>

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
            <h2 class="section-title"><i class="fas fa-cog"></i> <?= __('admin_panel') ?></h2>
            <p class="text-muted"><?= __('system_management') ?></p>
        </div>
    </div>

    <!-- Sistem İstatistikleri -->
    <div class="row g-2 mb-4">
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card stat-card h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stats-number"><?= $user_count ?></div>
                    <div class="stats-label"><?= __('user_count') ?></div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="card stat-card h-100">
                <div class="card-body text-center d-flex flex-column justify-content-center">
                    <div class="stat-icon mb-2">
                        <i class="fas fa-globe"></i>
                    </div>
                    <div class="stats-number"><?= $site_count ?></div>
                    <div class="stats-label"><?= __('site_count') ?></div>
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
                    <div class="stats-number"><?= $down_sites ?></div>
                    <div class="stats-label"><?= __('down_sites') ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Manuel Kontrol Butonu -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title mb-3">
                        <i class="fas fa-play-circle text-primary"></i> 
                        Tüm Siteleri Manuel Kontrol Et
                    </h5>
                    <p class="card-text text-muted mb-3">
                        Tüm siteleri anında kontrol etmek için aşağıdaki butona tıklayın. Bu işlem arka planda çalışacaktır.
                    </p>
                    <button type="button" class="btn btn-primary btn-lg" id="checkAllSitesBtn" onclick="checkAllSites()">
                        <i class="fas fa-play"></i> 
                        <span id="checkAllSitesText">Tüm Siteleri Kontrol Et</span>
                        <span id="checkAllSitesSpinner" class="spinner-border spinner-border-sm ms-2" style="display: none;"></span>
                    </button>
                    <div id="checkAllSitesResult" class="mt-3" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin Paneli Linkleri -->
    <div class="row g-3">
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-users fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title"><?= __('user_management') ?></h5>
                    <p class="card-text text-muted"><?= __('user_management_desc') ?></p>
                    <a href="<?= $base_url ?>admin/users" class="btn btn-primary">
                        <i class="fas fa-users"></i> <?= __('users') ?>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-globe fa-3x text-success"></i>
                    </div>
                    <h5 class="card-title"><?= __('site_management') ?></h5>
                    <p class="card-text text-muted"><?= __('site_management_desc') ?></p>
                    <a href="<?= $base_url ?>admin/sites" class="btn btn-success">
                        <i class="fas fa-globe"></i> <?= __('all_sites') ?>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-envelope fa-3x text-info"></i>
                    </div>
                    <h5 class="card-title"><?= __('mail_settings') ?></h5>
                    <p class="card-text text-muted"><?= __('mail_settings_desc') ?></p>
                    <a href="<?= $base_url ?>settings/mail" class="btn btn-info">
                        <i class="fas fa-envelope"></i> <?= __('mail_settings') ?>
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-bell fa-3x text-warning"></i>
                    </div>
                    <h5 class="card-title">Bildirim Ayarları</h5>
                    <p class="card-text text-muted">SMS, Telegram ve Webhook bildirimlerini yönetin</p>
                    <a href="<?= $base_url ?>admin/notifications" class="btn btn-warning">
                        <i class="fas fa-bell"></i> Bildirimler
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-list-alt fa-3x text-primary"></i>
                    </div>
                    <h5 class="card-title">Tüm Site Logları</h5>
                    <p class="card-text text-muted">Tüm sitelerin loglarını, tarihlerini ve up/down durumlarını tek sayfada görüntüleyin</p>
                    <a href="<?= $base_url ?>admin/site_logs" class="btn btn-primary">
                        <i class="fas fa-list-alt"></i> Tüm Site Logları
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-chart-line fa-3x text-secondary"></i>
                    </div>
                    <h5 class="card-title">Sistem Durumu</h5>
                    <p class="card-text text-muted">Monitor servisleri ve sistem durumunu kontrol edin</p>
                    <a href="<?= $base_url ?>admin/monitor" class="btn btn-secondary">
                        <i class="fas fa-chart-line"></i> Sistem Durumu
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function checkAllSites() {
    const btn = document.getElementById('checkAllSitesBtn');
    const text = document.getElementById('checkAllSitesText');
    const spinner = document.getElementById('checkAllSitesSpinner');
    const result = document.getElementById('checkAllSitesResult');
    
    // Butonu devre dışı bırak ve spinner göster
    btn.disabled = true;
    text.textContent = 'Kontrol Ediliyor...';
    spinner.style.display = 'inline-block';
    result.style.display = 'none';
    
    // AJAX isteği gönder
    fetch('<?= $base_url ?>pages/ajax/check_all_sites.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        // Butonu tekrar aktif et
        btn.disabled = false;
        text.textContent = 'Tüm Siteleri Kontrol Et';
        spinner.style.display = 'none';
        
        // Sonucu göster
        result.style.display = 'block';
        if (data.success) {
            result.innerHTML = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle"></i> ${data.message}
                    <br><small class="text-muted">Sayfa 2 saniye sonra yenilenecek...</small>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Başarılı olduğunda sayfayı yenile
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            result.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i> ${data.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Hata durumunda 5 saniye sonra sonucu gizle
            setTimeout(() => {
                result.style.display = 'none';
            }, 5000);
        }
    })
    .catch(error => {
        // Hata durumunda butonu tekrar aktif et
        btn.disabled = false;
        text.textContent = 'Tüm Siteleri Kontrol Et';
        spinner.style.display = 'none';
        
        result.style.display = 'block';
        result.innerHTML = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> Bağlantı hatası oluştu.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        setTimeout(() => {
            result.style.display = 'none';
        }, 5000);
    });
}
</script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../../includes/layout/footer.php';
?>