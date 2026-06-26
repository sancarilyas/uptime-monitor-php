<?php
// Router - URL yönlendirme sistemi
session_start();

// URL'yi parse et
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Query string'i ayır
$parsed_url = parse_url($request_uri);
$path = $parsed_url['path'] ?? '';

// Script name'i path'ten çıkar (hem root hem alt dizin için uyumlu)
$base_path = rtrim(dirname($script_name), '/');
if ($base_path !== '' && $base_path !== '/') {
    $path = str_replace($base_path, '', $path);
}
$path = trim($path, '/');

// Route tanımları
$routes = [
    // Ana sayfa
    ''          => 'pages/auth/login.php',
    'login'     => 'pages/auth/login.php',
    'register'  => 'pages/auth/register.php',
    'logout'    => 'pages/auth/logout.php',
    
    // Dashboard
    'dashboard' => 'pages/dashboard/index.php',
    
    // Site yönetimi
    'sites' => 'pages/sites/index.php',
    'sites/detail' => 'pages/sites/detail.php',
    'sites/edit' => 'pages/sites/edit.php',
    
    // API Dokümantasyonu
    'docs/api' => 'pages/api/guide.php',
    
    // Admin paneli
    'admin' => 'pages/admin/index.php',
    'admin/users' => 'pages/admin/users.php',
    'admin/add_user' => 'pages/admin/add_user.php',
    'admin/groups' => 'pages/admin/groups.php',
    'admin/sites' => 'pages/admin/sites.php',
    'admin/site_logs' => 'pages/admin/site_logs.php',
    'admin/monitor_status' => 'pages/admin/monitor_status.php',
    'admin/monitor' => 'pages/admin/monitor_status.php',
    'admin/notifications' => 'pages/admin/notifications.php',
    'admin/notifications/sms' => 'pages/admin/notifications/sms.php',
    'admin/notifications/telegram' => 'pages/admin/notifications/telegram.php',
    'admin/notifications/webhooks' => 'pages/admin/notifications/webhooks.php',
    'admin/notifications/rules' => 'pages/admin/notifications/rules.php',
    'admin/notifications/rules/add' => 'pages/admin/notifications/rules/add.php',
    'admin/notifications/rules/edit' => 'pages/admin/notifications/rules/edit.php',

    // Ayarlar
    'settings/mail' => 'pages/settings/mail.php',
    'settings/change_password' => 'pages/settings/change_password.php',
    
    // AJAX
    'ajax/add_user' => 'pages/ajax/add_user.php',
    
    // API
    'api'           => 'api/index.php',
];

// Debug için
if (isset($_GET['debug'])) {
    echo "Request URI: " . $request_uri . "<br>";
    echo "Script Name: " . $script_name . "<br>";
    echo "Path: " . $path . "<br>";
    echo "Available routes: " . implode(', ', array_keys($routes)) . "<br>";
    echo "Route found: " . (isset($routes[$path]) ? 'Yes' : 'No') . "<br>";
    exit;
}

// API istekleri için özel kontrol (route kontrolünden önce)
if (strpos($path, 'api/') === 0) {
    $api_path = substr($path, 4); // 'api/' kısmını çıkar
    
    // Debug için
    if (isset($_GET['debug'])) {
        echo "API Path: " . $api_path . "<br>";
    }
    
    if ($api_path === '' || $api_path === 'index.php') {
        include 'api/index.php';
        exit;
    } elseif ($api_path === 'auth' || strpos($api_path, 'auth/') === 0) {
        include 'api/auth.php';
        exit;
    } elseif ($api_path === 'sites' || strpos($api_path, 'sites/') === 0) {
        include 'api/sites.php';
        exit;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'API endpoint bulunamadı', 'error_code' => 'NOT_FOUND']);
        exit;
    }
}

// AJAX dosyaları için doğrudan erişim
if (strpos($path, 'pages/ajax/') === 0) {
    $ajax_file = $path;
    if (file_exists($ajax_file)) {
        include $ajax_file;
        exit;
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'AJAX dosyası bulunamadı']);
        exit;
    }
}

// Route bul
if (isset($routes[$path])) {
    $file = $routes[$path];
} else {
    // 404 - Sayfa bulunamadı
    http_response_code(404);
    include 'pages/errors/404.php';
    exit;
}

// Dosya var mı kontrol et
if (file_exists($file)) {
    include $file;
} else {
    // 404 - Dosya bulunamadı
    http_response_code(404);
    include 'pages/errors/404.php';
    exit;
}
?>
