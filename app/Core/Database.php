<?php
namespace App\Core;

/**
 * PDO erişim yardımcısı.
 *
 * MEVCUT SİSTEM (config/database.php) korunur — orada hâlâ global $pdo
 * üretiliyor ve tüm eski kod + monitor*.php onu kullanıyor.
 *
 * Bu sınıfın tek amacı: yeni Service/Repository sınıflarının PDO'ya
 * tutarlı bir şekilde ulaşabilmesi. PDO zaten global olarak mevcutsa
 * onu döndürür; yoksa (CLI/test bağlamı) opsiyonel olarak yeni bağlantı
 * kurar.
 *
 * Kullanım:
 *   $pdo = Database::pdo();
 */
final class Database
{
    private static ?\PDO $instance = null;

    /**
     * Global $pdo varsa onu kullanır (mevcut sistemle uyumlu),
     * yoksa null döner (bağlantı config/database.php'de kurulmalı).
     */
    public static function pdo(): ?\PDO
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // Global $pdo varsa (config/database.php tarafından kurulmuş) onu sakla
        if (isset($GLOBALS['pdo']) && $GLOBALS['pdo'] instanceof \PDO) {
            self::$instance = $GLOBALS['pdo'];
        }

        return self::$instance;
    }

    /**
     * Test veya bağımsız kullanım için PDO'yu elle enjekte etmeye izin ver.
     */
    public static function setInstance(\PDO $pdo): void
    {
        self::$instance = $pdo;
    }
}
