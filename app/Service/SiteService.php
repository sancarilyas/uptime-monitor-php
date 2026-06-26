<?php
namespace App\Service;

use App\Repository\SiteRepository;
use App\Repository\UserRepository;
use App\Repository\UptimeLogRepository;

/**
 * Site iş mantığı servisi.
 *
 * Tekrar eden "grup erişim kontrolü + veri çekme" desenini tek yere toplar.
 * Kaynak: pages/dashboard/index.php, pages/sites/detail.php, api/sites.php
 * içindeki tekrarlı kod blokları.
 *
 * Yetki modeli (mevcut sistemden AYNEN): bir siteyi görebilir/düzenleyebilir
 * eğer site sahibi ise VEYA site kullanıcının grubuna aitse.
 */
final class SiteService
{
    private SiteRepository $sites;
    private UserRepository $users;
    private UptimeLogRepository $logs;

    public function __construct(SiteRepository $sites, UserRepository $users, UptimeLogRepository $logs)
    {
        $this->sites = $sites;
        $this->users = $users;
        $this->logs = $logs;
    }

    /**
     * Kullanıcının görebileceği tüm siteleri getir (dashboard için).
     * Sıralama: down olanlar önce.
     *
     * @return array<int,array>
     */
    public function getVisibleSites(int $userId): array
    {
        $groupId = $this->users->getGroupId($userId);
        return $this->sites->findVisibleForUser($userId, $groupId);
    }

    /**
     * Erişilebilir tek siteyi getir (detail sayfası için).
     * Erişim yoksa null.
     *
     * @return array|null
     */
    public function getAccessibleSite(int $siteId, int $userId): ?array
    {
        $groupId = $this->users->getGroupId($userId);
        return $this->sites->findAccessible($siteId, $userId, $groupId);
    }

    /**
     * Yeni site ekle (dashboard add_site).
     *
     * @param array $data validate edilmiş alanlar (url/name + bildirim ayarları)
     * @return int yeni site id
     */
    public function addSite(array $data): int
    {
        return $this->sites->insert($data);
    }

    /**
     * Site güncelle (sadece sahip).
     */
    public function updateSiteByOwner(array $data, int $siteId, int $userId): bool
    {
        return $this->sites->updateByOwner($data, $siteId, $userId);
    }

    /**
     * Site sil (sadece sahip).
     */
    public function deleteSiteByOwner(int $siteId, int $userId): bool
    {
        return $this->sites->deleteByOwner($siteId, $userId);
    }

    /**
     * Bir sitenin uptime yüzdesi.
     */
    public function calculateUptime(int $siteId, int $days = 30): float
    {
        return $this->logs->calculateUptime($siteId, $days);
    }

    /**
     * Bir sitenin günlük uptime şeridi (public status sayfası).
     *
     * @return array<string,float|null>
     */
    public function getDailyUptimeStrip(int $siteId, int $days = 90): array
    {
        return $this->logs->getDailyUptimeStrip($siteId, $days);
    }

    /**
     * Detail sayfası için tam veri paketi: loglar + istatistikler + sayfalama meta.
     *
     * @return array{
     *   logs:array,
     *   stats:array,
     *   total_logs:int,
     *   total_pages:int
     * }
     */
    public function getSiteDetailData(int $siteId, int $hours, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $totalLogs = $this->logs->countBySiteAndHours($siteId, $hours);
        $logs = $this->logs->findPaginatedBySite($siteId, $hours, $perPage, $offset);
        $stats = $this->logs->getStatsBySiteAndHours($siteId, $hours);

        return [
            'logs' => $logs,
            'stats' => $stats,
            'total_logs' => $totalLogs,
            'total_pages' => (int)ceil($totalLogs / max(1, $perPage)),
        ];
    }
}
