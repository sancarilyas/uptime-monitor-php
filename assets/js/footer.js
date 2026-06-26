        // Modern Notification System
        class NotificationManager {
            constructor() {
                this.container = document.getElementById('toastContainer');
                this.notifications = new Map();
            }

            show(type, title, message, duration = 5000) {
                const id = 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                const icons = {
                    success: 'fas fa-check-circle',
                    danger: 'fas fa-exclamation-triangle',
                    warning: 'fas fa-exclamation-circle',
                    info: 'fas fa-info-circle'
                };

                const toastHtml = `
                    <div class="toast toast-${type}" id="${id}" role="alert" aria-live="assertive" aria-atomic="true" style="width: 100%; max-width: 400px; min-width: 300px;">
                        <div class="toast-header">
                            <i class="${icons[type]} me-2"></i>
                            <strong class="me-auto">${title}</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            ${message}
                        </div>
                    </div>
                `;

                this.container.insertAdjacentHTML('beforeend', toastHtml);
                const toastElement = document.getElementById(id);
                const toast = new bootstrap.Toast(toastElement, {
                    autohide: true,
                    delay: duration
                });

                this.notifications.set(id, toast);
                toast.show();

                // Auto remove from notifications map when hidden
                toastElement.addEventListener('hidden.bs.toast', () => {
                    this.notifications.delete(id);
                    toastElement.remove();
                });

                return id;
            }

            success(title, message, duration) {
                return this.show('success', title, message, duration);
            }

            error(title, message, duration) {
                return this.show('danger', title, message, duration);
            }

            warning(title, message, duration) {
                return this.show('warning', title, message, duration);
            }

            info(title, message, duration) {
                return this.show('info', title, message, duration);
            }

            hide(id) {
                const toast = this.notifications.get(id);
                if (toast) {
                    toast.hide();
                }
            }

            hideAll() {
                this.notifications.forEach(toast => toast.hide());
            }
        }

        // Global notification manager
        window.notifications = new NotificationManager();

        // Convenience functions
        function showSuccess(title, message, duration) {
            return window.notifications.success(title, message, duration);
        }

        function showError(title, message, duration) {
            return window.notifications.error(title, message, duration);
        }

        function showWarning(title, message, duration) {
            return window.notifications.warning(title, message, duration);
        }

        function showInfo(title, message, duration) {
            return window.notifications.info(title, message, duration);
        }

        // Logout fonksiyonu
        function logout() {
            if (confirm('Çıkmak istediğinize emin misiniz?')) {
                fetch(base_url+'pages/ajax/logout.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccess('Çıkış Yapıldı', 'Başarıyla çıkış yaptınız');
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1000);
                    } else {
                        showError('Hata', 'Çıkış yapılırken bir hata oluştu');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Hata', 'Çıkış yapılırken bir hata oluştu');
                });
            }
        }

        // Modern Alert fonksiyonu (backward compatibility)
        function showAlert(type, message) {
            const title = type === 'success' ? 'Başarılı!' :
                         type === 'danger' ? 'Hata!' :
                         type === 'warning' ? 'Uyarı!' : 'Bilgi';
            
            if (type === 'success') return showSuccess(title, message);
            if (type === 'danger') return showError(title, message);
            if (type === 'warning') return showWarning(title, message);
            return showInfo(title, message);
        }

        // Form submit handler
        function handleFormSubmit(form, successMessage, errorMessage) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(form);
                const button = form.querySelector('button[type="submit"]');
                const originalText = button.innerHTML;
                
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
                
                fetch(form.action || window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    if (data.includes('success') || data.includes('başarılı')) {
                        showSuccess('Başarılı!', successMessage);
                        form.reset();
                    } else {
                        showError('Hata!', errorMessage);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Hata!', errorMessage);
                })
                .finally(() => {
                    button.disabled = false;
                    button.innerHTML = originalText;
                });
            });
        }

        // Auto-show alerts from PHP (Dashboard ve Sites hariç)
        document.addEventListener('DOMContentLoaded', function() {
            // Dashboard ve Sites sayfasında otomatik dönüştürme yapma
            if (window.location.pathname.includes('/dashboard') || window.location.pathname.includes('/sites')) {
                return;
            }
            
            // Convert existing alerts to toasts
            const alerts = document.querySelectorAll('.alert:not(.alert-dismissible)');
            alerts.forEach(alert => {
                const type = alert.classList.contains('alert-success') ? 'success' :
                           alert.classList.contains('alert-danger') ? 'danger' :
                           alert.classList.contains('alert-warning') ? 'warning' : 'info';
                
                const title = type === 'success' ? 'Başarılı!' :
                             type === 'danger' ? 'Hata!' :
                             type === 'warning' ? 'Uyarı!' : 'Bilgi';
                
                const message = alert.textContent.trim();
                
                // Show as toast
                if (type === 'success') showSuccess(title, message);
                else if (type === 'danger') showError(title, message);
                else if (type === 'warning') showWarning(title, message);
                else showInfo(title, message);
                
                // Hide original alert
                alert.style.display = 'none';
            });

            // Handle dismissible alerts
            const dismissibleAlerts = document.querySelectorAll('.alert-dismissible');
            dismissibleAlerts.forEach(alert => {
                const type = alert.classList.contains('alert-success') ? 'success' :
                           alert.classList.contains('alert-danger') ? 'danger' :
                           alert.classList.contains('alert-warning') ? 'warning' : 'info';
                
                const title = type === 'success' ? 'Başarılı!' :
                             type === 'danger' ? 'Hata!' :
                             type === 'warning' ? 'Uyarı!' : 'Bilgi';
                
                const message = alert.textContent.replace('×', '').trim();
                
                // Show as toast
                if (type === 'success') showSuccess(title, message);
                else if (type === 'danger') showError(title, message);
                else if (type === 'warning') showWarning(title, message);
                else showInfo(title, message);
                
                // Hide original alert
                alert.style.display = 'none';
            });
        });
    
        // Z-Index fix for dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            // Fix dropdown z-index issues
            const dropdowns = document.querySelectorAll('.dropdown');
            dropdowns.forEach(function(dropdown) {
                const toggle = dropdown.querySelector('.dropdown-toggle');
                const menu = dropdown.querySelector('.dropdown-menu');
                
                if (toggle && menu) {
                    toggle.addEventListener('click', function() {
                        // Close other dropdowns
                        document.querySelectorAll('.dropdown-menu.show').forEach(function(otherMenu) {
                            if (otherMenu !== menu) {
                                otherMenu.classList.remove('show');
                            }
                        });
                        
                        // Set high z-index for this dropdown
                        setTimeout(function() {
                            if (menu.classList.contains('show')) {
                                menu.style.zIndex = '1050';
                                dropdown.style.zIndex = '1050';
                            }
                        }, 10);
                    });
                }
            });

            // Fix modal z-index
            const modals = document.querySelectorAll('.modal');
            modals.forEach(function(modal) {
                modal.addEventListener('show.bs.modal', function() {
                    modal.style.zIndex = '1060';
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.style.zIndex = '1055';
                    }
                });
            });

            // Fix card hover z-index
            const cards = document.querySelectorAll('.card');
            cards.forEach(function(card) {
                card.addEventListener('mouseenter', function() {
                    this.style.zIndex = '5';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.zIndex = '1';
                });
            });
        });
        // Tema değiştirme fonksiyonu
        function toggleTheme() {
            try {
                const body = document.body;
                const themeIcon = document.getElementById('themeIcon');
                
                if (body.classList.contains('dark-mode')) {
                    // Gece modundan gündüz moduna geç
                    body.classList.remove('dark-mode');
                    themeIcon.className = 'fas fa-moon';
                    localStorage.setItem('theme', 'light');
                } else {
                    // Gündüz modundan gece moduna geç
                    body.classList.add('dark-mode');
                    themeIcon.className = 'fas fa-sun';
                    localStorage.setItem('theme', 'dark');
                }
            } catch (error) {
                console.error('Tema değiştirme hatası:', error);
            }
        }
        
        // Sayfa yüklendiğinde kaydedilen temayı geri yükle
        function loadTheme() {
            try {
                const savedTheme = localStorage.getItem('theme');
                const body = document.body;
                const themeIcon = document.getElementById('themeIcon');
                
                if (savedTheme === 'dark') {
                    body.classList.add('dark-mode');
                    themeIcon.className = 'fas fa-sun';
                } else {
                    body.classList.remove('dark-mode');
                    themeIcon.className = 'fas fa-moon';
                }
            } catch (error) {
                console.error('Tema yükleme hatası:', error);
            }
        }

        // Sayfa yüklendiğinde temayı yükle
        document.addEventListener('DOMContentLoaded', function() {
            loadTheme();
        });
    