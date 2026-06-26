<?php
// Hata raporlamayı kapat
error_reporting(0);
ini_set('display_errors', 0);

// Output buffering başlat
ob_start();

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

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

// POST verilerini al
$input = json_decode(file_get_contents('php://input'), true);
$site_id = $input['site_id'] ?? '';

if (empty($site_id) || !is_numeric($site_id)) {
    echo json_encode(['success' => false, 'message' => 'Geçerli site ID gerekli']);
    exit;
}

try {
    // Site sahipliğini kontrol et
    $stmt = $pdo->prepare("SELECT id, name FROM sites WHERE id = ? AND user_id = ?");
    $stmt->execute([$site_id, $_SESSION['user_id']]);
    $site = $stmt->fetch();
    
    if (!$site) {
        echo json_encode(['success' => false, 'message' => 'Site bulunamadı veya yetkiniz yok']);
        exit;
    }
    
    // İlgili logları sil
    $stmt = $pdo->prepare("DELETE FROM uptime_logs WHERE site_id = ?");
    $stmt->execute([$site_id]);
    
    // Siteyi sil
    $stmt = $pdo->prepare("DELETE FROM sites WHERE id = ?");
    $result = $stmt->execute([$site_id]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Site başarıyla silindi']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Site silinirken hata oluştu']);
        exit;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    exit;
}
?>
