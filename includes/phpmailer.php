<?php
// Basit SMTP Mail sınıfı
class SimpleSMTP {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $smtp_encryption;
    private $from_email;
    private $from_name;
    
    public function __construct($host, $port, $username, $password, $encryption = 'tls', $from_email = '', $from_name = '') {
        $this->smtp_host = $host;
        $this->smtp_port = $port;
        $this->smtp_username = $username;
        $this->smtp_password = $password;
        $this->smtp_encryption = $encryption;
        $this->from_email = $from_email;
        $this->from_name = $from_name;
    }
    
    public function sendMail($to, $subject, $message, $is_html = false) {
        // Eğer SMTP ayarları yoksa, hata döndür
        if (empty($this->smtp_host)) {
            throw new Exception("SMTP ayarları yapılmamış. Lütfen önce mail ayarlarını kaydedin.");
        }
        
        // SMTP ile mail gönder
        return $this->sendSMTPMail($to, $subject, $message, $is_html);
    }
    
    private function sendSMTPMail($to, $subject, $message, $is_html = false) {
        try {
            // Socket bağlantısı
            $socket = fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 30);
            if (!$socket) {
                throw new Exception("SMTP bağlantı hatası: $errstr ($errno)");
            }
            
            // SMTP komutları
            $this->smtpCommand($socket, "EHLO " . $_SERVER['HTTP_HOST']);
            $this->smtpCommand($socket, "AUTH LOGIN");
            $this->smtpCommand($socket, base64_encode($this->smtp_username));
            $this->smtpCommand($socket, base64_encode($this->smtp_password));
            $this->smtpCommand($socket, "MAIL FROM: <{$this->from_email}>");
            $this->smtpCommand($socket, "RCPT TO: <$to>");
            $this->smtpCommand($socket, "DATA");
            
            // Mail başlıkları
            $headers = "From: {$this->from_name} <{$this->from_email}>\r\n";
            $headers .= "To: $to\r\n";
            $headers .= "Subject: $subject\r\n";
            $headers .= "Content-Type: " . ($is_html ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
            $headers .= "Date: " . date('r') . "\r\n";
            $headers .= "\r\n";
            
            // Mail içeriği
            $full_message = $headers . $message . "\r\n.\r\n";
            fwrite($socket, $full_message);
            
            $this->smtpCommand($socket, "QUIT");
            fclose($socket);
            
            return true;
            
        } catch (Exception $e) {
            error_log("SMTP Mail Hatası: " . $e->getMessage());
            return false;
        }
    }
    
    private function smtpCommand($socket, $command) {
        fwrite($socket, $command . "\r\n");
        $response = fgets($socket, 512);
        
        if (substr($response, 0, 3) >= 400) {
            throw new Exception("SMTP Komut Hatası: $command - $response");
        }
        
        return $response;
    }
}

// Mail gönderme fonksiyonu
function sendMailWithSMTP($to, $subject, $message, $is_html = false) {
    global $pdo;
    
    // SMTP ayarlarını al
    $smtp_host = getSystemSetting('smtp_host', '');
    $smtp_port = getSystemSetting('smtp_port', 587);
    $smtp_username = getSystemSetting('smtp_username', '');
    $smtp_password = getSystemSetting('smtp_password', '');
    $smtp_encryption = getSystemSetting('smtp_encryption', 'tls');
    $from_email = getSystemSetting('from_email', 'noreply@' . $_SERVER['HTTP_HOST']);
    $from_name = getSystemSetting('from_name', 'Uptime Monitor');
    
    // SMTP mail sınıfını kullan
    $mailer = new SimpleSMTP($smtp_host, $smtp_port, $smtp_username, $smtp_password, $smtp_encryption, $from_email, $from_name);
    
    return $mailer->sendMail($to, $subject, $message, $is_html);
}
?>
