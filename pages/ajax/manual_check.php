<?php
// Oturum başlat
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Sadece POST isteklerini kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Sadece POST istekleri kabul edilir']);
    exit;
}

// Kullanıcı giriş kontrolü
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Oturum gerekli. Lütfen giriş yapın.']);
    exit;
}

// CSRF doğrulaması
verifyCsrf(true);

// JSON verisini al
$input = json_decode(file_get_contents('php://input'), true);
$site_id = $input['site_id'] ?? null;

if (!$site_id) {
    echo json_encode(['success' => false, 'message' => 'Site ID gerekli']);
    exit;
}

try {
    // Site'nin kullanıcıya ait olduğunu kontrol et
    $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ? AND user_id = ?");
    $stmt->execute([$site_id, $_SESSION['user_id']]);
    $site = $stmt->fetch();
    
    if (!$site) {
        echo json_encode(['success' => false, 'message' => 'Site bulunamadı']);
        exit;
    }
    
    // Manuel kontrol fonksiyonu
    function performManualCheck($site) {
        $start_time = microtime(true);
        
        // Site URL'ini hazırla
        $url = rtrim($site['url'], '/');
        if (!empty($site['monitor_path'])) {
            $url .= '/' . ltrim($site['monitor_path'], '/');
        }
        
        // cURL ile kontrol
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Uptime Monitor/1.0',
            CURLOPT_NOBODY => false, // HEAD değil, GET isteği
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: tr-TR,tr;q=0.8,en-US;q=0.5,en;q=0.3',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1'
            ]
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        $end_time = microtime(true);
        $response_time = round(($end_time - $start_time) * 1000); // ms cinsinden
        
        // Durum belirleme
        $status = 'down';
        $status_message = 'Site erişilemiyor';
        
        if ($response !== false && $http_code >= 200 && $http_code < 400) {
            $status = 'up';
            $status_message = 'Site çalışıyor';
        } else if ($http_code >= 400) {
            $status_message = "HTTP $http_code hatası";
        } else if (!empty($error)) {
            $status_message = "cURL hatası: $error";
        }
        
        return [
            'status' => $status,
            'response_time' => $response_time,
            'http_code' => $http_code,
            'error' => $error,
            'message' => $status_message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    // Manuel kontrolü gerçekleştir
    $check_result = performManualCheck($site);
    
    // Veritabanını güncelle
    $stmt = $pdo->prepare("
        UPDATE sites 
        SET 
            last_status = ?, 
            response_time = ?, 
            last_check = NOW()
        WHERE id = ?
    ");
    
    $stmt->execute([
        $check_result['status'],
        $check_result['response_time'],
        $site_id
    ]);
    
    // Uptime log kaydı ekle
    $error_message = null;
    if ($check_result['status'] === 'down') {
        $error_message = $check_result['message'];
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO uptime_logs (site_id, status, response_time, timestamp, error) 
        VALUES (?, ?, ?, NOW(), ?)
    ");
    
    $stmt->execute([
        $site_id,
        $check_result['status'],
        $check_result['response_time'],
        $error_message
    ]);
    
    // Site bazında bildirim ayarlarını kontrol et ve bildirim gönder
    if ($site['notifications_enabled']) {
        // Manuel kontrol için her zaman bildirim gönder (durum değişikliği kontrolü yapmadan)
        $event_type = $check_result['status'] === 'up' ? 'up' : 'down';
        
        // Site ayarlarına göre bildirim kontrolü
        $should_notify = false;
        
        if ($event_type === 'down' && $site['notify_on_down']) {
            $should_notify = true;
        } elseif ($event_type === 'up' && $site['notify_on_up']) {
            $should_notify = true;
        }
        
        if ($should_notify) {
            // Site bazında bildirim gönder (manuel kontrol olarak işaretle)
            sendAdvancedNotification(
                $site_id, 
                $site['name'], 
                $event_type, 
                $check_result['status'], 
                $check_result['response_time'],
                true // Manuel kontrol olduğunu belirt
            );
        }
    }
    
    // Başarılı yanıt
    echo json_encode([
        'success' => true,
        'data' => [
            'status' => $check_result['status'],
            'response_time' => $check_result['response_time'],
            'http_code' => $check_result['http_code'],
            'message' => $check_result['message'],
            'timestamp' => $check_result['timestamp'],
            'status_text' => $check_result['status'] === 'up' ? 'Çalışıyor' : 'Kesinti',
            'status_color' => $check_result['status'] === 'up' ? 'success' : 'danger'
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Kontrol sırasında hata oluştu'
    ]);
}
?>
