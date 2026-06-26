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

// Site silme
if ($_POST['action'] ?? '' === 'delete_site') {
    $site_id = $_POST['site_id'] ?? 0;
    
    if ($site_id) {
        $stmt = $pdo->prepare("DELETE FROM sites WHERE id = ?");
        if ($stmt->execute([$site_id])) {
            $success_message = __('site_deleted');
        } else {
            $error_message = __('database_error');
        }
    }
}

// Tüm siteleri al - Down olanlar önce
$stmt = $pdo->prepare("
    SELECT s.*, 
           u.email as user_email,
           ul.last_status,
           ul.last_check_time,
           ul.response_time
    FROM sites s 
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN (
        SELECT site_id, 
               status as last_status,
               timestamp as last_check_time,
               response_time,
               ROW_NUMBER() OVER (PARTITION BY site_id ORDER BY timestamp DESC) as rn
        FROM uptime_logs
    ) ul ON s.id = ul.site_id AND ul.rn = 1
    ORDER BY 
        CASE WHEN ul.last_status = 'down' THEN 0 ELSE 1 END,
        CASE WHEN ul.last_status = 'up' THEN 0 ELSE 1 END,
        s.created_at DESC
");
$stmt->execute();
$sites = $stmt->fetchAll();

// Sayfa başlığı
$page_title = __('all_sites');
$page_description = __('site_management_desc');

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
            <h2 class="section-title"><i class="fas fa-globe"></i> <?= __('all_sites') ?></h2>
            <p class="text-muted"><?= __('site_management_desc') ?></p>
        </div>
    </div>

    <!-- Siteler -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-globe"></i> <?= __('all_sites') ?></h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><?= __('site_name') ?></th>
                                    <th><?= __('site_url') ?></th>
                                    <th><?= __('owner') ?></th>
                                    <th><?= __('status') ?></th>
                                    <th><?= __('uptime_percentage') ?></th>
                                    <th><?= __('last_check') ?></th>
                                    <th><?= __('actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sites as $site): ?>
                                    <?php 
                                    $uptime_24h = calculateUptime($site['id'], 1);
                                    $status_class = $site['last_status'] === 'up' ? 'up' : 'down';
                                    $status_text = $site['last_status'] === 'up' ? __('up') : __('down');
                                    $status_color = $site['last_status'] === 'up' ? 'success' : 'danger';
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($site['name']) ?></strong>
                                            <?php if (!empty($site['monitor_path'])): ?>
                                                <br><small class="text-muted"><i class="fas fa-route"></i> <?= htmlspecialchars($site['monitor_path']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?= htmlspecialchars($site['url']) ?>" target="_blank" class="text-decoration-none">
                                                <i class="fas fa-external-link-alt"></i> <?= htmlspecialchars($site['url']) ?>
                                            </a>
                                        </td>
                                        <td>
                                            <i class="fas fa-user"></i> <?= htmlspecialchars($site['user_email']) ?>
                                        </td>
                                        <td>
                                            <span class="status-indicator status-<?= $status_class ?>"></span>
                                            <span class="badge bg-<?= $status_color ?>"><?= $status_text ?></span>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-<?= $status_color ?>"><?= formatUptime($uptime_24h) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($site['last_check']): ?>
                                                <?= date('d.m.Y H:i', strtotime($site['last_check'])) ?>
                                            <?php else: ?>
                                                <span class="text-muted"><?= __('never') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteSite(<?= $site['id'] ?>)">
                                                <i class="fas fa-trash"></i> <?= __('delete') ?>
                                            </button>
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

<!-- Site Silme Modal -->
<div class="modal fade" id="deleteSiteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?= __('delete') ?> <?= __('site_name') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><?= __('confirm_delete_site') ?></p>
                <p class="text-muted small"><?= __('delete_warning') ?></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?= __('cancel') ?></button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="delete_site">
                    <input type="hidden" name="site_id" id="delete_site_id">
                    <button type="submit" class="btn btn-danger"><?= __('delete') ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function deleteSite(siteId) {
        document.getElementById('delete_site_id').value = siteId;
        new bootstrap.Modal(document.getElementById('deleteSiteModal')).show();
    }
</script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../../includes/layout/footer.php';
?>