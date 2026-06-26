<?php
/**
 * Uptime Monitor Daemon
 * Sürekli çalışan monitoring sistemi
 */

require_once __DIR__ . '/config/timezone.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Daemon ayarları
$check_interval = 30; // saniye - Performans için optimize edildi
$log_file = __DIR__ . '/logs/monitor_daemon.log';
$pid_file = __DIR__ . '/monitor_daemon.pid';

// Log klasörünü oluştur
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

function writeLog($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    // Emoji'leri ASCII'ye çevir (encoding sorunlarını önlemek için)
    $message = str_replace(
        ['🚀', '✅', '❌', '📧', '⚠️', '💥', '✨'],
        ['[START]', '[OK]', '[FAIL]', '[MAIL]', '[WARN]', '[ERROR]', '[DONE]'],
        $message
    );
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

function reconnectDatabase() {
    global $pdo;
    
    // Mevcut bağlantıyı kapat
    $pdo = null;
    
    // Yeniden bağlan
    require __DIR__ . '/config/database.php';
    
    writeLog("Veritabani baglantisi yenilendi");
}

function isDaemonRunning() {
    global $pid_file;
    if (!file_exists($pid_file)) {
        return false;
    }
    
    $pid = trim(file_get_contents($pid_file));
    if (empty($pid)) {
        return false;
    }
    
    // Windows'ta process kontrolü
    $output = shell_exec("tasklist /FI \"PID eq $pid\" 2>NUL");
    return strpos($output, $pid) !== false;
}

function startDaemon() {
    global $pid_file, $check_interval;
    
    if (isDaemonRunning()) {
        echo "Daemon zaten çalışıyor!\n";
        return false;
    }
    
    // PID dosyasını oluştur
    file_put_contents($pid_file, getmypid());
    writeLog("Monitor daemon başlatıldı (PID: " . getmypid() . ")");
    
    echo "Monitor daemon başlatıldı. Çıkmak için Ctrl+C basın.\n";
    echo "Log dosyası: logs/monitor_daemon.log\n";
    echo "PID dosyası: monitor_daemon.pid\n\n";
    
    return true;
}

function stopDaemon() {
    global $pid_file;
    
    if (!isDaemonRunning()) {
        echo "Daemon çalışmıyor!\n";
        return false;
    }
    
    $pid = trim(file_get_contents($pid_file));
    exec("taskkill /PID $pid /F 2>NUL");
    unlink($pid_file);
    writeLog("Monitor daemon durduruldu");
    
    echo "Daemon durduruldu.\n";
    return true;
}

function runMonitor() {
    global $check_interval, $pdo;
    
    try {
        // PDO bağlantısını kontrol et ve gerekirse yenile
        try {
            $pdo->query("SELECT 1");
        } catch (PDOException $e) {
            writeLog("Veritabani baglantisi yenileniyor...");
            reconnectDatabase();
        }
        
        // Sites tablosundan tüm siteleri al
        $stmt = $pdo->prepare("SELECT * FROM sites WHERE status = 'active'");
        $stmt->execute();
        $sites = $stmt->fetchAll();
        
        if (empty($sites)) {
            writeLog("İzlenecek site bulunamadı");
            return;
        }
        
        writeLog("🚀 MULTI-CURL BAŞLATILIYOR: Toplam " . count($sites) . " site paralel kontrol ediliyor...");
        $start_time = microtime(true);
        
        // MULTI-CURL ile tüm siteleri paralel kontrol et
        $results = checkMultipleSitesParallel($sites);
        
        // Özet bilgiyi al
        $summary = $results['_summary'] ?? [];
        unset($results['_summary']);
        
        $total_elapsed = round((microtime(true) - $start_time) * 1000);
        writeLog("✅ Paralel kontrol tamamlandı: {$total_elapsed}ms (Ortalama: " . round($total_elapsed / count($sites), 2) . "ms/site)");
        
        // Her site için sonuçları işle
        foreach ($results as $site_id => $result) {
            $status = $result['status'];
            $response_time = $result['response_time'];
            $http_code = $result['http_code'];
            $error = $result['error'];
            $previous_status = $result['previous_status'];
            $site_name = $result['site_name'];
            $url = $result['url'];
            
            // Veritabanını güncelle
            try {
                $stmt = $pdo->prepare("UPDATE sites SET last_status = ?, last_check = NOW(), last_response_time = ? WHERE id = ?");
                $stmt->execute([$status, $response_time, $site_id]);
                
                // Uptime log tablosuna kaydet
                $stmt = $pdo->prepare("INSERT INTO uptime_logs (site_id, status, response_time, http_code, error, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
                $result_db = $stmt->execute([$site_id, $status, $response_time, $http_code, $error]);
                
                if ($result_db) {
                    $status_icon = $status === 'up' ? '✅' : '❌';
                    $error_text = $error ? " [HATA: $error]" : "";
                    writeLog("{$status_icon} {$site_name} - {$status} - {$response_time}ms{$error_text}");
                    
                    // Durum değiştiyse bildirim gönder
                    if ($previous_status !== null && $previous_status !== $status) {
                        $event_type = $status === 'up' ? 'up' : 'down';
                        
                        // Site bazında bildirim ayarlarını kontrol et
                        $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
                        $stmt->execute([$site_id]);
                        $site_data = $stmt->fetch();
                        
                        if ($site_data && $site_data['notifications_enabled']) {
                            // Site ayarlarına göre bildirim kontrolü
                            $should_notify = false;
                            
                            if ($event_type === 'down' && $site_data['notify_on_down']) {
                                $should_notify = true;
                            } elseif ($event_type === 'up' && $site_data['notify_on_up']) {
                                $should_notify = true;
                            }
                            
                            if ($should_notify) {
                                // Site bazında bildirim gönder (otomatik kontrol)
                                sendAdvancedNotification($site_id, $site_name, $event_type, $status, $response_time, false);
                                writeLog("📧 BILDIRIM GÖNDERILDI: {$site_name} - {$previous_status} → {$status} (Site ayarlarına göre)");
                            } else {
                                writeLog("⏭️ BILDIRIM ATLANDI: {$site_name} - {$previous_status} → {$status} (Site bildirimleri kapalı)");
                            }
                        } else {
                            writeLog("⏭️ BILDIRIM ATLANDI: {$site_name} - {$previous_status} → {$status} (Site bildirimleri devre dışı)");
                        }
                    }
                } else {
                    writeLog("⚠️ HATA: {$site_name} - DB kaydedilemedi!");
                }
            } catch (PDOException $e) {
                writeLog("💥 VERITABANI HATASI: " . $e->getMessage());
            }
        }
        
        // Sistem ayarlarını güncelle
        setSystemSetting('last_monitor_run', date('Y-m-d H:i:s'));
        setSystemSetting('last_check_duration_ms', $total_elapsed);
        
        writeLog("✨ Monitoring tamamlandı - Toplam süre: {$total_elapsed}ms");
        
    } catch (Exception $e) {
        writeLog("💥 KRITIK HATA: " . $e->getMessage());
    }
}

function checkSiteStatusDaemon($url) {
    $start_time = microtime(true);
    
    // cURL ile site kontrolü
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Uptime Monitor/1.0');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $response_time = round((microtime(true) - $start_time) * 1000);
    
    if ($response === false || !empty($error)) {
        return ['status' => 'down', 'response_time' => $response_time];
    }
    
    if ($http_code >= 200 && $http_code < 400) {
        return ['status' => 'up', 'response_time' => $response_time];
    }
    
    return ['status' => 'down', 'response_time' => $response_time];
}

// Komut satırı argümanlarını kontrol et
if ($argc < 2) {
    echo "Kullanım: php monitor_daemon.php [start|stop|status|run]\n";
    echo "  start  - Daemon'u başlat\n";
    echo "  stop   - Daemon'u durdur\n";
    echo "  status - Daemon durumunu kontrol et\n";
    echo "  run    - Tek seferlik çalıştır\n";
    exit(1);
}

$command = $argv[1];

switch ($command) {
    case 'start':
        if (startDaemon()) {
            // Ana döngü
            while (true) {
                runMonitor();
                sleep($check_interval);
            }
        }
        break;
        
    case 'stop':
        stopDaemon();
        break;
        
    case 'status':
        if (isDaemonRunning()) {
            $pid = trim(file_get_contents($pid_file));
            echo "Daemon çalışıyor (PID: $pid)\n";
        } else {
            echo "Daemon çalışmıyor\n";
        }
        break;
        
    case 'run':
        echo "Tek seferlik monitoring çalıştırılıyor...\n";
        runMonitor();
        echo "Tamamlandı!\n";
        break;
        
    default:
        echo "Geçersiz komut: $command\n";
        exit(1);
}
?>
