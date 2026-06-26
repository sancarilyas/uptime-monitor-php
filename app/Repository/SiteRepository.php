<?php
namespace App\Repository;

/**
 * Site veri erişim katmanı.
 *
 * TÜM SQL sorguları mevcut sistemden (pages/dashboard, pages/sites/detail,
 * api/sites.php) AYNEN taşınmıştır — sadece dosya yeri değişti.
 *
 * Bağımlılık: PDO enjekte edilir (global $pdo kullanılmaz).
 */
final class SiteRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Bir kullanıcının görebileceği tüm siteler (kendi + grubundaki).
     * Sıralama: önce down olanlar, sonra up, sonra diğerleri.
     * Kaynak: pages/dashboard/index.php (eski sorgu).
     *
     * @return array<int,array>
     */
    public function findVisibleForUser(int $userId, ?int $groupId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*,
                   g.name as group_name
            FROM sites s
            LEFT JOIN `groups` g ON s.group_id = g.id
            WHERE (s.user_id = ? OR (s.group_id IS NOT NULL AND s.group_id = ?))
            ORDER BY
                CASE
                    WHEN s.last_status = 'down' THEN 0
                    WHEN s.last_status = 'up' THEN 1
                    ELSE 2
                END,
                s.created_at DESC
        ");
        $stmt->execute([$userId, $groupId]);
        return $stmt->fetchAll();
    }

    /**
     * Tek bir siteyi getir (grup adı ile birlikte).
     * Erişim kontrolü: site sahibi veya aynı gruptan olmalı.
     * Kaynak: pages/sites/detail.php
     *
     * @return array|null
     */
    public function findAccessible(int $siteId, int $userId, ?int $groupId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*,
                   g.name as group_name
            FROM sites s
            LEFT JOIN `groups` g ON s.group_id = g.id
            WHERE s.id = ? AND (s.user_id = ? OR (s.group_id IS NOT NULL AND s.group_id = ?))
        ");
        $stmt->execute([$siteId, $userId, $groupId]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * İd'ye göre tek site (erişim kontrolü yok — dikkatli kullan).
     *
     * @return array|null
     */
    public function findById(int $siteId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM sites WHERE id = ?");
        $stmt->execute([$siteId]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Tüm aktif siteler (monitor/cron için).
     * Kaynak: monitor.php (eski "SELECT * FROM sites WHERE status = 'active'").
     *
     * @return array<int,array>
     */
    public function findAllActive(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM sites WHERE status = 'active'");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Public status sayfasında gösterilecek siteler.
     * Kaynak: includes/functions.php -> getPublicSites()
     *
     * @return array<int,array>
     */
    public function findPublic(): array
    {
        $stmt = $this->pdo->query("
            SELECT id, name, url, last_status, last_check, response_time
            FROM sites
            WHERE is_public = 1 AND status = 'active'
            ORDER BY name ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Yeni site ekle. Bildirim alanlarının tamamını içerir.
     * Kaynak: pages/dashboard/index.php (add_site).
     *
     * @param array $data Site alanları (validate edilmiş)
     * @return int Yeni site id
     */
    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO sites
            (user_id, group_id, url, monitor_path, name, description, notification_emails,
             notifications_enabled, email_notifications, telegram_notifications,
             sms_notifications, webhook_notifications, notify_on_down, notify_on_up,
             notification_priority, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $data['user_id'],
            $data['group_id'] ?? null,
            $data['url'],
            $data['monitor_path'] ?? '',
            $data['name'],
            $data['description'] ?? '',
            $data['notification_emails'] ?? '',
            $data['notifications_enabled'] ?? 0,
            $data['email_notifications'] ?? 0,
            $data['telegram_notifications'] ?? 0,
            $data['sms_notifications'] ?? 0,
            $data['webhook_notifications'] ?? 0,
            $data['notify_on_down'] ?? 0,
            $data['notify_on_up'] ?? 0,
            $data['notification_priority'] ?? 'medium',
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Site güncelle (sadece site sahibi).
     * Kaynak: pages/dashboard/index.php (update_site).
     */
    public function updateByOwner(array $data, int $siteId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE sites SET
            url = ?, monitor_path = ?, name = ?, description = ?, notification_emails = ?,
            notifications_enabled = ?, email_notifications = ?, telegram_notifications = ?,
            sms_notifications = ?, webhook_notifications = ?, notify_on_down = ?, notify_on_up = ?,
            notification_priority = ?
            WHERE id = ? AND user_id = ?
        ");
        return $stmt->execute([
            $data['url'],
            $data['monitor_path'] ?? '',
            $data['name'],
            $data['description'] ?? '',
            $data['notification_emails'] ?? '',
            $data['notifications_enabled'] ?? 0,
            $data['email_notifications'] ?? 0,
            $data['telegram_notifications'] ?? 0,
            $data['sms_notifications'] ?? 0,
            $data['webhook_notifications'] ?? 0,
            $data['notify_on_down'] ?? 0,
            $data['notify_on_up'] ?? 0,
            $data['notification_priority'] ?? 'medium',
            $siteId,
            $userId,
        ]);
    }

    /**
     * Site sil (sadece site sahibi).
     * Kaynak: pages/dashboard/index.php (delete_site).
     */
    public function deleteByOwner(int $siteId, int $userId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM sites WHERE id = ? AND user_id = ?");
        return $stmt->execute([$siteId, $userId]);
    }

    /**
     * Monitor için son durum alanlarını güncelle.
     * Kaynak: monitor.php
     */
    public function updateStatus(int $siteId, string $status, ?int $responseTime, ?int $lastResponseTime = null): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE sites SET last_check = NOW(), last_status = ?, response_time = ?, last_response_time = ?
            WHERE id = ?
        ");
        return $stmt->execute([$status, $responseTime, $lastResponseTime ?? $responseTime, $siteId]);
    }
}
