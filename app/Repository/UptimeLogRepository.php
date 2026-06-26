<?php
namespace App\Repository;

/**
 * uptime_logs veri erişim + hesaplama katmanı.
 *
 * SQL'ler mevcut sistemden (includes/functions.php, pages/sites/detail.php)
 * AYNEN taşınmıştır. calculateUptime fonksiyonu da buraya taşındı
 * (DB + JSON fallback mantığı korundu).
 */
final class UptimeLogRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Yeni uptime log kaydı + alert kaydı ekle.
     * Kaynak: includes/functions.php -> logSiteStatus()
     *
     * NOT: Bu metod DB'ye log YAZAR. JSON dosya yedeği de güncellenir
     * (geriye dönük uyumluluk için).
     */
    public function logStatus(int $siteId, string $status, ?int $responseTime, ?int $httpCode, ?string $error = null): void
    {
        // uptime_logs tablosuna yaz
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO uptime_logs (site_id, status, response_time, http_code, error, timestamp) VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$siteId, $status, $responseTime, $httpCode, $error]);
        } catch (\Exception $e) {
            error_log("Uptime log kaydetme hatası: " . $e->getMessage());
        }

        // JSON log dosyasına yedek yaz (eski davranış korunuyor)
        $this->writeJsonBackup($siteId, $status, $responseTime, $httpCode, $error);

        // alerts tablosuna da yaz
        try {
            $message = $status === 'up' ? 'Site çalışıyor' : 'Site kesintide';
            if ($error) {
                $message .= ' - ' . $error;
            }
            $stmt = $this->pdo->prepare("INSERT INTO alerts (site_id, status, message, timestamp) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$siteId, $status, $message]);
        } catch (\Exception $e) {
            error_log("Alert kaydetme hatası: " . $e->getMessage());
        }
    }

    /**
     * Bir site için son N günün uptime yüzdesini hesapla.
     * Kaynak: includes/functions.php -> calculateUptime()
     * Önce DB'den, başarısız olursa JSON fallback'ten hesaplar.
     *
     * @return float 0-100
     */
    public function calculateUptime(int $siteId, int $days = 30): float
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT
                    COUNT(*) as total_checks,
                    SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as up_checks
                FROM uptime_logs
                WHERE site_id = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            $stmt->execute([$siteId, $days]);
            $result = $stmt->fetch();

            if ($result && $result['total_checks'] > 0) {
                return ($result['up_checks'] / $result['total_checks']) * 100;
            }

            // Fallback: JSON dosyasından hesapla
            return $this->calculateUptimeFromJson($siteId, $days);
        } catch (\Exception $e) {
            error_log("Uptime hesaplama hatası: " . $e->getMessage());
            return 0.0;
        }
    }

    /**
     * Günlük uptime yüzdeleri (status sayfası şerit görünümü).
     * Kaynak: includes/functions.php -> getDailyUptimeStrip()
     *
     * @return array<string,float|null> ['Y-m-d' => float|null]
     */
    public function getDailyUptimeStrip(int $siteId, int $days = 90): array
    {
        $strip = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $strip[date('Y-m-d', strtotime("-{$i} days"))] = null;
        }

        try {
            $stmt = $this->pdo->prepare("
                SELECT DATE(timestamp) AS day,
                       COUNT(*) AS total,
                       SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) AS up_count
                FROM uptime_logs
                WHERE site_id = ? AND timestamp >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY DATE(timestamp)
            ");
            $stmt->execute([$siteId, $days]);
            foreach ($stmt->fetchAll() as $row) {
                if (isset($strip[$row['day']]) || array_key_exists($row['day'], $strip)) {
                    $total = (int)$row['total'];
                    $strip[$row['day']] = $total > 0 ? ((int)$row['up_count'] / $total) * 100 : null;
                }
            }
        } catch (\PDOException $e) {
            error_log("getDailyUptimeStrip hatası: " . $e->getMessage());
        }

        return $strip;
    }

    /**
     * Belirli zaman aralığında log sayısı.
     * Kaynak: pages/sites/detail.php
     */
    public function countBySiteAndHours(int $siteId, int $hours): int
    {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as total
            FROM uptime_logs
            WHERE site_id = ?
            AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        ");
        $stmt->execute([$siteId, $hours]);
        $row = $stmt->fetch();
        return $row ? (int)$row['total'] : 0;
    }

    /**
     * Sayfalanmış logları getir (zaman aralığı ile).
     * Kaynak: pages/sites/detail.php (named params + INT bind).
     *
     * @return array<int,array>
     */
    public function findPaginatedBySite(int $siteId, int $hours, int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                status,
                response_time,
                timestamp
            FROM uptime_logs
            WHERE site_id = :site_id
            AND timestamp >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
            ORDER BY timestamp DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':site_id', $siteId, \PDO::PARAM_INT);
        $stmt->bindValue(':hours', $hours, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Zaman aralığındaki toplam istatistikler.
     * Kaynak: pages/sites/detail.php
     *
     * @return array{total_checks:int,up_count:int,down_count:int,avg_response_time:int}
     */
    public function getStatsBySiteAndHours(int $siteId, int $hours): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                COUNT(*) as total_checks,
                SUM(CASE WHEN status = 'up' THEN 1 ELSE 0 END) as up_count,
                SUM(CASE WHEN status = 'down' THEN 1 ELSE 0 END) as down_count,
                AVG(CASE WHEN status = 'up' THEN response_time ELSE NULL END) as avg_response_time
            FROM uptime_logs
            WHERE site_id = ?
            AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
        ");
        $stmt->execute([$siteId, $hours]);
        $stats = $stmt->fetch();

        return [
            'total_checks' => $stats['total_checks'] ? (int)$stats['total_checks'] : 0,
            'up_count' => $stats['up_count'] ? (int)$stats['up_count'] : 0,
            'down_count' => $stats['down_count'] ? (int)$stats['down_count'] : 0,
            'avg_response_time' => $stats['avg_response_time'] ? (int)round($stats['avg_response_time']) : 0,
        ];
    }

    /**
     * Toplam log sayısı (API pagination meta'sı için).
     * Kaynak: api/sites.php -> getSiteLogs()
     */
    public function countBySite(int $siteId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM uptime_logs WHERE site_id = ?");
        $stmt->execute([$siteId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * API için sayfalanmış loglar.
     * Kaynak: api/sites.php -> getSiteLogs()
     *
     * @return array<int,array>
     */
    public function findBySiteForApi(int $siteId, int $limit, int $offset): array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM uptime_logs
            WHERE site_id = ?
            ORDER BY timestamp DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$siteId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    // ---------------------------------------------------------------
    // JSON dosya yedeği (eski davranışın korunması)
    // ---------------------------------------------------------------

    /**
     * JSON log dosyasına yedek yaz + 30 günden eskileri temizle.
     * Kaynak: includes/functions.php -> logSiteStatus()
     */
    private function writeJsonBackup(int $siteId, string $status, ?int $responseTime, ?int $httpCode, ?string $error): void
    {
        $yearMonth = date('Y-m');
        $logFile = "logs/{$yearMonth}.json";

        if (!file_exists('logs')) {
            @mkdir('logs', 0755, true);
        }

        $logData = [];
        if (file_exists($logFile)) {
            $logData = json_decode(file_get_contents($logFile), true) ?: [];
        }

        $logData[] = [
            'site_id' => $siteId,
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => $status,
            'response_time' => $responseTime,
            'http_code' => $httpCode,
            'error' => $error,
        ];

        // Son 30 günü tut
        $cutoff = date('Y-m-d H:i:s', strtotime('-30 days'));
        $logData = array_filter($logData, function ($entry) use ($cutoff) {
            return $entry['timestamp'] >= $cutoff;
        });

        @file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));
    }

    /**
     * JSON dosyasından uptime hesapla (DB fallback).
     * Kaynak: includes/functions.php -> calculateUptime()
     */
    private function calculateUptimeFromJson(int $siteId, int $days): float
    {
        $logFile = "logs/" . date('Y-m') . ".json";
        if (!file_exists($logFile)) {
            return 0.0;
        }

        $logData = json_decode(file_get_contents($logFile), true) ?: [];
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $filtered = array_filter($logData, function ($entry) use ($siteId, $cutoff) {
            return $entry['site_id'] == $siteId && $entry['timestamp'] >= $cutoff;
        });

        if (empty($filtered)) {
            return 0.0;
        }

        $total = count($filtered);
        $up = count(array_filter($filtered, function ($entry) {
            return $entry['status'] === 'up';
        }));

        return ($up / $total) * 100;
    }
}
