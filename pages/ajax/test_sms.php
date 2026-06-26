<?php
// Test endpoint: SMS bildirimi
// Standart yapılandırma: kimlik bilgileri .env'den gelir, decryptSecret/env kullanılabilir.
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit;
}

// CSRF doğrulaması
verifyCsrf(true);

// POST verilerini al
$input = json_decode(file_get_contents('php://input'), true);
$phone_number = $input['phone_number'] ?? '';

if (empty($phone_number)) {
    echo json_encode(['success' => false, 'message' => 'Telefon numarası gerekli']);
    exit;
}

try {
    // SMS ayarlarını getir
    $stmt = $pdo->prepare("SELECT * FROM sms_settings WHERE enabled = 1 LIMIT 1");
    $stmt->execute();
    $sms_settings = $stmt->fetch();
    
    if (!$sms_settings) {
        echo json_encode(['success' => false, 'message' => 'SMS ayarları bulunamadı veya aktif değil']);
        exit;
    }
    
    // Twilio SMS gönder
    if ($sms_settings['provider'] === 'twilio') {
        $account_sid = $sms_settings['account_sid'];
        $auth_token = decryptSecret($sms_settings['auth_token']);
        $from_number = $sms_settings['from_number'];
        
        if (empty($account_sid) || empty($auth_token) || empty($from_number)) {
            echo json_encode(['success' => false, 'message' => 'SMS ayarları eksik']);
            exit;
        }
        
        $message = "🔔 Uptime Monitor Test\n\nBu bir test SMS'idir. SMS bildirimleri çalışıyor! ✅\n\nTarih: " . date('d.m.Y H:i:s');
        
        // Twilio API çağrısı
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
        
        $data = [
            'From' => $from_number,
            'To' => $phone_number,
            'Body' => $message
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $account_sid . ':' . $auth_token);
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
        
        if ($http_code === 200 || $http_code === 201) {
            // Başarılı - log'a kaydet
            try {
                $stmt = $pdo->prepare("INSERT INTO notification_logs (site_id, event_type, priority, notification_type, recipient, message, status, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([0, 'test', 'medium', 'sms', $phone_number, $message, 'sent']);
            } catch (Exception $e) {
                // Log hatası önemli değil
            }
            
            echo json_encode(['success' => true, 'message' => 'Test SMS başarıyla gönderildi!']);
            exit;
        } else {
            // Hata - log'a kaydet
            try {
                $stmt = $pdo->prepare("INSERT INTO notification_logs (site_id, event_type, priority, notification_type, recipient, message, status, response) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([0, 'test', 'medium', 'sms', $phone_number, $message, 'failed', $response]);
            } catch (Exception $e) {
                // Log hatası önemli değil
            }
            
            echo json_encode(['success' => false, 'message' => 'SMS gönderilemedi: HTTP ' . $http_code]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Desteklenmeyen SMS sağlayıcısı']);
        exit;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    exit;
}
?>