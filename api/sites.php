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
    $siteService = new \App\Service\SiteService(
        new \App\Repository\SiteRepository($pdo),
        new \App\Repository\UserRepository($pdo),
        new \App\Repository\UptimeLogRepository($pdo)
    );
    $sites = $siteService->getVisibleSites($user_id);

    // API geriye dönük uyumluluk: eski sürüm window-function ile uptime_logs'tan
    // last_status/last_check_time/response_time ekliyordu. sites tablosundaki
    // karşılıklarını bu anahtarlara map'leyerek aynı JSON yapısını koruyoruz.
    foreach ($sites as &$s) {
        if (!array_key_exists('last_check_time', $s)) {
            $s['last_check_time'] = $s['last_check'] ?? null;
        }
        // last_status / response_time zaten sites tablosunda mevcut
    }
    unset($s);

    echo json_encode([
        'success' => true,
        'data' => $sites
    ]);
}

// Site detaylarını getir
function getSiteDetails($pdo, $site_id, $user_id) {
    $siteService = new \App\Service\SiteService(
        new \App\Repository\SiteRepository($pdo),
        new \App\Repository\UserRepository($pdo),
        new \App\Repository\UptimeLogRepository($pdo)
    );
    $site = $siteService->getAccessibleSite((int)$site_id, $user_id);

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
    $siteService = new \App\Service\SiteService(
        new \App\Repository\SiteRepository($pdo),
        new \App\Repository\UserRepository($pdo),
        new \App\Repository\UptimeLogRepository($pdo)
    );
    $site = $siteService->getAccessibleSite((int)$site_id, $user_id);

    if (!$site) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Site bulunamadı',
            'error_code' => 'NOT_FOUND'
        ]);
        return;
    }

    // Logları getir (UptimeLogRepository)
    $limit = min(intval($_GET['limit'] ?? 50), 100); // Max 100
    $offset = intval($_GET['offset'] ?? 0);

    $logRepo = new \App\Repository\UptimeLogRepository($pdo);
    $logs = $logRepo->findBySiteForApi((int)$site_id, $limit, $offset);
    $total = $logRepo->countBySite((int)$site_id);

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

    // URL'yi validate et
    $validated_url = validateUrl($url);
    if (!$validated_url) {
        throw new Exception('Geçersiz URL formatı');
    }

    // Kullanıcının grup kontrolü
    $group_id = $input['group_id'] ?? null;
    if ($group_id) {
        $userRepo = new \App\Repository\UserRepository($pdo);
        $userGroupId = $userRepo->getGroupId($user_id);
        if ($userGroupId != $group_id) {
            throw new Exception('Bu gruba site ekleme yetkiniz yok');
        }
    }

    $siteRepo = new \App\Repository\SiteRepository($pdo);
    $site_id = $siteRepo->insert([
        'user_id'             => $user_id,
        'group_id'            => $group_id,
        'url'                 => $validated_url,
        'monitor_path'        => trim($input['monitor_path'] ?? ''),
        'name'                => $name,
        'description'         => trim($input['description'] ?? ''),
        'notification_emails' => trim($input['notification_emails'] ?? ''),
        'notification_priority' => 'medium',
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Site başarıyla eklendi',
        'data' => [
            'id' => $site_id,
            'name' => $name,
            'url' => $validated_url
        ]
    ]);
}

// Site güncelle
function updateSite($pdo, $site_id, $user_id) {
    // Önce site erişim kontrolü
    $userRepo = new \App\Repository\UserRepository($pdo);
    $siteRepo = new \App\Repository\SiteRepository($pdo);
    $userGroupId = $userRepo->getGroupId($user_id);
    $user = $userRepo->findById($user_id);
    $user_role = $user['role'] ?? 'user';

    $site = $siteRepo->findAccessible((int)$site_id, $user_id, $userGroupId);

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
    } elseif ($user_role === 'admin' && $site['group_id'] == $userGroupId) {
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

    // Güncellenecek alanları belirle (dinamik, alan bazlı)
    $update_data = [];

    if (isset($input['name'])) {
        $update_data['name'] = trim($input['name']);
    }

    if (isset($input['url'])) {
        $validated_url = validateUrl(trim($input['url']));
        if (!$validated_url) {
            throw new Exception('Geçersiz URL formatı');
        }
        $update_data['url'] = $validated_url;
    }

    if (isset($input['monitor_path'])) {
        $update_data['monitor_path'] = trim($input['monitor_path']);
    }

    if (isset($input['description'])) {
        $update_data['description'] = trim($input['description']);
    }

    if (isset($input['group_id'])) {
        $group_id = $input['group_id'];
        if ($group_id && $group_id != $userGroupId) {
            throw new Exception('Bu gruba site ekleme yetkiniz yok');
        }
        // API update için grup değişimi SiteRepository::updateByOwner üzerinden
        // gitmiyor (o sadece sahibin kendi alanlarını günceller); eski davranışı
        // korumak için basit bir sorgu. Grup taşıması nadir bir operasyon.
        // NOT: SQL aynen korundu — sadece yer değişti.
        $stmt = $pdo->prepare("UPDATE sites SET group_id = ? WHERE id = ?");
        $stmt->execute([$group_id, $site_id]);
    }

    if (isset($input['notification_emails'])) {
        $update_data['notification_emails'] = trim($input['notification_emails']);
    }

    if (isset($input['check_interval'])) {
        // check_interval alanı mevcut tabloda opsiyonel; güvenli upsert
        try {
            $stmt = $pdo->prepare("UPDATE sites SET check_interval = ? WHERE id = ?");
            $stmt->execute([intval($input['check_interval']), $site_id]);
        } catch (Exception $e) { /* kolon yoksa sessizce geç */ }
    }

    if (empty($update_data)) {
        // group_id/check_interval dışında alan yoksa yine de bir şey güncellendi sayılabilir
        echo json_encode([
            'success' => true,
            'message' => 'Site başarıyla güncellendi'
        ]);
        return;
    }

    // Site sahibi alanlarını güncelle (SiteRepository)
    $ok = $siteRepo->updateByOwner($update_data, (int)$site_id, $user_id);

    if ($ok) {
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
    $userRepo = new \App\Repository\UserRepository($pdo);
    $siteRepo = new \App\Repository\SiteRepository($pdo);
    $userGroupId = $userRepo->getGroupId($user_id);

    $site = $siteRepo->findAccessible((int)$site_id, $user_id, $userGroupId);

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

    if ($siteRepo->deleteByOwner((int)$site_id, $user_id)) {
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
