<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/language.php';

requireLogin();

// Sayfa başlığı
$page_title = 'API Dokümantasyonu - Swagger UI';
$page_description = 'İnteraktif API dokümantasyonu ve test arayüzü';

// Header'ı dahil et
include __DIR__ . '/../../includes/layout/header.php';
?>

<div class="container-fluid p-0">
    <!-- Swagger UI Container -->
    <div id="swagger-ui"></div>
</div>

<!-- Swagger UI CSS -->
<link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css" />

<!-- Swagger UI JavaScript -->
<script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
<script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js"></script>

<script>
window.onload = function() {
    // Swagger UI konfigürasyonu
    const ui = SwaggerUIBundle({
        url: '<?= $base_url ?>api/swagger.json',
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIStandalonePreset
        ],
        plugins: [
            SwaggerUIBundle.plugins.DownloadUrl
        ],
        layout: "StandaloneLayout",
        validatorUrl: null,
        tryItOutEnabled: true,
        supportedSubmitMethods: ['get', 'post', 'put', 'delete', 'patch'],
        onComplete: function() {
            console.log('Swagger UI yüklendi');
        },
        onFailure: function(data) {
            console.error('Swagger UI yüklenemedi:', data);
            document.getElementById('swagger-ui').innerHTML = `
                <div class="alert alert-danger m-3">
                    <h4>API Dokümantasyonu Yüklenemedi</h4>
                    <p>Swagger UI yüklenirken bir hata oluştu. Lütfen sayfayı yenileyin veya sistem yöneticisi ile iletişime geçin.</p>
                    <p><strong>Hata:</strong> ${data.message || 'Bilinmeyen hata'}</p>
                </div>
            `;
        }
    });

    // Session cookie'sini otomatik ekle
    const sessionId = getCookie('PHPSESSID');
    if (sessionId) {
        ui.preauthorizeApiKey('sessionAuth', sessionId);
    }

    // Cookie okuma fonksiyonu
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
        return null;
    }

    // API isteklerine otomatik cookie ekleme
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        if (url.includes('/api/')) {
            if (!options.headers) options.headers = {};
            if (!options.credentials) options.credentials = 'include';
        }
        return originalFetch(url, options);
    };
};
</script>

<style>
/* Swagger UI özelleştirmeleri */
.swagger-ui .topbar {
    display: none;
}

.swagger-ui .info {
    margin: 20px 0;
}

.swagger-ui .scheme-container {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin: 20px 0;
}

.swagger-ui .opblock.opblock-get {
    border-color: #61affe;
    background: rgba(97, 175, 254, .1);
}

.swagger-ui .opblock.opblock-post {
    border-color: #49cc90;
    background: rgba(73, 204, 144, .1);
}

.swagger-ui .opblock.opblock-put {
    border-color: #fca130;
    background: rgba(252, 161, 48, .1);
}

.swagger-ui .opblock.opblock-delete {
    border-color: #f93e3e;
    background: rgba(249, 62, 62, .1);
}

/* Responsive düzenlemeler */
@media (max-width: 768px) {
    .swagger-ui .wrapper {
        padding: 10px;
    }
    
    .swagger-ui .opblock {
        margin: 10px 0;
    }
}

/* Custom header */
.api-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    text-align: center;
    margin-bottom: 0;
}

.api-header h1 {
    margin: 0;
    font-size: 2rem;
}

.api-header p {
    margin: 10px 0 0 0;
    opacity: 0.9;
}
</style>

<!-- Custom Header -->
<div class="api-header">
    <h1><i class="fas fa-code"></i> API Dokümantasyonu</h1>
    <p>İnteraktif API test arayüzü - Swagger UI</p>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
