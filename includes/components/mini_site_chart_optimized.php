<?php
/**
 * Mini Site Chart Component - OPTIMIZED VERSION
 * Performans için optimize edilmiş basit grafik - Animasyonsuz
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Site ID'sini al
$site_id = $site_id ?? null;
if (!$site_id) return;

// Site bilgilerini al
$stmt = $pdo->prepare("SELECT * FROM sites WHERE id = ?");
$stmt->execute([$site_id]);
$site = $stmt->fetch();

if (!$site) return;

// Son 24 saatlik verileri al - TEK SORGU İLE
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(timestamp, '%Y-%m-%d %H:00:00') as hour_slot,
        AVG(CASE WHEN status = 'up' THEN 100 ELSE 0 END) as uptime_percent,
        COUNT(*) as check_count
    FROM uptime_logs 
    WHERE site_id = ? 
    AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY hour_slot
    ORDER BY hour_slot ASC
");
$stmt->execute([$site_id]);
$hourly_data = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 24 saatlik veri oluştur
$uptime_data = [];
$total_uptime = 0;
for ($i = 23; $i >= 0; $i--) {
    $hour_key = date('Y-m-d H:00:00', strtotime("-$i hours"));
    
    if (isset($hourly_data[$hour_key])) {
        $uptime = (float)$hourly_data[$hour_key];
    } else {
        // Veri yoksa site'nin mevcut durumunu kullan
        $uptime = $site['last_status'] === 'up' ? 100 : 0;
    }
    
    $uptime_data[] = $uptime;
    $total_uptime += $uptime;
}

$avg_uptime = count($uptime_data) > 0 ? round($total_uptime / count($uptime_data), 1) : 0;
$current_color = $site['last_status'] === 'up' ? '#28a745' : '#dc3545';
?>

<!-- Mini Site Grafiği - Basit & Performanslı -->
<div class="mt-3">
    <div class="card bg-light">
        <div class="card-body p-2">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0 text-muted">
                    <i class="fas fa-chart-bar"></i> Son 24 Saat
                </h6>
                <small class="text-muted">Ort: <?= $avg_uptime ?>%</small>
            </div>
            
            <!-- Basit Bar Grafiği -->
            <div class="simple-chart" data-site-id="<?= $site_id ?>" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 8px; height: 70px;">
                <!-- Barlar -->
                <div style="display: flex; align-items: flex-end; height: 45px; gap: 1px;">
                    <?php foreach($uptime_data as $i => $uptime): 
                        $bar_height = max($uptime, 5); // Minimum 5% görünürlük
                        $bar_color = $uptime >= 50 ? '#28a745' : '#dc3545';
                    ?>
                        <div class="chart-bar" 
                             data-uptime="<?= $uptime ?>"
                             data-hour="<?= $i ?>"
                             style="flex: 1; 
                                    height: <?= $bar_height ?>%; 
                                    background: <?= $bar_color ?>; 
                                    border-radius: 2px;
                                    transition: opacity 0.2s;"
                             title="Uptime: <?= $uptime ?>%">
                        </div>
                    <?php endforeach; ?>
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

<script>
// Minimal JavaScript - Sadece güncelleme için
(function() {
    const chartEl = document.querySelector('.simple-chart[data-site-id="<?= $site_id ?>"]');
    if (!chartEl) return;
    
    let isUpdating = false;
    
    // Grafik güncelleme
    function updateChart() {
        if (isUpdating) return;
        isUpdating = true;
        
        fetch('/uptime/pages/ajax/get_live_data.php?site_id=<?= $site_id ?>')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const bars = chartEl.querySelectorAll('.chart-bar');
                    const newColor = data.data.status === 'up' ? '#28a745' : '#dc3545';
                    
                    // Son barı güncelle (en sağdaki)
                    if (bars.length > 0) {
                        const lastBar = bars[bars.length - 1];
                        const newUptime = data.data.uptime || 0;
                        lastBar.style.background = newUptime >= 50 ? '#28a745' : '#dc3545';
                        lastBar.style.height = Math.max(newUptime, 5) + '%';
                        lastBar.setAttribute('data-uptime', newUptime);
                        lastBar.title = 'Uptime: ' + newUptime + '%';
                    }
                }
            })
            .catch(err => console.error('Chart update error:', err))
            .finally(() => {
                isUpdating = false;
            });
    }
    
    // 30 saniyede bir güncelle
    setInterval(updateChart, 30000);
    
    // Hover efekti - minimal
    chartEl.addEventListener('mouseover', function(e) {
        if (e.target.classList.contains('chart-bar')) {
            e.target.style.opacity = '0.7';
        }
    });
    
    chartEl.addEventListener('mouseout', function(e) {
        if (e.target.classList.contains('chart-bar')) {
            e.target.style.opacity = '1';
        }
    });
})();
</script>

