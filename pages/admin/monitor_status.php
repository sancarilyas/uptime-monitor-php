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

// Daemon durumu kontrol et
$daemon_running = false;
$daemon_pid = null;
$pid_file = __DIR__ . '/../../monitor_daemon.pid';

if (file_exists($pid_file)) {
    $daemon_pid = trim(file_get_contents($pid_file));
    if (!empty($daemon_pid)) {
        // İşletim sistemi tespiti
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            $output = shell_exec("tasklist /FI \"PID eq $daemon_pid\" 2>NUL");
            $daemon_running = strpos($output ?? '', $daemon_pid) !== false;
        } else {
            // Linux/Unix
            $result = shell_exec("ps -p $daemon_pid -o pid= 2>/dev/null");
            $daemon_running = !empty(trim($result ?? ''));
        }
    }
}

// Log dosyası bilgileri
$log_file = __DIR__ . '/../../logs/monitor_daemon.log';
$log_exists = file_exists($log_file);
$log_size = $log_exists ? filesize($log_file) : 0;
$log_lines = $log_exists ? count(file($log_file)) : 0;

// Son monitoring bilgileri
$last_run = getSystemSetting('last_monitor_run', 'Hiç çalışmadı');
$total_checks = getSystemSetting('total_sites_checked', 0);

// Sayfa başlığı
$page_title = __('monitor_status');
$page_description = __('monitor_status_desc');

// Header'ı dahil et
include __DIR__ . '/../../includes/layout/header.php';
?>

<div class="container p-3">
    <div class="row mb-3">
        <div class="col-12">
            <h2 class="section-title"><i class="fas fa-server"></i> <?= __('monitor_status') ?></h2>
            <p class="text-muted"><?= __('monitor_status_desc') ?></p>
        </div>
    </div>

    <!-- Daemon Durumu -->
    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-server fa-3x <?= $daemon_running ? 'text-success' : 'text-danger' ?>"></i>
                    </div>
                    <h5 class="card-title"><?= __('daemon_status') ?></h5>
                    <p class="card-text">
                        <?php if ($daemon_running): ?>
                            <span class="badge bg-success"><?= __('running') ?></span>
                            <br><small class="text-muted">PID: <?= $daemon_pid ?></small>
                        <?php else: ?>
                            <span class="badge bg-danger"><?= __('stopped') ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-clock fa-3x text-info"></i>
                    </div>
                    <h5 class="card-title"><?= __('last_run') ?></h5>
                    <p class="card-text">
                        <strong><?= $last_run ?></strong>
                    </p>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="card">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-chart-line fa-3x text-warning"></i>
                    </div>
                    <h5 class="card-title"><?= __('total_checks') ?></h5>
                    <p class="card-text">
                        <strong><?= number_format($total_checks) ?></strong>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Kontrol Butonları -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-cogs"></i> <?= __('monitor_controls') ?></h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2 flex-wrap">
                        <?php if ($daemon_running): ?>
                            <button class="btn btn-danger" onclick="stopDaemon()">
                                <i class="fas fa-stop"></i> <?= __('stop_daemon') ?>
                            </button>
                        <?php else: ?>
                            <button class="btn btn-success" onclick="startDaemon()">
                                <i class="fas fa-play"></i> <?= __('start_daemon') ?>
                            </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-info" onclick="runOnce()">
                            <i class="fas fa-sync"></i> <?= __('run_once') ?>
                        </button>
                        
                        <button class="btn btn-secondary" onclick="refreshStatus()">
                            <i class="fas fa-refresh"></i> <?= __('refresh') ?>
                        </button>
                        
                        <?php if ($log_exists): ?>
                            <button class="btn btn-outline-primary" onclick="viewLogs()">
                                <i class="fas fa-file-alt"></i> <?= __('view_logs') ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Log Bilgileri -->
    <?php if ($log_exists): ?>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-file-alt"></i> <?= __('log_info') ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <strong><?= __('log_size') ?>:</strong> <?= formatBytes($log_size) ?>
                        </div>
                        <div class="col-md-4">
                            <strong><?= __('log_lines') ?>:</strong> <?= number_format($log_lines) ?>
                        </div>
                        <div class="col-md-4">
                            <strong><?= __('last_modified') ?>:</strong> <?= date('d.m.Y H:i:s', filemtime($log_file)) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Base URL'i JavaScript'e aktar
window.base_url = '<?= $base_url ?>';
console.log('Base URL set to:', window.base_url);
</script>
<script src="<?= $base_url ?>assets/js/monitor_status.js?v=<?= time() ?>"></script>

<?php
// Footer'ı dahil et
include __DIR__ . '/../../includes/layout/footer.php';

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}
?>
