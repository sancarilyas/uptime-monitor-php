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

// Giriş işlemi
if (isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($email) || empty($password)) {
        $error_message = __('required_fields');
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            header('Location: ' . $base_url . 'dashboard');
            exit;
        } else {
            $error_message = __('login_error');
        }
    }
}

// Sayfa başlığı
$page_title = __('login');
$page_description = __('login_title');

// Header'ı dahil et
include __DIR__ . '/../../includes/layout/header.php';
?>,
<link rel="stylesheet" href="<?= $base_url ?>assets/css/login.css">

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
                    <p class="brand-tagline"><?= __('login_title') ?></p>
                </div>
                
                <div class="features-section">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="feature-text">
                            <h3><?= __('secure_monitoring') ?? 'Güvenli İzleme' ?></h3>
                            <p><?= __('secure_monitoring_desc') ?? '7/24 güvenli ve kesintisiz izleme' ?></p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="feature-text">
                            <h3><?= __('real_time_analytics') ?? 'Gerçek Zamanlı Analiz' ?></h3>
                            <p><?= __('real_time_analytics_desc') ?? 'Anlık performans metrikleri ve raporlar' ?></p>
                        </div>
                    </div>
                    
                    <div class="feature-item">
                        <div class="feature-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="feature-text">
                            <h3><?= __('instant_notifications') ?? 'Anında Bildirim' ?></h3>
                            <p><?= __('instant_notifications_desc') ?? 'Her türlü kesinti için hızlı uyarılar' ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="trust-badges">
                    <div class="badge-item">
                        <i class="fas fa-lock"></i>
                        <span><?= __('ssl_secured') ?? 'SSL Korumalı' ?></span>
                    </div>
                    <div class="badge-item">
                        <i class="fas fa-database"></i>
                        <span><?= __('data_security') ?? 'Veri Güvenliği' ?></span>
                    </div>
                    <div class="badge-item">
                        <i class="fas fa-clock"></i>
                        <span><?= __('uptime_guarantee') ?? '%99.9 Uptime' ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sağ Taraf - Login Form -->
        <div class="enterprise-login-right">
            <div class="login-form-container">
                <div class="form-header">
                    <h2><?= __('welcome_back') ?? 'Hoş Geldiniz' ?></h2>
                    <p><?= __('login_subtitle') ?? 'Hesabınıza giriş yapın' ?></p>
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
                
                <form method="POST" class="enterprise-form">
                    <input type="hidden" name="action" value="login">
                    
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
                                autocomplete="current-password"
                            >
                            <button type="button" class="password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                <?= __('remember_me') ?? 'Beni hatırla' ?>
                            </label>
                        </div>
                        <a href="#" class="forgot-link"><?= __('forgot_password') ?? 'Şifremi unuttum?' ?></a>
                    </div>
                    
                    <button type="submit" class="btn enterprise-login-btn">
                        <span><?= __('login') ?></span>
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
                
                <div class="form-footer">
                    <div class="divider">
                        <span><?= __('or') ?? 'veya' ?></span>
                    </div>
                    
                    <p class="register-link">
                        <?= __('no_account') ?> 
                        <a href="<?= $base_url ?>register"><?= __('create_account') ?></a>
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
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
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
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span><?= __('logging_in') ?? 'Giriş yapılıyor...' ?></span>';
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
</script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../../includes/layout/footer.php';
?>