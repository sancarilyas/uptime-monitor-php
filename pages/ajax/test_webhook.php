<?php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/includes/functions.php';

header('Content-Type: application/json');

// Session kontrolü
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
$webhook_id = $_POST['webhook_id'] ?? '';

// Eğer POST'ta yoksa JSON'dan al
if (empty($webhook_id)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $webhook_id = $input['webhook_id'] ?? '';
}

if (empty($webhook_id)) {
    echo json_encode(['success' => false, 'message' => 'Webhook ID gerekli']);
    exit;
}

try {
    // Webhook bilgilerini al
    $stmt = $pdo->prepare("SELECT * FROM webhook_settings WHERE id = ?");
    $stmt->execute([$webhook_id]);
    $webhook = $stmt->fetch();
    
    if (!$webhook) {
        echo json_encode(['success' => false, 'message' => 'Webhook bulunamadı']);
        exit;
    }
    
    // Test mesajı oluştur
    $test_message = "🧪 Test Webhook Mesajı\n\n";
    $test_message .= "Site: Test Site\n";
    $test_message .= "Durum: UP\n";
    $test_message .= "Zaman: " . date('Y-m-d H:i:s') . "\n";
    $test_message .= "Bu bir test mesajıdır.";
    
    // Payload template'i işle
    $payload = $webhook['payload_template'];
    if (empty($payload)) {
        $payload = '{"text": "{{message}}"}';
    }
    
    // Değişkenleri değiştir
    $payload = str_replace('{{message}}', $test_message, $payload);
    $payload = str_replace('{{site_name}}', 'Test Site', $payload);
    $payload = str_replace('{{status}}', 'UP', $payload);
    $payload = str_replace('{{timestamp}}', date('Y-m-d H:i:s'), $payload);
    
    // Headers'ı parse et
    $headers = [];
    if (!empty($webhook['headers'])) {
        $parsed_headers = json_decode($webhook['headers'], true);
        if (is_array($parsed_headers)) {
            $headers = $parsed_headers;
        }
    }
    
    // Default headers
    if (!isset($headers['Content-Type'])) {
        $headers['Content-Type'] = 'application/json';
    }
    
    // CURL ile webhook gönder
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $webhook['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $webhook['method']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array_map(function($key, $value) {
        return "$key: $value";
    }, array_keys($headers), $headers));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        echo json_encode(['success' => false, 'message' => 'CURL Hatası: ' . $curl_error]);
        exit;
    }
    
    // HTTP koduna göre sonuç döndür
    if ($http_code >= 200 && $http_code < 300) {
        echo json_encode([
            'success' => true, 
            'message' => 'Test webhook başarıyla gönderildi!',
            'http_code' => $http_code,
            'response' => $response
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Webhook gönderilirken hata oluştu. HTTP Code: ' . $http_code,
            'http_code' => $http_code,
            'response' => $response
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
