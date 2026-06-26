 
    function editSite(site) {
        document.getElementById('edit_site_id').value = site.id;
        document.getElementById('edit_site_name').value = site.name;
        document.getElementById('edit_site_url').value = site.url;
        document.getElementById('edit_monitor_path').value = site.monitor_path || '';
        document.getElementById('edit_description').value = site.description || '';
        document.getElementById('edit_notification_emails').value = site.notification_emails || '';
        
        // Bildirim ayarları
        document.getElementById('edit_notifications_enabled').checked = site.notifications_enabled == 1;
        document.getElementById('edit_notify_on_down').checked = site.notify_on_down == 1;
        document.getElementById('edit_notify_on_up').checked = site.notify_on_up == 1;
        document.getElementById('edit_notification_priority').value = site.notification_priority || 'medium';
        document.getElementById('edit_email_notifications').checked = site.email_notifications == 1;
        document.getElementById('edit_telegram_notifications').checked = site.telegram_notifications == 1;
        document.getElementById('edit_sms_notifications').checked = site.sms_notifications == 1;
        document.getElementById('edit_webhook_notifications').checked = site.webhook_notifications == 1;
        
        new bootstrap.Modal(document.getElementById('editSiteModal')).show();
    }

    function deleteSite(siteId) {
        document.getElementById('delete_site_id').value = siteId;
        new bootstrap.Modal(document.getElementById('deleteSiteModal')).show();
    }

    // Görünüm değiştirme işlevselliği
    document.addEventListener('DOMContentLoaded', function() {
        const cardViewRadio = document.getElementById('cardView');
        const tableViewRadio = document.getElementById('tableView');
        const cardViewContainer = document.getElementById('cardViewContainer');
        const tableViewContainer = document.getElementById('tableViewContainer');
        
        // Elementlerin varlığını kontrol et
        if (!cardViewRadio || !tableViewRadio || !cardViewContainer || !tableViewContainer) {
            console.warn('Görünüm değiştirme elementleri bulunamadı');
            return;
        }
        
        // LocalStorage'dan görünüm tercihini al
        const savedView = localStorage.getItem('sitesViewMode') || 'card';
        
        if (savedView === 'table') {
            tableViewRadio.checked = true;
            switchToTableView();
        } else {
            cardViewRadio.checked = true;
            switchToCardView();
        }
        
        function switchToCardView() {
            if (cardViewContainer && tableViewContainer) {
                cardViewContainer.classList.remove('d-none');
                tableViewContainer.classList.add('d-none');
                localStorage.setItem('sitesViewMode', 'card');
                console.log('Kart görünümüne geçildi');
            }
        }
        
        function switchToTableView() {
            if (cardViewContainer && tableViewContainer) {
                cardViewContainer.classList.add('d-none');
                tableViewContainer.classList.remove('d-none');
                localStorage.setItem('sitesViewMode', 'table');
                console.log('Tablo görünümüne geçildi');
            }
        }
        
        cardViewRadio.addEventListener('change', function() {
            if (this.checked) {
                switchToCardView();
            }
        });
        
        tableViewRadio.addEventListener('change', function() {
            if (this.checked) {
                switchToTableView();
            }
        });
    });
    document.addEventListener('DOMContentLoaded', function () {
        const editForm = document.getElementById('editSiteForm');
        if (editForm) {
            editForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const button = this.querySelector('button[type="submit"]');
                const originalText = button.innerHTML;
                
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                
                fetch(base_url+'pages/ajax/update_site.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showSuccess('Başarılı!', data.message);
                        bootstrap.Modal.getInstance(document.getElementById('editSiteModal')).hide();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showError('Hata!', data.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    showError('Hata!', 'Database error');
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            });
        }
    
        const deleteForm = document.getElementById('deleteSiteForm');
        if (deleteForm) {
            deleteForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const button = this.querySelector('button[type="submit"]');
                const originalText = button.innerHTML;
                
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                
                fetch(base_url+'pages/ajax/delete_site.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        site_id: formData.get('site_id')
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showSuccess('Site Silindi!', data.message);
                        bootstrap.Modal.getInstance(document.getElementById('deleteSiteModal')).hide();
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        showError('Hata!', data.message);
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    showError('Hata!', 'Database error');
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            });
        }
    });

    // ================== ARAMA FONKSİYONLARI ==================
    
    let searchTimeout = null;
    
    // Site arama fonksiyonu
    function searchSites(searchTerm) {
        try {
            searchTerm = searchTerm.toLowerCase().trim();
            
            const cardViewContainer = document.getElementById('cardViewContainer');
            const tableViewContainer = document.getElementById('tableViewContainer');
            const searchResultInfo = document.getElementById('searchResultInfo');
            const searchResultText = document.getElementById('searchResultText');
            const clearSearchBtn = document.getElementById('clearSearch');
            
            if (!searchTerm) {
                // Arama boşsa tüm siteleri göster
                showAllSites();
                if (searchResultInfo) searchResultInfo.style.display = 'none';
                if (clearSearchBtn) clearSearchBtn.style.display = 'none';
                return;
            }
            
            if (clearSearchBtn) clearSearchBtn.style.display = 'inline-block';
            
            let visibleCount = 0;
            let totalCount = 0;
        
        // Kart görünümünde ara
        if (cardViewContainer) {
            const cards = cardViewContainer.querySelectorAll('.col-lg-4');
            totalCount = cards.length;
            
            cards.forEach(card => {
                const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
                const url = card.querySelector('.card-text')?.textContent.toLowerCase() || '';
                
                if (title.includes(searchTerm) || url.includes(searchTerm)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Tablo görünümünde ara
        if (tableViewContainer) {
            const rows = tableViewContainer.querySelectorAll('tbody tr');
            totalCount = rows.length;
            visibleCount = 0;
            
            rows.forEach(row => {
                const name = row.querySelector('td:first-child strong')?.textContent.toLowerCase() || '';
                const url = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
                
                if (name.includes(searchTerm) || url.includes(searchTerm)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Sonuç bilgisini göster
        if (visibleCount === 0) {
            searchResultText.innerHTML = `<strong>"${searchTerm}"</strong> için sonuç bulunamadı (${totalCount} siteden 0 eşleşme)`;
            searchResultInfo.className = 'alert alert-warning mb-3';
        } else if (visibleCount === totalCount) {
            searchResultText.innerHTML = `Tüm siteler gösteriliyor (${totalCount} site)`;
            searchResultInfo.className = 'alert alert-info mb-3';
        } else {
            searchResultText.innerHTML = `<strong>"${searchTerm}"</strong> için ${visibleCount} site bulundu (${totalCount} siteden)`;
            searchResultInfo.className = 'alert alert-success mb-3';
        }
        if (searchResultInfo) searchResultInfo.style.display = 'block';
        } catch (error) {
            console.error('Arama hatası:', error);
        }
    }
    
    // Tüm siteleri göster
    function showAllSites() {
        try {
            const cardViewContainer = document.getElementById('cardViewContainer');
            const tableViewContainer = document.getElementById('tableViewContainer');
            
            if (cardViewContainer) {
                cardViewContainer.querySelectorAll('.col-lg-4').forEach(card => {
                    card.style.display = 'block';
                });
            }
            
            if (tableViewContainer) {
                tableViewContainer.querySelectorAll('tbody tr').forEach(row => {
                    row.style.display = '';
                });
            }
        } catch (error) {
            console.error('showAllSites hatası:', error);
        }
    }
    
    // Aramayı temizle
    window.clearSiteSearch = function() {
        try {
            const searchInput = document.getElementById('siteSearchInput');
            if (searchInput) {
                searchInput.value = '';
                searchSites('');
            }
        } catch (error) {
            console.error('clearSiteSearch hatası:', error);
        }
    }
    
    // Arama input'una event listener ekle
    const searchInput = document.getElementById('siteSearchInput');
    const clearSearchBtn = document.getElementById('clearSearch');
    
    if (searchInput) {
        // Input değiştiğinde
        searchInput.addEventListener('input', function() {
            try {
                if (searchTimeout) {
                    clearTimeout(searchTimeout);
                }
                searchTimeout = setTimeout(() => {
                    searchSites(this.value);
                }, 300); // 300ms bekle
            } catch (error) {
                console.error('Arama input hatası:', error);
            }
        });
        
        // Enter tuşuna basıldığında
        searchInput.addEventListener('keypress', function(e) {
            try {
                if (e.key === 'Enter') {
                    clearTimeout(searchTimeout);
                    searchSites(this.value);
                }
            } catch (error) {
                console.error('Enter arama hatası:', error);
            }
        });
    }
    
    // Temizle butonuna tıklandığında
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            clearSiteSearch();
        });
    }

    // ================== GERÇEK ZAMANLI GÜNCELLEME ==================
    
    let updateInterval = null;
    let isUpdatingSites = false;
    let previousSiteStatuses = new Map();
    
    // Önceki durumları yükle
    function loadPreviousSitesStatuses() {
        try {
            const stored = localStorage.getItem('sites_previous_statuses');
            if (stored) {
                return new Map(JSON.parse(stored));
            }
        } catch (e) {
            console.error('loadPreviousSitesStatuses hatası:', e);
        }
        return new Map();
    }
    
    // Durumları kaydet
    function saveSitesStatuses(statusMap) {
        try {
            localStorage.setItem('sites_previous_statuses', JSON.stringify([...statusMap]));
        } catch (e) {
            console.error('saveSitesStatuses hatası:', e);
        }
    }
    
    previousSiteStatuses = loadPreviousSitesStatuses();
    
    // Sites sayfasını güncelle
    function updateSitesPage() {
        try {
            if (isUpdatingSites) return;
            isUpdatingSites = true;
            
            fetch(base_url+'pages/ajax/get_sites_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // DURUM DEĞİŞİKLİĞİ KONTROLÜ
                        let statusChanged = false;
                        let changedSites = [];
                        
                        data.data.sites.forEach(site => {
                            const siteId = site.id;
                            const currentStatus = site.last_status;
                            const previousStatus = previousSiteStatuses.get(siteId);
                            
                            // Önceki durum varsa ve değiştiyse
                            if (previousStatus !== undefined && previousStatus !== currentStatus) {
                                statusChanged = true;
                                changedSites.push({
                                    name: site.name,
                                    oldStatus: previousStatus,
                                    newStatus: currentStatus
                                });
                            }
                            
                            // Durumu kaydet
                            previousSiteStatuses.set(siteId, currentStatus);
                        });
                        
                        // Durumları localStorage'a kaydet
                        saveSitesStatuses(previousSiteStatuses);
                        
                        if (statusChanged) {
                            // Durum değişikliği bildirimleri
                            changedSites.forEach(site => {
                                if (site.newStatus === 'down') {
                                    showError('Site Kesintisi!', `${site.name} sitesi çalışmıyor`);
                                } else if (site.newStatus === 'up' && site.oldStatus === 'down') {
                                    showSuccess('Site Düzeldi!', `${site.name} sitesi tekrar çalışıyor`);
                                }
                            });
                            
                            // 2 saniye bekle, sonra yenile
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                            
                            return;
                        }
                        
                        // Durum değişmediyse kartları güncelle
                        updateSiteCards(data.data.sites);
                    }
                })
                .catch(error => {
                    console.error('Sites güncelleme hatası:', error);
                })
                .finally(() => {
                    isUpdatingSites = false;
                });
        } catch (error) {
            console.error('updateSitesPage hatası:', error);
            isUpdatingSites = false;
        }
    }
    
    // Site kartlarını güncelle
    function updateSiteCards(sites) {
        sites.forEach(site => {
            updateSingleCard(site);
            updateSingleTableRow(site);
        });
    }
    
    // Tek bir kartı güncelle
    function updateSingleCard(site) {
        const cardViewContainer = document.getElementById('cardViewContainer');
        if (!cardViewContainer) return;
        
        const cards = cardViewContainer.querySelectorAll('.site-card');
        cards.forEach(card => {
            if (card.getAttribute('data-site-id') == site.id) {
                // Durum sınıfını güncelle
                const oldClass = card.classList.contains('up') ? 'up' : 'down';
                const newClass = site.status_class;
                
                if (oldClass !== newClass) {
                    card.classList.remove(oldClass);
                    card.classList.add(newClass);
                }
                
                // Durum göstergesini güncelle
                const statusIndicator = card.querySelector('.status-indicator');
                if (statusIndicator) {
                    statusIndicator.className = 'status-indicator status-' + newClass;
                }
                
                // Durum badge'ini güncelle
                const statusBadge = card.querySelector('.badge');
                if (statusBadge) {
                    statusBadge.className = 'badge bg-' + site.status_color;
                    statusBadge.textContent = site.status_text;
                }
                
                // Uptime yüzdesini güncelle
                const uptimeElement = card.querySelector('.fw-bold');
                if (uptimeElement) {
                    uptimeElement.className = 'fw-bold text-' + site.status_color;
                    uptimeElement.textContent = site.uptime_24h.toFixed(2) + '%';
                }
                
                // Son kontrol zamanını güncelle
                const lastCheckElement = card.querySelector('.mt-2.text-muted.small');
                if (lastCheckElement && site.last_check) {
                    const checkDate = new Date(site.last_check);
                    const formatted = checkDate.toLocaleDateString('tr-TR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    lastCheckElement.innerHTML = `<i class="fas fa-clock"></i> Son kontrol: ${formatted}`;
                }
            }
        });
    }
    
    // Tek bir tablo satırını güncelle
    function updateSingleTableRow(site) {
        const tableViewContainer = document.getElementById('tableViewContainer');
        if (!tableViewContainer) return;
        
        const rows = tableViewContainer.querySelectorAll('tbody tr');
        rows.forEach(row => {
            if (row.getAttribute('data-site-id') == site.id) {
                // Durum sınıfını güncelle
                const oldClass = row.className.includes('up') ? 'up' : 'down';
                const newClass = site.status_class;
                
                if (oldClass !== newClass) {
                    row.className = newClass + '-row';
                    row.setAttribute('data-site-id', site.id);
                    row.style.cursor = 'pointer';
                    row.onclick = function() { window.location.href = base_url + 'sites/detail?id=' + site.id; };
                }
                
                // Durum göstergesini güncelle
                const statusIndicator = row.querySelector('.status-indicator');
                if (statusIndicator) {
                    statusIndicator.className = 'status-indicator status-' + newClass + ' me-2';
                }
                
                // Durum badge'ini güncelle
                const badge = row.querySelector('.badge');
                if (badge) {
                    badge.className = 'badge bg-' + site.status_color;
                    badge.textContent = site.status_text;
                }
                
                // Uptime progress bar güncelle
                const progressFill = row.querySelector('.mini-progress-fill');
                if (progressFill) {
                    progressFill.style.width = site.uptime_24h + '%';
                    progressFill.style.background = site.status_color === 'success' ? '#28a745' : '#dc3545';
                }
                
                // Uptime text güncelle
                const uptimeText = row.querySelector('.fw-bold');
                if (uptimeText) {
                    uptimeText.className = 'fw-bold text-' + site.status_color + ' me-2';
                    uptimeText.textContent = site.uptime_24h.toFixed(2) + '%';
                }
                
                // Son kontrol zamanını güncelle
                const lastCheckCell = row.querySelector('td:nth-child(5) small');
                if (lastCheckCell && site.last_check) {
                    const checkDate = new Date(site.last_check);
                    const formatted = checkDate.toLocaleDateString('tr-TR', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    lastCheckCell.textContent = formatted;
                }
            }
        });
    }
    
    // Otomatik güncellemeyi başlat
    function startAutoUpdate() {
        if (updateInterval) return;
        
        // İlk güncelleme 10 saniye sonra
        setTimeout(updateSitesPage, 10000);
        
        // Her 30 saniyede bir güncelle - Performans için optimize edildi
        updateInterval = setInterval(updateSitesPage, 30000);
        
        console.log('Gerçek zamanlı sites güncellemesi başlatıldı (30 saniye aralıklarla)');
    }
    
    // Otomatik güncellemeyi durdur
    function stopAutoUpdate() {
        if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
            console.log('Gerçek zamanlı sites güncellemesi durduruldu');
        }
    }
    
    // Sayfa görünür/gizli olduğunda güncellemeyi kontrol et
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopAutoUpdate();
        } else {
            updateSitesPage();
            startAutoUpdate();
        }
    });
    
    // Sayfa yüklendiğinde otomatik güncellemeyi başlat
    startAutoUpdate();
    
    // Dark mode kontrolü ve kartları güncelleme fonksiyonu - Dashboard ile aynı
    function applyDarkModeToCards() {
        try {
            const isDarkMode = document.body.classList.contains('dark-mode');
            const siteCards = document.querySelectorAll('.site-card, .card.site-card, .card');
            
            siteCards.forEach(card => {
                if (isDarkMode) {
                    // Dark mode stillerini uygula
                    card.style.backgroundColor = 'rgba(45, 55, 72, 0.95)';
                    card.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                    card.style.color = '#e2e8f0';
                    card.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.3)';
                    
                    // Up/Down durumuna göre özel stiller
                    if (card.classList.contains('up')) {
                        card.style.background = 'linear-gradient(135deg, rgba(40, 167, 69, 0.2), rgba(45, 55, 72, 0.95))';
                        card.style.borderLeft = '4px solid #28a745';
                    } else if (card.classList.contains('down')) {
                        card.style.background = 'linear-gradient(135deg, rgba(220, 53, 69, 0.2), rgba(45, 55, 72, 0.95))';
                        card.style.borderLeft = '4px solid #dc3545';
                        card.style.boxShadow = '0 4px 12px rgba(220, 53, 69, 0.3)';
                    }
                    
                    // Card body'yi güncelle
                    const cardBody = card.querySelector('.card-body');
                    if (cardBody) {
                        cardBody.style.backgroundColor = 'transparent';
                        cardBody.style.color = '#e2e8f0';
                    }
                    
                    // Text-muted elementleri güncelle
                    const textMutedElements = card.querySelectorAll('.text-muted, small');
                    textMutedElements.forEach(el => {
                        el.style.color = '#a0aec0';
                    });
                    
                    // Card title'ları güncelle
                    const cardTitles = card.querySelectorAll('.card-title');
                    cardTitles.forEach(title => {
                        title.style.color = '#e2e8f0';
                        title.style.fontWeight = '600';
                    });
                    
                    // Down kartları için özel başlık rengi
                    if (card.classList.contains('down')) {
                        cardTitles.forEach(title => {
                            title.style.color = '#ef4444';
                        });
                    }
                    
                    // Uptime yüzdeleri için özel renkler
                    const fwBoldElements = card.querySelectorAll('.fw-bold');
                    fwBoldElements.forEach(el => {
                        if (el.classList.contains('text-success')) {
                            el.style.color = '#10b981';
                        } else if (el.classList.contains('text-danger')) {
                            el.style.color = '#ef4444';
                        } else {
                            el.style.color = '#e2e8f0';
                        }
                    });
                    
                    // Renkli metinler için özel kurallar
                    const successElements = card.querySelectorAll('.text-success');
                    successElements.forEach(el => {
                        el.style.color = '#10b981';
                    });
                    
                    const dangerElements = card.querySelectorAll('.text-danger');
                    dangerElements.forEach(el => {
                        el.style.color = '#ef4444';
                    });
                    
                    const infoElements = card.querySelectorAll('.text-info');
                    infoElements.forEach(el => {
                        el.style.color = '#3b82f6';
                    });
                    
                    const warningElements = card.querySelectorAll('.text-warning');
                    warningElements.forEach(el => {
                        el.style.color = '#f59e0b';
                    });
                    
                } else {
                    // Light mode'a geri dön
                    card.style.backgroundColor = '';
                    card.style.borderColor = '';
                    card.style.color = '';
                    card.style.boxShadow = '';
                    card.style.background = '';
                    card.style.borderLeft = '';
                    
                    const cardBody = card.querySelector('.card-body');
                    if (cardBody) {
                        cardBody.style.backgroundColor = '';
                        cardBody.style.color = '';
                    }
                    
                    const textMutedElements = card.querySelectorAll('.text-muted, small');
                    textMutedElements.forEach(el => {
                        el.style.color = '';
                    });
                    
                    const cardTitles = card.querySelectorAll('.card-title');
                    cardTitles.forEach(title => {
                        title.style.color = '';
                        title.style.fontWeight = '';
                    });
                    
                    const fwBoldElements = card.querySelectorAll('.fw-bold');
                    fwBoldElements.forEach(el => {
                        el.style.color = '';
                    });
                    
                    const coloredElements = card.querySelectorAll('.text-success, .text-danger, .text-info, .text-warning');
                    coloredElements.forEach(el => {
                        el.style.color = '';
                    });
                }
            });
        } catch (error) {
            console.error('Dark mode kart güncelleme hatası:', error);
        }
    }
    
    // Dark mode değişikliklerini dinle
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                setTimeout(applyDarkModeToCards, 100);
                setTimeout(applyDarkModeToSitesPage, 100);
                setTimeout(applyDarkModeToEditPage, 100);
            }
        });
    });
    
    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });
    
    // Sayfa yüklendiğinde dark mode kontrolü yap
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(applyDarkModeToCards, 500);
        setTimeout(applyDarkModeToSitesPage, 500);
        setTimeout(applyDarkModeToEditPage, 500);
    });
    
    // Sites sayfası için özel dark mode kontrolü
    function applyDarkModeToSitesPage() {
        try {
            const isDarkMode = document.body.classList.contains('dark-mode');
            
            // Container'ı güncelle
            const container = document.querySelector('.container');
            if (container) {
                if (isDarkMode) {
                    container.style.backgroundColor = 'rgba(45, 55, 72, 0.95)';
                    container.style.color = '#e2e8f0';
                } else {
                    container.style.backgroundColor = '';
                    container.style.color = '';
                }
            }
            
            // Başlık ve açıklama metinlerini güncelle
            const sectionTitle = document.querySelector('.section-title');
            if (sectionTitle) {
                if (isDarkMode) {
                    sectionTitle.style.color = '#e2e8f0';
                    const icon = sectionTitle.querySelector('i');
                    if (icon) {
                        icon.style.color = '#60a5fa';
                    }
                } else {
                    sectionTitle.style.color = '';
                    const icon = sectionTitle.querySelector('i');
                    if (icon) {
                        icon.style.color = '';
                    }
                }
            }
            
            // Text-muted elementleri güncelle
            const textMutedElements = document.querySelectorAll('.text-muted');
            textMutedElements.forEach(el => {
                if (isDarkMode) {
                    el.style.color = '#a0aec0';
                } else {
                    el.style.color = '';
                }
            });
            
            // Butonları güncelle
            const primaryButtons = document.querySelectorAll('.btn-primary');
            primaryButtons.forEach(btn => {
                if (isDarkMode) {
                    btn.style.backgroundColor = 'rgba(59, 130, 246, 0.8)';
                    btn.style.borderColor = 'rgba(59, 130, 246, 0.8)';
                    btn.style.color = '#ffffff';
                } else {
                    btn.style.backgroundColor = '';
                    btn.style.borderColor = '';
                    btn.style.color = '';
                }
            });
            
            const outlineButtons = document.querySelectorAll('.btn-outline-secondary');
            outlineButtons.forEach(btn => {
                if (isDarkMode) {
                    btn.style.color = '#e2e8f0';
                    btn.style.borderColor = 'rgba(255, 255, 255, 0.3)';
                } else {
                    btn.style.color = '';
                    btn.style.borderColor = '';
                }
            });
            
            // Form elemanlarını güncelle
            const formControlsSites = document.querySelectorAll('.form-control');
            formControlsSites.forEach(input => {
                if (isDarkMode) {
                    input.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
                    input.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                    input.style.color = '#e2e8f0';
                } else {
                    input.style.backgroundColor = '';
                    input.style.borderColor = '';
                    input.style.color = '';
                }
            });
            
            // Input group text'leri güncelle
            const inputGroupTexts = document.querySelectorAll('.input-group-text');
            inputGroupTexts.forEach(text => {
                if (isDarkMode) {
                    text.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
                    text.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                    text.style.color = '#e2e8f0';
                } else {
                    text.style.backgroundColor = '';
                    text.style.borderColor = '';
                    text.style.color = '';
                }
            });
            
            // Alert kutularını güncelle
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                if (isDarkMode) {
                    alert.style.color = '#e2e8f0';
                    if (alert.classList.contains('alert-info')) {
                        alert.style.backgroundColor = 'rgba(59, 130, 246, 0.1)';
                        alert.style.borderColor = 'rgba(59, 130, 246, 0.2)';
                    } else if (alert.classList.contains('alert-success')) {
                        alert.style.backgroundColor = 'rgba(16, 185, 129, 0.1)';
                        alert.style.borderColor = 'rgba(16, 185, 129, 0.2)';
                    } else if (alert.classList.contains('alert-danger')) {
                        alert.style.backgroundColor = 'rgba(239, 68, 68, 0.1)';
                        alert.style.borderColor = 'rgba(239, 68, 68, 0.2)';
                    }
                } else {
                    alert.style.color = '';
                    alert.style.backgroundColor = '';
                    alert.style.borderColor = '';
                }
            });
            
        } catch (error) {
            console.error('Sites sayfası dark mode güncelleme hatası:', error);
        }
    }
    
    // Edit sayfası için özel dark mode kontrolü
    function applyDarkModeToEditPage() {
        try {
            const isDarkMode = document.body.classList.contains('dark-mode');
            
            // Breadcrumb'ı güncelle
            const breadcrumb = document.querySelector('.breadcrumb');
            if (breadcrumb) {
                if (isDarkMode) {
                    breadcrumb.style.backgroundColor = 'rgba(45, 55, 72, 0.95)';
                    breadcrumb.style.border = '1px solid rgba(255, 255, 255, 0.1)';
                } else {
                    breadcrumb.style.backgroundColor = '';
                    breadcrumb.style.border = '';
                }
            }
            
            // Breadcrumb item'ları güncelle
            const breadcrumbItems = document.querySelectorAll('.breadcrumb-item');
            breadcrumbItems.forEach(item => {
                if (isDarkMode) {
                    item.style.color = '#e2e8f0';
                    const link = item.querySelector('a');
                    if (link) {
                        link.style.color = '#60a5fa';
                    }
                    if (item.classList.contains('active')) {
                        item.style.color = '#a0aec0';
                    }
                } else {
                    item.style.color = '';
                    const link = item.querySelector('a');
                    if (link) {
                        link.style.color = '';
                    }
                }
            });
            
            // Form elemanlarını güncelle
            const formControls = document.querySelectorAll('.form-control, .form-select');
            formControls.forEach(input => {
                if (isDarkMode) {
                    input.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
                    input.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                    input.style.color = '#e2e8f0';
                } else {
                    input.style.backgroundColor = '';
                    input.style.borderColor = '';
                    input.style.color = '';
                }
            });
            
            // Form etiketlerini güncelle
            const formLabels = document.querySelectorAll('.form-label');
            formLabels.forEach(label => {
                if (isDarkMode) {
                    label.style.color = '#ffffff';
                    label.style.fontWeight = '700';
                    label.style.fontSize = '0.95rem';
                } else {
                    label.style.color = '';
                    label.style.fontWeight = '';
                    label.style.fontSize = '';
                }
            });
            
            // Form text'leri güncelle
            const formTexts = document.querySelectorAll('.form-text');
            formTexts.forEach(text => {
                if (isDarkMode) {
                    text.style.color = '#f1f5f9';
                    text.style.opacity = '1';
                    text.style.fontWeight = '600';
                    text.style.fontSize = '0.875rem';
                } else {
                    text.style.color = '';
                    text.style.opacity = '';
                    text.style.fontWeight = '';
                    text.style.fontSize = '';
                }
            });
            
            // Placeholder metinlerini güncelle
            const formControlsEdit = document.querySelectorAll('.form-control, .form-select');
            formControlsEdit.forEach(input => {
                if (isDarkMode) {
                    input.style.backgroundColor = 'rgba(255, 255, 255, 0.2)';
                    input.style.borderColor = 'rgba(255, 255, 255, 0.4)';
                    input.style.borderWidth = '2px';
                    input.style.color = '#f8fafc';
                    input.style.fontWeight = '500';
                    input.style.setProperty('--placeholder-color', 'rgba(248, 250, 252, 0.8)');
                    input.style.setProperty('--placeholder-opacity', '1');
                } else {
                    input.style.backgroundColor = '';
                    input.style.borderColor = '';
                    input.style.borderWidth = '';
                    input.style.color = '';
                    input.style.fontWeight = '';
                    input.style.removeProperty('--placeholder-color');
                    input.style.removeProperty('--placeholder-opacity');
                }
            });
            
            // Checkbox'ları güncelle
            const checkboxes = document.querySelectorAll('.form-check-input');
            checkboxes.forEach(checkbox => {
                if (isDarkMode) {
                    checkbox.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
                    checkbox.style.borderColor = 'rgba(255, 255, 255, 0.2)';
                } else {
                    checkbox.style.backgroundColor = '';
                    checkbox.style.borderColor = '';
                }
            });
            
            // Checkbox etiketlerini güncelle
            const checkboxLabels = document.querySelectorAll('.form-check-label');
            checkboxLabels.forEach(label => {
                if (isDarkMode) {
                    label.style.color = '#f1f5f9';
                    const strong = label.querySelector('strong');
                    if (strong) {
                        strong.style.color = '#f1f5f9';
                    }
                } else {
                    label.style.color = '';
                    const strong = label.querySelector('strong');
                    if (strong) {
                        strong.style.color = '';
                    }
                }
            });
            
            // H6 başlıklarını güncelle
            const h6Elements = document.querySelectorAll('h6');
            h6Elements.forEach(h6 => {
                if (isDarkMode) {
                    h6.style.color = '#ffffff';
                    h6.style.fontWeight = '700';
                    h6.style.fontSize = '1.1rem';
                } else {
                    h6.style.color = '';
                    h6.style.fontWeight = '';
                    h6.style.fontSize = '';
                }
            });
            
            // Section title'ı güncelle
            const sectionTitle = document.querySelector('.section-title');
            if (sectionTitle) {
                if (isDarkMode) {
                    sectionTitle.style.color = '#e2e8f0';
                    const icon = sectionTitle.querySelector('i');
                    if (icon) {
                        icon.style.color = '#60a5fa';
                    }
                } else {
                    sectionTitle.style.color = '';
                    const icon = sectionTitle.querySelector('i');
                    if (icon) {
                        icon.style.color = '';
                    }
                }
            }
            
            // Text muted elementleri güncelle
            const textMutedElements = document.querySelectorAll('.text-muted');
            textMutedElements.forEach(el => {
                if (isDarkMode) {
                    el.style.color = '#f1f5f9';
                    el.style.fontWeight = '600';
                    el.style.opacity = '1';
                } else {
                    el.style.color = '';
                    el.style.fontWeight = '';
                    el.style.opacity = '';
                }
            });
            
            // Card header'ı güncelle
            const cardHeaders = document.querySelectorAll('.card-header');
            cardHeaders.forEach(header => {
                if (isDarkMode) {
                    header.style.backgroundColor = 'rgba(30, 41, 59, 0.8)';
                    header.style.borderBottom = '1px solid rgba(255, 255, 255, 0.1)';
                    header.style.color = '#e2e8f0';
                    const h5 = header.querySelector('h5');
                    if (h5) {
                        h5.style.color = '#e2e8f0';
                        const icon = h5.querySelector('i');
                        if (icon) {
                            icon.style.color = '#60a5fa';
                        }
                    }
                } else {
                    header.style.backgroundColor = '';
                    header.style.borderBottom = '';
                    header.style.color = '';
                    const h5 = header.querySelector('h5');
                    if (h5) {
                        h5.style.color = '';
                        const icon = h5.querySelector('i');
                        if (icon) {
                            icon.style.color = '';
                        }
                    }
                }
            });
            
            // HR çizgilerini güncelle
            const hrElements = document.querySelectorAll('hr');
            hrElements.forEach(hr => {
                if (isDarkMode) {
                    hr.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                } else {
                    hr.style.borderColor = '';
                }
            });
            
            // Icon renklerini güncelle
            const primaryIcons = document.querySelectorAll('.text-primary');
            primaryIcons.forEach(icon => {
                if (isDarkMode) {
                    icon.style.color = '#60a5fa';
                } else {
                    icon.style.color = '';
                }
            });
            
            const infoIcons = document.querySelectorAll('.text-info');
            infoIcons.forEach(icon => {
                if (isDarkMode) {
                    icon.style.color = '#22d3ee';
                } else {
                    icon.style.color = '';
                }
            });
            
            const successIcons = document.querySelectorAll('.text-success');
            successIcons.forEach(icon => {
                if (isDarkMode) {
                    icon.style.color = '#10b981';
                } else {
                    icon.style.color = '';
                }
            });
            
            const warningIcons = document.querySelectorAll('.text-warning');
            warningIcons.forEach(icon => {
                if (isDarkMode) {
                    icon.style.color = '#f59e0b';
                } else {
                    icon.style.color = '';
                }
            });
            
            // Butonları güncelle
            const primaryButtons = document.querySelectorAll('.btn-primary');
            primaryButtons.forEach(btn => {
                if (isDarkMode) {
                    btn.style.backgroundColor = 'rgba(59, 130, 246, 0.8)';
                    btn.style.borderColor = 'rgba(59, 130, 246, 0.8)';
                    btn.style.color = '#ffffff';
                } else {
                    btn.style.backgroundColor = '';
                    btn.style.borderColor = '';
                    btn.style.color = '';
                }
            });
            
            const secondaryButtons = document.querySelectorAll('.btn-secondary');
            secondaryButtons.forEach(btn => {
                if (isDarkMode) {
                    btn.style.backgroundColor = 'rgba(107, 114, 128, 0.8)';
                    btn.style.borderColor = 'rgba(107, 114, 128, 0.8)';
                    btn.style.color = '#ffffff';
                } else {
                    btn.style.backgroundColor = '';
                    btn.style.borderColor = '';
                    btn.style.color = '';
                }
            });
            
        } catch (error) {
            console.error('Edit sayfası dark mode güncelleme hatası:', error);
        }
    }
    