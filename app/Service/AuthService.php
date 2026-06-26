<?php
namespace App\Service;

use App\Repository\UserRepository;
use App\Repository\GroupRepository;

/**
 * Kimlik doğrulama servisi: giriş, kayıt, çıkış.
 *
 * İş mantığı pages/auth/login.php ve register.php'den taşındı.
 * Session yapısı KORUNDU ($_SESSION['user_id'], ['user_email'], ['user_role']).
 * Rate-limit + session fixation önlemleri aynen korundu.
 */
final class AuthService
{
    private UserRepository $users;
    private SecurityService $security;

    public function __construct(UserRepository $users, SecurityService $security)
    {
        $this->users = $users;
        $this->security = $security;
    }

    /**
     * Kullanıcı girişi.
     *
     * @return array{success:bool,message?:string,retry_after?:int}
     */
    public function login(string $email, string $password): array
    {
        // Brute-force koruması: IP başına 15 dk içinde 5 deneme, sonra 15 dk blok
        $rlKey = 'login:' . $this->security->clientIp();
        $rl = $this->security->rateLimitHit($rlKey, 5, 900, 900);

        if (!$rl['allowed']) {
            return [
                'success' => false,
                'message' => 'Çok fazla başarısız giriş denemesi. Lütfen '
                    . ceil($rl['retry_after'] / 60) . ' dakika sonra tekrar deneyin.',
                'retry_after' => $rl['retry_after'],
            ];
        }

        $user = $this->users->findByEmail($email);

        if ($user && !empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
            // Başarılı — sayaç sıfırla + session fixation önlemi
            $this->security->rateLimitReset($rlKey);
            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            return ['success' => true];
        }

        return ['success' => false, 'message' => null]; // mesaj sayfada dil ile çevrilir
    }

    /**
     * Yeni kullanıcı kaydı.
     *
     * Doğrulama: e-posta formatı, şifre uzunluğu (>=8), eşleşme, benzersizlik.
     * Rate-limit: IP başına saatte 10 deneme.
     *
     * @param string $email
     * @param string $password
     * @param string $confirmPassword
     * @return array{success:bool,error_code?:string,retry_after?:int}
     */
    public function register(string $email, string $password, string $confirmPassword): array
    {
        $rl = $this->security->rateLimitHit('register:' . $this->security->clientIp(), 10, 3600, 3600);
        if (!$rl['allowed']) {
            return ['success' => false, 'error_code' => 'rate_limited', 'retry_after' => $rl['retry_after']];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error_code' => 'invalid_email'];
        }
        if (strlen($password) < 8) {
            return ['success' => false, 'error_code' => 'password_too_short'];
        }
        if ($password !== $confirmPassword) {
            return ['success' => false, 'error_code' => 'password_mismatch'];
        }
        if ($this->users->emailExists($email)) {
            return ['success' => false, 'error_code' => 'email_exists'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->users->insert($email, $hash, 'user');

        return ['success' => true];
    }

    /**
     * Çıkış — session'ı tamamen temizle.
     */
    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    /**
     * Oturum kontrolü: giriş yapılmış mı?
     */
    public function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Admin mi?
     */
    public function isAdmin(): bool
    {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}
