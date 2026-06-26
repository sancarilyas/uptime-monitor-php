<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/language.php';

requireLogin();

// Sayfa başlığı
$page_title = 'API Dokümantasyonu';
$page_description = 'Uptime Monitor API kullanım kılavuzu';

// Header'ı dahil et
include __DIR__ . '/../../includes/layout/header.php';
?>

<div class="container p-3">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $base_url ?>dashboard">Dashboard</a></li>
            <li class="breadcrumb-item active">API Dokümantasyonu</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-book"></i> API Dokümantasyonu</h5>
                </div>
                <div class="card-body">
                    <!-- API Genel Bilgiler -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="alert alert-success">
                                <i class="fas fa-rocket"></i>
                                <strong>API Base URL:</strong><br>
                                <code><?= $base_url ?>api/</code>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                <strong>API Versiyonu:</strong><br>
                                <code>v1.0.0</code>
                            </div>
                        </div>
                    </div>

                    <!-- Hızlı Başlangıç -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-play-circle"></i> Hızlı Başlangıç</h5>
                        </div>
                        <div class="card-body">
                            <p>API'yi kullanmaya başlamak için aşağıdaki adımları takip edin:</p>
                            <ol>
                                <li><strong>Giriş Yapın:</strong> Web arayüzünden sisteme giriş yapın</li>
                                <li><strong>Session ID Alın:</strong> Tarayıcı geliştirici araçlarından PHPSESSID cookie'sini kopyalayın</li>
                                <li><strong>API İsteği Gönderin:</strong> Aşağıdaki örnekleri kullanarak API'ye istek gönderin</li>
                            </ol>
                            
                            <div class="mt-3">
                                <h6>Örnek: Tüm Siteleri Getir</h6>
                                <pre><code>curl -X GET "<?= $base_url ?>api/sites" \
  -H "Content-Type: application/json" \
  -b "PHPSESSID=your_session_id"</code></pre>
                            </div>
                        </div>
                    </div>

                    <!-- API Authentication -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Kimlik Doğrulama</h5>
                        </div>
                        <div class="card-body">
                            <p>API'ye erişim için <strong>session tabanlı kimlik doğrulama</strong> kullanılır. Bu yöntem güvenli ve kullanıcı dostudur.</p>
                            
                            <h6>🔑 Gerekli Headers:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Header</th>
                                            <th>Değer</th>
                                            <th>Açıklama</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>Content-Type</code></td>
                                            <td><code>application/json</code></td>
                                            <td>JSON formatında veri gönderildiğini belirtir</td>
                                        </tr>
                                        <tr>
                                            <td><code>Cookie</code></td>
                                            <td><code>PHPSESSID=session_id</code></td>
                                            <td>Session kimlik doğrulaması için gerekli</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Önemli:</strong> Session ID'nizi kimseyle paylaşmayın. Bu, hesabınıza tam erişim sağlar.
                            </div>
                        </div>
                    </div>

                    <!-- API Endpoints -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-list"></i> API Endpoints</h5>
                        </div>
                        <div class="card-body">
                            <p>Aşağıda mevcut tüm API endpoint'leri ve kullanım örnekleri bulunmaktadır.</p>
                        </div>
                    </div>

                    <!-- Get All Sites -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <span class="badge bg-success me-2">GET</span>
                                Tüm Siteleri Getir
                            </h6>
                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#getAllSites">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                        <div class="collapse show" id="getAllSites">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>📋 Endpoint Bilgileri</h6>
                                        <p><strong>URL:</strong> <code><?= $base_url ?>api/sites</code></p>
                                        <p><strong>Method:</strong> <span class="badge bg-success">GET</span></p>
                                        <p><strong>Açıklama:</strong> Kullanıcının tüm sitelerini ve grup sitelerini döndürür.</p>
                                        
                                        <h6 class="mt-3">🔐 Yetki Gereksinimleri</h6>
                                        <ul>
                                            <li>Giriş yapmış kullanıcı</li>
                                            <li>Kendi siteleri + grup siteleri</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>📤 Örnek İstek</h6>
                                        <pre><code>curl -X GET "<?= $base_url ?>api/sites" \
  -H "Content-Type: application/json" \
  -b "PHPSESSID=your_session_id"</code></pre>
                                        
                                        <h6 class="mt-3">📥 Başarılı Yanıt</h6>
                                        <pre><code>{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Örnek Site",
            "url": "https://example.com",
            "status": "active",
            "last_status": "up",
            "response_time": 250,
            "group_name": "Grup Adı",
            "created_at": "2024-01-01 12:00:00"
        }
    ]
}</code></pre>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <h6>📊 Yanıt Alanları</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Alan</th>
                                                    <th>Tip</th>
                                                    <th>Açıklama</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td><code>id</code></td>
                                                    <td>integer</td>
                                                    <td>Site benzersiz kimliği</td>
                                                </tr>
                                                <tr>
                                                    <td><code>name</code></td>
                                                    <td>string</td>
                                                    <td>Site adı</td>
                                                </tr>
                                                <tr>
                                                    <td><code>url</code></td>
                                                    <td>string</td>
                                                    <td>Site URL'si</td>
                                                </tr>
                                                <tr>
                                                    <td><code>last_status</code></td>
                                                    <td>string</td>
                                                    <td>Son durum (up/down)</td>
                                                </tr>
                                                <tr>
                                                    <td><code>response_time</code></td>
                                                    <td>integer</td>
                                                    <td>Yanıt süresi (ms)</td>
                                                </tr>
                                                <tr>
                                                    <td><code>group_name</code></td>
                                                    <td>string</td>
                                                    <td>Grup adı (varsa)</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Get Site Details -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <span class="badge bg-success me-2">GET</span>
                                Site Detayları
                            </h6>
                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#getSiteDetails">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                        <div class="collapse" id="getSiteDetails">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>📋 Endpoint Bilgileri</h6>
                                        <p><strong>URL:</strong> <code><?= $base_url ?>api/sites/{id}</code></p>
                                        <p><strong>Method:</strong> <span class="badge bg-success">GET</span></p>
                                        <p><strong>Açıklama:</strong> Belirli bir site için detaylı bilgileri döndürür.</p>
                                        
                                        <h6 class="mt-3">📝 Parametreler</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Parametre</th>
                                                        <th>Tip</th>
                                                        <th>Gerekli</th>
                                                        <th>Açıklama</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><code>id</code></td>
                                                        <td>integer</td>
                                                        <td><span class="badge bg-danger">Evet</span></td>
                                                        <td>Site benzersiz kimliği</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>📤 Örnek İstek</h6>
                                        <pre><code>curl -X GET "<?= $base_url ?>api/sites/1" \
  -H "Content-Type: application/json" \
  -b "PHPSESSID=your_session_id"</code></pre>
                                        
                                        <h6 class="mt-3">📥 Başarılı Yanıt</h6>
                                        <pre><code>{
    "success": true,
    "data": {
        "id": 1,
        "name": "Örnek Site",
        "url": "https://example.com",
        "monitor_path": "",
        "description": "Site açıklaması",
        "status": "active",
        "last_status": "up",
        "response_time": 250,
        "group_name": "Grup Adı",
        "notification_emails": "admin@example.com",
        "created_at": "2024-01-01 12:00:00"
    }
}</code></pre>
                                        
                                        <h6 class="mt-3">❌ Hata Yanıtı</h6>
                                        <pre><code>{
    "success": false,
    "message": "Site bulunamadı",
    "error_code": "NOT_FOUND"
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Get Site Logs -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <span class="badge bg-success me-2">GET</span>
                                Site Logları
                            </h6>
                        </div>
                        <div class="card-body">
                            <p><strong>URL:</strong> <code><?= $base_url ?>api/sites/{id}/logs</code></p>
                            <p><strong>Açıklama:</strong> Belirli bir site için uptime loglarını döndürür.</p>
                            
                            <h6>Parameters:</h6>
                            <ul>
                                <li><code>id</code> - Site ID (gerekli)</li>
                                <li><code>limit</code> - Kayıt sayısı (varsayılan: 50)</li>
                                <li><code>offset</code> - Başlangıç pozisyonu (varsayılan: 0)</li>
                            </ul>
                            
                            <h6>Response:</h6>
                            <pre><code>{
    "success": true,
    "data": [
        {
            "id": 1,
            "site_id": 1,
            "status": "up",
            "response_time": 250,
            "timestamp": "2024-01-01 12:00:00"
        }
    ],
    "total": 100
}</code></pre>
                        </div>
                    </div>

                    <!-- Add Site -->
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">
                                <span class="badge bg-primary me-2">POST</span>
                                Site Ekle
                            </h6>
                            <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addSite">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                        </div>
                        <div class="collapse" id="addSite">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>📋 Endpoint Bilgileri</h6>
                                        <p><strong>URL:</strong> <code><?= $base_url ?>api/sites</code></p>
                                        <p><strong>Method:</strong> <span class="badge bg-primary">POST</span></p>
                                        <p><strong>Açıklama:</strong> Yeni bir site ekler ve izlemeye başlar.</p>
                                        
                                        <h6 class="mt-3">📝 Request Body</h6>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Alan</th>
                                                        <th>Tip</th>
                                                        <th>Gerekli</th>
                                                        <th>Açıklama</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><code>name</code></td>
                                                        <td>string</td>
                                                        <td><span class="badge bg-danger">Evet</span></td>
                                                        <td>Site adı</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>url</code></td>
                                                        <td>string</td>
                                                        <td><span class="badge bg-danger">Evet</span></td>
                                                        <td>Site URL'si (http/https)</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>monitor_path</code></td>
                                                        <td>string</td>
                                                        <td><span class="badge bg-secondary">Hayır</span></td>
                                                        <td>İzlenecek özel yol</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>description</code></td>
                                                        <td>string</td>
                                                        <td><span class="badge bg-secondary">Hayır</span></td>
                                                        <td>Site açıklaması</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>group_id</code></td>
                                                        <td>integer</td>
                                                        <td><span class="badge bg-secondary">Hayır</span></td>
                                                        <td>Grup ID'si</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>notification_emails</code></td>
                                                        <td>string</td>
                                                        <td><span class="badge bg-secondary">Hayır</span></td>
                                                        <td>Bildirim e-postaları</td>
                                                    </tr>
                                                    <tr>
                                                        <td><code>check_interval</code></td>
                                                        <td>integer</td>
                                                        <td><span class="badge bg-secondary">Hayır</span></td>
                                                        <td>Kontrol aralığı (saniye)</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>📤 Örnek İstek</h6>
                                        <pre><code>curl -X POST "<?= $base_url ?>api/sites" \
  -H "Content-Type: application/json" \
  -b "PHPSESSID=your_session_id" \
  -d '{
    "name": "Yeni Site",
    "url": "https://newsite.com",
    "monitor_path": "api/health",
    "description": "Site açıklaması",
    "group_id": 1,
    "notification_emails": "admin@example.com",
    "check_interval": 60
}'</code></pre>
                                        
                                        <h6 class="mt-3">📥 Başarılı Yanıt</h6>
                                        <pre><code>{
    "success": true,
    "message": "Site başarıyla eklendi",
    "data": {
        "id": 2,
        "name": "Yeni Site",
        "url": "https://newsite.com"
    }
}</code></pre>
                                        
                                        <h6 class="mt-3">❌ Hata Yanıtı</h6>
                                        <pre><code>{
    "success": false,
    "message": "name alanı gerekli",
    "error_code": "VALIDATION_ERROR"
}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Update Site -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <span class="badge bg-warning me-2">PUT</span>
                                Site Güncelle
                            </h6>
                        </div>
                        <div class="card-body">
                            <p><strong>URL:</strong> <code><?= $base_url ?>api/sites/{id}</code></p>
                            <p><strong>Açıklama:</strong> Mevcut bir siteyi günceller.</p>
                            
                            <h6>Parameters:</h6>
                            <ul>
                                <li><code>id</code> - Site ID (gerekli)</li>
                            </ul>
                            
                            <h6>Request Body:</h6>
                            <pre><code>{
    "name": "Güncellenmiş Site",
    "url": "https://updatedsite.com",
    "description": "Güncellenmiş açıklama"
}</code></pre>
                            
                            <h6>Response:</h6>
                            <pre><code>{
    "success": true,
    "message": "Site başarıyla güncellendi"
}</code></pre>
                        </div>
                    </div>

                    <!-- Delete Site -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <span class="badge bg-danger me-2">DELETE</span>
                                Site Sil
                            </h6>
                        </div>
                        <div class="card-body">
                            <p><strong>URL:</strong> <code><?= $base_url ?>api/sites/{id}</code></p>
                            <p><strong>Açıklama:</strong> Bir siteyi siler.</p>
                            
                            <h6>Parameters:</h6>
                            <ul>
                                <li><code>id</code> - Site ID (gerekli)</li>
                            </ul>
                            
                            <h6>Response:</h6>
                            <pre><code>{
    "success": true,
    "message": "Site başarıyla silindi"
}</code></pre>
                        </div>
                    </div>

                    <!-- Manual Check -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <span class="badge bg-info me-2">POST</span>
                                Manuel Kontrol
                            </h6>
                        </div>
                        <div class="card-body">
                            <p><strong>URL:</strong> <code><?= $base_url ?>api/sites/{id}/check</code></p>
                            <p><strong>Açıklama:</strong> Belirli bir site için manuel uptime kontrolü yapar.</p>
                            
                            <h6>Parameters:</h6>
                            <ul>
                                <li><code>id</code> - Site ID (gerekli)</li>
                            </ul>
                            
                            <h6>Response:</h6>
                            <pre><code>{
    "success": true,
    "message": "Kontrol tamamlandı",
    "data": {
        "status": "up",
        "response_time": 250,
        "timestamp": "2024-01-01 12:00:00"
    }
}</code></pre>
                        </div>
                    </div>

                    <!-- Error Responses -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Hata Yanıtları</h5>
                        </div>
                        <div class="card-body">
                            <p>API, standart HTTP status kodları ve JSON formatında hata mesajları döndürür.</p>
                            
                            <h6>📋 Genel Hata Formatı:</h6>
                            <pre><code>{
    "success": false,
    "message": "Hata mesajı",
    "error_code": "ERROR_CODE"
}</code></pre>
                            
                            <h6 class="mt-4">🔢 HTTP Status Kodları:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Kod</th>
                                            <th>Açıklama</th>
                                            <th>Kullanım</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>200</code></td>
                                            <td>OK</td>
                                            <td>Başarılı işlem</td>
                                        </tr>
                                        <tr>
                                            <td><code>400</code></td>
                                            <td>Bad Request</td>
                                            <td>Geçersiz istek</td>
                                        </tr>
                                        <tr>
                                            <td><code>401</code></td>
                                            <td>Unauthorized</td>
                                            <td>Kimlik doğrulama gerekli</td>
                                        </tr>
                                        <tr>
                                            <td><code>403</code></td>
                                            <td>Forbidden</td>
                                            <td>Erişim yetkisi yok</td>
                                        </tr>
                                        <tr>
                                            <td><code>404</code></td>
                                            <td>Not Found</td>
                                            <td>Kaynak bulunamadı</td>
                                        </tr>
                                        <tr>
                                            <td><code>500</code></td>
                                            <td>Internal Server Error</td>
                                            <td>Sunucu hatası</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            
                            <h6 class="mt-4">🚨 Yaygın Hata Kodları:</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Hata Kodu</th>
                                            <th>Açıklama</th>
                                            <th>HTTP Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><code>UNAUTHORIZED</code></td>
                                            <td>Kimlik doğrulama gerekli</td>
                                            <td>401</td>
                                        </tr>
                                        <tr>
                                            <td><code>FORBIDDEN</code></td>
                                            <td>Erişim yetkisi yok</td>
                                            <td>403</td>
                                        </tr>
                                        <tr>
                                            <td><code>NOT_FOUND</code></td>
                                            <td>Kaynak bulunamadı</td>
                                            <td>404</td>
                                        </tr>
                                        <tr>
                                            <td><code>VALIDATION_ERROR</code></td>
                                            <td>Veri doğrulama hatası</td>
                                            <td>400</td>
                                        </tr>
                                        <tr>
                                            <td><code>REQUEST_ERROR</code></td>
                                            <td>Geçersiz istek</td>
                                            <td>400</td>
                                        </tr>
                                        <tr>
                                            <td><code>SERVER_ERROR</code></td>
                                            <td>Sunucu hatası</td>
                                            <td>500</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Rate Limiting -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-tachometer-alt"></i> Rate Limiting</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Önemli:</strong> API'ye aşırı yük getirmemek için rate limiting uygulanmaktadır.
                            </div>
                            
                            <h6>📊 Limitler:</h6>
                            <ul>
                                <li><strong>Genel Limit:</strong> Dakikada 100 istek</li>
                                <li><strong>Manuel Kontrol:</strong> Dakikada 10 istek</li>
                                <li><strong>Site Ekleme:</strong> Dakikada 5 istek</li>
                            </ul>
                            
                            <h6 class="mt-3">⚠️ Limit Aşımı:</h6>
                            <p>Rate limit aşıldığında <code>429 Too Many Requests</code> hatası döner:</p>
                            <pre><code>{
    "success": false,
    "message": "Rate limit aşıldı. Lütfen daha sonra tekrar deneyin.",
    "error_code": "RATE_LIMIT_EXCEEDED",
    "retry_after": 60
}</code></pre>
                        </div>
                    </div>

                    <!-- Examples -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-code"></i> Örnek Kullanım</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>🌐 JavaScript (Fetch API):</h6>
                                    <pre><code>// Tüm siteleri getir
fetch('<?= $base_url ?>api/sites', {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json'
    },
    credentials: 'include'
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Siteler:', data.data);
    } else {
        console.error('Hata:', data.message);
    }
});

// Yeni site ekle
fetch('<?= $base_url ?>api/sites', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    credentials: 'include',
    body: JSON.stringify({
        name: 'Yeni Site',
        url: 'https://example.com',
        description: 'Test sitesi'
    })
})
.then(response => response.json())
.then(data => {
    console.log('Sonuç:', data);
});</code></pre>
                                </div>
                                <div class="col-md-6">
                                    <h6>🔧 cURL:</h6>
                                    <pre><code># Tüm siteleri getir
curl -X GET "<?= $base_url ?>api/sites" \
  -H "Content-Type: application/json" \
  -b "PHPSESSID=your_session_id"

# Yeni site ekle
curl -X POST "<?= $base_url ?>api/sites" \
  -H "Content-Type: application/json" \
  -b "PHPSESSID=your_session_id" \
  -d '{
    "name": "Yeni Site",
    "url": "https://example.com",
    "description": "Test sitesi"
  }'</code></pre>
                                    
                                    <h6 class="mt-3">🐍 Python (requests):</h6>
                                    <pre><code>import requests

# Session ID'nizi buraya yazın
session_id = "your_session_id"

# Tüm siteleri getir
response = requests.get(
    '<?= $base_url ?>api/sites',
    headers={'Content-Type': 'application/json'},
    cookies={'PHPSESSID': session_id}
)

data = response.json()
if data['success']:
    print('Siteler:', data['data'])
else:
    print('Hata:', data['message'])</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Best Practices -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-star"></i> En İyi Uygulamalar</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>✅ Yapılması Gerekenler:</h6>
                                    <ul>
                                        <li>Her zaman <code>Content-Type: application/json</code> header'ını kullanın</li>
                                        <li>Session ID'nizi güvenli saklayın</li>
                                        <li>Hata yanıtlarını kontrol edin</li>
                                        <li>Rate limit'leri dikkate alın</li>
                                        <li>Gerekli alanları doğrulayın</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>❌ Yapılmaması Gerekenler:</h6>
                                    <ul>
                                        <li>Session ID'nizi URL'de paylaşmayın</li>
                                        <li>Aşırı sık istek göndermeyin</li>
                                        <li>Geçersiz veri göndermeyin</li>
                                        <li>Hata yanıtlarını görmezden gelmeyin</li>
                                        <li>Production'da debug bilgilerini göstermeyin</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Support -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-life-ring"></i> Destek ve İletişim</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>📞 İletişim:</h6>
                                    <ul>
                                        <li><strong>Teknik Destek:</strong> Sistem yöneticisi ile iletişime geçin</li>
                                        <li><strong>Hata Bildirimi:</strong> API hatalarını detaylı şekilde raporlayın</li>
                                        <li><strong>Özellik İsteği:</strong> Yeni endpoint önerilerinizi paylaşın</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>📚 Kaynaklar:</h6>
                                    <ul>
                                        <li><strong>API Versiyonu:</strong> v1.0.0</li>
                                        <li><strong>Son Güncelleme:</strong> <?= date('Y-m-d') ?></li>
                                        <li><strong>Dokümantasyon:</strong> Bu sayfa</li>
                                        <li><strong>Test Endpoint:</strong> <code><?= $base_url ?>api/</code></li>
                                    </ul>
                                </div>
                            </div>
                            
                            <div class="alert alert-success mt-3">
                                <i class="fas fa-check-circle"></i>
                                <strong>Başarılı Kullanım:</strong> API'yi doğru şekilde kullanarak güvenli ve verimli entegrasyonlar oluşturabilirsiniz.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/layout/footer.php'; ?>
