<?php
require_once dirname(__DIR__, 2) . '/config/database.php';

header('Content-Type: application/json');

// Session kontrolü
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit;
}

// Admin kontrolü
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Bu işlem için admin yetkisi gerekli']);
    exit;
}

// POST verilerini al
$rule_id = $_POST['rule_id'] ?? '';

// Eğer POST'ta yoksa JSON'dan al
if (empty($rule_id)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $rule_id = $input['rule_id'] ?? '';
}

if (empty($rule_id)) {
    echo json_encode(['success' => false, 'message' => 'Kural ID gerekli']);
    exit;
}

// Debug: PDO bağlantısını kontrol et
if (!isset($pdo)) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantısı bulunamadı']);
    exit;
}

try {
    // Kuralı sil
    $stmt = $pdo->prepare("DELETE FROM notification_rules WHERE id = ?");
    $stmt->execute([$rule_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Bildirim kuralı başarıyla silindi']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Kural bulunamadı']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Genel hata: ' . $e->getMessage()]);
}
?>

