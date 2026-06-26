<?php
require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';
require_once dirname(__DIR__, 3) . '/includes/language.php';

requireLogin();

// Admin kontrolü
if (!isAdmin()) {
    header('Location: ' . $base_url . 'dashboard');
    exit;
}

$error_message = '';
$success_message = '';

// Form işleme
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $provider = $_POST['provider'] ?? 'twilio';
    $account_sid = $_POST['account_sid'] ?? '';
    $auth_token = $_POST['auth_token'] ?? '';
    $from_number = $_POST['from_number'] ?? '';
    $enabled = isset($_POST['enabled']) ? 1 : 0;
    
    try {
        // SMS ayarlarını güncelle veya oluştur
        $stmt = $pdo->prepare("SELECT id FROM sms_settings LIMIT 1");
        $stmt->execute();
        $existing = $stmt->fetch();
        
        if ($existing) {
            $stmt = $pdo->prepare("UPDATE sms_settings SET provider = ?, account_sid = ?, auth_token = ?, from_number = ?, enabled = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$provider, $account_sid, $auth_token, $from_number, $enabled, $existing['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO sms_settings (provider, account_sid, auth_token, from_number, enabled) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$provider, $account_sid, $auth_token, $from_number, $enabled]);
        }
        
        $success_message = 'SMS ayarları başarıyla kaydedildi!';
        
    } catch (Exception $e) {
        $error_message = 'SMS ayarları kaydedilirken hata oluştu: ' . $e->getMessage();
    }
}

// SMS ayarlarını getir
$stmt = $pdo->prepare("SELECT * FROM sms_settings LIMIT 1");
$stmt->execute();
$sms_settings = $stmt->fetch() ?: ['provider' => 'twilio', 'enabled' => 0];

// Sayfa başlığı
$page_title = 'SMS Bildirim Ayarları';
$page_description = 'Twilio SMS bildirimlerini yapılandırın';

// Header'ı dahil et
include dirname(__DIR__, 3) . '/includes/layout/header.php';
?>

<div class="container p-3">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $base_url ?>admin">Admin Panel</a></li>
            <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/notifications">Bildirimler</a></li>
            <li class="breadcrumb-item active">SMS Ayarları</li>
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
            <h2 class="section-title"><i class="fas fa-sms"></i> SMS Bildirim Ayarları</h2>
            <p class="text-muted">Twilio üzerinden SMS bildirimleri gönderin</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> Twilio SMS Ayarları</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="provider" class="form-label">SMS Sağlayıcısı</label>
                                    <select class="form-select" id="provider" name="provider" required>
                                        <option value="twilio" <?= $sms_settings['provider'] === 'twilio' ? 'selected' : '' ?>>Twilio</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="from_number" class="form-label">Gönderen Numara</label>
                                    <input type="text" class="form-control" id="from_number" name="from_number" 
                                           value="<?= htmlspecialchars($sms_settings['from_number'] ?? '') ?>" 
                                           placeholder="+1234567890" required>
                                    <div class="form-text">Twilio'dan aldığınız telefon numarası</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="account_sid" class="form-label">Account SID</label>
                            <input type="text" class="form-control" id="account_sid" name="account_sid" 
                                   value="<?= htmlspecialchars($sms_settings['account_sid'] ?? '') ?>" 
                                   placeholder="ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx" required>
                            <div class="form-text">Twilio Console'dan alacağınız Account SID</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="auth_token" class="form-label">Auth Token</label>
                            <input type="password" class="form-control" id="auth_token" name="auth_token" 
                                   value="<?= htmlspecialchars($sms_settings['auth_token'] ?? '') ?>" 
                                   placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxx" required>
                            <div class="form-text">Twilio Console'dan alacağınız Auth Token</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enabled" name="enabled" 
                                       <?= $sms_settings['enabled'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enabled">
                                    SMS bildirimlerini aktif et
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Ayarları Kaydet
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="testSMS()">
                                <i class="fas fa-paper-plane"></i> Test SMS Gönder
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Twilio Kurulum</h5>
                </div>
                <div class="card-body">
                    <h6>1. Twilio Hesabı Oluşturun</h6>
                    <p class="small text-muted">Twilio.com'da ücretsiz hesap oluşturun</p>
                    
                    <h6>2. Telefon Numarası Alın</h6>
                    <p class="small text-muted">Console > Phone Numbers > Buy a number</p>
                    
                    <h6>3. Credentials'ları Alın</h6>
                    <p class="small text-muted">Console > Account > API keys & tokens</p>
                    
                    <h6>4. Test Edin</h6>
                    <p class="small text-muted">Sağdaki "Test SMS Gönder" butonunu kullanın</p>
                    
                    <div class="alert alert-info mt-3">
                        <small>
                            <i class="fas fa-lightbulb"></i> 
                            <strong>İpucu:</strong> Twilio trial hesabında sadece doğrulanmış numaralara SMS gönderebilirsiniz.
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-mobile-alt"></i> Test SMS</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="test_number" class="form-label">Test Telefon Numarası</label>
                        <input type="text" class="form-control" id="test_number" 
                               placeholder="+1234567890">
                        <div class="form-text">Uluslararası format kullanın (+90...)</div>
                    </div>
                    <button type="button" class="btn btn-outline-primary w-100" onclick="testSMS()">
                        <i class="fas fa-paper-plane"></i> Test SMS Gönder
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testSMS() {
    const testNumber = document.getElementById('test_number').value;
    if (!testNumber) {
        alert('Lütfen test telefon numarası girin!');
        return;
    }
    
    if (confirm('Test SMS gönderilsin mi? (' + testNumber + ')')) {
        // AJAX ile test SMS gönder
        fetch('<?= $base_url ?>pages/ajax/test_sms.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                phone_number: testNumber
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Test SMS başarıyla gönderildi!');
            } else {
                alert('SMS gönderilirken hata oluştu: ' + data.message);
            }
        })
        .catch(error => {
            alert('Bağlantı hatası: ' + error.message);
        });
    }
}
</script>

<?php
// Footer'ı dahil et
include dirname(__DIR__, 3) . '/includes/layout/footer.php';
?>
