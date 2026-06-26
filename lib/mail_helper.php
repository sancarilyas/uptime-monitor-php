<?php
// PHPMailer ile mail gönderme helper sınıfı
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/mail_templates.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailHelper {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
    }
    
    /**
     * SMTP ayarları ile mail gönder
     */
    public function sendMail($to, $subject, $message, $is_html = false) {
        try {
            // SMTP ayarlarını al
            $smtp_host = getSystemSetting('smtp_host', '');
            $smtp_port = getSystemSetting('smtp_port', 587);
            $smtp_username = getSystemSetting('smtp_username', '');
            $smtp_password = getSystemSetting('smtp_password', '');
            $smtp_encryption = getSystemSetting('smtp_encryption', 'tls');
            $from_email = getSystemSetting('from_email', '');
            $from_name = getSystemSetting('from_name', 'Uptime Monitor');
            
            // SMTP ayarları kontrolü
            if (empty($smtp_host) || empty($smtp_username) || empty($smtp_password)) {
                throw new Exception("SMTP ayarları eksik. Lütfen mail ayarlarını tamamlayın.");
            }
            
            // Server ayarları
            $this->mail->isSMTP();
            $this->mail->Host = $smtp_host;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $smtp_username;
            $this->mail->Password = $smtp_password;
            $this->mail->SMTPSecure = $smtp_encryption;
            $this->mail->Port = $smtp_port;
            
            // Karakter seti
            $this->mail->CharSet = 'UTF-8';
            
            // Gönderen bilgileri
            $this->mail->setFrom($from_email, $from_name);
            
            // Alıcı
            $this->mail->addAddress($to);
            
            // İçerik
            $this->mail->isHTML($is_html);
            $this->mail->Subject = $subject;
            $this->mail->Body = $message;
            
            // Mail gönder
            $result = $this->mail->send();
            
            return [
                'success' => true,
                'message' => 'Mail başarıyla gönderildi.',
                'error_info' => $this->mail->ErrorInfo
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Mail gönderilemedi: ' . $e->getMessage(),
                'error_info' => $this->mail->ErrorInfo
            ];
        }
    }
    
    /**
     * Test mail gönder
     */
    public function sendTestMail($to) {
        $subject = '✅ Uptime Monitor - Test Mail Başarılı';
        $timestamp = date('d.m.Y H:i:s');
        $message = getTestMailTemplate($timestamp);
        
        return $this->sendMail($to, $subject, $message, true);
    }
    
    /**
     * Site durumu bildirimi gönder
     */
    public function sendSiteNotification($to, $site_name, $site_url, $status) {
        $subject = $status === 'up' ? 
            "✅ Site Tekrar Çalışıyor: {$site_name}" : 
            "❌ Site Kesintide: {$site_name}";
            
        $timestamp = date('d.m.Y H:i:s');
        $message = getSiteNotificationTemplate($site_name, $site_url, $status, $timestamp);
            
        return $this->sendMail($to, $subject, $message, true);
    }
}

/**
 * Global mail gönderme fonksiyonu
 */
function sendMailWithPHPMailer($to, $subject, $message, $is_html = false) {
    $mailHelper = new MailHelper();
    return $mailHelper->sendMail($to, $subject, $message, $is_html);
}

/**
 * Test mail gönderme fonksiyonu
 */
function sendTestMailWithPHPMailer($to) {
    $mailHelper = new MailHelper();
    return $mailHelper->sendTestMail($to);
}
?>
