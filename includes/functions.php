<?php
// Saat dilimini ayarla
require_once __DIR__ . '/../config/timezone.php';

// Yardımcı fonksiyonlar

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
 * Multi-cURL ile paralel site kontrolü
 * Tüm siteleri aynı anda kontrol eder - ÇOK HIZLI!
 * 
 * @param array $sites Site bilgileri dizisi [['id' => 1, 'url' => 'http://...'], ...]
 * @return array Her site için durum bilgileri
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

function logSiteStatus($site_id, $status, $response_time, $http_code, $error = null) {
    global $pdo;
    
    // Veritabanına kaydet (ana kaynak)
    try {
        $stmt = $pdo->prepare("INSERT INTO uptime_logs (site_id, status, response_time, http_code, error, timestamp) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$site_id, $status, $response_time, $http_code, $error]);
        
        // Sites tablosunu güncelle (monitor.php'den çağrılıyorsa gereksiz ama zarar vermez)
        // $stmt = $pdo->prepare("UPDATE sites SET last_check = NOW(), last_status = ?, response_time = ? WHERE id = ?");
        // $stmt->execute([$status, $response_time, $site_id]);
        
    } catch (Exception $e) {
        error_log("Uptime log kaydetme hatası: " . $e->getMessage());
    }
    
    // JSON log dosyasına da kaydet (yedek)
    $year_month = date('Y-m');
    $log_file = "logs/{$year_month}.json";
    
    // Logs klasörünü oluştur
    if (!file_exists('logs')) {
        mkdir('logs', 0755, true);
    }
    
    $log_data = [];
    if (file_exists($log_file)) {
        $log_data = json_decode(file_get_contents($log_file), true) ?: [];
    }
    
    $log_entry = [
        'site_id' => $site_id,
        'timestamp' => date('Y-m-d H:i:s'),
        'status' => $status,
        'response_time' => $response_time,
        'http_code' => $http_code,
        'error' => $error
    ];
    
    $log_data[] = $log_entry;
    
    // Son 30 günlük veriyi tut
    $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
    $log_data = array_filter($log_data, function($entry) use ($cutoff_date) {
        return $entry['timestamp'] >= $cutoff_date;
    });
    
    file_put_contents($log_file, json_encode($log_data, JSON_PRETTY_PRINT));
    
    // Alert tablosuna da kaydet
    $stmt = $pdo->prepare("INSERT INTO alerts (site_id, status, message, timestamp) VALUES (?, ?, ?, NOW())");
    $message = $status === 'up' ? 'Site çalışıyor' : 'Site kesintide';
    if ($error) {
        $message .= ' - ' . $error;
    }
    $stmt->execute([$site_id, $status, $message]);
}

function sendResponseTimeNotification($site_id, $response_time, $site_url, $site_name, $threshold = 5000) {
    global $pdo;
    
    // Response time bildirim kurallarını kontrol et
    $stmt = $pdo->prepare("
        SELECT * FROM notification_rules 
        WHERE event_type = 'response_time' 
        AND (site_id = ? OR site_id IS NULL) 
        AND is_active = 1
    ");
    $stmt->execute([$site_id]);
    $rules = $stmt->fetchAll();
    
    if (empty($rules)) {
        return;
    }
    
    $subject = "⚠️ Yanıt Süresi Aşımı: {$site_name}";
    $message = "Site yanıt süresi belirlenen eşiği aştı:\n\n";
    $message .= "Site: {$site_name}\n";
    $message .= "URL: {$site_url}\n";
    $message .= "Yanıt Süresi: " . number_format($response_time) . " ms\n";
    $message .= "Eşik: " . number_format($threshold) . " ms\n";
    $message .= "Zaman: " . date('Y-m-d H:i:s');
    
    foreach ($rules as $rule) {
        // Email bildirimi
        if ($rule['email_enabled']) {
            sendEmailNotification($message, $subject, 'high');
        }
        
        // SMS bildirimi
        if ($rule['sms_enabled']) {
            sendSMSNotification($message, 'high');
        }
        
        // Telegram bildirimi
        if ($rule['telegram_enabled']) {
            sendTelegramNotification($message, 'high', $site_id);
        }
        
        // Webhook bildirimi
        if ($rule['webhook_enabled']) {
            sendWebhookNotification($message, 'high', $site_id);
        }
    }
}

function sendNotification($site_id, $status, $site_url, $site_name) {
    global $pdo;
    
    // Site bilgilerini al
    $stmt = $pdo->prepare("SELECT notification_emails FROM sites WHERE id = ?");
    $stmt->execute([$site_id]);
    $site = $stmt->fetch();
    
    if (!$site || empty($site['notification_emails'])) {
        return;
    }
    
    $emails = explode(',', $site['notification_emails']);
    $emails = array_map('trim', $emails);
    
    $subject = $status === 'up' ? 
        "✅ Site Tekrar Çalışıyor: {$site_name}" : 
        "❌ Site Kesintide: {$site_name}";
    
    $message = $status === 'up' ? 
        "Site tekrar çalışmaya başladı:\n\nSite: {$site_name}\nURL: {$site_url}\nZaman: " . date('Y-m-d H:i:s') :
        "Site kesintide:\n\nSite: {$site_name}\nURL: {$site_url}\nZaman: " . date('Y-m-d H:i:s');
    
        // PHPMailer sınıfını yükle
        require_once 'lib/mail_helper.php';

        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                // MailHelper sınıfını kullan
                $mailHelper = new MailHelper();
                $result = $mailHelper->sendSiteNotification($email, $site_name, $site_url, $status);
                
                // Log'a kaydet
                $log_status = $result['success'] ? 'sent' : 'failed';
                $stmt = $pdo->prepare("INSERT INTO notification_logs (site_id, event_type, priority, notification_type, recipient, message, status, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$site_id, $status, 'medium', 'email', $email, $message, $log_status]);
            }
        }
}

function calculateUptime($site_id, $days = 30) {
    global $pdo;
    
    try {
        // Veritabanından hesapla (ana kaynak)
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_checks,
                SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as up_checks
            FROM uptime_logs 
            WHERE site_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ");
        $stmt->execute([$site_id, $days]);
        $result = $stmt->fetch();
        
        if ($result['total_checks'] > 0) {
            return ($result['up_checks'] / $result['total_checks']) * 100;
        }
        
        // Fallback: JSON dosyasından hesapla
        $year_month = date('Y-m');
        $log_file = "logs/{$year_month}.json";
        
        if (!file_exists($log_file)) {
            return 0;
        }
        
        $log_data = json_decode(file_get_contents($log_file), true) ?: [];
        
        // Belirtilen gün sayısına göre filtrele
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $filtered_logs = array_filter($log_data, function($entry) use ($site_id, $cutoff_date) {
            return $entry['site_id'] == $site_id && $entry['timestamp'] >= $cutoff_date;
        });
        
        if (empty($filtered_logs)) {
            return 0;
        }
        
        $total_checks = count($filtered_logs);
        $up_checks = count(array_filter($filtered_logs, function($entry) {
            return $entry['status'] === 'up';
        }));
        
        return ($up_checks / $total_checks) * 100;
        
    } catch (Exception $e) {
        error_log("Uptime hesaplama hatası: " . $e->getMessage());
        return 0;
    }
}

function getSystemSetting($key, $default = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetch();
    
    return $result ? $result['setting_value'] : $default;
}

function setSystemSetting($key, $value) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    return $stmt->execute([$key, $value]);
}

/**
 * Site durumu değiştiğinde gelişmiş bildirim gönder
 */
function sendAdvancedNotification($site_id, $site_name, $event_type, $status, $response_time = null, $is_manual_check = false) {
    global $pdo;
    
    try {
        // Önce site bildirim ayarlarını kontrol et
        $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
        $stmt->execute([$site_id]);
        $site = $stmt->fetch();
        
        if (!$site) {
            return; // Site bulunamadı
        }
        
        // Site bildirimleri kapalı mı kontrol et
        if (!$site['notifications_enabled']) {
            return; // Site bildirimleri kapalı
        }
        
        // Manuel kontrol değilse, olay türüne göre bildirim kontrolü yap
        if (!$is_manual_check) {
            if ($event_type === 'down' && !$site['notify_on_down']) {
                return; // Site kapandığında bildirim gönderilmesin
            }
            
            if ($event_type === 'up' && !$site['notify_on_up']) {
                return; // Site açıldığında bildirim gönderilmesin
            }
        }
        
        // Mesaj hazırla
        $status_text = $status === 'up' ? '✅ Çalışıyor' : '❌ Kesinti';
        $message = "🔔 *Uptime Monitor*\n\n";
        $message .= "🌐 Site: *{$site_name}*\n";
        $message .= "📊 Durum: {$status_text}\n";
        
        // Manuel kontrol için özel mesaj
        if ($is_manual_check) {
            $message .= "🔧 *Manuel Kontrol Sonucu*\n";
        }
        
        $message .= "📅 Tarih: " . date('d.m.Y H:i:s') . "\n";
        
        if ($response_time) {
            $message .= "⏱️ Yanıt Süresi: {$response_time}ms\n";
        }
        
        // Site ayarlarına göre bildirim gönder
        $priority = $site['notification_priority'] ?? 'medium';
        
        // Email bildirimi
        if ($site['email_notifications'] && !empty($site['notification_emails'])) {
            sendEmailNotification($site_name, $event_type, $status, $site['notification_emails'], $response_time, $is_manual_check);
        }
        
        // Telegram bildirimi
        if ($site['telegram_notifications']) {
            sendTelegramNotification($message, $priority, $site['id']);
        }
        
        // SMS bildirimi
        if ($site['sms_notifications']) {
            sendSMSNotification($message, $priority);
        }
        
        // Webhook bildirimi
        if ($site['webhook_notifications']) {
            sendWebhookNotification($site_id, $site_name, $event_type, $status, $response_time, $priority);
        }
        
    } catch (Exception $e) {
        error_log("Bildirim hatası: " . $e->getMessage());
    }
}

/**
 * Email bildirimi gönder
 */
function sendEmailNotification($site_name, $event_type, $status, $notification_emails, $response_time = null, $is_manual_check = false) {
    try {
        if (empty($notification_emails)) {
            return false;
        }
        
        $emails = array_map('trim', explode(',', $notification_emails));
        $emails = array_filter($emails, function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });
        
        if (empty($emails)) {
            return false;
        }
        
        $subject = "Uptime Monitor - {$site_name} " . ($status === 'up' ? 'Çalışıyor' : 'Kesinti');
        if ($is_manual_check) {
            $subject = "Uptime Monitor - Manuel Kontrol - {$site_name} " . ($status === 'up' ? 'Çalışıyor' : 'Kesinti');
        }
        
        // Site URL'ini al
        global $pdo;
        $stmt = $pdo->prepare("SELECT url FROM sites WHERE name = ?");
        $stmt->execute([$site_name]);
        $site_data = $stmt->fetch();
        $site_url = $site_data ? $site_data['url'] : 'Bilinmiyor';
        
        $timestamp = date('d.m.Y H:i:s');
        
        // Template'leri kullan
        require_once __DIR__ . '/../lib/mail_templates.php';
        
        if ($is_manual_check) {
            $message = getManualCheckTemplate($site_name, $site_url, $status, $timestamp, $response_time);
        } else {
            $message = getSiteNotificationTemplate($site_name, $site_url, $status, $timestamp);
        }
        
        // PHPMailer ile gönder
        require_once __DIR__ . '/../lib/mail_helper.php';
        
        $success_count = 0;
        
        foreach ($emails as $email) {
            $result = sendMailWithPHPMailer($email, $subject, $message, true);
            
            if ($result['success']) {
                $success_count++;
            }
        }
        
        return $success_count > 0;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Telegram bildirimi gönder
 */
function sendTelegramNotification($message, $priority = 'medium', $site_id = null) {
    global $pdo;
    
    try {
        // Telegram ayarlarını getir
        $stmt = $pdo->prepare("SELECT * FROM telegram_settings WHERE enabled = 1 LIMIT 1");
        $stmt->execute();
        $telegram_settings = $stmt->fetch();
        
        if (!$telegram_settings || empty($telegram_settings['bot_token']) || empty($telegram_settings['chat_id'])) {
            return false;
        }
        
        $bot_token = $telegram_settings['bot_token'];
        $chat_id = $telegram_settings['chat_id'];
        
        // Telegram Bot API çağrısı
        $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        
        $data = [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => 'Markdown'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Log'a kaydet
        $log_status = ($http_code === 200) ? 'sent' : 'failed';
        $stmt = $pdo->prepare("INSERT INTO notification_logs (site_id, event_type, priority, notification_type, recipient, message, status, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$site_id ?? 0, 'status_change', $priority, 'telegram', $chat_id, $message, $log_status]);
        
        return $http_code === 200;
        
    } catch (Exception $e) {
        error_log("Telegram bildirim hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * SMS bildirimi gönder
 */
function sendSMSNotification($message, $priority = 'medium') {
    global $pdo;
    
    try {
        // SMS ayarlarını getir
        $stmt = $pdo->prepare("SELECT * FROM sms_settings WHERE enabled = 1 LIMIT 1");
        $stmt->execute();
        $sms_settings = $stmt->fetch();
        
        if (!$sms_settings || empty($sms_settings['account_sid']) || empty($sms_settings['auth_token']) || empty($sms_settings['from_number'])) {
            return false;
        }
        
        // SMS için telefon numarası gerekli - şimdilik skip
        // Bu fonksiyon daha sonra telefon numarası listesi ile genişletilebilir
        return false;
        
    } catch (Exception $e) {
        error_log("SMS bildirim hatası: " . $e->getMessage());
        return false;
    }
}

/**
 * Webhook bildirimi gönder
 */
function sendWebhookNotification($site_id, $site_name, $event_type, $status, $response_time, $priority = 'medium') {
    global $pdo;
    
    try {
        // Aktif webhook'ları getir
        $stmt = $pdo->prepare("SELECT * FROM webhook_settings WHERE enabled = 1");
        $stmt->execute();
        $webhooks = $stmt->fetchAll();
        
        foreach ($webhooks as $webhook) {
            // Payload template'i işle
            $payload = $webhook['payload_template'];
            $payload = str_replace('{{message}}', "Site {$site_name} durumu: {$status}", $payload);
            $payload = str_replace('{{site_name}}', $site_name, $payload);
            $payload = str_replace('{{status}}', $status, $payload);
            $payload = str_replace('{{timestamp}}', date('d.m.Y H:i:s'), $payload);
            
            // Headers'ı parse et
            $headers = [];
            if (!empty($webhook['headers'])) {
                $headers_array = json_decode($webhook['headers'], true);
                if ($headers_array) {
                    foreach ($headers_array as $key => $value) {
                        $headers[] = $key . ': ' . $value;
                    }
                }
            }
            
            // Webhook gönder
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $webhook['url']);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $webhook['method']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            // Log'a kaydet
            $log_status = ($http_code >= 200 && $http_code < 300) ? 'sent' : 'failed';
            $stmt = $pdo->prepare("INSERT INTO notification_logs (site_id, event_type, priority, notification_type, recipient, message, status, sent_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$site_id, $event_type, $priority, 'webhook', $webhook['url'], $payload, $log_status]);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Webhook bildirim hatası: " . $e->getMessage());
        return false;
    }
}
?>
