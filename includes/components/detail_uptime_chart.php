<?php
/**
 * Detail Page Uptime Chart Component
 * EKG/Nabız tarzı grafik - Her kontrol noktasını gösterir
 */

// Bu dosya detail.php'den çağrılır ve şu değişkenlere ihtiyaç duyar:
// $site_id, $time_range, $hours, $site

if (!isset($site_id) || !isset($hours)) {
    die('Grafik için gerekli parametreler eksik.');
}

// Tüm log kayıtlarını al (sıralı)
$stmt = $pdo->prepare("
    SELECT 
        status,
        response_time,
        timestamp
    FROM uptime_logs 
    WHERE site_id = ? 
    AND timestamp >= DATE_SUB(NOW(), INTERVAL ? HOUR)
    ORDER BY timestamp ASC
");
$stmt->execute([$site_id, $hours]);
$all_logs = $stmt->fetchAll();

// Grafik verileri
$chart_labels = [];
$chart_data = [];
$chart_colors = [];
$chart_segment_colors = [];

if (count($all_logs) > 0) {
    foreach ($all_logs as $log) {
        $timestamp = strtotime($log['timestamp']);
        
        // Label formatı (zaman aralığına göre)
        if ($hours <= 6) {
            $label = date('H:i', $timestamp); // Saatlik görünüm
        } elseif ($hours <= 48) {
            $label = date('d.m H:i', $timestamp); // Günlük görünüm
        } else {
            $label = date('d.m.Y', $timestamp); // Haftalık/aylık görünüm
        }
        
        $chart_labels[] = $label;
        
        // UP = 1, DOWN = 0 (çizgi grafiği için)
        $chart_data[] = $log['status'] === 'up' ? 1 : 0;
        
        // Renk belirleme
        $chart_colors[] = $log['status'] === 'up' ? 'rgba(40, 167, 69, 0.8)' : 'rgba(220, 53, 69, 0.8)';
    }
} else {
    // Veri yoksa mevcut durumu göster
    $chart_labels[] = date('H:i');
    $chart_data[] = $site['last_status'] === 'up' ? 1 : 0;
    $chart_colors[] = $site['last_status'] === 'up' ? 'rgba(40, 167, 69, 0.8)' : 'rgba(220, 53, 69, 0.8)';
}

$chart_title = $site['name'] . ' - Durum Grafiği';
$interval_text = '';
switch ($time_range) {
    case '1h': $interval_text = 'Son 1 Saat'; break;
    case '6h': $interval_text = 'Son 6 Saat'; break;
    case '24h': $interval_text = 'Son 24 Saat'; break;
    case '7d': $interval_text = 'Son 7 Gün'; break;
    case '30d': $interval_text = 'Son 30 Gün'; break;
}
?>

<div class="card mb-4">
    <div class="card-header bg-white dark-mode-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-heartbeat text-primary"></i> <?= htmlspecialchars($chart_title) ?>
            </h5>
            <div class="d-flex gap-3">
                <small class="text-muted">
                    <i class="fas fa-clock"></i> <?= $interval_text ?>
                </small>
                <small class="text-muted">
                    <i class="fas fa-database"></i> <?= count($all_logs) ?> kontrol kaydı
                </small>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div style="position: relative; height: 250px;">
            <canvas id="detailUptimeChart"></canvas>
        </div>
        
        <!-- Durum Göstergeleri -->
        <div class="mt-3 d-flex justify-content-center gap-3">
            <span class="badge bg-success">
                <i class="fas fa-circle"></i> Çalışıyor (UP)
            </span>
            <span class="badge bg-danger">
                <i class="fas fa-circle"></i> Kesinti (DOWN)
            </span>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('detailUptimeChart');
    if (!ctx) return;
    
    const chartLabels = <?= json_encode($chart_labels) ?>;
    const chartData = <?= json_encode($chart_data) ?>;
    const allLogs = <?= json_encode($all_logs) ?>;
    
    // Area chart için renk dizilerini hazırla
    const backgroundColorArray = chartData.map(val => 
        val === 1 ? 'rgba(40, 167, 69, 0.4)' : 'rgba(220, 53, 69, 0.4)'
    );
    
    const borderColorArray = chartData.map(val => 
        val === 1 ? 'rgba(40, 167, 69, 0.8)' : 'rgba(220, 53, 69, 0.8)'
    );
    
    const pointColorArray = chartData.map(val => 
        val === 1 ? '#28a745' : '#dc3545'
    );
    
    // Area chart için veri hazırla
    const areaData = {
        labels: chartLabels,
        datasets: [{
            label: 'Durum',
            data: chartData,
            backgroundColor: backgroundColorArray,
            borderColor: borderColorArray,
            borderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 6,
            pointBackgroundColor: pointColorArray,
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            tension: 0.1,
            fill: true,
            stepped: false
        }]
    };

    const config = {
        type: 'line',
        data: areaData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 1000,
                easing: 'easeInOutQuart'
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.9)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#333',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        title: function(context) {
                            const idx = context[0].dataIndex;
                            if (allLogs[idx]) {
                                return 'Kontrol Zamanı: ' + context[0].label;
                            }
                            return context[0].label;
                        },
                        label: function(context) {
                            const idx = context.dataIndex;
                            const log = allLogs[idx];
                            if (!log) return '';
                            
                            const status = log.status === 'up' ? '✅ Çalışıyor (UP)' : '❌ Kesinti (DOWN)';
                            const responseTime = log.response_time ? log.response_time + 'ms' : '-';
                            
                            return [
                                'Durum: ' + status,
                                'Yanıt Süresi: ' + responseTime
                            ];
                        }
                    }
                }
            },
            scales: {
                y: {
                    display: true,
                    min: -0.1,
                    max: 1.1,
                    ticks: {
                        stepSize: 1,
                        callback: function(value) {
                            if (value === 1) return '✅ UP';
                            if (value === 0) return '❌ DOWN';
                            return '';
                        },
                        color: document.body.classList.contains('dark-mode') ? '#e2e8f0' : '#666',
                        font: {
                            size: 11
                        }
                    },
                    grid: {
                        color: function(context) {
                            const isDarkMode = document.body.classList.contains('dark-mode');
                            if (context.tick.value === 0.5) {
                                return isDarkMode ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)';
                            }
                            return isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)';
                        },
                        drawBorder: true
                    }
                },
                x: {
                    display: true,
                    ticks: {
                        color: document.body.classList.contains('dark-mode') ? '#e2e8f0' : '#666',
                        maxRotation: 45,
                        minRotation: 0,
                        font: {
                            size: 10
                        },
                        maxTicksLimit: 20
                    },
                    grid: {
                        color: document.body.classList.contains('dark-mode') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.05)'
                    }
                }
            },
            interaction: {
                intersect: false,
                mode: 'index'
            }
        }
    };
    
    new Chart(ctx, config);
});
</script>

<style>
#detailUptimeChart {
    background: linear-gradient(to bottom, 
        rgba(40, 167, 69, 0.08) 0%, 
        rgba(255, 255, 255, 1) 50%,
        rgba(220, 53, 69, 0.08) 100%
    );
    border-radius: 8px;
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid rgba(0,0,0,0.1);
}

/* Dark mode için detail chart stilleri */
body.dark-mode #detailUptimeChart {
    background: linear-gradient(to bottom, 
        rgba(40, 167, 69, 0.15) 0%, 
        rgba(45, 55, 72, 0.95) 50%,
        rgba(220, 53, 69, 0.15) 100%
    ) !important;
    border: 1px solid rgba(255, 255, 255, 0.1) !important;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3) !important;
}

/* Dark mode için card header */
body.dark-mode .dark-mode-header {
    background: rgba(45, 55, 72, 0.95) !important;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
}

body.dark-mode .dark-mode-header h5 {
    color: #e2e8f0 !important;
}

body.dark-mode .dark-mode-header .text-muted {
    color: #a0aec0 !important;
}

body.dark-mode .dark-mode-header .text-primary {
    color: #60a5fa !important;
}

/* Area chart için özel efektler */
.chartjs-render-monitor {
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}

/* Hover efektleri */
#detailUptimeChart:hover {
    box-shadow: inset 0 2px 8px rgba(0,0,0,0.15);
    transition: box-shadow 0.3s ease;
}

/* Area chart için ekstra stil */
.chartjs-render-monitor canvas {
    background: transparent;
}
</style>
