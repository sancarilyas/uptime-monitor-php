<?php
namespace App\Repository;

/**
 * Kullanıcı veri erişim katmanı.
 * SQL'ler mevcut sistemden (login.php, register.php, admin/users.php) AYNEN taşınmıştır.
 */
final class UserRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * E-posta ile kullanıcı bul.
     * Kaynak: pages/auth/login.php
     *
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * İd ile kullanıcı bul.
     *
     * @return array|null
     */
    public function findById(int $userId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Kullanıcının group_id'sini getir.
     * Kaynak: pages/dashboard/index.php, pages/sites/detail.php
     */
    public function getGroupId(int $userId): ?int
    {
        $stmt = $this->pdo->prepare("SELECT group_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row || $row['group_id'] === null) {
            return null;
        }
        return (int)$row['group_id'];
    }

    /**
     * E-posta daha önce kayıtlı mı?
     * Kaynak: pages/auth/register.php
     */
    public function emailExists(string $email): bool
    {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }

    /**
     * Yeni kullanıcı kaydı (varsayılan rol: user).
     * Kaynak: pages/auth/register.php
     *
     * @return int Yeni kullanıcı id
     */
    public function insert(string $email, string $passwordHash, string $role = 'user'): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (email, password_hash, role, created_at) VALUES (?, ?, ?, NOW())"
        );
        $stmt->execute([$email, $passwordHash, $role]);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Tüm kullanıcıları getir (admin paneli).
     *
     * @return array<int,array>
     */
    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    /**
     * Şifre hash güncelle (şifre değiştirme).
     */
    public function updatePasswordHash(int $userId, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        return $stmt->execute([$passwordHash, $userId]);
    }
}
