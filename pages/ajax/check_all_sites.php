<?php
/**
 * Tüm siteleri manuel kontrol et
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Oturum kontrolü
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum gerekli']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Kullanıcının grup ID'sini al
    $user_group_id = null;
    $stmt = $pdo->prepare("SELECT group_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user) {
        $user_group_id = $user['group_id'];
    }
    
    // Kullanıcının sitelerini al - Grup bazlı erişim kontrolü
    $stmt = $pdo->prepare("
        SELECT s.*, 
               g.name as group_name
        FROM sites s 
        LEFT JOIN `groups` g ON s.group_id = g.id
        WHERE (s.user_id = ? OR (s.group_id IS NOT NULL AND s.group_id = ?))
        AND s.status = 'active'
    ");
    $stmt->execute([$user_id, $user_group_id]);
    $sites = $stmt->fetchAll();
    
    if (empty($sites)) {
        echo json_encode(['success' => false, 'message' => 'Kontrol edilecek site bulunamadı']);
        exit;
    }
    
    // MULTI-CURL ile tüm siteleri paralel kontrol et
    $results = checkMultipleSitesParallel($sites);
    $summary = $results['_summary'] ?? [];
    unset($results['_summary']);
    
    $checked_sites = 0;
    $status_changes = 0;
    
    foreach ($results as $site_id => $result) {
        $checked_sites++;
        
        // Site durumunu güncelle
        $stmt = $pdo->prepare("
            UPDATE sites 
            SET last_check = NOW(), 
                last_status = ?, 
                response_time = ?
            WHERE id = ?
        ");
        $stmt->execute([$result['status'], $result['response_time'], $site_id]);
        
        // Durum değişikliği kontrolü
        $stmt = $pdo->prepare("SELECT last_status FROM sites WHERE id = ?");
        $stmt->execute([$site_id]);
        $old_status = $stmt->fetchColumn();
        
        if ($old_status !== $result['status']) {
            $status_changes++;
            
            // Uptime log'a kaydet
            $stmt = $pdo->prepare("
                INSERT INTO uptime_logs (site_id, status, response_time, timestamp) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$site_id, $result['status'], $result['response_time']]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "{$checked_sites} site kontrol edildi. {$status_changes} durum değişikliği tespit edildi.",
        'data' => [
            'checked_sites' => $checked_sites,
            'total_sites' => count($sites),
            'status_changes' => $status_changes,
            'summary' => $summary
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Kontrol hatası: ' . $e->getMessage()
    ]);
}
?>