<?php
/**
 * Basit PSR-4 benzeri autoloader.
 *
 * app/ namespace haritası:
 *   App\Core\X          -> app/Core/X.php
 *   App\Repository\X    -> app/Repository/X.php
 *   App\Service\X       -> app/Service/X.php
 *
 * Geriye dönük uyumlu: halihazırda global $pdo kullanan eski kod
 * etkilenmez. Bu dosya sadece yeni App\* sınıflarını yükler.
 */

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../';

    // App\ ile başlamayan sınıfları yoksay (örn. PHPMailer\PHPMailer\...)
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    // "App\Core\Database" -> "Core/Database.php"
    $relative = substr($class, strlen($prefix));
    $file = $base_dir . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
