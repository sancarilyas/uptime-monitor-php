<?php
namespace App\Service;

use App\Repository\SettingsRepository;

/**
 * Bildirim servisi: Email, Telegram, SMS, Webhook gönderim iş mantığı.
 *
 * Gönderim mantığı includes/functions.php'deki fonksiyonlardan AYNEN taşındı
 * (sendAdvancedNotification, sendEmailNotification, sendTelegramNotification,
 * sendSMSNotification, sendWebhookNotification, sendResponseTimeNotification).
 *
 * NOT: lib/mail_helper.php (MailHelper sınıfı) ve lib/mail_templates.php
 * olduğu gibi korunur. MailHelper halâ global getSystemSetting/decryptSecret
 * fonksiyonlarını çağırır — bu wrapper'lar Adım 3'te eklenecek, böylece
 * MailHelper da yeni servislere şeffaf biçimde bağlanır.
 */
final class NotificationService
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Site durumu değiştiğinde gelişmiş bildirim gönder.
     * Kaynak: functions.php -> sendAdvancedNotification()
     *
     * @param string      $eventType 'up'|'down'
     * @param string      $status    'up'|'down'
     * @param int|null    $responseTime
     * @param bool        $isManualCheck Manuel kontrol sonucu mu?
     */
    public function notifyStatusChange(int $siteId, string $siteName, string $eventType, string $status, ?int $responseTime = null, bool $isManualCheck = false): void
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM sites WHERE id = ?");
            $stmt->execute([$siteId]);
            $site = $stmt->fetch();

            if (!$site) {
                return;
            }

            // Site bildirimleri kapalı mı?
            if (!$site['notifications_enabled']) {
                return;
            }

            // Manuel kontrol değilse, olay türüne göre bildirim kontrolü
            if (!$isManualCheck) {
                if ($eventType === 'down' && !$site['notify_on_down']) {
                    return;
                }
                if ($eventType === 'up' && !$site['notify_on_up']) {
                    return;
                }
            }

            // Mesaj hazırla
            $statusText = $status === 'up' ? '✅ Çalışıyor' : '❌ Kesinti';
            $message = "🔔 *Uptime Monitor*\n\n";
            $message .= "🌐 Site: *{$siteName}*\n";
            $message .= "📊 Durum: {$statusText}\n";

            if ($isManualCheck) {
                $message .= "🔧 *Manuel Kontrol Sonucu*\n";
            }

            $message .= "📅 Tarih: " . date('d.m.Y H:i:s') . "\n";

            if ($responseTime) {
                $message .= "⏱️ Yanıt Süresi: {$responseTime}ms\n";
            }

            $priority = $site['notification_priority'] ?? 'medium';

            // Email
            if ($site['email_notifications'] && !empty($site['notification_emails'])) {
                $this->sendEmailNotification($siteName, $eventType, $status, $site['notification_emails'], $responseTime, $isManualCheck);
            }

            // Telegram
            if ($site['telegram_notifications']) {
                $this->sendTelegramNotification($message, $priority, $site['id']);
            }

            // SMS
            if ($site['sms_notifications']) {
                $this->sendSMSNotification($message, $priority);
            }

            // Webhook
            if ($site['webhook_notifications']) {
                $this->sendWebhookNotification($siteId, $siteName, $eventType, $status, $responseTime, $priority);
            }
        } catch (\Exception $e) {
            error_log("Bildirim hatası: " . $e->getMessage());
        }
    }

    /**
     * Response time eşiği aşıldığında bildirim.
     * Kaynak: functions.php -> sendResponseTimeNotification()
     */
    public function notifyResponseTimeExceeded(int $siteId, string $siteName, string $siteUrl, int $responseTime, int $threshold = 5000): void
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM notification_rules
            WHERE event_type = 'response_time'
            AND (site_id = ? OR site_id IS NULL)
            AND is_active = 1
        ");
        $stmt->execute([$siteId]);
        $rules = $stmt->fetchAll();

        if (empty($rules)) {
            return;
        }

        $subject = "⚠️ Yanıt Süresi Aşımı: {$siteName}";
        $message = "Site yanıt süresi belirlenen eşiği aştı:\n\n";
        $message .= "Site: {$siteName}\n";
        $message .= "URL: {$siteUrl}\n";
        $message .= "Yanıt Süresi: " . number_format($responseTime) . " ms\n";
        $message .= "Eşik: " . number_format($threshold) . " ms\n";
        $message .= "Zaman: " . date('Y-m-d H:i:s');

        foreach ($rules as $rule) {
            if ($rule['email_enabled']) {
                $this->sendEmailNotification($siteName, 'response_time', 'up', '', $responseTime, false);
            }
            if ($rule['sms_enabled']) {
                $this->sendSMSNotification($message, 'high');
            }
            if ($rule['telegram_enabled']) {
                $this->sendTelegramNotification($message, 'high', $siteId);
            }
            if ($rule['webhook_enabled']) {
                $this->sendWebhookNotification($siteId, $siteName, 'response_time', 'up', $responseTime, 'high');
            }
        }
    }

    /**
     * Site bildirim e-postaları (basit yöntem — mail_helper kullanır).
     * Kaynak: functions.php -> sendNotification()
     */
    public function sendSiteNotification(int $siteId, string $siteName, string $siteUrl, string $status): void
    {
        $stmt = $this->pdo->prepare("SELECT notification_emails FROM sites WHERE id = ?");
        $stmt->execute([$siteId]);
        $site = $stmt->fetch();

        if (!$site || empty($site['notification_emails'])) {
            return;
        }

        $emails = array_map('trim', explode(',', $site['notification_emails']));

        $message = $status === 'up'
            ? "Site tekrar çalışmaya başladı:\n\nSite: {$siteName}\nURL: {$siteUrl}\nZaman: " . date('Y-m-d H:i:s')
            : "Site kesintide:\n\nSite: {$siteName}\nURL: {$siteUrl}\nZaman: " . date('Y-m-d H:i:s');

        require_once __DIR__ . '/../../lib/mail_helper.php';

        foreach ($emails as $email) {
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $mailHelper = new \MailHelper();
                $result = $mailHelper->sendSiteNotification($email, $siteName, $siteUrl, $status);

                $logStatus = $result['success'] ? 'sent' : 'failed';
                $stmt = $this->pdo->prepare(
                    "INSERT INTO notification_logs (site_id, event_type, priority, notification_type, recipient, message, status, sent_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
                );
                $stmt->execute([$siteId, $status, 'medium', 'email', $email, $message, $logStatus]);
            }
        }
    }

    /**
     * Email bildirimi (şablonlu).
     * Kaynak: functions.php -> sendEmailNotification()
     */
    public function sendEmailNotification(string $siteName, string $eventType, string $status, string $notificationEmails, ?int $responseTime = null, bool $isManualCheck = false): bool
    {
        try {
            if (empty($notificationEmails)) {
                return false;
            }

            $emails = array_filter(array_map('trim', explode(',', $notificationEmails)), function ($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            });

            if (empty($emails)) {
                return false;
            }

            $subject = "Uptime Monitor - {$siteName} " . ($status === 'up' ? 'Çalışıyor' : 'Kesinti');
            if ($isManualCheck) {
                $subject = "Uptime Monitor - Manuel Kontrol - {$siteName} " . ($status === 'up' ? 'Çalışıyor' : 'Kesinti');
            }

            // Site URL'ini al
            $stmt = $this->pdo->prepare("SELECT url FROM sites WHERE name = ?");
            $stmt->execute([$siteName]);
            $siteData = $stmt->fetch();
            $siteUrl = $siteData ? $siteData['url'] : 'Bilinmiyor';

            $timestamp = date('d.m.Y H:i:s');

            require_once __DIR__ . '/../../lib/mail_templates.php';

            if ($isManualCheck) {
                $message = \getManualCheckTemplate($siteName, $siteUrl, $status, $timestamp, $responseTime);
            } else {
                $message = \getSiteNotificationTemplate($siteName, $siteUrl, $status, $timestamp);
            }

            require_once __DIR__ . '/../../lib/mail_helper.php';

            $successCount = 0;
            foreach ($emails as $email) {
                $result = \sendMailWithPHPMailer($email, $subject, $message, true);
                if ($result['success']) {
                    $successCount++;
                }
            }

            return $successCount > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Telegram bildirimi.
     * Kaynak: functions.php -> sendTelegramNotification()
     */
    public function sendTelegramNotification(string $message, string $priority = 'medium', ?int $siteId = null): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM telegram_settings WHERE enabled = 1 LIMIT 1");
            $stmt->execute();
            $telegramSettings = $stmt->fetch();

            if (!$telegramSettings || empty($telegramSettings['bot_token']) || empty($telegramSettings['chat_id'])) {
                return false;
            }

            $botToken = $this->decryptSecretGlobal($telegramSettings['bot_token']);
            $chatId = $telegramSettings['chat_id'];

            $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

            $data = [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $logStatus = ($httpCode === 200) ? 'sent' : 'failed';
            $stmt = $this->pdo->prepare(
                "INSERT INTO notification_logs (site_id, event_type, priority, notification_type, recipient, message, status, sent_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$siteId ?? 0, 'status_change', $priority, 'telegram', $chatId, $message, $logStatus]);

            return $httpCode === 200;
        } catch (\Exception $e) {
            error_log("Telegram bildirim hatası: " . $e->getMessage());
            return false;
        }
    }

    /**
     * SMS bildirimi (şu an pasif — Twilio altyapısı hazır).
     * Kaynak: functions.php -> sendSMSNotification()
     */
    public function sendSMSNotification(string $message, string $priority = 'medium'): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM sms_settings WHERE enabled = 1 LIMIT 1");
            $stmt->execute();
            $smsSettings = $stmt->fetch();

            if (!$smsSettings || empty($smsSettings['account_sid']) || empty($smsSettings['auth_token']) || empty($smsSettings['from_number'])) {
                return false;
            }

            // SMS için telefon numarası gerekli - şimdilik skip
            return false;
        } catch (\Exception $e) {
            error_log("SMS bildirim hatası: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Webhook bildirimi.
     * Kaynak: functions.php -> sendWebhookNotification()
     */
    public function sendWebhookNotification(int $siteId, string $siteName, string $eventType, string $status, ?int $responseTime, string $priority = 'medium'): bool
    {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM webhook_settings WHERE enabled = 1");
            $stmt->execute();
            $webhooks = $stmt->fetchAll();

            foreach ($webhooks as $webhook) {
                $payload = $webhook['payload_template'];
                $payload = str_replace('{{message}}', "Site {$siteName} durumu: {$status}", $payload);
                $payload = str_replace('{{site_name}}', $siteName, $payload);
                $payload = str_replace('{{status}}', $status, $payload);
                $payload = str_replace('{{timestamp}}', date('d.m.Y H:i:s'), $payload);

                $headers = [];
                if (!empty($webhook['headers'])) {
                    $headersArray = json_decode($webhook['headers'], true);
                    if ($headersArray) {
                        foreach ($headersArray as $key => $value) {
                            $headers[] = $key . ': ' . $value;
                        }
                    }
                }

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

                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $logStatus = ($httpCode >= 200 && $httpCode < 300) ? 'sent' : 'failed';
                $stmt = $this->pdo->prepare(
                    "INSERT INTO notification_logs (site_id, event_type, priority, notification_type, recipient, message, status, sent_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
                );
                $stmt->execute([$siteId, $eventType, $priority, 'webhook', $webhook['url'], $payload, $logStatus]);
            }

            return true;
        } catch (\Exception $e) {
            error_log("Webhook bildirim hatası: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Global decryptSecret fonksiyonuna güvenli erişim.
     * (Wrapper Adım 3'te eklenecek; yoksa düz değer döner.)
     */
    private function decryptSecretGlobal(?string $value): ?string
    {
        if (function_exists('decryptSecret')) {
            return \decryptSecret($value);
        }
        return $value;
    }
}
