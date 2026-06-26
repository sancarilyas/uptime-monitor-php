/**
 * Monitor Status Page JavaScript
 * Admin panel için daemon kontrolü
 */

// Global değişkenler
window.monitorStatus = {
    baseUrl: window.base_url || '/uptime/',
    
    init: function() {
        console.log('Monitor Status JS loaded, base_url:', this.baseUrl);
    },
    
    startDaemon: function() {
        const confirmMsg = 'Daemon başlatılsın mı?';
        if (!confirm(confirmMsg)) return;
        
        console.log('Starting daemon, URL:', this.baseUrl + 'pages/ajax/monitor_action.php');
        
        fetch(this.baseUrl + 'pages/ajax/monitor_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({action: 'start'})
        })
        .then(response => {
            console.log('Response status:', response.status);
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                this.showAlert('success', data.message);
                setTimeout(() => location.reload(), 2000);
            } else {
                this.showAlert('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showAlert('error', 'Hata: ' + error.message);
        });
    },
    
    stopDaemon: function() {
        const confirmMsg = 'Daemon durdurulsun mu?';
        if (!confirm(confirmMsg)) return;
        
        fetch(this.baseUrl + 'pages/ajax/monitor_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({action: 'stop'})
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.showAlert('success', data.message);
                setTimeout(() => location.reload(), 2000);
            } else {
                this.showAlert('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showAlert('error', 'Hata: ' + error.message);
        });
    },
    
    runOnce: function() {
        this.showAlert('info', 'Monitoring çalıştırılıyor...');
        
        fetch(this.baseUrl + 'pages/ajax/monitor_action.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({action: 'run'})
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                this.showAlert('success', data.message);
                setTimeout(() => location.reload(), 2000);
            } else {
                this.showAlert('error', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            this.showAlert('error', 'Hata: ' + error.message);
        });
    },
    
    refreshStatus: function() {
        location.reload();
    },
    
    viewLogs: function() {
        window.open(this.baseUrl + 'logs/monitor_daemon.log', '_blank');
    },
    
    showAlert: function(type, message) {
        const alertTypes = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'info': 'alert-info',
            'warning': 'alert-warning'
        };
        
        const alertHtml = `
            <div class="alert ${alertTypes[type] || 'alert-info'} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        const container = document.querySelector('.container');
        if (container) {
            container.insertAdjacentHTML('afterbegin', alertHtml);
        } else {
            showInfo('Bilgi', message);
        }
    }
};

// Global fonksiyonlar (onclick için)
function startDaemon() {
    window.monitorStatus.startDaemon();
}

function stopDaemon() {
    window.monitorStatus.stopDaemon();
}

function runOnce() {
    window.monitorStatus.runOnce();
}

function refreshStatus() {
    window.monitorStatus.refreshStatus();
}

function viewLogs() {
    window.monitorStatus.viewLogs();
}

// Sayfa yüklendiğinde
document.addEventListener('DOMContentLoaded', function() {
    window.monitorStatus.init();
});

console.log('Monitor Status JS file loaded');

