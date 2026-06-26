<?php
/**
 * Canlı Uptime Verisi AJAX Endpoint
 * Her 5 saniyede bir çağrılacak
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Site ID'sini al
$site_id = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;

if (!$site_id) {
    echo json_encode(['success' => false, 'message' => 'Site ID gerekli']);
    exit;
}

try {
    // Site'in mevcut durumunu al
    $stmt = $pdo->prepare("SELECT last_status, last_check, last_response_time FROM sites WHERE id = ?");
    $stmt->execute([$site_id]);
    $site_info = $stmt->fetch();
    
    $current_status = $site_info['last_status'] ?? 'down';
    $last_check = $site_info['last_check'] ?? date('Y-m-d H:i:s');
    $last_response_time = $site_info['last_response_time'] ?? 0;
    
    // Son 1 saat için veri topla
    $stmt = $pdo->prepare("
        SELECT 
            AVG(CASE WHEN status = 'up' THEN 100 ELSE 0 END) as uptime,
            AVG(response_time) as avg_response_time,
            COUNT(*) as check_count
        FROM uptime_logs 
        WHERE site_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$site_id]);
    
    $data = $stmt->fetch();
    
    $uptime = $data['uptime'] ? round($data['uptime'], 1) : ($current_status === 'up' ? 100 : 0);
    $response_time = $last_response_time ?: ($data['avg_response_time'] ? round($data['avg_response_time'], 0) : 0);
    $check_count = $data['check_count'] ?: 0;
    
    // Renk hesapla
    $color = '#dc3545'; // Kırmızı (varsayılan)
    if ($uptime >= 99) {
        $color = '#28a745'; // Yeşil
    } elseif ($uptime >= 95) {
        $color = '#ffc107'; // Sarı
    } elseif ($uptime >= 90) {
        $color = '#fd7e14'; // Turuncu
    }
    
    // Son 12 saat için mini grafik verisi
    $chart_data = [];
    for ($i = 11; $i >= 0; $i--) {
        $hour_start = date('Y-m-d H:i:00', strtotime("-$i hours"));
        $hour_end = date('Y-m-d H:i:59', strtotime("-$i hours"));
        
        $stmt = $pdo->prepare("
            SELECT 
                AVG(CASE WHEN status = 'up' THEN 100 ELSE 0 END) as uptime,
                COUNT(*) as check_count
            FROM uptime_logs 
            WHERE site_id = ? AND timestamp BETWEEN ? AND ?
        ");
        $stmt->execute([$site_id, $hour_start, $hour_end]);
        
        $hour_data = $stmt->fetch();
        
        // Eğer o saat diliminde veri yoksa, site'nin mevcut durumunu kullan
        if ($hour_data['check_count'] == 0) {
            $hour_uptime = $current_status === 'up' ? 100 : 0;
        } else {
            $hour_uptime = $hour_data['uptime'] ? round($hour_data['uptime'], 1) : 0;
        }
        
        $chart_data[] = [
            'time' => date('H:i', strtotime("-$i hours")),
            'uptime' => $hour_uptime,
            'color' => $hour_uptime >= 99 ? '#28a745' : ($hour_uptime >= 95 ? '#ffc107' : ($hour_uptime >= 90 ? '#fd7e14' : '#dc3545'))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'uptime' => $uptime,
            'response_time' => $response_time,
            'check_count' => $check_count,
            'last_check' => $last_check,
            'color' => $color,
            'status' => $current_status,
            'chart_data' => $chart_data,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Veri alınamadı: ' . $e->getMessage()
    ]);
}
?>
