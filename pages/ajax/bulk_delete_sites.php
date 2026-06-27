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
$site_ids = $input['site_ids'] ?? [];

// Sadece geçerli sayısal ID'leri al
$site_ids = array_values(array_filter(array_map('intval', (array)$site_ids), function ($id) {
    return $id > 0;
}));

if (empty($site_ids)) {
    echo json_encode(['success' => false, 'message' => 'Silinecek site seçilmedi']);
    exit;
}

try {
    // Sadece kullanıcının kendi sahip olduğu siteleri seç
    $placeholders = implode(',', array_fill(0, count($site_ids), '?'));
    $stmt = $pdo->prepare("SELECT id FROM sites WHERE id IN ($placeholders) AND user_id = ?");
    $stmt->execute(array_merge($site_ids, [$_SESSION['user_id']]));
    $owned_ids = array_map('intval', array_column($stmt->fetchAll(), 'id'));

    if (empty($owned_ids)) {
        echo json_encode(['success' => false, 'message' => 'Silinecek site bulunamadı veya yetkiniz yok']);
        exit;
    }

    $ownedPlaceholders = implode(',', array_fill(0, count($owned_ids), '?'));

    $pdo->beginTransaction();

    // İlgili logları sil
    $stmt = $pdo->prepare("DELETE FROM uptime_logs WHERE site_id IN ($ownedPlaceholders)");
    $stmt->execute($owned_ids);

    // Siteleri sil
    $stmt = $pdo->prepare("DELETE FROM sites WHERE id IN ($ownedPlaceholders)");
    $stmt->execute($owned_ids);

    $pdo->commit();

    $count = count($owned_ids);
    echo json_encode([
        'success' => true,
        'deleted' => $count,
        'message' => $count . ' site başarıyla silindi',
    ]);
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
    exit;
}
