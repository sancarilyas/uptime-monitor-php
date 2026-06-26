<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/language.php';

// JSON response header
header('Content-Type: application/json');

// Token doğrulama
$user_id = validateApiToken($pdo);
if (!$user_id) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Geçersiz token',
        'error_code' => 'UNAUTHORIZED'
    ]);
    exit;
}

// HTTP method kontrolü
$method = $_SERVER['REQUEST_METHOD'];

// URL path'i parse et
$request_uri = $_SERVER['REQUEST_URI'];
$path_info = parse_url($request_uri, PHP_URL_PATH);
$path_parts = explode('/', trim($path_info, '/'));

// API endpoint'ini belirle
$endpoint = $path_parts[2] ?? ''; // api/sites/... -> sites
$site_id = $path_parts[3] ?? null; // api/sites/123 -> 123
$action = $path_parts[4] ?? ''; // api/sites/123/logs -> logs

try {
    switch ($method) {
        case 'GET':
            if ($endpoint === 'sites') {
                if ($site_id && $action === 'logs') {
                    // GET /api/sites/{id}/logs
                    getSiteLogs($pdo, $site_id, $user_id);
                } elseif ($site_id) {
                    // GET /api/sites/{id}
                    getSiteDetails($pdo, $site_id, $user_id);
                } else {
                    // GET /api/sites
                    getAllSites($pdo, $user_id);
                }
            } else {
                throw new Exception('Geçersiz endpoint');
            }
            break;
            
        case 'POST':
            if ($endpoint === 'sites') {
                if ($site_id && $action === 'check') {
                    // POST /api/sites/{id}/check
                    manualCheck($pdo, $site_id, $user_id);
                } else {
                    // POST /api/sites
                    addSite($pdo, $user_id);
                }
            } else {
                throw new Exception('Geçersiz endpoint');
            }
            break;
            
        case 'PUT':
            if ($endpoint === 'sites' && $site_id) {
                // PUT /api/sites/{id}
                updateSite($pdo, $site_id, $user_id);
            } else {
                throw new Exception('Geçersiz endpoint');
            }
            break;
            
        case 'DELETE':
            if ($endpoint === 'sites' && $site_id) {
                // DELETE /api/sites/{id}
                deleteSite($pdo, $site_id, $user_id);
            } else {
                throw new Exception('Geçersiz endpoint');
            }
            break;
            
        default:
            throw new Exception('Desteklenmeyen HTTP method');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'REQUEST_ERROR'
    ]);
}

// Tüm siteleri getir
function getAllSites($pdo, $user_id) {
    // Kullanıcının grup bilgisini al
    $user_group_id = null;
    $stmt = $pdo->prepare("SELECT group_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user) {
        $user_group_id = $user['group_id'];
    }
    
    $stmt = $pdo->prepare("
        SELECT s.*, 
               g.name as group_name,
               ul.last_status,
               ul.last_check_time,
               ul.response_time
        FROM sites s 
        LEFT JOIN (
            SELECT site_id, 
                   status as last_status,
                   timestamp as last_check_time,
                   response_time,
                   ROW_NUMBER() OVER (PARTITION BY site_id ORDER BY timestamp DESC) as rn
            FROM uptime_logs
        ) ul ON s.id = ul.site_id AND ul.rn = 1
        LEFT JOIN `groups` g ON s.group_id = g.id
        WHERE (s.user_id = ? OR (s.group_id IS NOT NULL AND s.group_id = ?))
        ORDER BY s.created_at DESC
    ");
    $stmt->execute([$user_id, $user_group_id]);
    $sites = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $sites
    ]);
}

// Site detaylarını getir
function getSiteDetails($pdo, $site_id, $user_id) {
    // Kullanıcının grup bilgisini al
    $user_group_id = null;
    $stmt = $pdo->prepare("SELECT group_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user) {
        $user_group_id = $user['group_id'];
    }
    
    $stmt = $pdo->prepare("
        SELECT s.*, 
               g.name as group_name
        FROM sites s 
        LEFT JOIN `groups` g ON s.group_id = g.id
        WHERE s.id = ? AND (s.user_id = ? OR (s.group_id IS NOT NULL AND s.group_id = ?))
    ");
    $stmt->execute([$site_id, $user_id, $user_group_id]);
    $site = $stmt->fetch();
    
    if (!$site) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Site bulunamadı',
            'error_code' => 'NOT_FOUND'
        ]);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $site
    ]);
}

// Site loglarını getir
function getSiteLogs($pdo, $site_id, $user_id) {
    // Önce site erişim kontrolü
    $user_group_id = null;
    $stmt = $pdo->prepare("SELECT group_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user) {
        $user_group_id = $user['group_id'];
    }
    
    $stmt = $pdo->prepare("
        SELECT s.id FROM sites s 
        WHERE s.id = ? AND (s.user_id = ? OR (s.group_id IS NOT NULL AND s.group_id = ?))
    ");
    $stmt->execute([$site_id, $user_id, $user_group_id]);
    $site = $stmt->fetch();
    
    if (!$site) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Site bulunamadı',
            'error_code' => 'NOT_FOUND'
        ]);
        return;
    }
    
    // Logları getir
    $limit = min(intval($_GET['limit'] ?? 50), 100); // Max 100
    $offset = intval($_GET['offset'] ?? 0);
    
    $stmt = $pdo->prepare("
        SELECT * FROM uptime_logs 
        WHERE site_id = ? 
        ORDER BY timestamp DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$site_id, $limit, $offset]);
    $logs = $stmt->fetchAll();
    
    // Toplam kayıt sayısı
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM uptime_logs WHERE site_id = ?");
    $stmt->execute([$site_id]);
    $total = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'data' => $logs,
        'total' => $total,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

// Yeni site ekle
function addSite($pdo, $user_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Geçersiz JSON verisi');
    }
    
    // Gerekli alanları kontrol et
    $required_fields = ['name', 'url'];
    foreach ($required_fields as $field) {
        if (empty($input[$field])) {
            throw new Exception("$field alanı gerekli");
        }
    }
    
    $name = trim($input['name']);
    $url = trim($input['url']);
    $monitor_path = trim($input['monitor_path'] ?? '');
    $description = trim($input['description'] ?? '');
    $group_id = $input['group_id'] ?? null;
    $notification_emails = trim($input['notification_emails'] ?? '');
    $check_interval = intval($input['check_interval'] ?? 60);
    
    // URL'yi validate et
    $validated_url = validateUrl($url);
    if (!$validated_url) {
        throw new Exception('Geçersiz URL formatı');
    }
    
    // Kullanıcının grup kontrolü
    if ($group_id) {
        $stmt = $pdo->prepare("SELECT group_id FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!$user || $user['group_id'] != $group_id) {
            throw new Exception('Bu gruba site ekleme yetkiniz yok');
        }
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO sites 
        (user_id, group_id, url, monitor_path, name, description, notification_emails, 
         check_interval, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    if ($stmt->execute([$user_id, $group_id, $validated_url, $monitor_path, $name, $description, $notification_emails, $check_interval])) {
        $site_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Site başarıyla eklendi',
            'data' => [
                'id' => $site_id,
                'name' => $name,
                'url' => $validated_url
            ]
        ]);
    } else {
        throw new Exception('Site eklenirken hata oluştu');
    }
}

// Site güncelle
function updateSite($pdo, $site_id, $user_id) {
    // Önce site erişim kontrolü
    $user_group_id = null;
    $stmt = $pdo->prepare("SELECT group_id, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user) {
        $user_group_id = $user['group_id'];
        $user_role = $user['role'];
    }
    
    $stmt = $pdo->prepare("
        SELECT s.* FROM sites s 
        WHERE s.id = ? AND (s.user_id = ? OR (s.group_id IS NOT NULL AND s.group_id = ?))
    ");
    $stmt->execute([$site_id, $user_id, $user_group_id]);
    $site = $stmt->fetch();
    
    if (!$site) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Site bulunamadı',
            'error_code' => 'NOT_FOUND'
        ]);
        return;
    }
    
    // Düzenleme yetkisi kontrolü
    $can_edit = false;
    if ($site['user_id'] == $user_id) {
        $can_edit = true; // Site sahibi
    } elseif ($user_role === 'admin' && $site['group_id'] == $user_group_id) {
        $can_edit = true; // Grup yöneticisi
    }
    
    if (!$can_edit) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Bu siteyi düzenleme yetkiniz yok',
            'error_code' => 'FORBIDDEN'
        ]);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Geçersiz JSON verisi');
    }
    
    // Güncellenecek alanları belirle
    $update_fields = [];
    $update_values = [];
    
    if (isset($input['name'])) {
        $update_fields[] = 'name = ?';
        $update_values[] = trim($input['name']);
    }
    
    if (isset($input['url'])) {
        $url = trim($input['url']);
        $validated_url = validateUrl($url);
        if (!$validated_url) {
            throw new Exception('Geçersiz URL formatı');
        }
        $update_fields[] = 'url = ?';
        $update_values[] = $validated_url;
    }
    
    if (isset($input['monitor_path'])) {
        $update_fields[] = 'monitor_path = ?';
        $update_values[] = trim($input['monitor_path']);
    }
    
    if (isset($input['description'])) {
        $update_fields[] = 'description = ?';
        $update_values[] = trim($input['description']);
    }
    
    if (isset($input['group_id'])) {
        $group_id = $input['group_id'];
        if ($group_id && $group_id != $user_group_id) {
            throw new Exception('Bu gruba site ekleme yetkiniz yok');
        }
        $update_fields[] = 'group_id = ?';
        $update_values[] = $group_id;
    }
    
    if (isset($input['notification_emails'])) {
        $update_fields[] = 'notification_emails = ?';
        $update_values[] = trim($input['notification_emails']);
    }
    
    if (isset($input['check_interval'])) {
        $update_fields[] = 'check_interval = ?';
        $update_values[] = intval($input['check_interval']);
    }
    
    if (empty($update_fields)) {
        throw new Exception('Güncellenecek alan bulunamadı');
    }
    
    $update_fields[] = 'updated_at = NOW()';
    $update_values[] = $site_id;
    
    $sql = "UPDATE sites SET " . implode(', ', $update_fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute($update_values)) {
        echo json_encode([
            'success' => true,
            'message' => 'Site başarıyla güncellendi'
        ]);
    } else {
        throw new Exception('Site güncellenirken hata oluştu');
    }
}

// Site sil
function deleteSite($pdo, $site_id, $user_id) {
    // Önce site erişim kontrolü
    $user_group_id = null;
    $stmt = $pdo->prepare("SELECT group_id, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user) {
        $user_group_id = $user['group_id'];
        $user_role = $user['role'];
    }
    
    $stmt = $pdo->prepare("
        SELECT s.* FROM sites s 
        WHERE s.id = ? AND (s.user_id = ? OR (s.group_id IS NOT NULL AND s.group_id = ?))
    ");
    $stmt->execute([$site_id, $user_id, $user_group_id]);
    $site = $stmt->fetch();
    
    if (!$site) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Site bulunamadı',
            'error_code' => 'NOT_FOUND'
        ]);
        return;
    }
    
    // Silme yetkisi kontrolü - sadece site sahibi silebilir
    if ($site['user_id'] != $user_id) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Bu siteyi silme yetkiniz yok',
            'error_code' => 'FORBIDDEN'
        ]);
        return;
    }
    
    $stmt = $pdo->prepare("DELETE FROM sites WHERE id = ? AND user_id = ?");
    
    if ($stmt->execute([$site_id, $user_id])) {
        echo json_encode([
            'success' => true,
            'message' => 'Site başarıyla silindi'
        ]);
    } else {
        throw new Exception('Site silinirken hata oluştu');
    }
}

// Manuel kontrol
function manualCheck($pdo, $site_id, $user_id) {
    // Önce site erişim kontrolü
    $user_group_id = null;
    $stmt = $pdo->prepare("SELECT group_id FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    if ($user) {
        $user_group_id = $user['group_id'];
    }
    
    $stmt = $pdo->prepare("
        SELECT s.* FROM sites s 
        WHERE s.id = ? AND (s.user_id = ? OR (s.group_id IS NOT NULL AND s.group_id = ?))
    ");
    $stmt->execute([$site_id, $user_id, $user_group_id]);
    $site = $stmt->fetch();
    
    if (!$site) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Site bulunamadı',
            'error_code' => 'NOT_FOUND'
        ]);
        return;
    }
    
    // Site kontrolü yap
    $check_url = $site['url'];
    if (!empty($site['monitor_path'])) {
        $check_url = rtrim($site['url'], '/') . '/' . ltrim($site['monitor_path'], '/');
    }
    
    $start_time = microtime(true);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $check_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Uptime Monitor Bot');
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    $end_time = microtime(true);
    $response_time = round(($end_time - $start_time) * 1000); // ms
    
    $status = 'down';
    if ($response !== false && $http_code >= 200 && $http_code < 400 && empty($error)) {
        $status = 'up';
    }
    
    // Log'a kaydet
    $stmt = $pdo->prepare("
        INSERT INTO uptime_logs (site_id, status, response_time, timestamp) 
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$site_id, $status, $response_time]);
    
    // Site tablosunu güncelle
    $stmt = $pdo->prepare("
        UPDATE sites SET 
        last_check = NOW(), 
        last_status = ?, 
        response_time = ? 
        WHERE id = ?
    ");
    $stmt->execute([$status, $response_time, $site_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Kontrol tamamlandı',
        'data' => [
            'status' => $status,
            'response_time' => $response_time,
            'http_code' => $http_code,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
}

// API Token doğrulama fonksiyonu
function validateApiToken($pdo) {
    $headers = getallheaders();
    $token = null;
    
    // Authorization header'dan token al
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        return false;
    }
    
    // Token'ı doğrula
    $stmt = $pdo->prepare("
        SELECT t.user_id 
        FROM api_tokens t 
        WHERE t.token = ? AND t.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $token_data = $stmt->fetch();
    
    if (!$token_data) {
        return false;
    }
    
    // Token kullanım zamanını güncelle
    $stmt = $pdo->prepare("UPDATE api_tokens SET last_used_at = NOW() WHERE token = ?");
    $stmt->execute([$token]);
    
    return $token_data['user_id'];
}
?>
