<?php
/**
 * Basit .env yükleyici (bağımlılıksız).
 *
 * Yükleme sırası (sonraki öncekini ezer):
 *   1. .env          (varsayılan / üretim)
 *   2. .env.local    (lokal geliştirme - git'e GİRMEZ)
 *
 * Kullanım:  env('DB_PASSWORD', 'varsayilan');
 */

if (!function_exists('loadEnv')) {
    function loadEnv($path)
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);

            // Yorum satırlarını ve geçersiz satırları atla
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name  = trim($name);
            $value = trim($value);

            // Tırnak işaretlerini kaldır
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last  = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            // Mevcut değerleri ezme (gerçek ortam değişkenleri öncelikli)
            if (getenv($name) === false) {
                putenv("$name=$value");
                $_ENV[$name]    = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

if (!function_exists('env')) {
    function env($key, $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }

        // Yaygın literal değerleri normalize et
        switch (strtolower($value)) {
            case 'true':  return true;
            case 'false': return false;
            case 'null':  return null;
            case 'empty': return '';
        }

        return $value;
    }
}

// .env dosyalarını birden fazla konumda ara.
//
// Öncelik (yukarıdaki sonraki önceki̇ ezer mantığı loadEnv içinde geçerlidir):
//   1. public_html DIŞI (production):
//        dirname(dirname(__DIR__))  ->  /home/<user>/domains/<domain>/.env.local
//      Sunucuda .env.local public_html dışına konduğunda otomatik bulunur ve
//      web üzerinden asla erişilemeyen güvenli bir konumda tutulur.
//   2. Proje kökü (lokal geliştirme):
//        dirname(__DIR__)            ->  <proje>/.env.local
//
// Aşağıdaki her konumda önce .env, sonra .env.local denir.
$__root_candidates = [
    dirname(dirname(__DIR__)), // public_html'in bir üstü (production)
    dirname(__DIR__),          // proje kökü (lokal)
];

foreach ($__root_candidates as $__root) {
    if (!is_string($__root) || $__root === '' || $__root === '/') {
        continue; // geçersiz/tehlikeli konumu atla
    }
    loadEnv($__root . '/.env');
    loadEnv($__root . '/.env.local');
}
unset($__root, $__root_candidates);
