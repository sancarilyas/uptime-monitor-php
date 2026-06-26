<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/language.php';

requireLogin();

// Admin kontrolü
if (!isAdmin()) {
    header('Location: ' . $base_url . 'dashboard');
    exit;
}

// Sayfa başlığı
$page_title = 'Tüm Site Logları';
$page_description = 'Tüm sitelerin loglarını, tarihlerini ve up/down durumlarını tek sayfada görüntüleyin';

// Header'ı dahil et
include __DIR__ . '/../../includes/layout/header.php';
?>

<div class="container p-3">
    <div class="row mb-3">
        <div class="col-12">
            <h2 class="section-title"><i class="fas fa-list-alt"></i> Tüm Site Logları</h2>
            <p class="text-muted">Tüm sitelerin loglarını, tarihlerini ve up/down durumlarını tek sayfada görüntüleyin</p>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="statusFilter" class="form-label">Durum:</label>
                            <select class="form-select" id="statusFilter">
                                <option value="">Tüm Durumlar</option>
                                <option value="up">UP</option>
                                <option value="down">DOWN</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="timeRangeFilter" class="form-label">Zaman Aralığı:</label>
                            <select class="form-select" id="timeRangeFilter">
                                <option value="1h">Son 1 Saat</option>
                                <option value="6h">Son 6 Saat</option>
                                <option value="24h" selected>Son 24 Saat</option>
                                <option value="7d">Son 7 Gün</option>
                                <option value="30d">Son 30 Gün</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="perPageFilter" class="form-label">Sayfa Başına:</label>
                            <select class="form-select" id="perPageFilter">
                                <option value="15" selected>15</option>
                                <option value="30">30</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <button type="button" class="btn btn-primary" id="refreshLogsBtn">
                                <i class="fas fa-sync-alt"></i> Logları Yenile
                            </button>
                            <button type="button" class="btn btn-success" id="exportLogsBtn">
                                <i class="fas fa-download"></i> CSV İndir
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loglar Tablosu -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history"></i> Log Kayıtları
                    </h5>
                    <div class="d-flex align-items-center">
                        <span class="badge bg-info me-2" id="totalLogsBadge">0 kayıt</span>
                        <div class="spinner-border spinner-border-sm" id="logsSpinner" style="display: none;"></div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Site</th>
                                    <th>Tarih/Saat</th>
                                    <th>Durum</th>
                                    <th>Yanıt Süresi</th>
                                    <th>Süre</th>
                                    <th>Hata Mesajı</th>
                                </tr>
                            </thead>
                            <tbody id="logsTableBody">
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Yükleniyor...</span>
                                        </div>
                                        <div class="mt-2">Loglar yükleniyor...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <nav aria-label="Sayfa navigasyonu">
                                <ul class="pagination pagination-sm mb-0" id="pagination">
                                    <!-- Sayfalama buraya eklenecek -->
                                </ul>
                            </nav>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                Sayfa <span id="currentPage">1</span> / <span id="totalPages">1</span>
                                (<span id="totalRecords">0</span> kayıt)
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let totalPages = 1;
let totalRecords = 0;

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    loadLogs();
    
    // Filtre değişikliklerini dinle
    document.getElementById('statusFilter').addEventListener('change', loadLogs);
    document.getElementById('timeRangeFilter').addEventListener('change', loadLogs);
    document.getElementById('perPageFilter').addEventListener('change', function() {
        currentPage = 1;
        loadLogs();
    });
    
    document.getElementById('refreshLogsBtn').addEventListener('click', function() {
        currentPage = 1;
        loadLogs();
    });
    
    document.getElementById('exportLogsBtn').addEventListener('click', exportLogs);
});

// Logları yükle
function loadLogs() {
    const status = document.getElementById('statusFilter').value;
    const timeRange = document.getElementById('timeRangeFilter').value;
    const perPage = document.getElementById('perPageFilter').value;
    
    const spinner = document.getElementById('logsSpinner');
    const tableBody = document.getElementById('logsTableBody');
    
    spinner.style.display = 'inline-block';
    tableBody.innerHTML = `
        <tr style="height: 400px;">
            <td colspan="6" class="text-center py-4 align-middle">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Yükleniyor...</span>
                </div>
                <div class="mt-2">Loglar yükleniyor...</div>
            </td>
        </tr>
    `;
    
    const params = new URLSearchParams({
        page: currentPage,
        per_page: perPage,
        time_range: timeRange,
        status: status || ''
    });
    
    fetch(`<?= $base_url ?>pages/ajax/get_all_site_logs.php?${params}`)
        .then(response => response.json())
        .then(data => {
            spinner.style.display = 'none';
            
            if (data.success) {
                displayLogs(data.data);
                updatePagination(data.data);
                updateStats(data.data);
            } else {
                // Oturum süresi dolmuşsa login sayfasına yönlendir
                if (data.message && data.message.includes('Oturum süresi dolmuş')) {
                    showWarning('Uyarı', 'Oturum süreniz dolmuş. Lütfen tekrar giriş yapın.');
                    window.location.href = base_url + 'login';
                    return;
                }
                
                tableBody.innerHTML = `
                    <tr style="height: 400px;">
                        <td colspan="6" class="text-center py-4 text-danger align-middle">
                            <i class="fas fa-exclamation-triangle"></i> ${data.message}
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            spinner.style.display = 'none';
            console.error('Loglar yüklenirken hata:', error);
            tableBody.innerHTML = `
                <tr style="height: 400px;">
                    <td colspan="6" class="text-center py-4 text-danger align-middle">
                        <i class="fas fa-exclamation-triangle"></i> Bağlantı hatası oluştu.
                    </td>
                </tr>
            `;
        });
}

// Logları görüntüle
function displayLogs(data) {
    const tableBody = document.getElementById('logsTableBody');
    
    if (!data.logs || data.logs.length === 0) {
        // Boş durum için sabit yükseklik
        tableBody.innerHTML = `
            <tr style="height: 400px;">
                <td colspan="6" class="text-center py-4 text-muted align-middle">
                    <i class="fas fa-info-circle"></i> Seçilen kriterlere uygun log bulunamadı.
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    data.logs.forEach(log => {
        const statusClass = log.status === 'up' ? 'success' : 'danger';
        const statusIcon = log.status === 'up' ? 'check' : 'times';
        const statusText = log.status === 'up' ? 'UP' : 'DOWN';
        
        // Durum değişikliği kontrolü
        let durationHtml = '<small class="text-muted">-</small>';
        if (log.duration_text) {
            durationHtml = `<small>${log.duration_text}</small>`;
        }
        
        // Hata mesajı
        let errorHtml = '<small class="text-muted">-</small>';
        if (log.error) {
            errorHtml = `<small class="text-danger" title="${log.error}">
                <i class="fas fa-exclamation-circle"></i> ${log.error.length > 50 ? log.error.substring(0, 50) + '...' : log.error}
            </small>`;
        }
        
        html += `
            <tr class="table-${statusClass}" style="height: 60px;">
                <td class="align-middle">
                    <strong>${log.site_name}</strong>
                    <br><small class="text-muted">${log.site_url}</small>
                </td>
                <td class="align-middle">
                    <strong>${log.formatted_time}</strong>
                    <br><small class="text-muted">${log.relative_time}</small>
                </td>
                <td class="align-middle">
                    <span class="badge bg-${statusClass}">
                        <i class="fas fa-${statusIcon}"></i> ${statusText}
                    </span>
                    ${log.status_changed ? '<span class="badge bg-warning text-dark ms-1"><i class="fas fa-exchange-alt"></i> Değişti</span>' : ''}
                </td>
                <td class="align-middle">
                    <span class="badge bg-info">${log.response_time || '-'}ms</span>
                </td>
                <td class="align-middle">${durationHtml}</td>
                <td class="align-middle">${errorHtml}</td>
            </tr>
        `;
    });
    
    tableBody.innerHTML = html;
}

// Sayfalama güncelle
function updatePagination(data) {
    currentPage = data.page || 1;
    totalPages = data.total_pages || 1;
    totalRecords = data.total_logs || 0;
    
    // Sayfa bilgilerini güncelle
    const currentPageEl = document.getElementById('currentPage');
    const totalPagesEl = document.getElementById('totalPages');
    const totalRecordsEl = document.getElementById('totalRecords');
    
    if (currentPageEl) currentPageEl.textContent = currentPage;
    if (totalPagesEl) totalPagesEl.textContent = totalPages;
    if (totalRecordsEl) totalRecordsEl.textContent = totalRecords;
    
    const pagination = document.getElementById('pagination');
    if (!pagination) {
        return;
    }
    
    pagination.innerHTML = '';
    
    // Eğer sadece 1 sayfa varsa sayfalama gösterme
    if (totalPages <= 1) {
        pagination.innerHTML = '<li class="page-item disabled"><span class="page-link">Sayfalama yok</span></li>';
        return;
    }
    
    // Önceki sayfa
    const prevLi = document.createElement('li');
    prevLi.className = `page-item ${currentPage <= 1 ? 'disabled' : ''}`;
    if (currentPage <= 1) {
        prevLi.innerHTML = '<span class="page-link">Önceki</span>';
    } else {
        const prevLink = document.createElement('a');
        prevLink.className = 'page-link';
        prevLink.href = 'javascript:void(0)';
        prevLink.textContent = 'Önceki';
        prevLink.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            changePage(currentPage - 1);
            return false;
        });
        prevLi.appendChild(prevLink);
    }
    pagination.appendChild(prevLi);
    
    // Sayfa numaraları
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    // İlk sayfa (eğer başta değilse)
    if (startPage > 1) {
        const firstLi = document.createElement('li');
        firstLi.className = 'page-item';
        const firstLink = document.createElement('a');
        firstLink.className = 'page-link';
        firstLink.href = 'javascript:void(0)';
        firstLink.textContent = '1';
        firstLink.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            changePage(1);
            return false;
        });
        firstLi.appendChild(firstLink);
        pagination.appendChild(firstLi);
        
        if (startPage > 2) {
            const dotsLi = document.createElement('li');
            dotsLi.className = 'page-item disabled';
            dotsLi.innerHTML = '<span class="page-link">...</span>';
            pagination.appendChild(dotsLi);
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const li = document.createElement('li');
        li.className = `page-item ${i === currentPage ? 'active' : ''}`;
        
        if (i === currentPage) {
            li.innerHTML = `<span class="page-link">${i}</span>`;
        } else {
            const link = document.createElement('a');
            link.className = 'page-link';
            link.href = 'javascript:void(0)';
            link.textContent = i;
            link.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                changePage(i);
                return false;
            });
            li.appendChild(link);
        }
        pagination.appendChild(li);
    }
    
    // Son sayfa (eğer sonda değilse)
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const dotsLi = document.createElement('li');
            dotsLi.className = 'page-item disabled';
            dotsLi.innerHTML = '<span class="page-link">...</span>';
            pagination.appendChild(dotsLi);
        }
        
        const lastLi = document.createElement('li');
        lastLi.className = 'page-item';
        const lastLink = document.createElement('a');
        lastLink.className = 'page-link';
        lastLink.href = 'javascript:void(0)';
        lastLink.textContent = totalPages;
        lastLink.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            changePage(totalPages);
            return false;
        });
        lastLi.appendChild(lastLink);
        pagination.appendChild(lastLi);
    }
    
    // Sonraki sayfa
    const nextLi = document.createElement('li');
    nextLi.className = `page-item ${currentPage >= totalPages ? 'disabled' : ''}`;
    if (currentPage >= totalPages) {
        nextLi.innerHTML = '<span class="page-link">Sonraki</span>';
    } else {
        const nextLink = document.createElement('a');
        nextLink.className = 'page-link';
        nextLink.href = 'javascript:void(0)';
        nextLink.textContent = 'Sonraki';
        nextLink.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            changePage(currentPage + 1);
            return false;
        });
        nextLi.appendChild(nextLink);
    }
    pagination.appendChild(nextLi);
}

// Sayfa değiştir
function changePage(page) {
    if (page >= 1 && page <= totalPages && page !== currentPage) {
        currentPage = page;
        loadLogs();
    }
}

// İstatistikleri güncelle
function updateStats(data) {
    document.getElementById('totalLogsBadge').textContent = `${data.total_logs || 0} kayıt`;
}

// Logları dışa aktar
function exportLogs() {
    const status = document.getElementById('statusFilter').value;
    const timeRange = document.getElementById('timeRangeFilter').value;
    
    const params = new URLSearchParams({
        export: 'csv',
        time_range: timeRange,
        status: status || ''
    });
    
    window.open(`<?= $base_url ?>pages/ajax/get_all_site_logs.php?${params}`, '_blank');
}
</script>

<style>
.table-responsive {
    /* Yeni CSS'de tanımlandı */
}

.table th {
    position: sticky;
    top: 0;
    background-color: #212529;
    z-index: 10;
}

.table tbody tr {
    transition: none !important;
}

.table-success {
    background-color: rgba(25, 135, 84, 0.1);
}

.table-danger {
    background-color: rgba(220, 53, 69, 0.1);
}

.badge {
    font-size: 0.75em;
}

.card-footer {
    background-color: #f8f9fa;
    border-top: 1px solid #dee2e6;
}

/* Tablo sabitliği için */
#logsTableBody {
    min-height: 400px;
}

#logsTableBody tr {
    vertical-align: middle;
}

/* Loading animasyonu için */
.spinner-border {
    width: 2rem;
    height: 2rem;
}

/* Scrollbar gizleme */
.table-responsive::-webkit-scrollbar {
    display: none;
}

.table-responsive {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
    height: 400px;
    overflow-y: auto;
    overflow-x: hidden;
}

/* Sayfalama tıklamasında sayfa kaymasını engelle */
.pagination .page-link {
    position: relative;
    z-index: 1;
}

.pagination .page-link:focus {
    box-shadow: none;
}

/* Sayfa kaymasını engelle */
html {
    scroll-behavior: smooth;
}

body {
    scroll-padding-top: 0;
}

/* Sayfalama container'ı için */
.pagination {
    margin-bottom: 0;
}

.pagination .page-link {
    border: 1px solid #dee2e6;
    color: #0d6efd;
}

.pagination .page-item.active .page-link {
    background-color: #0d6efd;
    border-color: #0d6efd;
}

/* Sayfa kaymasını tamamen engelle */
.pagination .page-link:hover {
    z-index: 2;
    color: #0a58ca;
    background-color: #e9ecef;
    border-color: #dee2e6;
}

/* Tablo container'ına sabit yükseklik - yukarıda tanımlandı */
</style>

<?php
// Footer'ı dahil et
include __DIR__ . '/../../includes/layout/footer.php';
?>
