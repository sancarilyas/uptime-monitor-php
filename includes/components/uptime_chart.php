<?php
/**
 * Uptime Chart Component
 * Her sayfanın altında gösterilecek uptime grafiği
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Site ID'sini al (eğer belirtilmişse)
$site_id = $_GET['site_id'] ?? null;
$chart_title = 'Sistem Uptime Grafiği';

if ($site_id) {
    // Belirli bir site için grafik
    $stmt = $pdo->prepare("SELECT name FROM sites WHERE id = ?");
    $stmt->execute([$site_id]);
    $site = $stmt->fetch();
    if ($site) {
        $chart_title = $site['name'] . ' - Uptime Grafiği';
    }
}

// Son 24 saatlik verileri al
$hours_back = 24;
$chart_data = [];
$chart_labels = [];
$uptime_data = [];
$response_times = [];

// Her saat için veri topla
for ($i = $hours_back - 1; $i >= 0; $i--) {
    $hour_start = date('Y-m-d H:i:00', strtotime("-$i hours"));
    $hour_end = date('Y-m-d H:i:59', strtotime("-$i hours"));
    
    $chart_labels[] = date('H:i', strtotime("-$i hours"));
    
    if ($site_id) {
        // Belirli site için veri - uptime_logs tablosundan al
        $stmt = $pdo->prepare("
            SELECT 
                AVG(CASE WHEN status = 'up' THEN 100 ELSE 0 END) as uptime,
                AVG(response_time) as avg_response_time,
                COUNT(*) as check_count
            FROM uptime_logs 
            WHERE site_id = ? AND timestamp BETWEEN ? AND ?
        ");
        $stmt->execute([$site_id, $hour_start, $hour_end]);
    } else {
        // Tüm siteler için ortalama
        $stmt = $pdo->prepare("
            SELECT 
                AVG(CASE WHEN status = 'up' THEN 100 ELSE 0 END) as uptime,
                AVG(response_time) as avg_response_time,
                COUNT(*) as check_count
            FROM uptime_logs 
            WHERE timestamp BETWEEN ? AND ?
        ");
        $stmt->execute([$hour_start, $hour_end]);
    }
    
    $data = $stmt->fetch();
    
    // Eğer veri yoksa, mevcut durumu kullan
    if ($data['check_count'] == 0 && $site_id) {
        $stmt_site = $pdo->prepare("SELECT last_status, last_response_time FROM sites WHERE id = ?");
        $stmt_site->execute([$site_id]);
        $site_info = $stmt_site->fetch();
        $uptime = $site_info['last_status'] === 'up' ? 100 : 0;
        $response_time = $site_info['last_response_time'] ?: 0;
        $check_count = 0;
    } else {
        $uptime = $data['uptime'] ? round($data['uptime'], 1) : 0;
        $response_time = $data['avg_response_time'] ? round($data['avg_response_time'], 0) : 0;
        $check_count = $data['check_count'] ?: 0;
    }
    
    $uptime_data[] = $uptime;
    $response_times[] = $response_time;
    
    $chart_data[] = [
        'uptime' => $uptime,
        'response_time' => $response_time,
        'check_count' => $check_count,
        'hour' => $hour_start
    ];
}

// Genel istatistikler
$total_uptime = array_sum($uptime_data) / count($uptime_data);
$avg_response_time = array_sum($response_times) / count($response_times);
$total_checks = array_sum(array_column($chart_data, 'check_count'));

// Renk hesaplama (yeşil: 100%, kırmızı: 0%)
function getUptimeColor($uptime) {
    if ($uptime >= 99) return '#28a745'; // Yeşil
    if ($uptime >= 95) return '#ffc107'; // Sarı
    if ($uptime >= 90) return '#fd7e14'; // Turuncu
    return '#dc3545'; // Kırmızı
}

$chart_colors = array_map('getUptimeColor', $uptime_data);
?>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line text-primary"></i> <?= htmlspecialchars($chart_title) ?>
                    </h5>
                    <div class="d-flex gap-3">
                        <small class="text-muted">
                            <i class="fas fa-clock"></i> Son 24 Saat
                        </small>
                        <small class="text-muted">
                            <i class="fas fa-percentage"></i> Ortalama: <?= number_format($total_uptime, 1) ?>%
                        </small>
                        <small class="text-muted">
                            <i class="fas fa-tachometer-alt"></i> Ort. Süre: <?= round($avg_response_time) ?>ms
                        </small>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-8 col-md-12">
                        <canvas id="uptimeChart" height="100"></canvas>
                    </div>
                    <div class="col-lg-4 col-md-12 mt-3 mt-lg-0">
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body text-center p-3">
                                        <h6 class="text-primary mb-1"><?= number_format($total_uptime, 1) ?>%</h6>
                                        <small class="text-muted">Ortalama Uptime</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body text-center p-3">
                                        <h6 class="text-info mb-1"><?= round($avg_response_time) ?>ms</h6>
                                        <small class="text-muted">Ortalama Yanıt Süresi</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body text-center p-3">
                                        <h6 class="text-success mb-1"><?= $total_checks ?></h6>
                                        <small class="text-muted">Toplam Kontrol</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Durum Göstergeleri -->
                        <div class="mt-3">
                            <h6 class="mb-2">Durum Göstergeleri:</h6>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge" style="background-color: #28a745">
                                    <i class="fas fa-circle"></i> Mükemmel (99%+)
                                </span>
                                <span class="badge" style="background-color: #ffc107; color: #000">
                                    <i class="fas fa-circle"></i> İyi (95-99%)
                                </span>
                                <span class="badge" style="background-color: #fd7e14">
                                    <i class="fas fa-circle"></i> Orta (90-95%)
                                </span>
                                <span class="badge" style="background-color: #dc3545">
                                    <i class="fas fa-circle"></i> Kötü (<90%)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('uptimeChart').getContext('2d');
    
    const uptimeData = <?= json_encode($uptime_data) ?>;
    const chartColors = <?= json_encode($chart_colors) ?>;
    
    const chartData = {
        labels: <?= json_encode($chart_labels) ?>,
        datasets: [{
            label: 'Uptime (%)',
            data: uptimeData,
            backgroundColor: chartColors,
            borderColor: chartColors,
            borderWidth: 1,
            borderRadius: 4,
            barPercentage: 0.9,
            categoryPercentage: 0.95
        }]
    };
    
    const config = {
        type: 'bar',
        data: chartData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        title: function(context) {
                            return 'Saat: ' + context[0].label;
                        },
                        label: function(context) {
                            const dataIndex = context.dataIndex;
                            const data = <?= json_encode($chart_data) ?>;
                            const point = data[dataIndex];
                            
                            return [
                                'Uptime: ' + point.uptime + '%',
                                'Yanıt Süresi: ' + point.response_time + 'ms',
                                'Kontrol Sayısı: ' + point.check_count
                            ];
                        }
                    },
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: '#333',
                    borderWidth: 1
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    ticks: {
                        callback: function(value) {
                            return value + '%';
                        },
                        color: document.body.classList.contains('dark-mode') ? '#e2e8f0' : '#666'
                    },
                    grid: {
                        color: document.body.classList.contains('dark-mode') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    ticks: {
                        color: document.body.classList.contains('dark-mode') ? '#e2e8f0' : '#666',
                        maxTicksLimit: 12
                    },
                    grid: {
                        color: document.body.classList.contains('dark-mode') ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                    }
                }
            },
            interaction: {
                intersect: true,
                mode: 'index'
            }
        }
    };
    
    new Chart(ctx, config);
});
</script>
