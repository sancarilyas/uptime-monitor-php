<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Session'ı temizle
session_destroy();

// JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Başarıyla çıkış yapıldı',
    'redirect' => $base_url . 'login'
]);
?>
