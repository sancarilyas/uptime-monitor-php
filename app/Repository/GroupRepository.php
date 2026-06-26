<?php
namespace App\Repository;

/**
 * Grup veri erişim katmanı.
 * SQL'ler pages/admin/groups.php'den AYNEN taşınmıştır.
 */
final class GroupRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Grup adı daha önce kullanılmış mı? (belirli bir id hariç tutulabilir).
     * Kaynak: pages/admin/groups.php
     */
    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare("SELECT id FROM `groups` WHERE name = ? AND id != ?");
            $stmt->execute([$name, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare("SELECT id FROM `groups` WHERE name = ?");
            $stmt->execute([$name]);
        }
        return $stmt->fetch() !== false;
    }

    /**
     * Yeni grup ekle.
     * Kaynak: pages/admin/groups.php
     *
     * @return int Yeni grup id
     */
    public function insert(string $name, string $description, int $createdBy): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO `groups` (name, description, created_by, created_at) VALUES (?, ?, ?, NOW())"
        );
        $stmt->execute([$name, $description, $createdBy]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Gruptaki kullanıcı sayısı (silme öncesi kontrol).
     * Kaynak: pages/admin/groups.php
     */
    public function countUsers(int $groupId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as user_count FROM users WHERE group_id = ?");
        $stmt->execute([$groupId]);
        $row = $stmt->fetch();
        return $row ? (int)$row['user_count'] : 0;
    }

    /**
     * Grup sil.
     * Kaynak: pages/admin/groups.php
     */
    public function delete(int $groupId): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `groups` WHERE id = ?");
        return $stmt->execute([$groupId]);
    }

    /**
     * Grup güncelle.
     * Kaynak: pages/admin/groups.php
     */
    public function update(int $groupId, string $name, string $description): bool
    {
        $stmt = $this->pdo->prepare("UPDATE `groups` SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$name, $description, $groupId]);
    }

    /**
     * Tüm grupları kullanıcı sayısı ve oluşturan ile birlikte getir.
     * Kaynak: pages/admin/groups.php
     *
     * @return array<int,array>
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query("
            SELECT g.*,
                   u.email as created_by_email,
                   COUNT(ug.id) as user_count
            FROM `groups` g
            LEFT JOIN users u ON g.created_by = u.id
            LEFT JOIN users ug ON g.id = ug.group_id
            GROUP BY g.id
            ORDER BY g.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    /**
     * İd'ye göre grup bul.
     *
     * @return array|null
     */
    public function findById(int $groupId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `groups` WHERE id = ?");
        $stmt->execute([$groupId]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }
}
