<?php
// ============================================================
// Veritabanı bağlantı ayarları
// ------------------------------------------------------------
// Kimlik bilgileri ARTIK burada DEĞİL — .env / .env.local
// dosyalarından okunur (config/env.php aracılığıyla).
//
//   - Lokal:   <proje>/.env.local
//   - Üretim:  public_html DIŞINDA  <domain>/.env.local
//              (config/env.php otomatik olarak public_html'in
//               bir üst dizinine bakar)
// ============================================================

// .env sistemini yükle (env() fonksiyonunu sağlar).
// config/env.php, önce public_html dışına, sonra proje köküne bakar.
require_once __DIR__ . '/env.php';

// --- Kimlik bilgileri (.env'den) ---
$db_host = env('DB_HOST', 'localhost');
$db_name = env('DB_NAME', 'uptime_monitor');
$db_user = env('DB_USER', 'root');
$db_pass = env('DB_PASSWORD', '');

// --- PDO bağlantısı ---
try {
    $pdo = new PDO(
        "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4",
        $db_user,
        $db_pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_TIMEOUT, 30);
    $pdo->setAttribute(PDO::ATTR_PERSISTENT, false); // Persistent connections can cause issues

    // MySQL timeout ayarları (server has gone away hatasını önlemek için)
    $pdo->exec("SET SESSION wait_timeout = 600");
    $pdo->exec("SET SESSION interactive_timeout = 600");

    // MySQL saat dilimini ayarla
    $pdo->exec("SET time_zone = '+03:00'");
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// --- $base_url hesabı ---
// Web (Apache/Nginx) bağlamında istek bilgilerinden türetilir.
// CLI/cron/daemon bağlamında (HTTP_HOST yoksa) APP_BASE_URL fallback kullanılır.
function detectBaseUrl() {
    // Fallback: .env'den
    $fallback = env('APP_BASE_URL', 'http://localhost/');

    // CLI / cron / daemon: $_SERVER['HTTP_HOST'] yok
    if (PHP_SAPI === 'cli' || empty($_SERVER['HTTP_HOST'])) {
        return rtrim($fallback, '/') . '/';
    }

    // Protokol (HTTPS tespiti — reverse proxy'ler dahil)
    $https_on = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
    $forwarded = (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    $port_443 = (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    $protocol = ($https_on || $forwarded || $port_443) ? 'https' : 'http';

    $http_host = $_SERVER['HTTP_HOST'];

    // Base path (script'in bulunduğu dizin)
    $script_name = $_SERVER['SCRIPT_NAME'];
    $base_path = dirname($script_name);
    $base_path = rtrim($base_path, '/');
    if ($base_path === '\\' || $base_path === '/') {
        $base_path = '';
    }

    return $protocol . '://' . $http_host . $base_path . '/';
}

$base_url = detectBaseUrl();

// --- Tablo oluşturma (ilk kurulum için auto-create) ---
function createTables($pdo)
{
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('admin', 'user') DEFAULT 'user',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",

        "CREATE TABLE IF NOT EXISTS sites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            url VARCHAR(500) NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT,
            notification_emails TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            last_check TIMESTAMP NULL,
            last_status ENUM('up', 'down') NULL,
            response_time INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS alerts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            site_id INT NOT NULL,
            status ENUM('up', 'down') NOT NULL,
            message TEXT,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
        )",

        "CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",

        // Oran sınırlama (login brute-force + API rate limit) için
        "CREATE TABLE IF NOT EXISTS rate_limits (
            rate_key VARCHAR(190) PRIMARY KEY,
            hits INT NOT NULL DEFAULT 0,
            window_start INT NOT NULL DEFAULT 0,
            blocked_until INT NOT NULL DEFAULT 0
        )"
    ];

    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            error_log("Tablo oluşturma hatası: " . $e->getMessage());
        }
    }
}

// Tabloları oluştur
createTables($pdo);
?>