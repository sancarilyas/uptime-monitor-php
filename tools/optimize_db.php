<?php
/**
 * Veritabanı Optimizasyon Aracı (CLI)
 * ------------------------------------------------------------
 * Çok fazla log biriktiğinde sistemin yavaşlamasını/kilitlenmesini giderir:
 *   1. uptime_logs için bileşik index (site_id, timestamp) ekler — en büyük kazanç.
 *   2. Eski uptime_logs kayıtlarını saklama süresine göre temizler (batch'li).
 *   3. notification_logs için de saklama temizliği yapar.
 *
 * Büyük tablolarda (milyonlarca satır) index oluşturma ve silme uzun sürebilir;
 * bu yüzden web isteğinde DEĞİL, burada (CLI) çalıştırılır.
 *
 * Kullanım:
 *   php tools/optimize_db.php                 # index + 90 günden eski logları temizle
 *   php tools/optimize_db.php --days=60       # 60 günden eskileri temizle
 *   php tools/optimize_db.php --no-purge      # sadece index ekle, silme yapma
 *   php tools/optimize_db.php --optimize      # sonunda OPTIMIZE TABLE çalıştır (disk geri kazanımı)
 * ------------------------------------------------------------
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Bu araç yalnızca komut satırından çalıştırılabilir.\n");
}

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

// --- Argümanlar ---
$days = 90;
$do_purge = true;
$do_optimize = false;
foreach ($argv as $arg) {
    if (preg_match('/^--days=(\d+)$/', $arg, $m)) { $days = (int)$m[1]; }
    if ($arg === '--no-purge') { $do_purge = false; }
    if ($arg === '--optimize') { $do_optimize = true; }
}

function out($msg) { echo '[' . date('H:i:s') . '] ' . $msg . PHP_EOL; }

out("Veritabanı optimizasyonu başlıyor...");

// ============================================================
// 1) Bileşik index (site_id, timestamp)
// ============================================================
try {
    $has_index = false;
    $stmt = $pdo->query("SHOW INDEX FROM uptime_logs WHERE Key_name = 'idx_site_time'");
    if ($stmt->fetch()) { $has_index = true; }

    if ($has_index) {
        out("✓ idx_site_time index zaten mevcut.");
    } else {
        out("→ idx_site_time (site_id, timestamp) ekleniyor... (büyük tabloda dakikalar sürebilir)");
        $t0 = microtime(true);
        $pdo->exec("ALTER TABLE uptime_logs ADD INDEX idx_site_time (site_id, timestamp)");
        out(sprintf("✓ Index eklendi (%.1f sn).", microtime(true) - $t0));
    }
} catch (PDOException $e) {
    out("✗ Index hatası: " . $e->getMessage());
}

// ============================================================
// 2) Eski uptime_logs temizliği (batch'li — kilidi uzun tutmamak için)
// ============================================================
if ($do_purge) {
    try {
        $before = (int)$pdo->query("SELECT COUNT(*) FROM uptime_logs")->fetchColumn();
        out("uptime_logs satır sayısı: " . number_format($before));
        out("→ {$days} günden eski kayıtlar temizleniyor (batch: 20.000)...");

        $stmt = $pdo->prepare("DELETE FROM uptime_logs WHERE timestamp < DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT 20000");
        $total_deleted = 0;
        do {
            $stmt->execute([$days]);
            $n = $stmt->rowCount();
            $total_deleted += $n;
            if ($n > 0) {
                out("  ... " . number_format($total_deleted) . " kayıt silindi");
                usleep(100000); // 0.1 sn — DB'ye nefes aldır
            }
        } while ($n > 0);

        $after = (int)$pdo->query("SELECT COUNT(*) FROM uptime_logs")->fetchColumn();
        out("✓ uptime_logs temizlendi: " . number_format($total_deleted) . " silindi, kalan " . number_format($after));
    } catch (PDOException $e) {
        out("✗ uptime_logs temizlik hatası: " . $e->getMessage());
    }

    // notification_logs temizliği (varsa)
    try {
        $stmt = $pdo->prepare("DELETE FROM notification_logs WHERE sent_at < DATE_SUB(NOW(), INTERVAL ? DAY) LIMIT 20000");
        $nl_deleted = 0;
        do {
            $stmt->execute([$days]);
            $n = $stmt->rowCount();
            $nl_deleted += $n;
        } while ($n > 0);
        if ($nl_deleted > 0) {
            out("✓ notification_logs temizlendi: " . number_format($nl_deleted) . " silindi");
        }
    } catch (PDOException $e) {
        // notification_logs olmayabilir veya sent_at kolonu farklı — sessizce geç
    }
}

// ============================================================
// 3) OPTIMIZE TABLE (isteğe bağlı — disk alanını geri kazanır, tabloyu kilitler)
// ============================================================
if ($do_optimize) {
    try {
        out("→ OPTIMIZE TABLE uptime_logs... (tabloyu geçici kilitler)");
        $pdo->exec("OPTIMIZE TABLE uptime_logs");
        out("✓ OPTIMIZE tamamlandı.");
    } catch (PDOException $e) {
        out("✗ OPTIMIZE hatası: " . $e->getMessage());
    }
}

out("Tamamlandı. ✅");
