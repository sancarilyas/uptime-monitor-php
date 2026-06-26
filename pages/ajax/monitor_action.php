<?php
session_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

requireLogin();

// Admin kontrolü
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

$pid_file = __DIR__ . '/../../monitor_daemon.pid';
$log_file = __DIR__ . '/../../logs/monitor_daemon.log';

function isDaemonRunning() {
    global $pid_file;
    if (!file_exists($pid_file)) {
        return false;
    }
    
    $pid = trim(file_get_contents($pid_file));
    if (empty($pid)) {
        return false;
    }
    
    // İşletim sistemi tespiti
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        $output = shell_exec("tasklist /FI \"PID eq $pid\" 2>NUL");
        return strpos($output, $pid) !== false;
    } else {
        // Linux/Unix
        $result = shell_exec("ps -p $pid -o pid= 2>/dev/null");
        return !empty(trim($result));
    }
}

function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

switch ($action) {
    case 'start':
        if (isDaemonRunning()) {
            echo json_encode(['success' => false, 'message' => 'Daemon zaten çalışıyor']);
            break;
        }
        
        // Log klasörünü oluştur
        if (!is_dir(__DIR__ . '/../../logs')) {
            mkdir(__DIR__ . '/../../logs', 0755, true);
        }
        
        // İşletim sistemi tespiti
        $daemon_file = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' 
            ? 'monitor_daemon.php' 
            : 'monitor_daemon_linux.php';
        
        // Daemon'u arka planda başlat
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            $command = 'php "' . __DIR__ . '/../../' . $daemon_file . '" start';
            shell_exec("start /B $command 2>NUL");
        } else {
            // Linux/Unix - Arka planda çalıştır
            $command = 'nohup php "' . __DIR__ . '/../../' . $daemon_file . '" start > /dev/null 2>&1 &';
            shell_exec($command);
            sleep(2); // Process başlaması için bekle
        }
        
        writeLog("Daemon web arayüzünden başlatıldı");
        echo json_encode(['success' => true, 'message' => 'Daemon başlatıldı (2-3 saniye içinde aktif olacak)']);
        break;
        
    case 'stop':
        if (!isDaemonRunning()) {
            echo json_encode(['success' => false, 'message' => 'Daemon zaten durmuş']);
            break;
        }
        
        $pid = trim(file_get_contents($pid_file));
        
        // İşletim sistemi tespiti
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            exec("taskkill /PID $pid /F 2>NUL");
        } else {
            // Linux/Unix
            exec("kill $pid 2>/dev/null");
            sleep(1);
            
            // Hala çalışıyorsa force kill
            $result = shell_exec("ps -p $pid -o pid= 2>/dev/null");
            if (!empty(trim($result))) {
                exec("kill -9 $pid 2>/dev/null");
            }
        }
        
        if (file_exists($pid_file)) {
            unlink($pid_file);
        }
        
        writeLog("Daemon web arayüzünden durduruldu");
        echo json_encode(['success' => true, 'message' => 'Daemon durduruldu']);
        break;
        
    case 'run':
        // Tek seferlik çalıştır
        $daemon_file = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' 
            ? 'monitor_daemon.php' 
            : 'monitor_daemon_linux.php';
            
        $command = 'php "' . __DIR__ . '/../../' . $daemon_file . '" run';
        $output = shell_exec($command);
        
        writeLog("Tek seferlik monitoring web arayüzünden çalıştırıldı");
        echo json_encode(['success' => true, 'message' => 'Monitoring tamamlandı']);
        break;
        
    case 'status':
        $running = isDaemonRunning();
        $pid = $running ? trim(file_get_contents($pid_file)) : null;
        
        echo json_encode([
            'success' => true,
            'running' => $running,
            'pid' => $pid,
            'last_run' => getSystemSetting('last_monitor_run', 'Hiç çalışmadı')
        ]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Geçersiz işlem']);
}
?>
