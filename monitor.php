<?php
// Site izleme scripti - Cron job ile çalıştırılacak
require_once 'config/database.php';
require_once 'includes/functions.php';



echo "[" . date('Y-m-d H:i:s') . "] Site izleme başlatılıyor...\n";

// Aktif siteleri al
$stmt = $pdo->prepare("SELECT * FROM sites WHERE status = 'active'");
$stmt->execute();
$sites = $stmt->fetchAll();

$total_sites = count($sites);
$status_changes = 0;

echo "🚀 MULTI-CURL MODU AKTIF: Toplam {$total_sites} site paralel kontrol edilecek.\n";
echo "⏱️ Kontrol aralığı: 30 saniye (Performans optimizasyonu)\n\n";

$start_time = microtime(true);

// MULTI-CURL ile tüm siteleri paralel kontrol et
$results = checkMultipleSitesParallel($sites);

// Özet bilgiyi al
$summary = $results['_summary'] ?? [];
unset($results['_summary']);

$total_elapsed = round((microtime(true) - $start_time) * 1000);

echo "✅ Paralel kontrol tamamlandı!\n";
echo "📊 Toplam süre: {$total_elapsed}ms\n";
echo "⚡ Ortalama: " . round($total_elapsed / $total_sites, 2) . "ms/site\n\n";
echo str_repeat("=", 60) . "\n\n";

// Her site için sonuçları işle
$checked_sites = 0;
foreach ($results as $site_id => $result) {
    $checked_sites++;
    
    $new_status = $result['status'];
    $response_time = $result['response_time'];
    $http_code = $result['http_code'];
    $error = $result['error'];
    $site_name = $result['site_name'];
    $site_url = $result['url'];
    $previous_status = $result['previous_status'];
    
    echo "[{$checked_sites}/{$total_sites}] {$site_name} ({$site_url}): ";
    
    // Durum kontrolü
    $status_changed = false;
    if ($previous_status !== null && $previous_status !== $new_status) {
        $status_changed = true;
        $status_changes++;
        echo "⚠️ DURUM DEĞİŞTİ: {$previous_status} → {$new_status}";
    } else {
        if ($new_status === 'up') {
            echo "✅ ÇALIŞIYOR";
        } else {
            echo "❌ KESİNTİ";
        }
    }
    
    if ($response_time) {
        echo " ({$response_time}ms)";
    }
    
    if ($error) {
        echo " [HATA: {$error}]";
    }
    
    echo "\n";
    
    // Veritabanını güncelle
    $stmt = $pdo->prepare("UPDATE sites SET last_check = NOW(), last_status = ?, response_time = ?, last_response_time = ? WHERE id = ?");
    $stmt->execute([$new_status, $response_time, $response_time, $site_id]);
    
    // Log dosyasına kaydet (logSiteStatus zaten uptime_logs'a yazıyor)
    logSiteStatus($site_id, $new_status, $response_time, $http_code, $error);
    
    // Durum değiştiyse bildirim gönder
    if ($status_changed) {
        $event_type = $new_status === 'up' ? 'up' : 'down';
        sendAdvancedNotification($site_id, $site_name, $event_type, $new_status, $response_time, false);
        sendNotification($site_id, $new_status, $site_url, $site_name);
        echo "  └─ 📧 Bildirim gönderildi: {$event_type}\n";
    }
    
    // Response time kontrolü (5000ms = 5 saniye eşik)
    if ($new_status === 'up' && $response_time > 5000) {
        sendResponseTimeNotification($site_id, $response_time, $site_url, $site_name, 5000);
        echo "  └─ ⚠️ Response time bildirimi gönderildi: {$response_time}ms\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";

echo "\n[" . date('Y-m-d H:i:s') . "] İzleme tamamlandı.\n";
echo "Kontrol edilen site: {$checked_sites}\n";
echo "Durum değişikliği: {$status_changes}\n";

// Sistem ayarlarını güncelle
setSystemSetting('last_monitor_run', date('Y-m-d H:i:s'));
setSystemSetting('total_sites_checked', $checked_sites);
setSystemSetting('status_changes', $status_changes);

function isAllowedIP() {
    // Sadece localhost ve belirli IP'lerden erişime izin ver
    $allowed_ips = ['127.0.0.1', '::1'];
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    
    return in_array($client_ip, $allowed_ips);
}
?>
