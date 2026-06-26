// Global değişkenler
 
function editSite(site) {
        try {
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
        } catch (error) {
            console.error('Edit site error:', error);
        }
    }

    function deleteSite(siteId) {
        try {
            document.getElementById('delete_site_id').value = siteId;
            new bootstrap.Modal(document.getElementById('deleteSiteModal')).show();
        } catch (error) {
            console.error('Delete site error:', error);
        }
    }

    // Görünüm değiştirme işlevselliği
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const cardViewBtn = document.getElementById('cardView');
        const tableViewBtn = document.getElementById('tableView');
        const cardViewContent = document.getElementById('cardViewContent');
        const tableViewContent = document.getElementById('tableViewContent');

        if (cardViewBtn && tableViewBtn && cardViewContent && tableViewContent) {
            // Kart görünümü butonu
            cardViewBtn.addEventListener('change', function() {
                try {
                    if (this.checked) {
                        cardViewContent.style.display = 'block';
                        tableViewContent.style.display = 'none';
                        localStorage.setItem('dashboardViewMode', 'card');
                        console.log('Kart görünümü aktif');
                    }
                } catch (error) {
                    console.error('Kart görünümü hatası:', error);
                }
            });

            // Tablo görünümü butonu
            tableViewBtn.addEventListener('change', function() {
                try {
                    if (this.checked) {
                        cardViewContent.style.display = 'none';
                        tableViewContent.style.display = 'block';
                        localStorage.setItem('dashboardViewMode', 'table');
                        console.log('Tablo görünümü aktif');
                    }
                } catch (error) {
                    console.error('Tablo görünümü hatası:', error);
                }
            });

            // Sayfa yüklendiğinde kaydedilen görünümü geri yükle
            try {
                const savedViewMode = localStorage.getItem('dashboardViewMode');
                if (savedViewMode === 'table') {
                    tableViewBtn.checked = true;
                    cardViewContent.style.display = 'none';
                    tableViewContent.style.display = 'block';
                    console.log('Tablo görünümü geri yüklendi');
                } else {
                    cardViewBtn.checked = true;
                    cardViewContent.style.display = 'block';
                    tableViewContent.style.display = 'none';
                    console.log('Kart görünümü geri yüklendi');
                }
            } catch (error) {
                console.error('Görünüm geri yükleme hatası:', error);
            }
        } else {
            console.error('Görünüm elementleri bulunamadı:', {
                cardViewBtn: !!cardViewBtn,
                tableViewBtn: !!tableViewBtn,
                cardViewContent: !!cardViewContent,
                tableViewContent: !!tableViewContent
            });
        }
        } catch (error) {
            console.error('DOMContentLoaded görünüm hatası:', error);
        }
        
        // Dark mode kontrolü ve kartları güncelle
        applyDarkModeToCards();
    });
    
    // Dark mode kontrolü ve kartları güncelleme fonksiyonu
    function applyDarkModeToCards() {
        try {
            const isDarkMode = document.body.classList.contains('dark-mode');
            const siteCards = document.querySelectorAll('.site-card, .card.site-card');
            
            siteCards.forEach(card => {
                if (isDarkMode) {
                    // Dark mode stillerini uygula
                    card.style.backgroundColor = 'rgba(45, 55, 72, 0.95)';
                    card.style.borderColor = 'rgba(255, 255, 255, 0.1)';
                    card.style.color = '#e2e8f0';
                    
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
                    });
                } else {
                    // Light mode'a geri dön
                    card.style.backgroundColor = '';
                    card.style.borderColor = '';
                    card.style.color = '';
                    
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
            }
        });
    });
    
    observer.observe(document.body, {
        attributes: true,
        attributeFilter: ['class']
    });

    // Edit form AJAX
    document.getElementById('editSiteForm').addEventListener('submit', function(e) {
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
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                bootstrap.Modal.getInstance(document.getElementById('editSiteModal')).hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                showAlert('danger', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Database error');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = originalText;
        });
    });

    // ==========================================
    // GERÇEK ZAMANLI DASHBOARD GÜNCELLEMESİ
    // ==========================================
    let isUpdatingDashboard = false;
    let updateInterval = null;
    let lastUpdateTime = Date.now();
    let updateIndicatorDismissed = localStorage.getItem('updateIndicatorDismissed') === 'true';

    // Göstergeyi kapat
    function dismissUpdateIndicator() {
        localStorage.setItem('updateIndicatorDismissed', 'true');
        updateIndicatorDismissed = true;
    }

    // Güncelleme durumunu göster - Sakin tasarım
    function updateIndicatorStatus(status, message) {
        if (updateIndicatorDismissed) return;
        
        const indicator = document.getElementById('liveUpdateIndicator');
        const statusText = document.getElementById('updateStatusText');
        const spinner = indicator?.querySelector('.update-spinner');
        
        if (!indicator || !statusText) return;
        
        // İndikatörü göster
        indicator.style.display = 'flex';
        
        if (status === 'updating') {
            indicator.className = 'live-update-indicator updating';
            if (spinner) spinner.style.display = 'inline-block';
            statusText.innerHTML = '<i class="fas fa-sync fa-spin"></i> ' + message;
        } else if (status === 'success') {
            indicator.className = 'live-update-indicator';
            if (spinner) spinner.style.display = 'none';
            statusText.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
            
            // 2 saniye sonra gizle (sadece başarılı güncellemeler için)
            setTimeout(() => {
                if (!updateIndicatorDismissed && status === 'success') {
                    indicator.style.display = 'none';
                }
            }, 2000);
        } else if (status === 'error') {
            indicator.className = 'live-update-indicator error';
            if (spinner) spinner.style.display = 'none';
            statusText.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + message;
        }
    }

    // Son güncelleme zamanını göster
    function updateLastUpdateTime() {
        const statusText = document.getElementById('updateStatusText');
        if (!statusText || updateIndicatorDismissed) return;
        
        const now = new Date();
        const timeStr = now.toLocaleTimeString('tr-TR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        statusText.innerHTML = '<i class="fas fa-satellite-dish"></i> Canlı yayın aktif - Son güncelleme: ' + timeStr;
    }

    // Önceki site durumlarını localStorage'dan yükle
    function loadPreviousStatuses() {
        const saved = localStorage.getItem('siteStatuses');
        if (saved) {
            try {
                return new Map(JSON.parse(saved));
            } catch(e) {
                console.error('localStorage parse hatası:', e);
                return new Map();
            }
        }
        return new Map();
    }
    
    // Site durumlarını localStorage'a kaydet
    function saveSiteStatuses(statusMap) {
        try {
            localStorage.setItem('siteStatuses', JSON.stringify(Array.from(statusMap.entries())));
        } catch(e) {
            console.error('localStorage kayıt hatası:', e);
        }
    }
    
    let previousSiteStatuses = loadPreviousStatuses();
    
    // Dashboard verilerini güncelle
    function updateDashboard() {
        try {
            if (isUpdatingDashboard) return;
            isUpdatingDashboard = true;
            
            updateIndicatorStatus('updating', 'Veriler güncelleniyor...');

            fetch(base_url+'pages/ajax/get_dashboard_data.php')
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
                    saveSiteStatuses(previousSiteStatuses);
                    
                    // DURUM DEĞİŞTİYSE SAYFAYI YENİLE
                    if (statusChanged) {
                        const changeSummary = changedSites.map(s => 
                            `${s.name}: ${s.oldStatus} → ${s.newStatus}`
                        ).join(', ');
                        
                        // Sakin güncelleme bildirimi
                        updateIndicatorStatus('success', `Durum değişti! Sayfa yenileniyor...`);
                        
                        // Önemli durum değişiklikleri için toast bildirimi
                        changedSites.forEach(site => {
                            if (site.newStatus === 'down') {
                                showError('Site Kesintisi!', `${site.name} sitesi çalışmıyor`);
                            } else if (site.newStatus === 'up' && site.oldStatus === 'down') {
                                showSuccess('Site Düzeldi!', `${site.name} sitesi tekrar çalışıyor`);
                            }
                        });
                        
                        // Yenileme öncesi bildirim göster
                        if (window.Notification && Notification.permission === "granted") {
                            new Notification('Site Durumu Değişti!', {
                                body: changeSummary,
                                icon: base_url+'assets/img/favicon.ico'
                            });
                        }
                        
                        // 2 saniye bekle, sonra yenile
                        setTimeout(() => {
                            location.reload();
                        }, 2000);
                        
                        return; // Daha fazla güncelleme yapma
                    }
                    
                    // Durum değişmediyse normal güncelleme yap
                    updateStatistics(data.data.stats);
                    updateSites(data.data.sites);
                    lastUpdateTime = Date.now();
                    // Normal güncellemelerde sakin bildirim (2 saniye sonra gizlenir)
                    updateIndicatorStatus('success', 'Dashboard güncellendi!');
                }
            })
                .catch(error => {
                    console.error('Dashboard güncelleme hatası:', error);
                    updateIndicatorStatus('error', 'Güncelleme başarısız - Tekrar deneniyor...');
                })
                .finally(() => {
                    isUpdatingDashboard = false;
                });
        } catch (error) {
            console.error('Dashboard update function error:', error);
            isUpdatingDashboard = false;
            updateIndicatorStatus('error', 'Güncelleme hatası');
        }
    }

    // İstatistikleri güncelle (üstteki kartlar)
    function updateStatistics(stats) {
        // Toplam site sayısı
        const totalSitesElement = document.querySelector('.stats-number');
        if (totalSitesElement && totalSitesElement.textContent != stats.total_sites) {
            animateNumber(totalSitesElement, stats.total_sites);
        }

        // Aktif siteler
        const activeSitesElements = document.querySelectorAll('.stats-number');
        if (activeSitesElements[1] && activeSitesElements[1].textContent != stats.active_sites) {
            animateNumber(activeSitesElements[1], stats.active_sites);
        }

        // Kesintili siteler (eski uptime yüzdesi yerine)
        if (activeSitesElements[2] && activeSitesElements[2].textContent != stats.down_sites) {
            animateNumber(activeSitesElements[2], stats.down_sites);
            // Rengi güncelle (0 ise yeşil, 0'dan fazla ise kırmızı)
            if (stats.down_sites > 0) {
                activeSitesElements[2].classList.add('text-danger');
            } else {
                activeSitesElements[2].classList.remove('text-danger');
            }
        }

        // Son kontrol
        if (activeSitesElements[3] && activeSitesElements[3].textContent != stats.last_check) {
            activeSitesElements[3].textContent = stats.last_check;
        }
    }

    // Site kartlarını ve tablo satırlarını güncelle - Bootstrap grid sistemine dokunma
    function updateSites(sites) {
        // Önce siteleri duruma göre sırala (DOWN önce, UP sonra)
        sites.sort((a, b) => {
            if (a.last_status === 'down' && b.last_status !== 'down') return -1;
            if (a.last_status !== 'down' && b.last_status === 'down') return 1;
            return 0;
        });
        
        sites.forEach(site => {
            // Kart görünümünü güncelle
            updateSiteCard(site);
            
            // Tablo görünümünü güncelle
            updateSiteTableRow(site);
            
            // Mini grafiği güncelle
            updateMiniChart(site.id);
        });
        
        // Bootstrap grid sistemine dokunma - sadece yükseklik eşitleme
        setTimeout(() => {
            fixCardLayout();
        }, 200);
    }
    
    // Site kartlarını duruma göre yeniden sırala - Bootstrap grid sistemine hiç dokunma
    function reorderSiteCards(sites) {
        const cardViewContent = document.getElementById('cardViewContent');
        if (!cardViewContent) return;
        
        // Mevcut kartları al
        const cards = Array.from(cardViewContent.querySelectorAll('.site-card'));
        
        // Kartları site ID'lerine göre sırala
        cards.sort((a, b) => {
            const aId = parseInt(a.getAttribute('data-site-id'));
            const bId = parseInt(b.getAttribute('data-site-id'));
            
            const aSite = sites.find(s => s.id === aId);
            const bSite = sites.find(s => s.id === bId);
            
            if (!aSite || !bSite) return 0;
            
            // DOWN önce, UP sonra
            if (aSite.last_status === 'down' && bSite.last_status !== 'down') return -1;
            if (aSite.last_status !== 'down' && bSite.last_status === 'down') return 1;
            return 0;
        });
        
        // Kartları yeniden ekle - Bootstrap grid sistemine dokunma
        cards.forEach(card => {
            cardViewContent.appendChild(card);
        });
        
        // Bootstrap grid sistemine hiç dokunma - sadece yükseklik eşitleme
        setTimeout(() => {
            fixCardLayout();
        }, 100);
    }
    
    // Kartların CSS düzenini düzelt - Bootstrap grid sistemine hiç dokunma
    function fixCardLayout() {
        try {
            const cards = document.querySelectorAll('.site-card');
            
            // Sadece kartların yüksekliklerini eşitle
            cards.forEach(card => {
                card.style.height = 'auto';
                card.style.minHeight = '200px';
                
                // Sadece card'ın kendisini düzelt - Bootstrap grid sistemine dokunma
                card.style.width = '100%';
                card.style.maxWidth = '100%';
                card.style.boxSizing = 'border-box';
                card.style.overflow = 'hidden';
            });
            
            // Bootstrap grid sistemine hiç dokunma - sadece yükseklik eşitleme
            setTimeout(() => {
                // Sadece kartların yüksekliklerini eşitle
                const allCards = document.querySelectorAll('.site-card');
                let maxHeight = 0;
                
                // En yüksek kartı bul
                allCards.forEach(card => {
                    const cardHeight = card.offsetHeight;
                    if (cardHeight > maxHeight) {
                        maxHeight = cardHeight;
                    }
                });
                
                // Minimum yükseklik kontrolü
                if (maxHeight < 200) {
                    maxHeight = 200;
                }
                
                // Tüm kartları aynı yüksekliğe ayarla
                allCards.forEach(card => {
                    card.style.height = maxHeight + 'px';
                });
            }, 150);
            
        } catch (error) {
            console.error('fixCardLayout error:', error);
        }
    }
    
    // Tablo satırlarını duruma göre yeniden sırala
    function reorderTableRows(sites) {
        const tableViewContent = document.getElementById('tableViewContent');
        if (!tableViewContent) return;
        
        const tbody = tableViewContent.querySelector('tbody');
        if (!tbody) return;
        
        // Mevcut satırları al
        const rows = Array.from(tbody.querySelectorAll('tr[data-site-id]'));
        
        // Satırları site ID'lerine göre sırala
        rows.sort((a, b) => {
            const aId = parseInt(a.getAttribute('data-site-id'));
            const bId = parseInt(b.getAttribute('data-site-id'));
            
            const aSite = sites.find(s => s.id === aId);
            const bSite = sites.find(s => s.id === bId);
            
            if (!aSite || !bSite) return 0;
            
            // DOWN önce, UP sonra
            if (aSite.last_status === 'down' && bSite.last_status !== 'down') return -1;
            if (aSite.last_status !== 'down' && bSite.last_status === 'down') return 1;
            return 0;
        });
        
        // Satırları yeniden ekle
        rows.forEach(row => {
            tbody.appendChild(row);
        });
    }
    
    // Mini grafikleri güncelle
    function updateMiniChart(siteId) {
        // Global event dispatch et - mini grafikler bunu dinleyecek
        const event = new CustomEvent('updateMiniChart', { 
            detail: { siteId: siteId } 
        });
        window.dispatchEvent(event);
    }

    // Tek bir site kartını güncelle
    function updateSiteCard(site) {
        const cardViewContent = document.getElementById('cardViewContent');
        if (!cardViewContent) return;

        const cards = cardViewContent.querySelectorAll('.site-card');
        cards.forEach(card => {
            const cardTitle = card.querySelector('.card-title');
            if (cardTitle && cardTitle.textContent.trim() === site.name) {
                
                // Durum sınıfını güncelle (up/down)
                const oldClass = card.classList.contains('up') ? 'up' : 'down';
                const newClass = site.status_class;
                
                if (oldClass !== newClass) {
                    card.classList.remove(oldClass);
                    card.classList.add(newClass);
                    flashElement(card, newClass === 'up' ? '#28a745' : '#dc3545');
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
                const uptimeElement = card.querySelector('.fw-bold.text-success, .fw-bold.text-danger');
                if (uptimeElement) {
                    uptimeElement.className = 'fw-bold text-' + site.status_color;
                    if (uptimeElement.textContent !== site.uptime_24h.toFixed(2) + '%') {
                        uptimeElement.textContent = site.uptime_24h.toFixed(2) + '%';
                        flashElement(uptimeElement);
                    }
                }

                // Son kontrol zamanını güncelle
                const lastCheckElement = card.querySelector('.mt-2.text-muted.small');
                if (lastCheckElement && site.last_check) {
                    if (!lastCheckElement.textContent.includes(site.formatted_check)) {
                        lastCheckElement.innerHTML = '<i class="fas fa-clock"></i> Son Kontrol: ' + site.formatted_check;
                    }
                }
            }
        });
        
        // Bootstrap grid sistemini koru - sadece yükseklik eşitleme
        setTimeout(() => {
            fixCardLayout();
        }, 100);
    }

    // Tek bir tablo satırını güncelle
    function updateSiteTableRow(site) {
        const tableViewContent = document.getElementById('tableViewContent');
        if (!tableViewContent) return;

        const rows = tableViewContent.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const nameCell = row.querySelector('td:first-child .fw-bold');
            if (nameCell && nameCell.textContent.trim() === site.name) {
                // Durum sınıfını güncelle
                const oldClass = row.classList.contains('up') ? 'up' : 'down';
                const newClass = site.status_class;
                
                if (oldClass !== newClass) {
                    row.classList.remove(oldClass);
                    row.classList.add(newClass);
                    flashElement(row, newClass === 'up' ? '#28a745' : '#dc3545');
                }

                // Progress bar ve uptime yüzdesini güncelle
                const progressBar = row.querySelector('.progress-bar');
                const uptimeText = row.querySelector('.fw-bold');
                if (progressBar && uptimeText) {
                    const uptimeValue = site.uptime_24h.toFixed(2) + '%';
                    progressBar.style.width = site.uptime_24h + '%';
                    progressBar.style.backgroundColor = site.status_class === 'up' ? '#28a745' : '#dc3545';
                    
                    uptimeText.className = 'fw-bold text-' + site.status_color;
                    if (uptimeText.textContent !== uptimeValue) {
                        uptimeText.textContent = uptimeValue;
                        flashElement(uptimeText);
                    }
                }

                // Durum badge'ini güncelle
                const badge = row.querySelector('.badge');
                if (badge) {
                    badge.className = 'badge bg-' + site.status_color;
                    badge.innerHTML = '<i class="fas fa-circle me-1"></i> ' + site.status_text;
                }

                // Son kontrol zamanını güncelle
                const lastCheckCell = row.querySelector('td:nth-child(5) small');
                if (lastCheckCell && site.last_check) {
                    if (!lastCheckCell.textContent.includes(site.formatted_check)) {
                        lastCheckCell.textContent = site.formatted_check;
                    }
                }
            }
        });
    }

    // Sayı animasyonu
    function animateNumber(element, targetValue) {
        const currentValue = parseInt(element.textContent) || 0;
        const difference = targetValue - currentValue;
        const duration = 1000; // 1 saniye
        const steps = 20;
        const stepValue = difference / steps;
        const stepDuration = duration / steps;
        
        let currentStep = 0;
        const interval = setInterval(() => {
            currentStep++;
            const newValue = Math.round(currentValue + (stepValue * currentStep));
            element.textContent = newValue;
            
            if (currentStep >= steps) {
                element.textContent = targetValue;
                clearInterval(interval);
                flashElement(element.parentElement);
            }
        }, stepDuration);
    }

    // Element yanıp sönme efekti
    function flashElement(element, color = '#28a745') {
        const originalBg = element.style.backgroundColor;
        const originalTransition = element.style.transition;
        
        element.style.transition = 'background-color 0.3s ease';
        element.style.backgroundColor = color + '20'; // 20 = alpha opacity
        
        setTimeout(() => {
            element.style.backgroundColor = originalBg;
            setTimeout(() => {
                element.style.transition = originalTransition;
            }, 300);
        }, 300);
    }

    // Otomatik güncellemeyi başlat
    function startAutoUpdate() {
    // İlk güncelleme 10 saniye sonra
    setTimeout(updateDashboard, 10000);
    
    // Her 30 saniyede bir güncelle - Performans için optimize edildi
    updateInterval = setInterval(updateDashboard, 30000);
        
        console.log('Gerçek zamanlı dashboard güncellemesi başlatıldı (30 saniye aralıklarla)');
    }

    // Otomatik güncellemeyi durdur
    function stopAutoUpdate() {
        if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
            console.log('Gerçek zamanlı dashboard güncellemesi durduruldu');
        }
    }

    // Sayfa görünür/gizli olduğunda güncellemeyi kontrol et
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            stopAutoUpdate();
        } else {
            updateDashboard(); // Hemen bir güncelleme yap
            startAutoUpdate(); // Otomatik güncellemeyi yeniden başlat
        }
    });

    // ==========================================
    // SİTE ARAMA ÖZELLİĞİ
    // ==========================================
    
    let searchTimeout = null;
    
    // Site arama fonksiyonu
    function searchSites(searchTerm) {
        try {
            searchTerm = searchTerm.toLowerCase().trim();
            
            const cardViewContent = document.getElementById('cardViewContent');
            const tableViewContent = document.getElementById('tableViewContent');
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
        if (cardViewContent) {
            const cards = cardViewContent.querySelectorAll('.col-lg-4');
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
        if (tableViewContent) {
            const rows = tableViewContent.querySelectorAll('tbody tr');
            totalCount = rows.length;
            visibleCount = 0;
            
            rows.forEach(row => {
                const name = row.querySelector('td:first-child .fw-bold')?.textContent.toLowerCase() || '';
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
            const cardViewContent = document.getElementById('cardViewContent');
            const tableViewContent = document.getElementById('tableViewContent');
            
            if (cardViewContent) {
                cardViewContent.querySelectorAll('.col-lg-4').forEach(card => {
                    card.style.display = 'block';
                });
            }
            
            if (tableViewContent) {
                tableViewContent.querySelectorAll('tbody tr').forEach(row => {
                    row.style.display = '';
                });
            }
        } catch (error) {
            console.error('showAllSites hatası:', error);
        }
    }
    
    // Aramayı temizle
    function clearSiteSearch() {
        try {
            const searchInput = document.getElementById('siteSearchInput');
            if (searchInput) {
                searchInput.value = '';
                applyDashboardFilters();
            }
        } catch (error) {
            console.error('clearSiteSearch hatası:', error);
        }
    }
    
    // Arama input'una event listener ekle
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const searchInput = document.getElementById('siteSearchInput');
            const clearSearchBtn = document.getElementById('clearSearch');
            
            console.log('Arama elementleri:', {
                searchInput: !!searchInput,
                clearSearchBtn: !!clearSearchBtn
            });
            
            if (searchInput) {
                // Yazarken anlık arama (debounce ile)
                searchInput.addEventListener('input', function() {
                    try {
                        clearTimeout(searchTimeout);
                        searchTimeout = setTimeout(() => {
                            console.log('Arama yapılıyor:', this.value);
                            applyDashboardFilters();
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
                            console.log('Enter ile arama:', this.value);
                            applyDashboardFilters();
                        }
                    } catch (error) {
                        console.error('Enter arama hatası:', error);
                    }
                });
            }
            
            // Temizle butonuna tıklandığında
            if (clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    try {
                        console.log('Arama temizleniyor');
                        clearSiteSearch();
                    } catch (error) {
                        console.error('Temizle butonu hatası:', error);
                    }
                });
            }
            
            // ESC tuşuna basıldığında aramayı temizle
            document.addEventListener('keydown', function(e) {
                try {
                    if (e.key === 'Escape' && searchInput && searchInput.value) {
                        console.log('ESC ile arama temizleniyor');
                        clearSiteSearch();
                        searchInput.blur();
                    }
                } catch (error) {
                    console.error('ESC tuşu hatası:', error);
                }
            });
        } catch (error) {
            console.error('Arama event listener hatası:', error);
        }
    });
    
    // ==========================================
    
    // Sayfa yüklendiğinde göstergeyi kontrol et
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const indicator = document.getElementById('liveUpdateIndicator');
            if (indicator && updateIndicatorDismissed) {
                indicator.style.display = 'none';
            }
            
            // CSS düzenini düzelt
            setTimeout(() => {
                if (typeof fixCardLayout === 'function') {
                    fixCardLayout();
                }
            }, 500);
        } catch (error) {
            console.error('DOMContentLoaded error:', error);
        }
    });

    // Pencere boyutu değiştiğinde CSS düzenini düzelt
    window.addEventListener('resize', function() {
        try {
            setTimeout(() => {
                if (typeof fixCardLayout === 'function') {
                    fixCardLayout();
                }
            }, 300);
        } catch (error) {
            console.error('Resize event error:', error);
        }
    });

    // Sayfa yüklendiğinde otomatik güncellemeyi başlat
    try {
        console.log('Dashboard version:', document.querySelector('meta[name="version"]')?.content || 'unknown');
        console.log('Dashboard loaded at:', new Date().toISOString());
        console.log('JavaScript syntax check: OK');
        
        // Element kontrolü
        console.log('Element kontrolü:', {
            cardViewBtn: !!document.getElementById('cardView'),
            tableViewBtn: !!document.getElementById('tableView'),
            cardViewContent: !!document.getElementById('cardViewContent'),
            tableViewContent: !!document.getElementById('tableViewContent'),
            searchInput: !!document.getElementById('siteSearchInput'),
            clearSearchBtn: !!document.getElementById('clearSearch')
        });
        
        // Eğer eski cache varsa uyar
        if (performance.navigation.type === 1) {
            console.log('Page refreshed - cache should be cleared');
        }
        
        startAutoUpdate();
    } catch (error) {
        console.error('Dashboard initialization error:', error);
        // Hata durumunda sayfayı yenile
        console.log('Reloading page due to error...');
        setTimeout(() => {
            window.location.reload(true);
        }, 1000);
    }
 

    function checkAllSites() {
        const btn = document.getElementById('checkAllSitesBtn');
        const text = document.getElementById('checkAllSitesText');
        const spinner = document.getElementById('checkAllSitesSpinner');
        
        // Butonu devre dışı bırak ve spinner göster
        btn.disabled = true;
        text.textContent = 'Kontrol Ediliyor...';
        spinner.style.display = 'inline-block';

        // Yükleme durumu: kartlara/satırlara shimmer uygula
        document.querySelectorAll('.site-card, .dashboard-row').forEach(el => el.classList.add('is-refreshing'));

        // AJAX isteği gönder
        fetch(base_url + 'pages/ajax/check_all_sites.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            // Butonu tekrar aktif et
            btn.disabled = false;
            text.textContent = 'Tüm Siteleri Kontrol Et';
            spinner.style.display = 'none';
            
            // Toast bildirimi göster
            if (data.success) {
                showToast('success', 'Başarılı', data.message);
                
                // Başarılı olduğunda sayfayı yenile
                setTimeout(() => {
                    location.reload();
                }, 2000);
            } else {
                document.querySelectorAll('.site-card, .dashboard-row').forEach(el => el.classList.remove('is-refreshing'));
                showToast('error', 'Hata', data.message);
            }
        })
        .catch(error => {
            // Hata durumunda butonu tekrar aktif et
            btn.disabled = false;
            text.textContent = 'Tüm Siteleri Kontrol Et';
            spinner.style.display = 'none';
            document.querySelectorAll('.site-card, .dashboard-row').forEach(el => el.classList.remove('is-refreshing'));

            showToast('error', 'Hata', 'Bağlantı hatası oluştu.');
        });
    }
    
    // Toast bildirimi fonksiyonu
    function showToast(type, title, message) {
        const toastContainer = document.getElementById('toast-container') || createToastContainer();
        
        const toastId = 'toast-' + Date.now();
        const toastHtml = `
            <div class="toast" id="${toastId}" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <i class="fas fa-${type === 'success' ? 'check-circle text-success' : 'exclamation-triangle text-danger'} me-2"></i>
                    <strong class="me-auto">${title}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                </div>
                <div class="toast-body">
                    ${message}
                    ${type === 'success' ? '<br><small class="text-muted">Sayfa 2 saniye sonra yenilenecek...</small>' : ''}
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(toastId);
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
        
        // Toast kapandıktan sonra DOM'dan kaldır
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }

    // ============================================================
    // BİRLEŞİK DASHBOARD FİLTRELERİ (arama + durum + grup)
    // ============================================================
    let currentStatusFilter = 'all';

    function applyDashboardFilters() {
        try {
            const searchInput = document.getElementById('siteSearchInput');
            const groupSelect = document.getElementById('groupFilter');
            const term = (searchInput?.value || '').toLowerCase().trim();
            const groupFilter = groupSelect ? groupSelect.value : 'all';

            const items = document.querySelectorAll('.site-filter-item');
            let visible = 0;
            const total = items.length;

            items.forEach(item => {
                const status = item.getAttribute('data-status') || '';
                const group = item.getAttribute('data-group') || '';
                const text = item.textContent.toLowerCase();

                const statusMatch = currentStatusFilter === 'all' || status === currentStatusFilter;
                const groupMatch = groupFilter === 'all' || group === groupFilter;
                const textMatch = !term || text.includes(term);

                if (statusMatch && groupMatch && textMatch) {
                    item.style.display = '';
                    visible++;
                } else {
                    item.style.display = 'none';
                }
            });

            updateFilterResultInfo(visible, total, term, groupFilter);
        } catch (error) {
            console.error('applyDashboardFilters hatası:', error);
        }
    }

    function updateFilterResultInfo(visible, total, term, groupFilter) {
        const info = document.getElementById('searchResultInfo');
        const text = document.getElementById('searchResultText');
        const clearBtn = document.getElementById('clearSearch');
        if (!info || !text) return;

        const filtersActive = term || currentStatusFilter !== 'all' || (groupFilter && groupFilter !== 'all');
        if (clearBtn) clearBtn.style.display = term ? 'inline-block' : 'none';

        if (!filtersActive) {
            info.style.display = 'none';
            return;
        }

        // Toplam görünür sayısı (her site hem kart hem tabloda olduğu için 2 kapsayıcı olabilir)
        const cardItems = document.querySelectorAll('#cardViewContent .site-filter-item');
        const denom = cardItems.length || total;
        const shown = Array.from(cardItems).filter(c => c.style.display !== 'none').length || visible;

        if (shown === 0) {
            text.innerHTML = `Filtreye uyan site bulunamadı (${denom} siteden 0 eşleşme)`;
            info.className = 'alert alert-warning mb-3';
        } else {
            text.innerHTML = `${shown} site gösteriliyor (${denom} siteden)`;
            info.className = 'alert alert-info mb-3';
        }
        info.style.display = 'block';
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Durum filtresi çipleri
        document.querySelectorAll('.filter-chip[data-status-filter]').forEach(chip => {
            chip.addEventListener('click', function () {
                currentStatusFilter = this.getAttribute('data-status-filter');
                document.querySelectorAll('.filter-chip[data-status-filter]').forEach(c => {
                    c.classList.toggle('active', c === this);
                });
                applyDashboardFilters();
            });
        });

        // Grup filtresi
        const groupSelect = document.getElementById('groupFilter');
        if (groupSelect) {
            groupSelect.addEventListener('change', applyDashboardFilters);
        }
    });