<?php
/**
 * Sites Sayfası için Gerçek Zamanlı Veri Endpoint
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Session başlat
session_start();

// Login kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum gerekli']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Kullanıcının sitelerini al (Dashboard ile aynı yöntem)
    $stmt = $pdo->prepare("
        SELECT * FROM sites 
        WHERE user_id = ? 
        ORDER BY 
            CASE 
                WHEN last_status = 'down' THEN 0 
                WHEN last_status = 'up' THEN 1 
                ELSE 2 
            END, 
            created_at DESC
    ");
    $stmt->execute([$user_id]);
    $sites = $stmt->fetchAll();

    // PERFORMANS: 24s uptime'ı tüm siteler için TEK sorguda hesapla (N sorgu yerine 1).
    $uptime_map = calculateUptimeForSites(array_column($sites, 'id'), 1);

    // Her site için veri hazırla
    $sitesData = [];
    foreach ($sites as $site) {
        $uptime_24h = $uptime_map[$site['id']] ?? 0.0;
        $status_class = $site['last_status'] === 'up' ? 'up' : 'down';
        $status_color = $site['last_status'] === 'up' ? 'success' : 'danger';
        
        $sitesData[] = [
            'id' => $site['id'],
            'name' => $site['name'],
            'url' => $site['url'],
            'monitor_path' => $site['monitor_path'],
            'description' => $site['description'],
            'last_status' => $site['last_status'],
            'last_check' => $site['last_check'],
            'last_response_time' => $site['last_response_time'],
            'response_time' => $site['response_time'],
            'uptime_24h' => $uptime_24h,
            'status_class' => $status_class,
            'status_color' => $status_color,
            'status_text' => $site['last_status'] === 'up' ? 'Çalışıyor' : 'Kesintide',
            'formatted_check' => $site['last_check'] ? date('d.m.Y H:i', strtotime($site['last_check'])) : 'Hiç'
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'sites' => $sitesData,
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

