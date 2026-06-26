<?php
namespace App\Repository;

/**
 * alerts veri erişim katmanı.
 * (UptimeLogRepository::logStatus zaten alert ekliyor; bu sınıf
 * sorgu/silme işlemleri için ayrı tutuldu.)
 */
final class AlertRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Bir siteye ait son alarmlar.
     *
     * @return array<int,array>
     */
    public function findBySite(int $siteId, int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM alerts WHERE site_id = ? ORDER BY timestamp DESC LIMIT ?"
        );
        $stmt->execute([$siteId, $limit]);
        return $stmt->fetchAll();
    }
}
