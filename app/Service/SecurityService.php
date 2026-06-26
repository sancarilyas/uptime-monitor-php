<?php
namespace App\Service;

/**
 * Güvenlik servisi: CSRF token, sır şifreleme (at-rest), oran sınırlama.
 * Kaynak: includes/functions.php (csrfToken/csrfField/verifyCsrf/encryptSecret/
 * decryptSecret/rateLimitHit/rateLimitReset/clientIp).
 *
 * Tüm mantık aynen taşındı. Session ve rate_limits tablosu kullanımı değişmedi.
 */
final class SecurityService
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    // ------------------------------------------------------------------
    // CSRF
    // ------------------------------------------------------------------

    /**
     * Oturuma bağlı CSRF token'ı döndürür (yoksa üretir).
     */
    public function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Formlara eklenecek gizli CSRF input'unu döndürür.
     */
    public function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($this->csrfToken(), ENT_QUOTES) . '">';
    }

    /**
     * Gönderilen CSRF token'ı doğrular. Geçersizse:
     *   - JSON isteklerinde 403 + JSON hata
     *   - Normal isteklerde 403 + sade metin
     * Sadece durum değiştiren methodlarda (POST/PUT/PATCH/DELETE) kontrol eder.
     *
     * @param bool $asJson JSON yanıtı mi (AJAX için)
     */
    public function verifyCsrf(bool $asJson = false): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return true;
        }

        $sent = $_POST['csrf_token'] ?? '';
        if ($sent === '') {
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            foreach ($headers as $k => $v) {
                if (strcasecmp($k, 'X-CSRF-Token') === 0) {
                    $sent = $v;
                    break;
                }
            }
        }

        $valid = !empty($_SESSION['csrf_token']) && is_string($sent)
            && hash_equals($_SESSION['csrf_token'], $sent);

        if (!$valid) {
            http_response_code(403);
            if ($asJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Güvenlik doğrulaması başarısız (CSRF). Sayfayı yenileyip tekrar deneyin.',
                    'error_code' => 'CSRF_FAILED',
                ]);
            } else {
                echo 'Güvenlik doğrulaması başarısız (CSRF). Lütfen sayfayı yenileyip tekrar deneyin.';
            }
            exit;
        }
        return true;
    }

    // ------------------------------------------------------------------
    // Sır şifreleme (at-rest)
    // ------------------------------------------------------------------

    /**
     * Uygulama şifreleme anahtarını (.env APP_KEY) döndürür.
     */
    public function getAppKey(): ?string
    {
        static $key = false;
        if ($key !== false) {
            return $key;
        }
        $raw = function_exists('env') ? env('APP_KEY', '') : (getenv('APP_KEY') ?: '');
        if (!$raw) {
            $key = null;
            return $key;
        }
        if (strpos($raw, 'base64:') === 0) {
            $decoded = base64_decode(substr($raw, 7), true);
            $key = $decoded !== false ? $decoded : null;
        } else {
            $key = hash('sha256', $raw, true);
        }
        return $key;
    }

    /**
     * AES-256-GCM ile şifreler. Çıktı: "enc:v1:<base64>".
     * Boş değer veya anahtar yoksa girdi olduğu gibi döner.
     *
     * @param string|null $plaintext
     * @return string|null
     */
    public function encryptSecret(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return $plaintext;
        }
        $key = $this->getAppKey();
        if ($key === null || !function_exists('openssl_encrypt')) {
            return $plaintext;
        }
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            return $plaintext;
        }
        return 'enc:v1:' . base64_encode($iv . $tag . $cipher);
    }

    /**
     * encryptSecret ile şifrelenmiş değeri çözer.
     * "enc:v1:" ön eki yoksa değeri olduğu gibi döndürür (eski düz metin).
     *
     * @param string|null $value
     * @return string|null
     */
    public function decryptSecret(?string $value): ?string
    {
        if (!is_string($value) || strpos($value, 'enc:v1:') !== 0) {
            return $value;
        }
        $key = $this->getAppKey();
        if ($key === null || !function_exists('openssl_decrypt')) {
            return $value;
        }
        $bin = base64_decode(substr($value, 7), true);
        if ($bin === false || strlen($bin) < 28) {
            return $value;
        }
        $iv = substr($bin, 0, 12);
        $tag = substr($bin, 12, 16);
        $cipherText = substr($bin, 28);
        $plain = openssl_decrypt($cipherText, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return $plain === false ? $value : $plain;
    }

    // ------------------------------------------------------------------
    // Oran sınırlama (rate_limits tablosu)
    // ------------------------------------------------------------------

    /**
     * Genel amaçlı oran sınırlayıcı.
     *
     * @return array{allowed:bool,retry_after:int}
     */
    public function rateLimitHit(string $key, int $maxHits = 5, int $windowSec = 900, int $blockSec = 900): array
    {
        $now = time();
        $rateKey = substr($key, 0, 190);

        try {
            $stmt = $this->pdo->prepare("SELECT hits, window_start, blocked_until FROM rate_limits WHERE rate_key = ?");
            $stmt->execute([$rateKey]);
            $row = $stmt->fetch();

            // Aktif blok var mı?
            if ($row && (int)$row['blocked_until'] > $now) {
                return ['allowed' => false, 'retry_after' => (int)$row['blocked_until'] - $now];
            }

            if (!$row || ($now - (int)$row['window_start']) > $windowSec) {
                // Yeni pencere başlat
                $stmt = $this->pdo->prepare("INSERT INTO rate_limits (rate_key, hits, window_start, blocked_until)
                    VALUES (?, 1, ?, 0)
                    ON DUPLICATE KEY UPDATE hits = 1, window_start = VALUES(window_start), blocked_until = 0");
                $stmt->execute([$rateKey, $now]);
                return ['allowed' => true, 'retry_after' => 0];
            }

            $hits = (int)$row['hits'] + 1;
            if ($hits > $maxHits) {
                $blockedUntil = $now + $blockSec;
                $stmt = $this->pdo->prepare("UPDATE rate_limits SET hits = ?, blocked_until = ? WHERE rate_key = ?");
                $stmt->execute([$hits, $blockedUntil, $rateKey]);
                return ['allowed' => false, 'retry_after' => $blockSec];
            }

            $stmt = $this->pdo->prepare("UPDATE rate_limits SET hits = ? WHERE rate_key = ?");
            $stmt->execute([$hits, $rateKey]);
            return ['allowed' => true, 'retry_after' => 0];
        } catch (\Exception $e) {
            // Tablo yoksa veya hata olursa sistemi kilitleme — isteğe izin ver
            error_log("rateLimitHit hatası: " . $e->getMessage());
            return ['allowed' => true, 'retry_after' => 0];
        }
    }

    /**
     * Başarılı işlem sonrası oran sınırlama anahtarını sıfırlar.
     */
    public function rateLimitReset(string $key): void
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM rate_limits WHERE rate_key = ?");
            $stmt->execute([substr($key, 0, 190)]);
        } catch (\Exception $e) {
            // sessizce geç
        }
    }

    /**
     * İstemci IP adresini güvenli biçimde döndürür.
     */
    public function clientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'cli';
    }
}
