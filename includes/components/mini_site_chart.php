<?php
/**
 * Mini Site Status - ULTRA BASIT VERSIYON
 * Sadece durum göstergesi - Grafik YOK
 * MAKSIMUM PERFORMANS
 */

$site_id = $site_id ?? null;
if (!$site_id) return;

$site = $site ?? null;
if (!$site) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
    $stmt->execute([$site_id]);
    $site = $stmt->fetch();
    if (!$site) return;
}

// Uptime hesapla — çağıran sayfa zaten hesapladıysa yeniden hesaplama (PERFORMANS)
if (!isset($uptime_24h)) {
    $uptime_24h = calculateUptime($site_id, 1);
}
$status_color = $site['last_status'] === 'up' ? '#28a745' : '#dc3545';
$status_text = $site['last_status'] === 'up' ? 'ÇALIŞIYOR' : 'KESİNTİ';
$status_icon = $site['last_status'] === 'up' ? 'fa-check-circle' : 'fa-times-circle';
?>

<!-- Ultra Basit Durum Kartı -->
<div class="mt-3">
    <div class="card bg-light">
        <div class="card-body p-3">
            <!-- Durum Göstergesi -->
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center">
                    <i class="fas <?= $status_icon ?> me-2" style="color: <?= $status_color ?>; font-size: 1.2rem;"></i>
                    <strong style="color: <?= $status_color ?>;"><?= $status_text ?></strong>
                </div>
                <span class="badge" style="background: <?= $status_color ?>; font-size: 0.9rem;">
                    <?= number_format($uptime_24h, 1) ?>%
                </span>
            </div>
            
            <!-- İstatistikler -->
            <div class="row text-center mt-3">
                <div class="col-4">
                    <small class="text-muted d-block">Son 24 Saat</small>
                    <strong style="color: <?= $status_color ?>;"><?= number_format($uptime_24h, 1) ?>%</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted d-block">Yanıt Süresi</small>
                    <strong class="text-primary"><?= $site['last_response_time'] ?? 0 ?>ms</strong>
                </div>
                <div class="col-4">
                    <small class="text-muted d-block">Son Kontrol</small>
                    <strong class="text-secondary"><?= $site['last_check'] ? date('H:i', strtotime($site['last_check'])) : 'N/A' ?></strong>
                </div>
            </div>
            
            <!-- Uptime Bar (Basit) -->
            <div class="mt-3" style="background: #e9ecef; height: 8px; border-radius: 4px; overflow: hidden;">
                <div style="background: <?= $status_color ?>; height: 100%; width: <?= $uptime_24h ?>%; transition: width 0.3s;"></div>
            </div>
        </div>
    </div>
</div>
