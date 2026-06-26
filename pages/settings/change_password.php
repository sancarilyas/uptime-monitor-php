<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/language.php';

requireLogin();

$error_message = '';
$success_message = '';

// Şifre değiştirme işlemi
if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = __('required_fields');
    } elseif ($new_password !== $confirm_password) {
        $error_message = __('password_mismatch');
    } elseif (strlen($new_password) < 6) {
        $error_message = __('password_too_short') ?? 'Yeni şifre en az 6 karakter olmalıdır';
    } else {
        // Mevcut şifre kontrolü
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($current_password, $user['password_hash'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            
            if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                $success_message = __('password_changed_successfully') ?? 'Şifreniz başarıyla değiştirildi';
                // Formu temizle
                $_POST = array();
            } else {
                $error_message = __('password_change_failed') ?? 'Şifre değiştirilirken hata oluştu';
            }
        } else {
            $error_message = __('current_password_incorrect') ?? 'Mevcut şifre yanlış';
        }
    }
}

// Sayfa başlığı
$page_title = __('change_password');
$page_description = __('change_password_desc');

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
            <h2 class="section-title"><i class="fas fa-lock"></i> <?= __('change_password') ?></h2>
            <p class="text-muted"><?= __('change_password_desc') ?></p>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-lock"></i> <?= __('password_settings') ?? 'Şifre Ayarları' ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="changePasswordForm">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="mb-3">
                            <label for="current_password" class="form-label">
                                <i class="fas fa-key"></i> <?= __('current_password') ?>
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="current_password" 
                                    name="current_password" 
                                    required
                                    autocomplete="current-password"
                                >
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye" id="toggleIcon1"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label">
                                <i class="fas fa-lock"></i> <?= __('new_password') ?>
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="new_password" 
                                    name="new_password" 
                                    required
                                    minlength="6"
                                    autocomplete="new-password"
                                >
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                    <i class="fas fa-eye" id="toggleIcon2"></i>
                                </button>
                            </div>
                            <small class="text-muted"><?= __('password_min_length') ?? 'En az 6 karakter' ?></small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock"></i> <?= __('confirm_new_password') ?>
                                <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    required
                                    minlength="6"
                                    autocomplete="new-password"
                                >
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="toggleIcon3"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= __('change_password') ?>
                            </button>
                            <a href="<?= $base_url ?>dashboard" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> <?= __('back_to_dashboard') ?? 'Dashboard\'a Dön' ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-shield-alt"></i> <?= __('security_tips') ?? 'Güvenlik İpuçları' ?></h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-lightbulb"></i> <?= __('password_tips') ?? 'Şifre İpuçları' ?></h6>
                        <ul class="mb-0 small">
                            <li><?= __('strong_password_tip') ?? 'Güçlü bir şifre kullanın' ?></li>
                            <li><?= __('unique_password_tip') ?? 'Farklı siteler için farklı şifreler kullanın' ?></li>
                            <li><?= __('regular_change_tip') ?? 'Şifrenizi düzenli olarak değiştirin' ?></li>
                            <li><?= __('never_share_tip') ?? 'Şifrenizi kimseyle paylaşmayın' ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const passwordInput = document.getElementById(inputId);
    let toggleIcon;
    
    switch(inputId) {
        case 'current_password':
            toggleIcon = document.getElementById('toggleIcon1');
            break;
        case 'new_password':
            toggleIcon = document.getElementById('toggleIcon2');
            break;
        case 'confirm_password':
            toggleIcon = document.getElementById('toggleIcon3');
            break;
    }
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Şifre eşleşme kontrolü
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('<?= __('passwords_not_match') ?? 'Şifreler eşleşmiyor' ?>');
    } else {
        this.setCustomValidity('');
    }
});

// Form validasyonu
document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('<?= __('passwords_not_match') ?? 'Yeni şifreler eşleşmiyor' ?>');
        return false;
    }
    
    if (currentPassword === newPassword) {
        e.preventDefault();
        alert('<?= __('same_password_error') ?? 'Yeni şifre mevcut şifre ile aynı olamaz' ?>');
        return false;
    }
});

// Şifre güçlülük kontrolü
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strength = checkPasswordStrength(password);
    
    // Şifre güçlülük göstergesi eklenebilir
    console.log('Şifre güçlülüğü:', strength);
});

function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 6) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    return strength;
}
</script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../../includes/layout/footer.php';
?>
