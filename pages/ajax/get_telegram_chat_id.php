<?php
// Hata raporlamayı kapat
error_reporting(0);
ini_set('display_errors', 0);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

header('Content-Type: application/json');

// Session kontrolü yap
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit;
}

// CSRF doğrulaması (durum değiştiren istek)
verifyCsrf(true);

// Admin kontrolü
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Bu işlem için admin yetkisi gerekli']);
    exit;
}

// POST verilerini al
$input = json_decode(file_get_contents('php://input'), true);
$bot_token = $input['bot_token'] ?? '';

if (empty($bot_token)) {
    echo json_encode(['success' => false, 'message' => 'Bot token gerekli']);
    exit;
}

try {
    // Bot'un son mesajlarını al
    $url = "https://api.telegram.org/bot{$bot_token}/getUpdates";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        echo json_encode(['success' => false, 'message' => 'CURL Hatası: ' . $curl_error]);
        exit;
    }
    
    if ($http_code === 200) {
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'message' => 'JSON Parse Hatası: ' . json_last_error_msg()]);
            exit;
        }
        
        if (isset($data['ok']) && $data['ok'] && !empty($data['result'])) {
            // En son mesajı al
            $last_message = end($data['result']);
            
            if (isset($last_message['message']['chat']['id'])) {
                $chat_id = $last_message['message']['chat']['id'];
                echo json_encode(['success' => true, 'chat_id' => $chat_id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Chat ID bulunamadı. Bot\'a mesaj gönderin.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Bot\'a henüz mesaj gönderilmemiş.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Bot API\'ye erişilemedi. HTTP Code: ' . $http_code]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}

?>
