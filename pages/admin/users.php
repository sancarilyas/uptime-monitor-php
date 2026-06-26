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

// Kullanıcı düzenleme
if ($_POST['action'] ?? '' === 'edit_user') {
    verifyCsrf();
    $user_id = $_POST['user_id'] ?? 0;
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $group_id = $_POST['group_id'] ?? null;
    
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error_message = __('required_fields');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = __('invalid_email') ?? 'Geçersiz e-posta adresi';
    } else {
        // E-posta kontrolü (kendi hariç)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        
        if ($stmt->fetch()) {
            $error_message = __('email_already_exists') ?? 'Bu e-posta adresi zaten kullanılıyor';
        } else {
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, group_id = ?, updated_at = NOW() WHERE id = ?");
            
            if ($stmt->execute([$first_name, $last_name, $email, $role, $group_id, $user_id])) {
                $success_message = __('user_updated_successfully') ?? 'Kullanıcı başarıyla güncellendi';
            } else {
                $error_message = __('user_update_failed') ?? 'Kullanıcı güncellenirken hata oluştu';
            }
        }
    }
}

// Kullanıcı silme
if ($_POST['action'] ?? '' === 'delete_user') {
    verifyCsrf();
    $user_id = $_POST['user_id'] ?? 0;

    if ($user_id && $user_id != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $success_message = __('user_deleted');
        } else {
            $error_message = __('database_error');
        }
    } else {
        $error_message = __('cannot_delete_self');
    }
}

// Tüm kullanıcıları al
$stmt = $pdo->prepare("
    SELECT u.*, 
           g.name as group_name,
           COUNT(s.id) as site_count 
    FROM users u 
    LEFT JOIN `groups` g ON u.group_id = g.id
    LEFT JOIN sites s ON u.id = s.user_id 
    GROUP BY u.id 
    ORDER BY u.created_at DESC
");
$stmt->execute();
$users = $stmt->fetchAll();

// Sayfa başlığı
$page_title = __('user_management');
$page_description = __('user_management_desc');

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
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="section-title"><i class="fas fa-users"></i> <?= __('user_management') ?></h2>
                    <p class="text-muted"><?= __('user_management_desc') ?></p>
                </div>
                <div>
                    <a href="<?= $base_url ?>admin/add_user" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> <?= __('add_user') ?>
                    </a>
                    <a href="<?= $base_url ?>admin/groups" class="btn btn-outline-secondary">
                        <i class="fas fa-users"></i> <?= __('group_management') ?>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Kullanıcılar -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-users"></i> <?= __('users') ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?= __('name') ?></th>
                                    <th><?= __('email') ?></th>
                                    <th><?= __('role') ?></th>
                                    <th><?= __('group') ?></th>
                                    <th><?= __('site_count') ?></th>
                                    <th><?= __('registration_date') ?></th>
                                    <th><?= __('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-user"></i> 
                                            <strong><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></strong>
                                        </td>
                                        <td>
                                            <i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?>
                                        </td>
                                        <td>
                                            <?php if ($user['role'] === 'admin'): ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-user-shield"></i> <?= __('admin_role') ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-user"></i> <?= __('user_role') ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($user['group_name']): ?>
                                                <span class="badge bg-info">
                                                    <i class="fas fa-users"></i> <?= htmlspecialchars($user['group_name']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary"><?= $user['site_count'] ?></span>
                                        </td>
                                        <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="editUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['first_name']) ?>', '<?= htmlspecialchars($user['last_name']) ?>', '<?= htmlspecialchars($user['email']) ?>', '<?= $user['role'] ?>', '<?= $user['group_id'] ?>')">
                                                    <i class="fas fa-edit"></i> <?= __('edit') ?>
                                                </button>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <button class="btn btn-outline-danger" onclick="deleteUser(<?= $user['id'] ?>)">
                                                        <i class="fas fa-trash"></i> <?= __('delete') ?>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-muted small"><?= __('your_account') ?></span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Kullanıcı Düzenleme Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> <?= __('edit') ?> <?= __('user_role') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editUserForm">
<?= csrfField() ?>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_first_name" class="form-label">
                                    <i class="fas fa-user"></i> <?= __('first_name') ?>
                                    <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="edit_first_name" 
                                    name="first_name" 
                                    required
                                >
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_last_name" class="form-label">
                                    <i class="fas fa-user"></i> <?= __('last_name') ?>
                                    <span class="text-danger">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="edit_last_name" 
                                    name="last_name" 
                                    required
                                >
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">
                            <i class="fas fa-envelope"></i> <?= __('email') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="edit_email" 
                            name="email" 
                            required
                        >
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_role" class="form-label">
                                    <i class="fas fa-user-tag"></i> <?= __('role') ?>
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="edit_role" name="role" required>
                                    <option value="user"><?= __('user_role') ?></option>
                                    <option value="admin"><?= __('admin_role') ?></option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_group_id" class="form-label">
                                    <i class="fas fa-users"></i> <?= __('group') ?>
                                </label>
                                <select class="form-select" id="edit_group_id" name="group_id">
                                    <option value=""><?= __('no_group') ?? 'Grup seçiniz' ?></option>
                                    <?php 
                                    $stmt = $pdo->prepare("SELECT * FROM `groups` ORDER BY name");
                                    $stmt->execute();
                                    $groups = $stmt->fetchAll();
                                    foreach ($groups as $group): ?>
                                        <option value="<?= $group['id'] ?>">
                                            <?= htmlspecialchars($group['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                    <button type="submit" class="btn btn-primary"><?= __('update') ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Kullanıcı Silme Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?= __('delete') ?> <?= __('user_role') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?= __('confirm_delete_user') ?></p>
                <p class="text-muted small"><?= __('delete_warning') ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <form method="POST" style="display: inline;">
<?= csrfField() ?>
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <button type="submit" class="btn btn-danger"><?= __('delete') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function editUser(userId, firstName, lastName, email, role, groupId) {
        document.getElementById('edit_user_id').value = userId;
        document.getElementById('edit_first_name').value = firstName;
        document.getElementById('edit_last_name').value = lastName;
        document.getElementById('edit_email').value = email;
        document.getElementById('edit_role').value = role;
        document.getElementById('edit_group_id').value = groupId || '';
        new bootstrap.Modal(document.getElementById('editUserModal')).show();
    }
    
    function deleteUser(userId) {
        document.getElementById('delete_user_id').value = userId;
        new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
    }
</script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../../includes/layout/footer.php';
?>