<?php
// Hata raporlamayı kapat
error_reporting(0);
ini_set('display_errors', 0);

// Basit database bağlantısı
try {
    $pdo = new PDO('mysql:host=localhost;dbname=uptime_monitor;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantı hatası']);
    exit;
}

header('Content-Type: application/json');

// Session kontrolü yap
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit;
}

try {
    // Telegram ayarlarını getir
    $stmt = $pdo->prepare("SELECT * FROM telegram_settings WHERE enabled = 1 LIMIT 1");
    $stmt->execute();
    $telegram_settings = $stmt->fetch();
    
    if (!$telegram_settings) {
        echo json_encode(['success' => false, 'message' => 'Telegram ayarları bulunamadı veya aktif değil']);
        exit;
    }
    
    $bot_token = $telegram_settings['bot_token'];
    $chat_id = $telegram_settings['chat_id'];
    
    if (empty($bot_token) || empty($chat_id)) {
        echo json_encode(['success' => false, 'message' => 'Bot token veya chat ID boş']);
        exit;
    }
    
    $message = "🔔 *Uptime Monitor Test*\n\nBu bir test mesajıdır. Telegram bildirimleri çalışıyor! ✅\n\n📅 Tarih: " . date('d.m.Y H:i:s');
    
    // Telegram Bot API çağrısı
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $message,
        'parse_mode' => 'Markdown'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        echo json_encode(['success' => false, 'message' => 'cURL hatası: ' . $curl_error]);
        exit;
    }
    
    $response_data = json_decode($response, true);
    
    if ($http_code === 200 && isset($response_data['ok']) && $response_data['ok']) {
        // Başarılı - log'a kaydet
        try {
            $stmt = $pdo->prepare("INSERT INTO notification_logs (site_id, event_type, priority, notification_type, recipient, message, status, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([0, 'test', 'medium', 'telegram', $chat_id, $message, 'sent']);
        } catch (Exception $e) {
            // Log hatası önemli değil
        }
        
        echo json_encode(['success' => true, 'message' => 'Test mesajı başarıyla gönderildi!']);
        exit;
    } else {
        // Hata - log'a kaydet
        $error_msg = isset($response_data['description']) ? $response_data['description'] : 'HTTP ' . $http_code;
        try {
            $stmt = $pdo->prepare("INSERT INTO notification_logs (site_id, event_type, priority, notification_type, recipient, message, status, response) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([0, 'test', 'medium', 'telegram', $chat_id, $message, 'failed', $response]);
        } catch (Exception $e) {
            // Log hatası önemli değil
        }
        
        echo json_encode(['success' => false, 'message' => 'Mesaj gönderilemedi: ' . $error_msg]);
        exit;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    exit;
}
?>