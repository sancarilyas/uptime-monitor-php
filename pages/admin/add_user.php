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

// Bu sayfa artık AJAX ile çalışıyor, PHP form işleme kodu kaldırıldı

// Grupları al
$stmt = $pdo->prepare("SELECT * FROM `groups` ORDER BY name");
$stmt->execute();
$groups = $stmt->fetchAll();

// Sayfa başlığı
$page_title = __('add_user');
$page_description = __('add_user_desc');

// Header'ı dahil et
include __DIR__ . '/../../includes/layout/header.php';
?>

<div class="container p-3">
    <!-- Alert Mesajları AJAX ile gösterilecek -->
    <div id="alertContainer"></div>

    <div class="row mb-3">
        <div class="col-12">
            <h2 class="section-title"><i class="fas fa-user-plus"></i> <?= __('add_user') ?></h2>
            <p class="text-muted"><?= __('add_user_desc') ?></p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> <?= __('user_information') ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="addUserForm">
<?= csrfField() ?>
                        <input type="hidden" name="action" value="add_user">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="first_name" class="form-label">
                                        <i class="fas fa-user"></i> <?= __('first_name') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="first_name" 
                                        name="first_name" 
                                        required
                                    >
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="last_name" class="form-label">
                                        <i class="fas fa-user"></i> <?= __('last_name') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="last_name" 
                                        name="last_name" 
                                        required
                                    >
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">
                                <i class="fas fa-envelope"></i> <?= __('email') ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="email" 
                                name="email" 
                                required
                            >
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock"></i> <?= __('password') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <input 
                                            type="password" 
                                            class="form-control" 
                                            id="password" 
                                            name="password" 
                                            required
                                            minlength="6"
                                        >
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('password')">
                                            <i class="fas fa-eye" id="toggleIcon1"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted"><?= __('password_min_length') ?? 'En az 6 karakter' ?></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">
                                        <i class="fas fa-lock"></i> <?= __('confirm_password') ?>
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
                                        >
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye" id="toggleIcon2"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="role" class="form-label">
                                        <i class="fas fa-user-tag"></i> <?= __('role') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="user"><?= __('user_role') ?></option>
                                        <option value="admin"><?= __('admin_role') ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="group_id" class="form-label">
                                        <i class="fas fa-users"></i> <?= __('group') ?>
                                    </label>
                                    <select class="form-select" id="group_id" name="group_id">
                                        <option value=""><?= __('no_group') ?? 'Grup seçiniz' ?></option>
                                        <?php foreach ($groups as $group): ?>
                                            <option value="<?= $group['id'] ?>">
                                                <?= htmlspecialchars($group['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> <?= __('add_user') ?>
                            </button>
                            <a href="<?= $base_url ?>admin/users" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> <?= __('back_to_users') ?? 'Kullanıcılara Dön' ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> <?= __('information') ?></h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb"></i> <?= __('tips') ?? 'İpuçları' ?></h6>
                        <ul class="mb-0 small">
                            <li><?= __('password_tip') ?? 'Güçlü bir şifre kullanın' ?></li>
                            <li><?= __('group_tip') ?? 'Kullanıcıyı bir gruba atayabilirsiniz' ?></li>
                            <li><?= __('role_tip') ?? 'Admin kullanıcılar tüm özelliklere erişebilir' ?></li>
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
    const toggleIcon = document.getElementById(inputId === 'password' ? 'toggleIcon1' : 'toggleIcon2');
    
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
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;
    
    if (password !== confirmPassword) {
        this.setCustomValidity('<?= __('passwords_not_match') ?? 'Şifreler eşleşmiyor' ?>');
    } else {
        this.setCustomValidity('');
    }
});

// Form validasyonu
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        alert('<?= __('passwords_not_match') ?? 'Şifreler eşleşmiyor' ?>');
        return false;
    }
    
    // Form verilerini topla
    const formData = {
        email: document.getElementById('email').value,
        password: password,
        confirm_password: confirmPassword,
        first_name: document.getElementById('first_name').value,
        last_name: document.getElementById('last_name').value,
        role: document.getElementById('role').value,
        group_id: document.getElementById('group_id').value || null
    };
    
    // AJAX ile gönder
    fetch('<?= $base_url ?>ajax/add_user', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            document.getElementById('addUserForm').reset();
            // Sayfayı yenile
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showAlert(data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('<?= __('database_error') ?? 'Bir hata oluştu' ?>', 'danger');
    });
});

// Alert gösterme fonksiyonu
function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    const alertId = 'alert-' + Date.now();
    
    const alertHtml = `
        <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    alertContainer.innerHTML = alertHtml;
    
    // 5 saniye sonra otomatik kapat
    setTimeout(() => {
        const alertElement = document.getElementById(alertId);
        if (alertElement) {
            const bsAlert = new bootstrap.Alert(alertElement);
            bsAlert.close();
        }
    }, 5000);
}
</script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../../includes/layout/footer.php';
?>
