<?php
require_once __DIR__ . '/../config/database.php';

// JSON response header
header('Content-Type: application/json');

// API bilgilerini döndür
echo json_encode([
    'success' => true,
    'message' => 'Uptime Monitor API',
    'version' => '1.0.0',
    'endpoints' => [
        'POST /api/auth/login' => 'Token al (email, password)',
        'GET /api/auth/verify' => 'Token doğrula',
        'GET /api/sites' => 'Tüm siteleri getir',
        'GET /api/sites/{id}' => 'Site detayları',
        'GET /api/sites/{id}/logs' => 'Site logları',
        'POST /api/sites' => 'Yeni site ekle',
        'PUT /api/sites/{id}' => 'Site güncelle',
        'DELETE /api/sites/{id}' => 'Site sil',
        'POST /api/sites/{id}/check' => 'Manuel kontrol'
    ],
    'documentation' => '/docs/api',
    'authentication' => 'Bearer Token'
]);
?>