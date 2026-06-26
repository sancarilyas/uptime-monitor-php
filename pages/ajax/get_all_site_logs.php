<?php
/**
 * AJAX Endpoint - Tüm Site Loglarını Getir
 * Admin paneli için tüm sitelerin loglarını sayfalama ile getirir
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Debug: Session bilgilerini kontrol et
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session kontrolü (AJAX için özel)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum süresi dolmuş. Lütfen tekrar giriş yapın.']);
    exit;
}

// Admin kontrolü
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Bu işlem için admin yetkisi gerekli.']);
    exit;
}

$site_id = $_GET['site_id'] ?? '';
$status = $_GET['status'] ?? '';
$time_range = $_GET['time_range'] ?? '24h';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = intval($_GET['per_page'] ?? 15);
$per_page = in_array($per_page, [15, 30, 50, 100]) ? $per_page : 15;
$export = $_GET['export'] ?? '';

// Zaman aralığını belirle
$hours = 24;
switch ($time_range) {
    case '1h': $hours = 1; break;
    case '6h': $hours = 6; break;
    case '24h': $hours = 24; break;
    case '7d': $hours = 24 * 7; break;
    case '30d': $hours = 24 * 30; break;
}

// CSV Export için tüm kayıtları getir
if ($export === 'csv') {
    $per_page = 10000; // Büyük sayı
    $page = 1;
}

// WHERE koşulları oluştur
$where_conditions = ["ul.timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)"];
$params = [$hours];

if (!empty($site_id)) {
    $where_conditions[] = "ul.site_id = ?";
    $params[] = $site_id;
}

if (!empty($status)) {
    $where_conditions[] = "ul.status = ?";
    $params[] = $status;
}

$where_clause = implode(' AND ', $where_conditions);

// Toplam kayıt sayısını al
$count_sql = "
    SELECT COUNT(*) as total 
    FROM uptime_logs ul
    INNER JOIN sites s ON ul.site_id = s.id
    WHERE $where_clause
";

try {
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $result = $stmt->fetch();
    $total_logs = $result ? $result['total'] : 0;
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası: ' . $e->getMessage()]);
    exit;
}

if ($export === 'csv') {
    // CSV Export
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="site_logs_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // BOM ekle (Excel için)
    echo "\xEF\xBB\xBF";
    
    // CSV başlıkları
    echo "Site Adı,Site URL,Tarih/Saat,Durum,Yanıt Süresi (ms),Hata Mesajı\n";
    
    // Tüm logları al
    $sql = "
        SELECT 
            s.name as site_name,
            s.url as site_url,
            ul.timestamp,
            ul.status,
            ul.response_time,
            ul.error
        FROM uptime_logs ul
        INNER JOIN sites s ON ul.site_id = s.id
        WHERE $where_clause
        ORDER BY ul.timestamp DESC
        LIMIT 10000
    ";
    
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'CSV export hatası: ' . $e->getMessage()]);
        exit;
    }
    
    foreach ($logs as $log) {
        $status_text = $log['status'] === 'up' ? 'UP' : 'DOWN';
        $response_time = $log['response_time'] ?? '';
        $error = $log['error'] ?? '';
        
        echo '"' . str_replace('"', '""', $log['site_name']) . '",';
        echo '"' . str_replace('"', '""', $log['site_url']) . '",';
        echo '"' . date('d.m.Y H:i:s', strtotime($log['timestamp'])) . '",';
        echo '"' . $status_text . '",';
        echo '"' . $response_time . '",';
        echo '"' . str_replace('"', '""', $error) . '"' . "\n";
    }
    
    exit;
}

// Normal JSON response için sayfalama
$total_pages = ceil($total_logs / $per_page);
$offset = ($page - 1) * $per_page;

// Logları al
$sql = "
    SELECT 
        s.name as site_name,
        s.url as site_url,
        ul.timestamp,
        ul.status,
        ul.response_time,
        ul.error,
        ul.site_id
    FROM uptime_logs ul
    INNER JOIN sites s ON ul.site_id = s.id
    WHERE $where_clause
    ORDER BY ul.timestamp DESC
    LIMIT ? OFFSET ?
";

// LIMIT ve OFFSET değerlerini integer olarak bind et
try {
    $stmt = $pdo->prepare($sql);
    
    // WHERE parametrelerini bind et
    foreach ($params as $i => $param) {
        $stmt->bindValue($i + 1, $param);
    }
    
    // LIMIT ve OFFSET değerlerini integer olarak bind et
    $stmt->bindValue(count($params) + 1, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $logs = $stmt->fetchAll();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Log verileri alınırken hata: ' . $e->getMessage()]);
    exit;
}

// Logları işle ve süre bilgilerini ekle
$processed_logs = [];
$previous_log = null;

foreach ($logs as $log) {
    $formatted_time = date('d.m.Y H:i:s', strtotime($log['timestamp']));
    $relative_time = getRelativeTime(strtotime($log['timestamp']));
    
    // Durum değişikliği kontrolü
    $status_changed = false;
    $duration_text = null;
    
    if ($previous_log && $previous_log['site_id'] === $log['site_id']) {
        if ($previous_log['status'] !== $log['status']) {
            $status_changed = true;
            
            // Süre hesaplama
            $current_time = strtotime($log['timestamp']);
            $prev_time = strtotime($previous_log['timestamp']);
            $duration = $prev_time - $current_time;
            
            if ($duration < 60) {
                $duration_text = $duration . ' saniye';
            } elseif ($duration < 3600) {
                $mins = floor($duration / 60);
                $secs = $duration % 60;
                $duration_text = $mins . ' dakika ' . $secs . ' saniye';
            } else {
                $hours_dur = floor($duration / 3600);
                $mins = floor(($duration % 3600) / 60);
                $duration_text = $hours_dur . ' saat ' . $mins . ' dakika';
            }
            
            // Durum değişikliği mesajı
            if ($previous_log['status'] === 'up' && $log['status'] === 'down') {
                $duration_text = '<i class="fas fa-arrow-down text-danger"></i> <strong>' . $duration_text . '</strong> çalıştı';
            } else {
                $duration_text = '<i class="fas fa-arrow-up text-success"></i> <strong>' . $duration_text . '</strong> kesintideydi';
            }
        }
    }
    
    $processed_logs[] = [
        'site_name' => $log['site_name'],
        'site_url' => $log['site_url'],
        'timestamp' => $log['timestamp'],
        'formatted_time' => $formatted_time,
        'relative_time' => $relative_time,
        'status' => $log['status'],
        'response_time' => $log['response_time'],
        'error' => $log['error'],
        'status_changed' => $status_changed,
        'duration_text' => $duration_text
    ];
    
    $previous_log = $log;
}

try {
    echo json_encode([
        'success' => true,
        'data' => [
            'logs' => $processed_logs,
            'page' => $page,
            'total_pages' => $total_pages,
            'total_logs' => $total_logs,
            'per_page' => $per_page,
            'offset' => $offset
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'JSON oluşturma hatası: ' . $e->getMessage()]);
}

?>
