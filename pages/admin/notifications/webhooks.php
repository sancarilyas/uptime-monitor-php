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
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $name = $_POST['name'] ?? '';
        $url = $_POST['url'] ?? '';
        $method = $_POST['method'] ?? 'POST';
        $headers = $_POST['headers'] ?? '';
        $payload_template = $_POST['payload_template'] ?? '';
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("INSERT INTO webhook_settings (name, url, method, headers, payload_template, enabled) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $url, $method, $headers, $payload_template, $enabled]);
            $success_message = 'Webhook başarıyla eklendi!';
        } catch (Exception $e) {
            $error_message = 'Webhook eklenirken hata oluştu: ' . $e->getMessage();
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? '';
        $url = $_POST['url'] ?? '';
        $method = $_POST['method'] ?? 'POST';
        $headers = $_POST['headers'] ?? '';
        $payload_template = $_POST['payload_template'] ?? '';
        $enabled = isset($_POST['enabled']) ? 1 : 0;
        
        try {
            $stmt = $pdo->prepare("UPDATE webhook_settings SET name = ?, url = ?, method = ?, headers = ?, payload_template = ?, enabled = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$name, $url, $method, $headers, $payload_template, $enabled, $id]);
            $success_message = 'Webhook başarıyla güncellendi!';
        } catch (Exception $e) {
            $error_message = 'Webhook güncellenirken hata oluştu: ' . $e->getMessage();
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        
        try {
            $stmt = $pdo->prepare("DELETE FROM webhook_settings WHERE id = ?");
            $stmt->execute([$id]);
            $success_message = 'Webhook başarıyla silindi!';
        } catch (Exception $e) {
            $error_message = 'Webhook silinirken hata oluştu: ' . $e->getMessage();
        }
    }
}

// Webhook ayarlarını getir
$stmt = $pdo->prepare("SELECT * FROM webhook_settings ORDER BY created_at DESC");
$stmt->execute();
$webhooks = $stmt->fetchAll();

// Sayfa başlığı
$page_title = 'Webhook Ayarları';
$page_description = 'Slack, Discord ve diğer servislere webhook gönderin';

// Header'ı dahil et
include dirname(__DIR__, 3) . '/includes/layout/header.php';
?>

<div class="container p-3">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $base_url ?>admin">Admin Panel</a></li>
            <li class="breadcrumb-item"><a href="<?= $base_url ?>admin/notifications">Bildirimler</a></li>
            <li class="breadcrumb-item active">Webhook Ayarları</li>
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
            <h2 class="section-title"><i class="fas fa-link"></i> Webhook Ayarları</h2>
            <p class="text-muted">Slack, Discord ve diğer servislere webhook gönderin</p>
        </div>
    </div>

    <!-- Yeni Webhook Ekleme -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-plus"></i> Yeni Webhook Ekle</h5>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleWebhookForm()">
                        <i class="fas fa-chevron-down" id="webhookToggleIcon"></i>
                    </button>
                </div>
                <div class="card-body" id="webhookForm" style="display: none;">
                    <form method="POST">
<?= csrfField() ?>
                        <input type="hidden" name="action" value="add">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Webhook Adı</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           placeholder="Slack Webhook" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="method" class="form-label">HTTP Metodu</label>
                                    <select class="form-select" id="method" name="method">
                                        <option value="POST">POST</option>
                                        <option value="PUT">PUT</option>
                                        <option value="PATCH">PATCH</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="url" class="form-label">Webhook URL</label>
                            <input type="url" class="form-control" id="url" name="url" 
                                   placeholder="https://hooks.slack.com/services/..." required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="headers" class="form-label">Headers (JSON)</label>
                            <textarea class="form-control" id="headers" name="headers" rows="3" 
                                      placeholder='{"Content-Type": "application/json", "Authorization": "Bearer token"}'></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="payload_template" class="form-label">Payload Template</label>
                            <textarea class="form-control" id="payload_template" name="payload_template" rows="4" 
                                      placeholder='{"text": "{{message}}", "channel": "#uptime-alerts"}'></textarea>
                            <div class="form-text">
                                Kullanılabilir değişkenler: {{message}}, {{site_name}}, {{status}}, {{timestamp}}
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="enabled" name="enabled" checked>
                                <label class="form-check-label" for="enabled">
                                    Webhook'u aktif et
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Webhook Ekle
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Mevcut Webhook'lar -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Mevcut Webhook'lar</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($webhooks)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-link fa-3x mb-3"></i>
                            <p>Henüz webhook tanımlanmamış.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Ad</th>
                                        <th>URL</th>
                                        <th>Metod</th>
                                        <th>Durum</th>
                                        <th>Oluşturulma</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($webhooks as $webhook): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($webhook['name'] ?? 'İsimsiz') ?></td>
                                            <td class="text-truncate" style="max-width: 200px;">
                                                <?= htmlspecialchars($webhook['url'] ?? 'URL yok') ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $webhook['method'] ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $webhook['enabled'] ? 'success' : 'secondary' ?>">
                                                    <?= $webhook['enabled'] ? 'Aktif' : 'Pasif' ?>
                                                </span>
                                            </td>
                                            <td><?= date('d.m.Y H:i', strtotime($webhook['created_at'])) ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="editWebhook(<?= htmlspecialchars(json_encode($webhook)) ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success" 
                                                            onclick="testWebhook(<?= $webhook['id'] ?>)">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="deleteWebhook(<?= $webhook['id'] ?>, '<?= htmlspecialchars($webhook['name'] ?? 'İsimsiz') ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Webhook Düzenleme Modal -->
<div class="modal fade" id="editWebhookModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Webhook Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editWebhookForm">
<?= csrfField() ?>
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_name" class="form-label">Webhook Adı</label>
                                <input type="text" class="form-control" id="edit_name" name="name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_method" class="form-label">HTTP Metodu</label>
                                <select class="form-select" id="edit_method" name="method">
                                    <option value="POST">POST</option>
                                    <option value="PUT">PUT</option>
                                    <option value="PATCH">PATCH</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_url" class="form-label">Webhook URL</label>
                        <input type="url" class="form-control" id="edit_url" name="url" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_headers" class="form-label">Headers (JSON)</label>
                        <textarea class="form-control" id="edit_headers" name="headers" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_payload_template" class="form-label">Payload Template</label>
                        <textarea class="form-control" id="edit_payload_template" name="payload_template" rows="4"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_enabled" name="enabled">
                            <label class="form-check-label" for="edit_enabled">
                                Webhook'u aktif et
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary">Güncelle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleWebhookForm() {
    const form = document.getElementById('webhookForm');
    const icon = document.getElementById('webhookToggleIcon');
    
    if (form.style.display === 'none') {
        form.style.display = 'block';
        icon.className = 'fas fa-chevron-up';
    } else {
        form.style.display = 'none';
        icon.className = 'fas fa-chevron-down';
    }
}

function editWebhook(webhook) {
    document.getElementById('edit_id').value = webhook.id;
    document.getElementById('edit_name').value = webhook.name;
    document.getElementById('edit_url').value = webhook.url;
    document.getElementById('edit_method').value = webhook.method;
    document.getElementById('edit_headers').value = webhook.headers;
    document.getElementById('edit_payload_template').value = webhook.payload_template;
    document.getElementById('edit_enabled').checked = webhook.enabled == 1;
    
    new bootstrap.Modal(document.getElementById('editWebhookModal')).show();
}

function testWebhook(webhookId) {
    if (confirm('Test webhook gönderilsin mi?')) {
        // Butonu devre dışı bırak
        const button = event.target.closest('button');
        const originalContent = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch('<?= $base_url ?>pages/ajax/test_webhook.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                webhook_id: webhookId
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showSuccess('Başarılı', '✅ Test webhook başarıyla gönderildi!\n\nHTTP Code: ' + data.http_code);
            } else {
                showError('Hata', '❌ Webhook gönderilirken hata oluştu:\n\n' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Hata', '❌ Bağlantı hatası: ' + error.message);
        })
        .finally(() => {
            // Butonu eski haline getir
            button.disabled = false;
            button.innerHTML = originalContent;
        });
    }
}

function deleteWebhook(webhookId, webhookName) {
    if (confirm('"' + webhookName + '" webhook\'u silinsin mi?')) {
        const csrf = document.querySelector('meta[name="csrf-token"]');
        const csrfVal = csrf ? csrf.getAttribute('content') : '';
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="csrf_token" value="' + csrfVal + '">' +
            '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + webhookId + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php
// Footer'ı dahil et
include dirname(__DIR__, 3) . '/includes/layout/footer.php';
?>
