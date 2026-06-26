<?php
/**
 * Mini Site Chart Component - LAZY LOADED VERSION
 * Sadece görünür olduğunda yüklenir - Maksimum performans
 */

$site_id = $site_id ?? null;
if (!$site_id) return;

// Site bilgilerini hafızadan al (zaten çekilmiş)
$site = $site ?? null;
if (!$site) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
    $stmt->execute([$site_id]);
    $site = $stmt->fetch();
    if (!$site) return;
}

$current_color = $site['last_status'] === 'up' ? '#28a745' : '#dc3545';
?>

<!-- Mini Site Grafiği - Lazy Loaded -->
<div class="mt-3">
    <div class="card bg-light">
        <div class="card-body p-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 text-muted">
                    <i class="fas fa-chart-bar"></i> Son 24 Saat
                </h6>
                <small class="text-muted" id="avg-uptime-<?= $site_id ?>">Yükleniyor...</small>
            </div>
            
            <!-- Lazy Loading Placeholder -->
            <div class="lazy-chart" 
                 data-site-id="<?= $site_id ?>" 
                 data-loaded="false"
                 style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 8px; height: 70px;">
                
                <!-- Loading State -->
                <div class="chart-loading" style="display: flex; align-items: center; justify-content: center; height: 100%; color: #6c757d;">
                    <small>
                        <i class="fas fa-chart-bar"></i> Grafik yüklenecek...
                    </small>
                </div>
                
                <!-- Chart Container (gizli, lazy load ile gösterilecek) -->
                <div class="chart-content" style="display: none;">
                    <!-- Barlar buraya JavaScript ile eklenecek -->
                    <div class="chart-bars" style="display: flex; align-items: flex-end; height: 45px; gap: 1px;">
                        <!-- 24 adet bar placeholder -->
                    </div>
                    
                    <!-- Durum Bilgisi -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px; font-size: 11px; color: #6c757d;">
                        <div>
                            <span style="display: inline-block; width: 8px; height: 8px; background: <?= $current_color ?>; border-radius: 50%; margin-right: 4px;"></span>
                            <span><?= $site['last_status'] === 'up' ? 'ÇALIŞIYOR' : 'KESİNTİ' ?></span>
                        </div>
                        <div>
                            Son: <?= $site['last_check'] ? date('H:i', strtotime($site['last_check'])) : 'N/A' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

