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

// Mail ayarlarını kaydet
if ($_POST['action'] ?? '' === 'save_mail_settings') {
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = trim($_POST['smtp_port'] ?? '');
    $smtp_username = trim($_POST['smtp_username'] ?? '');
    $smtp_password = trim($_POST['smtp_password'] ?? '');
    $smtp_encryption = trim($_POST['smtp_encryption'] ?? '');
    $from_email = trim($_POST['from_email'] ?? '');
    $from_name = trim($_POST['from_name'] ?? '');
    
    try {
        $pdo->beginTransaction();
        
        // SMTP ayarlarını kaydet
        setSystemSetting('smtp_host', $smtp_host);
        setSystemSetting('smtp_port', $smtp_port);
        setSystemSetting('smtp_username', $smtp_username);
        setSystemSetting('smtp_password', $smtp_password);
        setSystemSetting('smtp_encryption', $smtp_encryption);
        setSystemSetting('from_email', $from_email);
        setSystemSetting('from_name', $from_name);
        
        $pdo->commit();
        $success_message = __('mail_settings_saved');
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = __('database_error');
    }
}

// Mevcut ayarları al
$smtp_host = getSystemSetting('smtp_host', '');
$smtp_port = getSystemSetting('smtp_port', '587');
$smtp_username = getSystemSetting('smtp_username', '');
$smtp_password = getSystemSetting('smtp_password', '');
$smtp_encryption = getSystemSetting('smtp_encryption', 'tls');
$from_email = getSystemSetting('from_email', '');
$from_name = getSystemSetting('from_name', 'Uptime Monitor');

// Sayfa başlığı
$page_title = __('mail_settings');
$page_description = __('mail_settings_desc');

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
            <h2 class="section-title"><i class="fas fa-envelope"></i> <?= __('mail_settings') ?></h2>
            <p class="text-muted"><?= __('mail_settings_desc') ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- SMTP Ayarları -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-server"></i> <?= __('smtp_settings') ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="mailSettingsForm">
                        <input type="hidden" name="action" value="save_mail_settings">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="smtp_host" class="form-label"><?= __('smtp_host') ?> *</label>
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" value="<?= htmlspecialchars($smtp_host) ?>" required>
                                    <div class="form-text"><?= __('smtp_host_example') ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="smtp_port" class="form-label"><?= __('smtp_port') ?> *</label>
                                    <input type="number" class="form-control" id="smtp_port" name="smtp_port" value="<?= htmlspecialchars($smtp_port) ?>" required>
                                    <div class="form-text"><?= __('smtp_port_example') ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="smtp_username" class="form-label"><?= __('smtp_username') ?> *</label>
                                    <input type="text" class="form-control" id="smtp_username" name="smtp_username" value="<?= htmlspecialchars($smtp_username) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="smtp_password" class="form-label"><?= __('smtp_password') ?> *</label>
                                    <input type="password" class="form-control" id="smtp_password" name="smtp_password" value="<?= htmlspecialchars($smtp_password) ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="smtp_encryption" class="form-label"><?= __('smtp_encryption') ?></label>
                                    <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                        <option value="tls" <?= $smtp_encryption === 'tls' ? 'selected' : '' ?>>TLS</option>
                                        <option value="ssl" <?= $smtp_encryption === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                        <option value="" <?= $smtp_encryption === '' ? 'selected' : '' ?>><?= __('none') ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="from_name" class="form-label"><?= __('from_name') ?></label>
                                    <input type="text" class="form-control" id="from_name" name="from_name" value="<?= htmlspecialchars($from_name) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="from_email" class="form-label"><?= __('from_email') ?> *</label>
                            <input type="email" class="form-control" id="from_email" name="from_email" value="<?= htmlspecialchars($from_email) ?>" required>
                            <div class="form-text"><?= __('from_email_example') ?></div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= __('save_settings') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Test Mail -->
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-paper-plane"></i> <?= __('test_mail') ?></h5>
                </div>
                <div class="card-body">
                    <form id="testMailForm">
                        <div class="mb-3">
                            <label for="test_email" class="form-label"><?= __('test_email') ?></label>
                            <input type="email" class="form-control" id="test_email" name="test_email" placeholder="test@example.com" required>
                        </div>
                        <button type="submit" class="btn btn-info w-100">
                            <i class="fas fa-paper-plane"></i> <?= __('send_test_mail') ?>
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Yardım -->
            <div class="card mt-3">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-question-circle"></i> <?= __('help') ?></h5>
                </div>
                <div class="card-body">
                    <h6><?= __('common_providers') ?>:</h6>
                    <ul class="list-unstyled small">
                        <li><strong>Gmail:</strong> smtp.gmail.com:587 (TLS)</li>
                        <li><strong>Yahoo:</strong> smtp.mail.yahoo.com:587 (TLS)</li>
                        <li><strong>Outlook:</strong> smtp-mail.outlook.com:587 (TLS)</li>
                        <li><strong>Yandex:</strong> smtp.yandex.com:587 (TLS)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Test mail form
    document.getElementById('testMailForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const button = this.querySelector('button[type="submit"]');
        const originalText = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?= __('sending') ?>...';
        
        fetch('/uptime/pages/ajax/test_mail.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
            } else {
                showAlert('danger', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', '<?= __('test_mail_error') ?>');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = originalText;
        });
    });
</script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../../includes/layout/footer.php';
?>