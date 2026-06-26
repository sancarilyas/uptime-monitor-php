<?php
/**
 * Monitor Cron Job
 * Hosting'de daemon yerine kullanılır
 * Her dakika çalıştırılır, eğer zaten çalışıyorsa atlar
 */

// CLI veya güvenli IP kontrolü
/*/if (php_sapi_name() !== 'cli') {
    $allowed_ips = ['127.0.0.1', '::1'];
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($client_ip, $allowed_ips)) {
        http_response_code(403);
        die('Erisim reddedildi');
    }
}*/

require_once __DIR__ . '/config/timezone.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$lock_file = __DIR__ . '/monitor_cron.lock';
$log_file = __DIR__ . '/logs/monitor_cron.log';

// Log fonksiyonu
function logMessage($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

// Lock kontrolü - Eğer başka bir instance çalışıyorsa çık
if (file_exists($lock_file)) {
    $lock_time = filemtime($lock_file);
    $now = time();
    
    // 2 dakikadan eski lock dosyası varsa temizle (takılı kalmış olabilir)
    if (($now - $lock_time) > 120) {
        unlink($lock_file);
        logMessage("[WARN] Eski lock dosyasi temizlendi");
    } else {
        // Başka bir instance çalışıyor, çık
        exit(0);
    }
}

// Lock dosyası oluştur
file_put_contents($lock_file, getmypid());

// Script sonunda lock'u temizle
register_shutdown_function(function() use ($lock_file) {
    if (file_exists($lock_file)) {
        unlink($lock_file);
    }
});

try {
    logMessage("[START] Monitor cron basladi");
    
    // PDO bağlantısını kontrol et
    try {
        $pdo->query("SELECT 1");
    } catch (PDOException $e) {
        logMessage("[WARN] Veritabani baglantisi yenileniyor...");
        $pdo = null;
        require __DIR__ . '/config/database.php';
    }
    
    // Aktif siteleri al
    $stmt = $pdo->prepare("SELECT * FROM sites WHERE status = 'active'");
    $stmt->execute();
    $sites = $stmt->fetchAll();
    
    if (empty($sites)) {
        logMessage("[INFO] Izlenecek site bulunamadi");
        exit(0);
    }
    
    logMessage("[INFO] " . count($sites) . " site kontrol ediliyor...");
    $start_time = microtime(true);
    
    // MULTI-CURL ile paralel kontrol
    $results = checkMultipleSitesParallel($sites);
    $summary = $results['_summary'] ?? [];
    unset($results['_summary']);
    
    $total_elapsed = round((microtime(true) - $start_time) * 1000);
    logMessage("[OK] Kontrol tamamlandi: {$total_elapsed}ms");
    
    // Sonuçları kaydet
    $status_changes = 0;
    foreach ($results as $site_id => $result) {
        $status = $result['status'];
        $response_time = $result['response_time'];
        $http_code = $result['http_code'];
        $error = $result['error'];
        $previous_status = $result['previous_status'];
        $site_name = $result['site_name'];
        $url = $result['url'];
        
        try {
            // Sites tablosunu güncelle
            $stmt = $pdo->prepare("UPDATE sites SET last_status = ?, last_check = NOW(), last_response_time = ? WHERE id = ?");
            $stmt->execute([$status, $response_time, $site_id]);
            
            // Log kaydet
            $stmt = $pdo->prepare("INSERT INTO uptime_logs (site_id, status, response_time, http_code, error, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$site_id, $status, $response_time, $http_code, $error]);
            
            // Durum değişimi kontrolü
            if ($previous_status !== null && $previous_status !== $status) {
                $status_changes++;
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
                        logMessage("[NOTIFICATION] {$site_name}: {$previous_status} -> {$status} (Site ayarlarına göre)");
                    } else {
                        logMessage("[SKIP] {$site_name}: {$previous_status} -> {$status} (Site bildirimleri kapalı)");
                    }
                } else {
                    logMessage("[SKIP] {$site_name}: {$previous_status} -> {$status} (Site bildirimleri devre dışı)");
                }
            }
            
        } catch (PDOException $e) {
            logMessage("[ERROR] {$site_name}: " . $e->getMessage());
        }
    }
    
    // Sistem ayarlarını güncelle
    try {
        setSystemSetting('last_monitor_run', date('Y-m-d H:i:s'));
        setSystemSetting('last_check_duration_ms', $total_elapsed);
    } catch (Exception $e) {
        logMessage("[ERROR] Sistem ayarlari guncellenemedi: " . $e->getMessage());
    }
    
    logMessage("[DONE] Tamamlandi. Durum degisimi: {$status_changes}");
    
} catch (Exception $e) {
    logMessage("[ERROR] KRITIK HATA: " . $e->getMessage());
} finally {
    // Lock dosyasını temizle
    if (file_exists($lock_file)) {
        unlink($lock_file);
    }
}
?>

