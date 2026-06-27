<?php
/**
 * Dashboard Gerçek Zamanlı Veri Endpoint
 * Tüm dashboard verilerini döner (istatistikler, site durumları, vb.)
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
        ORDER BY 
            CASE 
                WHEN s.last_status = 'down' THEN 0 
                WHEN s.last_status = 'up' THEN 1 
                ELSE 2 
            END, 
            s.created_at DESC
    ");
    $stmt->execute([$user_id, $user_group_id]);
    $sites = $stmt->fetchAll();

    // İstatistikleri hesapla
    $total_sites = count($sites);
    $active_sites = 0;
    $total_uptime = 0;

    // PERFORMANS: 24s uptime'ı tüm siteler için TEK sorguda hesapla (N sorgu yerine 1).
    $uptime_map = calculateUptimeForSites(array_column($sites, 'id'), 1);

    $sites_data = [];

    foreach ($sites as $site) {
        if ($site['last_status'] === 'up') {
            $active_sites++;
        }

        $uptime_24h = $uptime_map[$site['id']] ?? 0.0;
        $total_uptime += $uptime_24h;
        
        // Her site için veri hazırla
        $sites_data[] = [
            'id' => $site['id'],
            'name' => $site['name'],
            'url' => $site['url'],
            'monitor_path' => $site['monitor_path'],
            'description' => $site['description'],
            'last_status' => $site['last_status'],
            'last_check' => $site['last_check'],
            'last_response_time' => $site['last_response_time'],
            'uptime_24h' => $uptime_24h,
            'status_class' => $site['last_status'] === 'up' ? 'up' : 'down',
            'status_text' => $site['last_status'] === 'up' ? 'Çalışıyor' : 'Kapalı',
            'status_color' => $site['last_status'] === 'up' ? 'success' : 'danger',
            'formatted_check' => $site['last_check'] ? date('d.m.Y H:i', strtotime($site['last_check'])) : 'Hiç',
            'notification_emails' => $site['notification_emails'],
            'group_name' => $site['group_name']
        ];
    }
    
    $avg_uptime = $total_sites > 0 ? $total_uptime / $total_sites : 0;
    
    // Son kontrol zamanını hesapla (en son kontrol edilen site)
    $last_check_time = '1dk';
    if ($total_sites > 0) {
        $stmt = $pdo->prepare("
            SELECT last_check 
            FROM sites 
            WHERE (user_id = ? OR (group_id IS NOT NULL AND group_id = ?))
            ORDER BY last_check DESC LIMIT 1
        ");
        $stmt->execute([$user_id, $user_group_id]);
        $last_site = $stmt->fetch();
        
        if ($last_site && $last_site['last_check']) {
            $diff = time() - strtotime($last_site['last_check']);
            if ($diff < 60) {
                $last_check_time = 'Az önce';
            } elseif ($diff < 3600) {
                $last_check_time = floor($diff / 60) . 'dk';
            } else {
                $last_check_time = floor($diff / 3600) . 'sa';
            }
        }
    }
    
    $down_sites = $total_sites - $active_sites; // Kesintili site sayısı
    
    echo json_encode([
        'success' => true,
        'data' => [
            'stats' => [
                'total_sites' => $total_sites,
                'active_sites' => $active_sites,
                'down_sites' => $down_sites,
                'last_check' => $last_check_time
            ],
            'sites' => $sites_data,
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
