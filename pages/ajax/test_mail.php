<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';
require_once '../../lib/mail_helper.php';

// JSON response için header
header('Content-Type: application/json');

// Admin kontrolü
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim']);
    exit;
}

// Sadece POST istekleri kabul et
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek']);
    exit;
}

// Test mail gönder
$test_email = trim($_POST['test_email'] ?? '');

if (empty($test_email)) {
    echo json_encode(['success' => false, 'message' => 'E-posta adresi gerekli']);
    exit;
}

if (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz e-posta adresi']);
    exit;
}

try {
    $result = sendTestMailWithPHPMailer($test_email);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true, 
            'message' => 'Test mail başarıyla gönderildi!'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => $result['message']
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Mail hatası: ' . $e->getMessage()
    ]);
}
?>
