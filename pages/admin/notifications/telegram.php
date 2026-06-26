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
    $bot_token = $_POST['bot_token'] ?? '';
    $chat_id = $_POST['chat_id'] ?? '';
    $enabled = isset($_POST['enabled']) ? 1 : 0;
    
    try {
        // Telegram ayarlarını güncelle veya oluştur
        $stmt = $pdo->prepare("SELECT id FROM telegram_settings LIMIT 1");
        $stmt->execute();
        $existing = $stmt->fetch();
        
        if ($existing) {
            $stmt = $pdo->prepare("UPDATE telegram_settings SET bot_token = ?, chat_id = ?, enabled = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$bot_token, $chat_id, $enabled, $existing['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO telegram_settings (bot_token, chat_id, enabled) VALUES (?, ?, ?)");
            $stmt->execute([$bot_token, $chat_id, $enabled]);
        }
        
        $success_message = 'Telegram ayarları başarıyla kaydedildi!';
        
    } catch (Exception $e) {
        $error_message = 'Telegram ayarları kaydedilirken hata oluştu: ' . $e->getMessage();
    }
}

// Telegram ayarlarını getir
$stmt = $pdo->prepare("SELECT * FROM telegram_settings LIMIT 1");
$stmt->execute();
$telegram_settings = $stmt->fetch() ?: ['enabled' => 0];

// Sayfa başlığı
$page_title = 'Telegram Bot Ayarları';
$page_description = 'Telegram bot bildirimlerini yapılandırın';

// Header'ı dahil et
include dirname(__DIR__, 3) . '/includes/layout/header.php';
?>

<div class="container p-3">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $base_url ?>admin">Admin Panel</a></li>
            <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/notifications">Bildirimler</a></li>
            <li class="breadcrumb-item active">Telegram Ayarları</li>
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
            <h2 class="section-title"><i class="fab fa-telegram"></i> Telegram Bot Ayarları</h2>
            <p class="text-muted">Telegram bot üzerinden bildirimler gönderin</p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> Bot Ayarları</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="bot_token" class="form-label">Bot Token</label>
                            <input type="text" class="form-control" id="bot_token" name="bot_token" 
                                   value="<?= htmlspecialchars($telegram_settings['bot_token'] ?? '') ?>" 
                                   placeholder="123456789:ABCdefGHIjklMNOpqrsTUVwxyz" required>
                            <div class="form-text">@BotFather'dan aldığınız bot token</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="chat_id" class="form-label">Chat ID</label>
                            <input type="text" class="form-control" id="chat_id" name="chat_id" 
                                   value="<?= htmlspecialchars($telegram_settings['chat_id'] ?? '') ?>" 
                                   placeholder="-123456789" required>
                            <div class="form-text">Bildirimlerin gönderileceği chat ID</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enabled" name="enabled" 
                                       <?= $telegram_settings['enabled'] ? 'checked' : '' ?>>
                                <label class="form-check-label" for="enabled">
                                    Telegram bildirimlerini aktif et
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Ayarları Kaydet
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="testTelegram()">
                                <i class="fas fa-paper-plane"></i> Test Mesajı Gönder
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Bot Kurulum</h5>
                </div>
                <div class="card-body">
                    <h6>1. Bot Oluşturun</h6>
                    <p class="small text-muted">Telegram'da @BotFather'a mesaj gönderin</p>
                    
                    <h6>2. Bot Token Alın</h6>
                    <p class="small text-muted">/newbot komutu ile bot oluşturun</p>
                    
                    <h6>3. Chat ID Bulun</h6>
                    <p class="small text-muted">@userinfobot'a mesaj gönderin</p>
                    
                    <h6>4. Test Edin</h6>
                    <p class="small text-muted">Sağdaki "Test Mesajı" butonunu kullanın</p>
                    
                    <div class="alert alert-info mt-3">
                        <small>
                            <i class="fas fa-lightbulb"></i> 
                            <strong>İpucu:</strong> Grup chat'leri için önce bot'u gruba ekleyin, sonra chat ID'yi alın.
                        </small>
                    </div>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-comment"></i> Chat ID Bulma</h5>
                </div>
                <div class="card-body">
                    <h6>Kişisel Chat:</h6>
                    <p class="small text-muted">@userinfobot'a mesaj gönderin</p>
                    
                    <h6>Grup Chat:</h6>
                    <p class="small text-muted">1. Bot'u gruba ekleyin<br>2. @userinfobot'a mesaj gönderin<br>3. Negatif ID'yi kullanın</p>
                    
                    <button type="button" class="btn btn-outline-primary w-100 mt-2" onclick="getChatId()">
                        <i class="fas fa-search"></i> Chat ID Bul
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testTelegram() {
    if (confirm('Test Telegram mesajı gönderilsin mi?')) {
        // AJAX ile test mesajı gönder
        fetch('<?= $base_url ?>pages/ajax/test_telegram.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Test mesajı başarıyla gönderildi!');
            } else {
                alert('Mesaj gönderilirken hata oluştu: ' + data.message);
            }
        })
        .catch(error => {
            alert('Bağlantı hatası: ' + error.message);
        });
    }
}

function getChatId() {
    const botToken = document.getElementById('bot_token').value;
    if (!botToken) {
        alert('Lütfen önce Bot Token girin!');
        return;
    }
    
    // Bot'un son mesajlarını al
    fetch('<?= $base_url ?>pages/ajax/get_telegram_chat_id.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            bot_token: botToken
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.chat_id) {
            document.getElementById('chat_id').value = data.chat_id;
            alert('Chat ID bulundu: ' + data.chat_id);
        } else {
            alert('Chat ID bulunamadı. Bot\'a mesaj gönderip tekrar deneyin.');
        }
    })
    .catch(error => {
        alert('Bağlantı hatası: ' + error.message);
    });
}
</script>

<?php
// Footer'ı dahil et
include dirname(__DIR__, 3) . '/includes/layout/footer.php';
?>
