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

// Grup ekleme işlemi
if (isset($_POST['action']) && $_POST['action'] === 'add_group') {
    verifyCsrf();
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        $error_message = __('group_name_required') ?? 'Grup adı gereklidir';
    } else {
        // Grup adı kontrolü
        $stmt = $pdo->prepare("SELECT id FROM `groups` WHERE name = ?");
        $stmt->execute([$name]);
        
        if ($stmt->fetch()) {
            $error_message = __('group_name_exists') ?? 'Bu grup adı zaten kullanılıyor';
        } else {
            $stmt = $pdo->prepare("INSERT INTO `groups` (name, description, created_by, created_at) VALUES (?, ?, ?, NOW())");
            
            if ($stmt->execute([$name, $description, $_SESSION['user_id']])) {
                $success_message = __('group_added_successfully') ?? 'Grup başarıyla eklendi';
                // Formu temizle
                $_POST = array();
            } else {
                $error_message = __('group_add_failed') ?? 'Grup eklenirken hata oluştu';
            }
        }
    }
}

// Grup silme işlemi
if (isset($_POST['action']) && $_POST['action'] === 'delete_group') {
    verifyCsrf();
    $group_id = $_POST['group_id'] ?? 0;
    
    if ($group_id) {
        // Grupta kullanıcı var mı kontrol et
        $stmt = $pdo->prepare("SELECT COUNT(*) as user_count FROM users WHERE group_id = ?");
        $stmt->execute([$group_id]);
        $result = $stmt->fetch();
        
        if ($result['user_count'] > 0) {
            $error_message = __('cannot_delete_group_with_users') ?? 'Kullanıcıları olan grup silinemez';
        } else {
            $stmt = $pdo->prepare("DELETE FROM `groups` WHERE id = ?");
            if ($stmt->execute([$group_id])) {
                $success_message = __('group_deleted_successfully') ?? 'Grup başarıyla silindi';
            } else {
                $error_message = __('group_delete_failed') ?? 'Grup silinirken hata oluştu';
            }
        }
    }
}

// Grup düzenleme işlemi
if (isset($_POST['action']) && $_POST['action'] === 'edit_group') {
    verifyCsrf();
    $group_id = $_POST['group_id'] ?? 0;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($name)) {
        $error_message = __('group_name_required') ?? 'Grup adı gereklidir';
    } else {
        // Grup adı kontrolü (kendi hariç)
        $stmt = $pdo->prepare("SELECT id FROM `groups` WHERE name = ? AND id != ?");
        $stmt->execute([$name, $group_id]);
        
        if ($stmt->fetch()) {
            $error_message = __('group_name_exists') ?? 'Bu grup adı zaten kullanılıyor';
        } else {
            $stmt = $pdo->prepare("UPDATE `groups` SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
            
            if ($stmt->execute([$name, $description, $group_id])) {
                $success_message = __('group_updated_successfully') ?? 'Grup başarıyla güncellendi';
            } else {
                $error_message = __('group_update_failed') ?? 'Grup güncellenirken hata oluştu';
            }
        }
    }
}

// Tüm grupları al
$stmt = $pdo->prepare("
    SELECT g.*, 
           u.email as created_by_email,
           COUNT(ug.id) as user_count
    FROM `groups` g 
    LEFT JOIN users u ON g.created_by = u.id 
    LEFT JOIN users ug ON g.id = ug.group_id
    GROUP BY g.id 
    ORDER BY g.created_at DESC
");
$stmt->execute();
$groups = $stmt->fetchAll();

// Sayfa başlığı
$page_title = __('group_management');
$page_description = __('group_management_desc');

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
            <h2 class="section-title"><i class="fas fa-users"></i> <?= __('group_management') ?></h2>
            <p class="text-muted"><?= __('group_management_desc') ?></p>
        </div>
    </div>

    <!-- Grup Ekleme Formu -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-plus"></i> <?= __('add_group') ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="addGroupForm">
<?= csrfField() ?>
                        <input type="hidden" name="action" value="add_group">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-tag"></i> <?= __('group_name') ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="name" 
                                        name="name" 
                                        value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                        required
                                    >
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="description" class="form-label">
                                        <i class="fas fa-align-left"></i> <?= __('description') ?>
                                    </label>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="description" 
                                        name="description" 
                                        value="<?= htmlspecialchars($_POST['description'] ?? '') ?>"
                                    >
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> <?= __('add_group') ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Gruplar Listesi -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-users"></i> <?= __('groups') ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?= __('group_name') ?></th>
                                    <th><?= __('description') ?></th>
                                    <th><?= __('user_count') ?></th>
                                    <th><?= __('created_by') ?></th>
                                    <th><?= __('created_at') ?></th>
                                    <th><?= __('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($groups as $group): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-users text-primary"></i> 
                                            <strong><?= htmlspecialchars($group['name']) ?></strong>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($group['description'] ?: '-') ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?= $group['user_count'] ?></span>
                                        </td>
                                        <td>
                                            <i class="fas fa-user"></i> <?= htmlspecialchars($group['created_by_email']) ?>
                                        </td>
                                        <td><?= date('d.m.Y H:i', strtotime($group['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editGroup(<?= $group['id'] ?>, '<?= htmlspecialchars($group['name']) ?>', '<?= htmlspecialchars($group['description']) ?>')">
                                                <i class="fas fa-edit"></i> <?= __('edit') ?>
                                            </button>
                                            <?php if ($group['user_count'] == 0): ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteGroup(<?= $group['id'] ?>, '<?= htmlspecialchars($group['name']) ?>')">
                                                    <i class="fas fa-trash"></i> <?= __('delete') ?>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small"><?= __('has_users') ?? 'Kullanıcıları var' ?></span>
                                            <?php endif; ?>
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

<!-- Grup Düzenleme Modal -->
<div class="modal fade" id="editGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit"></i> <?= __('edit_group') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editGroupForm">
<?= csrfField() ?>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit_group">
                    <input type="hidden" name="group_id" id="edit_group_id">
                    
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">
                            <i class="fas fa-tag"></i> <?= __('group_name') ?>
                            <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="edit_name" 
                            name="name" 
                            required
                        >
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">
                            <i class="fas fa-align-left"></i> <?= __('description') ?>
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="edit_description" 
                            name="description"
                        >
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

<!-- Grup Silme Modal -->
<div class="modal fade" id="deleteGroupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?= __('delete') ?> <?= __('group') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?= __('confirm_delete_group') ?></p>
                <p class="text-muted small"><?= __('delete_group_warning') ?? 'Bu işlem geri alınamaz' ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <form method="POST" style="display: inline;">
<?= csrfField() ?>
                    <input type="hidden" name="action" value="delete_group">
                    <input type="hidden" name="group_id" id="delete_group_id">
                    <button type="submit" class="btn btn-danger"><?= __('delete') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editGroup(groupId, name, description) {
    document.getElementById('edit_group_id').value = groupId;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description;
    new bootstrap.Modal(document.getElementById('editGroupModal')).show();
}

function deleteGroup(groupId, groupName) {
    document.getElementById('delete_group_id').value = groupId;
    document.querySelector('#deleteGroupModal .modal-body p').innerHTML = '<?= __('confirm_delete_group') ?> "' + groupName + '"?';
    new bootstrap.Modal(document.getElementById('deleteGroupModal')).show();
}
</script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../../includes/layout/footer.php';
?>
