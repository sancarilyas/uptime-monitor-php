<?php
// ============================================================
// YARDIMCI FONKSİYONLAR + SERVICE/REPOSITORY WRAPPER'LARI
// ------------------------------------------------------------
// Bu dosya hem saf yardımcı fonksiyonları (format, URL, cURL kontrol)
// hem de yeni App\Service / App\Repository katmanlarına delege eden
// geriye-dönük-uyumlu wrapper'ları içerir.
//
// NEDEN WRAPPER?
//   monitor*.php (cron/daemon) bu dosyadaki fonksiyonları çağırır.
//   Onları tamamen kaldırmak yerine içlerinden yeni servisleri
//   çağırıyoruz — böylece cron bozulmadan yeni mimariye geçer.
// ============================================================

// Saat dilimini ayarla
require_once __DIR__ . '/../config/timezone.php';

// Oturum yardımcıları (header.php ve eski sayfalar bunları kullanır)
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    global $base_url;
    if (!isLoggedIn()) {
        header('Location: ' . $base_url . 'index.php');
        exit;
    }
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireAdmin() {
    global $base_url;
    requireLogin();
    if (!isAdmin()) {
        header('Location: ' . $base_url . 'dashboard');
        exit;
    }
}

// ============================================================
// FORMATLAMA YARDIMCILARI (saf fonksiyonlar)
// ============================================================

function formatUptime($percentage) {
    return number_format($percentage, 2) . '%';
}

function formatResponseTime($ms) {
    if ($ms === null) return 'N/A';
    if ($ms < 1000) return $ms . 'ms';
    return number_format($ms / 1000, 2) . 's';
}

function getStatusBadge($status) {
    if ($status === 'up') {
        return '<span class="badge bg-success"><i class="fas fa-check"></i> Çalışıyor</span>';
    } else {
        return '<span class="badge bg-danger"><i class="fas fa-times"></i> Kesinti</span>';
    }
}

function getStatusIcon($status) {
    return $status === 'up' ? 'fas fa-check-circle text-success' : 'fas fa-times-circle text-danger';
}

function validateUrl($url) {
    // URL'yi düzenle
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = 'http://' . $url;
    }

    return filter_var($url, FILTER_VALIDATE_URL) ? $url : false;
}

// ============================================================
// cURL İZLEME YARDIMCILARI (saf — monitor.php bunları kullanır)
// ============================================================

function checkSiteStatus($url) {
    $start_time = microtime(true);

    // cURL ile site kontrolü
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Uptime Monitor Bot 1.0');

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    $end_time = microtime(true);
    $response_time = round(($end_time - $start_time) * 1000); // milisaniye

    if ($response === false || !empty($error)) {
        return [
            'status' => 'down',
            'response_time' => null,
            'http_code' => 0,
            'error' => $error
        ];
    }

    // HTTP status koduna göre durum belirleme
    $status = ($http_code >= 200 && $http_code < 400) ? 'up' : 'down';

    return [
        'status' => $status,
        'response_time' => $response_time,
        'http_code' => $http_code,
        'error' => null
    ];
}

/**
 * Multi-cURL ile paralel site kontrolü.
 * monitor.php bunu çağırır — DOKUNULMADI (orijinal davranış korundu).
 */
function checkMultipleSitesParallel($sites) {
    if (empty($sites)) {
        return [];
    }

    $start_time = microtime(true);
    $multi_handle = curl_multi_init();
    $curl_handles = [];
    $results = [];

    // Her site için cURL handle oluştur
    foreach ($sites as $index => $site) {
        $ch = curl_init();

        // URL'yi belirle
        $url = $site['url'];
        if (!empty($site['monitor_path'])) {
            $url = rtrim($site['url'], '/') . '/' . ltrim($site['monitor_path'], '/');
        }

        // cURL ayarları
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Uptime Monitor Bot 1.0 (Multi-Curl)');
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_NOBODY, false); // Sadece header değil, body da al (bazı siteler gerektirir)

        // Handle'ı multi curl'e ekle
        curl_multi_add_handle($multi_handle, $ch);

        // Handle'ı kaydet (site bilgisi ile birlikte)
        $curl_handles[$index] = [
            'handle' => $ch,
            'site' => $site,
            'start_time' => microtime(true)
        ];
    }

    // Tüm istekleri paralel olarak çalıştır
    $running = null;
    do {
        curl_multi_exec($multi_handle, $running);
        curl_multi_select($multi_handle);
    } while ($running > 0);

    // Sonuçları topla
    foreach ($curl_handles as $index => $handle_data) {
        $ch = $handle_data['handle'];
        $site = $handle_data['site'];
        $request_start = $handle_data['start_time'];

        // Yanıt bilgilerini al
        $response = curl_multi_getcontent($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        $total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);

        // Yanıt süresini hesapla (milisaniye)
        $response_time = round((microtime(true) - $request_start) * 1000);

        // Alternatif: curl_getinfo'dan gelen süreyi kullan
        if ($total_time > 0) {
            $response_time = round($total_time * 1000);
        }

        // Durumu belirle
        $status = 'down';
        $error = null;

        if ($response === false || !empty($curl_error)) {
            $status = 'down';
            $error = $curl_error ?: 'Connection failed';
        } elseif ($http_code >= 200 && $http_code < 400) {
            $status = 'up';
        } else {
            $status = 'down';
            $error = "HTTP {$http_code}";
        }

        // Sonucu kaydet
        $results[$site['id']] = [
            'site_id' => $site['id'],
            'site_name' => $site['name'],
            'url' => $site['url'],
            'status' => $status,
            'response_time' => $response_time,
            'http_code' => $http_code,
            'error' => $error,
            'previous_status' => $site['last_status'] ?? null
        ];

        // Handle'ı kapat
        curl_multi_remove_handle($multi_handle, $ch);
        curl_close($ch);
    }

    // Multi handle'ı kapat
    curl_multi_close($multi_handle);

    $total_time = round((microtime(true) - $start_time) * 1000);

    // Log için özet bilgi
    $results['_summary'] = [
        'total_sites' => count($sites),
        'total_time_ms' => $total_time,
        'avg_time_per_site' => count($sites) > 0 ? round($total_time / count($sites), 2) : 0
    ];

    return $results;
}

// ============================================================
// SERVICE/REPOSITORY WRAPPER'LARI
// ------------------------------------------------------------
// Aşağıdaki fonksiyonlar yeni App\Service / App\Repository
// katmanlarına delege eder. monitor*.php ve eski çağıranlar
// fark etmeden yeni mimariyi kullanır.
//
// PDO'yu güvenli şekilde alır (global $pdo yoksa App\Core\Database).
// ============================================================

/**
 * Global PDO'yu güvenli şekilde döndürür.
 */
function _db(): \PDO {
    if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof \PDO) {
        return $GLOBALS['pdo'];
    }
    $pdo = \App\Core\Database::pdo();
    if ($pdo === null) {
        throw new \RuntimeException('PDO bağlantısı mevcut değil (config/database.php yüklenmemiş olabilir).');
    }
    return $pdo;
}

/**
 * SecurityService singleton (CSRF/encrypt/rate-limit).
 */
function _security(): \App\Service\SecurityService {
    static $svc = null;
    if ($svc === null) {
        $svc = new \App\Service\SecurityService(_db());
    }
    return $svc;
}

/**
 * NotificationService singleton.
 */
function _notifications(): \App\Service\NotificationService {
    static $svc = null;
    if ($svc === null) {
        $svc = new \App\Service\NotificationService(_db());
    }
    return $svc;
}

// ------------------------------------------------------------------
// UptimeLogRepository wrapper'ları
// ------------------------------------------------------------------

function logSiteStatus($site_id, $status, $response_time, $http_code, $error = null) {
    $repo = new \App\Repository\UptimeLogRepository(_db());
    $repo->logStatus($site_id, $status, $response_time, $http_code, $error);
}

function calculateUptime($site_id, $days = 30) {
    $repo = new \App\Repository\UptimeLogRepository(_db());
    return $repo->calculateUptime($site_id, $days);
}

/**
 * Birden fazla sitenin uptime yüzdesini TEK sorguda hesaplar.
 * Site listesi sayfalarında foreach + calculateUptime yerine bunu kullanın —
 * N site için N sorgu yerine 1 sorgu (büyük uptime_logs tablosunda kritik).
 *
 * @param int[] $site_ids
 * @param int   $days
 * @return array<int,float> [site_id => yüzde]
 */
function calculateUptimeForSites(array $site_ids, int $days = 1): array {
    if (empty($site_ids)) {
        return [];
    }
    $repo = new \App\Repository\UptimeLogRepository(_db());
    return $repo->calculateUptimeForSites($site_ids, $days);
}

function getDailyUptimeStrip($site_id, $days = 90) {
    $repo = new \App\Repository\UptimeLogRepository(_db());
    return $repo->getDailyUptimeStrip($site_id, $days);
}

// ------------------------------------------------------------------
// SettingsRepository wrapper'ları
// ------------------------------------------------------------------

function getSystemSetting($key, $default = null) {
    $repo = new \App\Repository\SettingsRepository(_db());
    return $repo->get($key, $default);
}

function setSystemSetting($key, $value) {
    $repo = new \App\Repository\SettingsRepository(_db());
    return $repo->set($key, $value);
}

// ------------------------------------------------------------------
// NotificationService wrapper'ları
// ------------------------------------------------------------------

/**
 * Site durumu değiştiğinde gelişmiş bildirim.
 */
function sendAdvancedNotification($site_id, $site_name, $event_type, $status, $response_time = null, $is_manual_check = false) {
    _notifications()->notifyStatusChange($site_id, $site_name, $event_type, $status, $response_time, $is_manual_check);
}

function sendNotification($site_id, $status, $site_url, $site_name) {
    _notifications()->sendSiteNotification($site_id, $site_name, $site_url, $status);
}

function sendResponseTimeNotification($site_id, $response_time, $site_url, $site_name, $threshold = 5000) {
    _notifications()->notifyResponseTimeExceeded($site_id, $site_name, $site_url, $response_time, $threshold);
}

function sendEmailNotification($site_name, $event_type, $status, $notification_emails, $response_time = null, $is_manual_check = false) {
    return _notifications()->sendEmailNotification($site_name, $event_type, $status, $notification_emails, $response_time, $is_manual_check);
}

function sendTelegramNotification($message, $priority = 'medium', $site_id = null) {
    return _notifications()->sendTelegramNotification($message, $priority, $site_id);
}

function sendSMSNotification($message, $priority = 'medium') {
    return _notifications()->sendSMSNotification($message, $priority);
}

function sendWebhookNotification($site_id, $site_name, $event_type, $status, $response_time, $priority = 'medium') {
    return _notifications()->sendWebhookNotification($site_id, $site_name, $event_type, $status, $response_time, $priority);
}

// ------------------------------------------------------------------
// SecurityService wrapper'ları
// ------------------------------------------------------------------

function csrfToken() {
    return _security()->csrfToken();
}

function csrfField() {
    return _security()->csrfField();
}

function verifyCsrf($asJson = false) {
    return _security()->verifyCsrf($asJson);
}

function getAppKey() {
    return _security()->getAppKey();
}

function encryptSecret($plaintext) {
    return _security()->encryptSecret($plaintext);
}

function decryptSecret($value) {
    return _security()->decryptSecret($value);
}

function rateLimitHit($key, $maxHits = 5, $windowSec = 900, $blockSec = 900) {
    return _security()->rateLimitHit($key, $maxHits, $windowSec, $blockSec);
}

function rateLimitReset($key) {
    _security()->rateLimitReset($key);
}

function clientIp() {
    return _security()->clientIp();
}

// ============================================================
// LOG SAKLAMA (RETENTION) — sınırsız büyümeyi önler
// ============================================================

/**
 * $days günden eski uptime_logs kayıtlarını TEK BATCH halinde siler (kilidi kısa tutar).
 * @return int silinen satır sayısı
 */
function purgeOldLogs($days = 90, $limit = 20000) {
    global $pdo;
    $days = (int)$days;
    $limit = (int)$limit;
    try {
        $stmt = $pdo->prepare("DELETE FROM uptime_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT {$limit}");
        $stmt->execute([$days]);
        return $stmt->rowCount();
    } catch (Exception $e) {
        error_log("purgeOldLogs hatası: " . $e->getMessage());
        return 0;
    }
}

/**
 * Günde en fazla bir kez eski log temizliği yapar (monitor turlarından çağrılır).
 * İlk büyük temizlik/index için: php tools/optimize_db.php
 */
function maybePurgeOldLogs($days = 90) {
    if (getSystemSetting('last_log_purge') === date('Y-m-d')) {
        return; // bugün zaten yapıldı
    }
    purgeOldLogs($days, 20000);
    setSystemSetting('last_log_purge', date('Y-m-d'));
}

// ============================================================
// PUBLIC STATUS SAYFASI YARDIMCILARI
// ============================================================

/**
 * Public status sayfasında gösterilecek siteleri döndürür.
 */
function getPublicSites() {
    try {
        $repo = new \App\Repository\SiteRepository(_db());
        return $repo->findPublic();
    } catch (\Exception $e) {
        error_log("getPublicSites hatası: " . $e->getMessage());
        return [];
    }
}

// ============================================================
// SSL SERTİFİKA TAKİBİ
// ============================================================

function extractSslExpiry($certinfo) {
    if (empty($certinfo) || !is_array($certinfo)) {
        return null;
    }
    $leaf = $certinfo[0] ?? null;
    if (!$leaf || empty($leaf['Expire date'])) {
        return null;
    }
    // Örn: "Jun 26 12:00:00 2026 GMT"
    $ts = strtotime($leaf['Expire date']);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

function processSslForSite($site, $expires_at, $threshold_days = 14) {
    try {
        $pdo = _db();
        $stmt = $pdo->prepare("UPDATE sites SET ssl_expires_at = ? WHERE id = ?");
        $stmt->execute([$expires_at, $site['id']]);

        $days_left = (int)floor((strtotime($expires_at) - time()) / 86400);
        if ($days_left <= $threshold_days) {
            $today = date('Y-m-d');
            if (($site['ssl_last_notified_date'] ?? null) !== $today) {
                sendSslExpiryNotification($site, $days_left, $expires_at);
                $stmt = $pdo->prepare("UPDATE sites SET ssl_last_notified_date = ? WHERE id = ?");
                $stmt->execute([$today, $site['id']]);
            }
        }
    } catch (Exception $e) {
        error_log("processSslForSite hatası: " . $e->getMessage());
    }
}

function sendSslExpiryNotification($site, $days_left, $expires_at) {
    if (empty($site['notifications_enabled'])) {
        return;
    }

    $site_name = $site['name'];
    $expiry_human = date('d.m.Y', strtotime($expires_at));
    $priority = $site['notification_priority'] ?? 'high';

    $msg = "🔒 *SSL Sertifika Uyarısı*\n\n";
    $msg .= "🌐 Site: *{$site_name}*\n";
    if ($days_left < 0) {
        $msg .= "⛔ Sertifika *süresi doldu* ({$expiry_human})\n";
    } else {
        $msg .= "⏳ Sertifika *{$days_left} gün* içinde doluyor\n";
        $msg .= "📅 Son geçerlilik: {$expiry_human}\n";
    }

    // Telegram
    if (!empty($site['telegram_notifications'])) {
        sendTelegramNotification($msg, $priority, $site['id']);
    }

    // Email
    if (!empty($site['email_notifications']) && !empty($site['notification_emails'])) {
        $emails = array_filter(
            array_map('trim', explode(',', $site['notification_emails'])),
            function ($e) { return filter_var($e, FILTER_VALIDATE_EMAIL); }
        );
        if ($emails) {
            $subject = $days_left < 0
                ? "SSL süresi doldu: {$site_name}"
                : "SSL {$days_left} gün içinde doluyor: {$site_name}";
            $body = "<h2>SSL Sertifika Uyarısı</h2>"
                . "<p><strong>" . htmlspecialchars($site_name) . "</strong> sitesinin SSL sertifikası "
                . ($days_left < 0 ? "<strong>süresi doldu</strong>" : "<strong>{$days_left} gün</strong> içinde dolacak")
                . ".</p><p>Son geçerlilik tarihi: <strong>{$expiry_human}</strong></p>";
            require_once __DIR__ . '/../lib/mail_helper.php';
            foreach ($emails as $email) {
                sendMailWithPHPMailer($email, $subject, $body, true);
            }
        }
    }
}
