<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/language.php';

// Zaten giriş yapmışsa dashboard'a yönlendir
if (isset($_SESSION['user_id'])) {
    header('Location: ' . $base_url . 'dashboard');
    exit;
}

$error_message = '';
$success_message = '';

// Kayıt işlemi
if (isset($_POST['action']) && $_POST['action'] === 'register') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = __('required_fields');
    } elseif ($password !== $confirm_password) {
        $error_message = __('password_mismatch');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = __('invalid_email') ?? 'Geçersiz e-posta adresi';
    } else {
        // E-posta kontrolü
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error_message = __('register_error');
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, created_at) VALUES (?, ?, 'user', NOW())");
            
            if ($stmt->execute([$email, $hashed_password])) {
                $success_message = __('register_success');
            } else {
                $error_message = __('registration_failed') ?? 'Kayıt başarısız oldu';
            }
        }
    }
}

// Sayfa başlığı
$page_title = __('register');
$page_description = __('register_title');

// Header'ı dahil et
include __DIR__ . '/../../includes/layout/header.php';
?>
<link rel="stylesheet" href="<?= $base_url ?>assets/css/register.css">
<div class="enterprise-login-wrapper">
    <div class="enterprise-login-container">
        <!-- Sol Taraf - Branding & Info -->
        <div class="enterprise-login-left">
            <div class="branding-content">
                <div class="logo-section">
                    <div class="logo-icon">
                        <i class="fas fa-heartbeat"></i>
                    </div>
                    <h1 class="brand-name"><?= __('app_name') ?></h1>
                    <p class="brand-tagline"><?= __('register_tagline') ?? 'Hemen başlayın ve sitelerinizi izleyin' ?></p>
                </div>
                
                <div class="features-section">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <div class="feature-text">
                            <h3><?= __('quick_setup') ?? 'Hızlı Kurulum' ?></h3>
                            <p><?= __('quick_setup_desc') ?? 'Dakikalar içinde hesabınızı oluşturun' ?></p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-infinity"></i>
                        </div>
                        <div class="feature-text">
                            <h3><?= __('unlimited_monitoring') ?? 'Sınırsız İzleme' ?></h3>
                            <p><?= __('unlimited_monitoring_desc') ?? 'İstediğiniz kadar site ekleyin' ?></p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div class="feature-text">
                            <h3><?= __('free_trial') ?? 'Ücretsiz Deneme' ?></h3>
                            <p><?= __('free_trial_desc') ?? 'Kredi kartı gerektirmez' ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="trust-badges">
                    <div class="badge-item">
                        <i class="fas fa-shield-check"></i>
                        <span><?= __('secure_signup') ?? 'Güvenli Kayıt' ?></span>
                    </div>
                    <div class="badge-item">
                        <i class="fas fa-envelope-circle-check"></i>
                        <span><?= __('no_spam') ?? 'Spam Yok' ?></span>
                    </div>
                    <div class="badge-item">
                        <i class="fas fa-user-shield"></i>
                        <span><?= __('privacy_protected') ?? 'Gizlilik Korumalı' ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sağ Taraf - Register Form -->
        <div class="enterprise-login-right">
            <div class="login-form-container">
                <div class="form-header">
                    <h2><?= __('create_new_account') ?? 'Yeni Hesap Oluştur' ?></h2>
                    <p><?= __('register_subtitle') ?? 'Ücretsiz hesabınızı hemen oluşturun' ?></p>
                </div>
                
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show modern-alert" role="alert">
                        <div class="alert-content">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($error_message) ?></span>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show modern-alert success-alert" role="alert">
                        <div class="alert-content">
                            <i class="fas fa-check-circle"></i>
                            <span><?= htmlspecialchars($success_message) ?></span>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <div class="text-center mb-4">
                        <a href="<?= $base_url ?>login" class="btn enterprise-login-btn">
                            <span><?= __('go_to_login') ?? 'Giriş Sayfasına Git' ?></span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
                
                <?php if (!$success_message): ?>
                <form method="POST" class="enterprise-form">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i>
                            <?= __('email') ?>
                        </label>
                        <input 
                            type="email" 
                            class="form-control modern-input" 
                            id="email" 
                            name="email" 
                            placeholder="ornek@sirket.com" 
                            required
                            autocomplete="email"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>
                            <?= __('password') ?>
                        </label>
                        <div class="password-input-wrapper">
                            <input 
                                type="password" 
                                class="form-control modern-input" 
                                id="password" 
                                name="password" 
                                placeholder="••••••••" 
                                required
                                autocomplete="new-password"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                                <i class="fas fa-eye" id="toggleIcon1"></i>
                            </button>
                        </div>
                        <small class="password-hint"><?= __('password_hint') ?? 'En az 8 karakter kullanın' ?></small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock-keyhole"></i>
                            <?= __('confirm_password') ?>
                        </label>
                        <div class="password-input-wrapper">
                            <input 
                                type="password" 
                                class="form-control modern-input" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder="••••••••" 
                                required
                                autocomplete="new-password"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', 'toggleIcon2')">
                                <i class="fas fa-eye" id="toggleIcon2"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                <?= __('accept_terms') ?? 'Şartları kabul ediyorum' ?>
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn enterprise-login-btn">
                        <span><?= __('create_account') ?></span>
                        <i class="fas fa-user-plus"></i>
                    </button>
                </form>
                <?php endif; ?>
                
                <div class="form-footer">
                    <div class="divider">
                        <span><?= __('or') ?? 'veya' ?></span>
                    </div>
                    
                    <p class="register-link">
                        <?= __('have_account') ?> 
                        <a href="<?= $base_url ?>login"><?= __('login') ?></a>
                    </p>
                    
                    <div class="language-selector-wrapper">
                        <?= getLanguageSelector() ?>
                    </div>
                </div>
            </div>
            
            <div class="login-footer">
                <p>&copy; <?= date('Y') ?> <?= __('app_name') ?>. <?= __('all_rights_reserved') ?? 'Tüm hakları saklıdır.' ?></p>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId, iconId) {
    const passwordInput = document.getElementById(inputId);
    const toggleIcon = document.getElementById(iconId);
    
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

// Form animasyonu
document.querySelector('.enterprise-form')?.addEventListener('submit', function(e) {
    const btn = this.querySelector('.enterprise-login-btn');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span><?= __('creating_account') ?? 'Hesap oluşturuluyor...' ?></span>';
});

// Input focus animasyonları
document.querySelectorAll('.modern-input').forEach(input => {
    input.addEventListener('focus', function() {
        this.parentElement.style.transform = 'scale(1.02)';
    });
    
    input.addEventListener('blur', function() {
        this.parentElement.style.transform = 'scale(1)';
    });
});

// Şifre eşleşme kontrolü
const password = document.getElementById('password');
const confirmPassword = document.getElementById('confirm_password');

confirmPassword?.addEventListener('input', function() {
    if (password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity('<?= __('passwords_not_match') ?? 'Şifreler eşleşmiyor' ?>');
    } else {
        confirmPassword.setCustomValidity('');
    }
});
</script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../../includes/layout/footer.php';
?>
