<?php
namespace App\Repository;

/**
 * system_settings veri erişim katmanı.
 * Kaynak: includes/functions.php -> getSystemSetting/setSystemSetting
 */
final class SettingsRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Bir ayarı getir.
     *
     * @return string|null
     */
    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();
        return $row ? $row['setting_value'] : $default;
    }

    /**
     * Bir ayarı kaydet/güncelle (upsert).
     */
    public function set(string $key, string $value): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
        );
        return $stmt->execute([$key, $value]);
    }
}
