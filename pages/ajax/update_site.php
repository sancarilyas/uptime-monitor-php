<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// JSON response için header
header('Content-Type: application/json');

// Login kontrolü
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekiyor']);
    exit;
}

// Sadece POST istekleri kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

// CSRF doğrulaması
verifyCsrf(true);

// Site güncelleme işlemi
$site_id = $_POST['site_id'] ?? 0;
$url = trim($_POST['url'] ?? '');
$name = trim($_POST['name'] ?? '');
$description = trim($_POST['description'] ?? '');
$notification_emails = trim($_POST['notification_emails'] ?? '');
$monitor_path = trim($_POST['monitor_path'] ?? '');

// Bildirim ayarları
$notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;
$email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
$telegram_notifications = isset($_POST['telegram_notifications']) ? 1 : 0;
$sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
$webhook_notifications = isset($_POST['webhook_notifications']) ? 1 : 0;
$notify_on_down = isset($_POST['notify_on_down']) ? 1 : 0;
$notify_on_up = isset($_POST['notify_on_up']) ? 1 : 0;
$notification_priority = $_POST['notification_priority'] ?? 'medium';

if (empty($url) || empty($name)) {
    echo json_encode(['success' => false, 'message' => 'URL ve site adı alanları zorunludur']);
    exit;
}

try {
    // Kullanıcının kendi sitesi mi kontrol et
    $stmt = $pdo->prepare("SELECT id FROM sites WHERE id = ? AND user_id = ?");
    $stmt->execute([$site_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Bu siteyi güncelleme yetkiniz yok']);
        exit;
    }
    
    $validated_url = validateUrl($url);
    if (!$validated_url) {
        echo json_encode(['success' => false, 'message' => 'Geçerli bir URL girin']);
        exit;
    }
    
    $stmt = $pdo->prepare("UPDATE sites SET url = ?, monitor_path = ?, name = ?, description = ?, notification_emails = ?, notifications_enabled = ?, email_notifications = ?, telegram_notifications = ?, sms_notifications = ?, webhook_notifications = ?, notify_on_down = ?, notify_on_up = ?, notification_priority = ? WHERE id = ?");
    if ($stmt->execute([$validated_url, $monitor_path, $name, $description, $notification_emails, $notifications_enabled, $email_notifications, $telegram_notifications, $sms_notifications, $webhook_notifications, $notify_on_down, $notify_on_up, $notification_priority, $site_id])) {
        echo json_encode([
            'success' => true, 
            'message' => '✅ Site başarıyla güncellendi!'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '❌ Site güncellenirken bir hata oluştu']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Hata: ' . $e->getMessage()]);
}
?>
