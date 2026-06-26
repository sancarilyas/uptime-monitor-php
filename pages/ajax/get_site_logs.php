<?php
/**
 * AJAX Endpoint - Site Log Verilerini Getir
 * Sayfalama için kullanılır
 */

require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

$site_id = $_GET['site_id'] ?? null;
$time_range = $_GET['range'] ?? '24h';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = intval($_GET['per_page'] ?? 15);
$per_page = in_array($per_page, [15, 30, 50, 100]) ? $per_page : 15; // Sadece 15, 30, 50, 100 kabul et

if (!$site_id) {
    echo json_encode(['success' => false, 'message' => 'Site ID gerekli']);
    exit;
}

// Site bilgisini al
$stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
$stmt->execute([$site_id]);
$site = $stmt->fetch();

if (!$site) {
    echo json_encode(['success' => false, 'message' => 'Site bulunamadı']);
    exit;
}

// Zaman aralığını belirle
$hours = 24;
switch ($time_range) {
    case '1h': $hours = 1; break;
    case '6h': $hours = 6; break;
    case '24h': $hours = 24; break;
    case '7d': $hours = 24 * 7; break;
    case '30d': $hours = 24 * 30; break;
}

// Toplam kayıt sayısını al
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM uptime_logs 
    WHERE site_id = ? 
    AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
");
$stmt->execute([$site_id, $hours]);
$total_logs = $stmt->fetch()['total'];
$total_pages = ceil($total_logs / $per_page);

// Offset hesapla
$offset = ($page - 1) * $per_page;

// Sayfalanmış logları al
$stmt = $pdo->prepare("
    SELECT 
        status,
        response_time,
        timestamp
    FROM uptime_logs 
    WHERE site_id = :site_id 
    AND timestamp >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
    ORDER BY timestamp DESC
    LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':site_id', $site_id, PDO::PARAM_INT);
$stmt->bindValue(':hours', $hours, PDO::PARAM_INT);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// HTML oluştur
$html = '';
$log_count = count($logs);

foreach ($logs as $idx => $log) {
    $status_changed = false;
    $duration_text = null;
    
    // Sonraki log ile karşılaştır (durum değişikliği kontrolü)
    if ($idx < $log_count - 1) {
        $next_log = $logs[$idx + 1];
        
        if ($log['status'] !== $next_log['status']) {
            $status_changed = true;
            
            // Süre hesaplama
            $current_time = strtotime($log['timestamp']);
            $next_time = strtotime($next_log['timestamp']);
            $duration = $current_time - $next_time;
            
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
            if ($log['status'] === 'up' && $next_log['status'] === 'down') {
                $duration_text = '<i class="fas fa-arrow-up text-success"></i> <strong>' . $duration_text . '</strong> çalıştı';
            } else {
                $duration_text = '<i class="fas fa-arrow-down text-danger"></i> <strong>' . $duration_text . '</strong> kesintideydi';
            }
        }
    } else {
        // En eski kayıt
        if ($log['status'] === 'up') {
            $duration_text = '<i class="fas fa-history"></i> İlk kontrol - UP';
        } else {
            $duration_text = '<i class="fas fa-history"></i> İlk kontrol - DOWN';
        }
    }
    
    $row_class = $log['status'] === 'up' ? 'table-success' : 'table-danger';
    if ($status_changed) $row_class .= ' fw-bold';
    
    $html .= '<tr class="' . $row_class . '">';
    $html .= '<td>' . date('d.m.Y H:i:s', strtotime($log['timestamp'])) . '</td>';
    $html .= '<td>';
    
    if ($log['status'] === 'up') {
        $html .= '<span class="badge bg-success"><i class="fas fa-check"></i> UP</span>';
    } else {
        $html .= '<span class="badge bg-danger"><i class="fas fa-times"></i> DOWN</span>';
    }
    
    if ($status_changed) {
        $html .= '<span class="badge bg-warning text-dark ms-1"><i class="fas fa-exchange-alt"></i> Değişti</span>';
    }
    
    $html .= '</td>';
    $html .= '<td>' . $log['response_time'] . 'ms</td>';
    $html .= '<td>';
    
    if ($duration_text) {
        $html .= '<small>' . $duration_text . '</small>';
    } else {
        $html .= '<small class="text-muted">-</small>';
    }
    
    $html .= '</td>';
    $html .= '</tr>';
}

echo json_encode([
    'success' => true,
    'data' => [
        'html' => $html,
        'page' => $page,
        'total_pages' => $total_pages,
        'total_logs' => $total_logs,
        'per_page' => $per_page,
        'offset' => $offset
    ]
]);
