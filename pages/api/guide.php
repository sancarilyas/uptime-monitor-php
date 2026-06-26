<?php
// Base URL'yi tanımla
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . '/';
$base_url = str_replace('/pages/api', '', $base_url);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uptime Monitor API Dokümantasyonu</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #2980b9;
            margin-top: 30px;
        }
        .endpoint {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .method {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            color: white;
            font-weight: bold;
            margin-right: 10px;
        }
        .post { background-color: #27ae60; }
        .get { background-color: #2980b9; }
        .put { background-color: #f39c12; }
        .delete { background-color: #e74c3c; }
        code {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 6px;
            display: block;
            overflow-x: auto;
            margin: 15px 0;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
            border: 1px solid #333;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        /* Syntax highlighting */
        code .comment {
            color: #6a9955;
            font-style: italic;
        }
        
        code .string {
            color: #ce9178;
        }
        
        code .keyword {
            color: #569cd6;
            font-weight: bold;
        }
        
        code .function {
            color: #dcdcaa;
        }
        
        code .number {
            color: #b5cea8;
        }
        
        code .method {
            color: #4ec9b0;
        }
        
        code .url {
            color: #9cdcfe;
        }
        
        code .header {
            color: #d7ba7d;
        }
        
        code .json-key {
            color: #9cdcfe;
        }
        
        code .json-value {
            color: #ce9178;
        }
        
        code .json-string {
            color: #ce9178;
        }
        
        code .json-number {
            color: #b5cea8;
        }
        
        code .json-boolean {
            color: #569cd6;
        }
        
        /* Code editor style */
        .code-editor {
            background: #1e1e1e;
            border: 1px solid #333;
            border-radius: 6px;
            overflow: hidden;
            margin: 15px 0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .code-header {
            background: #2d2d30;
            padding: 8px 15px;
            border-bottom: 1px solid #333;
            font-size: 12px;
            color: #cccccc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .code-content {
            position: relative;
            overflow-x: auto;
        }
        
        .line-numbers {
            background: #252526;
            color: #858585;
            padding: 15px 10px;
            border-right: 1px solid #333;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
            text-align: right;
            user-select: none;
            position: absolute;
            left: 0;
            top: 0;
            width: 40px;
        }
        
        .code-lines {
            margin-left: 50px;
            padding: 15px 20px;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.5;
            color: #d4d4d4;
            background: #1e1e1e;
        }
        
        .code-lines .line {
            display: block;
            min-height: 21px;
        }
        
        .code-lines .comment {
            color: #6a9955;
            font-style: italic;
        }
        
        .code-lines .keyword {
            color: #569cd6;
            font-weight: bold;
        }
        
        .code-lines .string {
            color: #ce9178;
        }
        
        .code-lines .function {
            color: #dcdcaa;
        }
        
        .code-lines .number {
            color: #b5cea8;
        }
        
        .code-lines .method {
            color: #4ec9b0;
        }
        
        .code-lines .url {
            color: #9cdcfe;
        }
        
        .code-lines .variable {
            color: #9cdcfe;
        }
        
        .code-lines .operator {
            color: #d4d4d4;
        }
        
        .code-lines .bracket {
            color: #ffd700;
        }
        
        /* Collapsible code blocks */
        .code-editor {
            margin: 15px 0;
        }
        
        .code-header {
            cursor: pointer;
            user-select: none;
            transition: background-color 0.2s ease;
            position: relative;
        }
        
        .code-header:hover {
            background: #3c3c3c;
        }
        
        .code-header::after {
            content: '▼';
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            transition: transform 0.3s ease;
            color: #cccccc;
            font-size: 12px;
        }
        
        .code-editor.collapsed .code-header::after {
            transform: translateY(-50%) rotate(-90deg);
        }
        
        .code-content {
            overflow: hidden;
            transition: max-height 0.3s ease-out;
            max-height: 2000px;
        }
        
        .code-editor.collapsed .code-content {
            max-height: 0;
        }
        .note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Uptime Monitor API Dokümantasyonu</h1>
        
        <div class="note">
            <strong>Not:</strong> Tüm API istekleri için aşağıdaki header'lar gereklidir:
            <ul>
                <li><code>Content-Type: application/json</code></li>
                <li><code>Authorization: Bearer your_token_here</code> (auth endpoint'leri hariç)</li>
            </ul>
        </div>

        <h2>1. Kimlik Doğrulama (Authentication)</h2>
        <p>API'yi kullanmak için önce email ve şifrenizle token almanız gerekmektedir.</p>

        <div class="endpoint">
            <span class="method post">POST</span> <strong>/api/auth/login</strong>
            <h3>Token Alma</h3>
            <p>Email ve şifrenizle giriş yaparak API token'ı alın.</p>
            <p>İstek örneği:</p>
            <code>
POST <?= $base_url ?>api/auth/login
Content-Type: application/json

{
    "email": "your_email@example.com",
    "password": "your_password"
}
            </code>
            <p>Başarılı yanıt:</p>
            <code>
{
    "success": true,
    "message": "Giriş başarılı",
    "data": {
        "token": "a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6",
        "expires_at": "2024-01-02 12:00:00",
        "user": {
            "id": 1,
            "email": "your_email@example.com",
            "role": "admin",
            "group_id": 1,
            "first_name": "Ad",
            "last_name": "Soyad"
        }
    }
}
            </code>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span> <strong>/api/auth/verify</strong>
            <h3>Token Doğrulama</h3>
            <p>Token'ınızın geçerli olup olmadığını kontrol edin.</p>
            <p>İstek örneği:</p>
            <code>
GET <?= $base_url ?>api/auth/verify
Authorization: Bearer your_token_here
            </code>
            <p>Başarılı yanıt:</p>
            <code>
{
    "success": true,
    "message": "Token geçerli",
    "data": {
        "user": {
            "id": 1,
            "email": "your_email@example.com",
            "role": "admin",
            "group_id": 1,
            "first_name": "Ad",
            "last_name": "Soyad"
        },
        "token": {
            "expires_at": "2024-01-02 12:00:00",
            "created_at": "2024-01-01 12:00:00"
        }
    }
}
            </code>
        </div>

        <div class="note">
            <strong>Önemli Notlar:</strong>
            <ul>
                <li>Token 24 saat geçerlidir</li>
                <li>Token'ı güvenli saklayın, başkalarıyla paylaşmayın</li>
                <li>Token süresi dolduğunda yeniden login olmanız gerekir</li>
                <li>Her login'de yeni token alırsınız</li>
            </ul>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span> <strong>/api/</strong>
            <h3>API Genel Bilgileri</h3>
            <p>API hakkında genel bilgileri döndürür. Token gerektirmez.</p>
            <p>İstek örneği:</p>
            <code>
GET <?= $base_url ?>api/
Content-Type: application/json
            </code>
            <p>Başarılı yanıt:</p>
            <code>
{
    "success": true,
    "message": "Uptime Monitor API",
    "version": "1.0.0",
    "endpoints": {
        "POST /api/auth/login": "Token al (email, password)",
        "GET /api/auth/verify": "Token doğrula",
        "GET /api/sites": "Tüm siteleri getir"
    }
}
            </code>
        </div>

        <h2>2. Site Yönetimi</h2>
        
        <div class="endpoint">
            <span class="method get">GET</span> <strong>/api/sites</strong>
            <h3>Tüm Siteleri Getir</h3>
            <p>Kullanıcının tüm sitelerini ve grup sitelerini döndürür. Token gerektirir.</p>
            <p>İstek örneği:</p>
            <code>
GET <?= $base_url ?>api/sites
Content-Type: application/json
Authorization: Bearer your_token_here
            </code>
            <p>Başarılı yanıt:</p>
            <code>
{
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
}
            </code>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span> <strong>/api/sites/{id}</strong>
            <h3>Site Detayları</h3>
            <p>Belirli bir site için detaylı bilgileri döndürür.</p>
            <p>İstek örneği:</p>
            <code>
GET <?= $base_url ?>api/sites/1
Content-Type: application/json
Authorization: Bearer your_token_here
            </code>
            <p>Başarılı yanıt:</p>
            <code>
{
    "success": true,
    "data": {
        "id": 1,
        "name": "Örnek Site",
        "url": "https://example.com",
        "monitor_path": "api/health",
        "description": "Site açıklaması",
        "status": "active",
        "last_status": "up",
        "response_time": 250,
        "group_name": "Grup Adı",
        "notification_emails": "admin@example.com",
        "created_at": "2024-01-01 12:00:00"
    }
}
            </code>
        </div>

        <div class="endpoint">
            <span class="method post">POST</span> <strong>/api/sites</strong>
            <h3>Yeni Site Ekle</h3>
            <p>Yeni bir site ekler ve izlemeye başlar.</p>
            <p>İstek örneği:</p>
            <code>
POST <?= $base_url ?>api/sites
Content-Type: application/json
Authorization: Bearer your_token_here

{
    "name": "Yeni Site",
    "url": "https://newsite.com",
    "monitor_path": "api/health",
    "description": "Site açıklaması",
    "group_id": 1,
    "notification_emails": "admin@example.com",
    "check_interval": 60
}
            </code>
            <p>Başarılı yanıt:</p>
            <code>
{
    "success": true,
    "message": "Site başarıyla eklendi",
    "data": {
        "id": 2,
        "name": "Yeni Site",
        "url": "https://newsite.com"
    }
}
            </code>
            
            <h4>Request Body Parametreleri</h4>
            <table>
                <tr>
                    <th>Alan</th>
                    <th>Tip</th>
                    <th>Gerekli</th>
                    <th>Açıklama</th>
                </tr>
                <tr>
                    <td>name</td>
                    <td>string</td>
                    <td>Evet</td>
                    <td>Site adı</td>
                </tr>
                <tr>
                    <td>url</td>
                    <td>string</td>
                    <td>Evet</td>
                    <td>Site URL'si (http/https)</td>
                </tr>
                <tr>
                    <td>monitor_path</td>
                    <td>string</td>
                    <td>Hayır</td>
                    <td>İzlenecek özel yol</td>
                </tr>
                <tr>
                    <td>description</td>
                    <td>string</td>
                    <td>Hayır</td>
                    <td>Site açıklaması</td>
                </tr>
                <tr>
                    <td>group_id</td>
                    <td>integer</td>
                    <td>Hayır</td>
                    <td>Grup ID'si</td>
                </tr>
                <tr>
                    <td>notification_emails</td>
                    <td>string</td>
                    <td>Hayır</td>
                    <td>Bildirim e-postaları</td>
                </tr>
                <tr>
                    <td>check_interval</td>
                    <td>integer</td>
                    <td>Hayır</td>
                    <td>Kontrol aralığı (saniye)</td>
                </tr>
            </table>
        </div>

        <div class="endpoint">
            <span class="method put">PUT</span> <strong>/api/sites/{id}</strong>
            <h3>Site Güncelle</h3>
            <p>Mevcut bir siteyi günceller. Sadece site sahibi veya grup yöneticisi güncelleyebilir.</p>
            <p>İstek örneği:</p>
            <code>
PUT /api/sites/1
Content-Type: application/json
Cookie: PHPSESSID=your_session_id

{
    "name": "Güncellenmiş Site",
    "description": "Yeni açıklama",
    "check_interval": 120
}
            </code>
            <p>Başarılı yanıt:</p>
            <code>
{
    "success": true,
    "message": "Site başarıyla güncellendi"
}
            </code>
        </div>

        <div class="endpoint">
            <span class="method delete">DELETE</span> <strong>/api/sites/{id}</strong>
            <h3>Site Sil</h3>
            <p>Bir siteyi siler. Sadece site sahibi silebilir.</p>
            <p>İstek örneği:</p>
            <code>
DELETE /api/sites/1
Content-Type: application/json
Cookie: PHPSESSID=your_session_id
            </code>
            <p>Başarılı yanıt:</p>
            <code>
{
    "success": true,
    "message": "Site başarıyla silindi"
}
            </code>
        </div>

        <div class="endpoint">
            <span class="method get">GET</span> <strong>/api/sites/{id}/logs</strong>
            <h3>Site Logları</h3>
            <p>Belirli bir site için uptime loglarını döndürür.</p>
            <p>İstek örneği:</p>
            <code>
GET /api/sites/1/logs?limit=20&offset=0
Content-Type: application/json
Cookie: PHPSESSID=your_session_id
            </code>
            <p>Başarılı yanıt:</p>
            <code>
{
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
    "total": 100,
    "limit": 20,
    "offset": 0
}
            </code>
            
            <h4>Query Parametreleri</h4>
            <table>
                <tr>
                    <th>Parametre</th>
                    <th>Tip</th>
                    <th>Gerekli</th>
                    <th>Açıklama</th>
                </tr>
                <tr>
                    <td>limit</td>
                    <td>integer</td>
                    <td>Hayır</td>
                    <td>Kayıt sayısı (varsayılan: 50, maksimum: 100)</td>
                </tr>
                <tr>
                    <td>offset</td>
                    <td>integer</td>
                    <td>Hayır</td>
                    <td>Başlangıç pozisyonu (varsayılan: 0)</td>
                </tr>
            </table>
        </div>

        <div class="endpoint">
            <span class="method post">POST</span> <strong>/api/sites/{id}/check</strong>
            <h3>Manuel Kontrol</h3>
            <p>Belirli bir site için manuel uptime kontrolü yapar.</p>
            <p>İstek örneği:</p>
            <code>
POST /api/sites/1/check
Content-Type: application/json
Cookie: PHPSESSID=your_session_id
            </code>
            <p>Başarılı yanıt:</p>
            <code>
{
    "success": true,
    "message": "Kontrol tamamlandı",
    "data": {
        "status": "up",
        "response_time": 250,
        "http_code": 200,
        "timestamp": "2024-01-01 12:00:00"
    }
}
            </code>
        </div>

        <h2>3. Hata Kodları</h2>
        <table>
            <tr>
                <th>HTTP Kodu</th>
                <th>Durum</th>
                <th>Açıklama</th>
            </tr>
            <tr>
                <td>200</td>
                <td>OK</td>
                <td>Başarılı işlem</td>
            </tr>
            <tr>
                <td>400</td>
                <td>VALIDATION_ERROR</td>
                <td>Veri doğrulama hatası</td>
            </tr>
            <tr>
                <td>401</td>
                <td>UNAUTHORIZED</td>
                <td>Kimlik doğrulama gerekli</td>
            </tr>
            <tr>
                <td>403</td>
                <td>FORBIDDEN</td>
                <td>Erişim yetkisi yok</td>
            </tr>
            <tr>
                <td>404</td>
                <td>NOT_FOUND</td>
                <td>Kaynak bulunamadı</td>
            </tr>
            <tr>
                <td>500</td>
                <td>SERVER_ERROR</td>
                <td>Sunucu hatası</td>
            </tr>
        </table>

        <h2>4. Önemli Notlar</h2>
        <ul>
            <li>Tüm endpoint'ler için base URL: <code><?= $base_url ?>api/</code></li>
            <li>Token tabanlı kimlik doğrulama kullanılır</li>
            <li>Bearer token gereklidir (login yaptıktan sonra alınır)</li>
            <li>Token 24 saat geçerlidir</li>
            <li>Grup yöneticileri grup sitelerini düzenleyebilir</li>
            <li>Rate limiting uygulanmaktadır (dakikada 100 istek)</li>
            <li>Tüm tarih değerleri Türkiye saatine (UTC+3) göredir</li>
        </ul>

        <h2>5. Hızlı Başlangıç Örneği</h2>
        <p>Aşağıdaki örnek, API'yi nasıl kullanacağınızı gösterir:</p>

        <h3>JavaScript ile Kullanım</h3>
        <div class="code-editor collapsed" onclick="toggleCodeBlock(this)">
            <div class="code-header">
                <i class="fab fa-js-square"></i> JavaScript - API Kullanım Örneği
            </div>
            <div class="code-content">
                <div class="line-numbers">
                    1<br>2<br>3<br>4<br>5<br>6<br>7<br>8<br>9<br>10<br>11<br>12<br>13<br>14<br>15<br>16<br>17<br>18<br>19<br>20<br>21<br>22<br>23<br>24<br>25<br>26<br>27<br>28<br>29<br>30<br>31<br>32<br>33<br>34<br>35<br>36<br>37<br>38<br>39<br>40<br>41<br>42<br>43<br>44<br>45<br>46<br>47<br>48<br>49<br>50<br>51<br>52<br>53<br>54<br>55<br>56<br>57<br>58<br>59<br>60
                </div>
                <div class="code-lines">
                    <div class="line"><span class="comment">// 1. API'ye giriş yaparak token alın</span></div>
                    <div class="line"><span class="keyword">const</span> <span class="variable">loginResponse</span> <span class="operator">=</span> <span class="keyword">await</span> <span class="function">fetch</span>(<span class="string">'<?= $base_url ?>api/auth/login'</span>, <span class="bracket">{</span></div>
                    <div class="line">    <span class="variable">method</span>: <span class="string">'POST'</span>,</div>
                    <div class="line">    <span class="variable">headers</span>: <span class="bracket">{</span></div>
                    <div class="line">        <span class="string">'Content-Type'</span>: <span class="string">'application/json'</span></div>
                    <div class="line">    <span class="bracket">}</span>,</div>
                    <div class="line">    <span class="variable">body</span>: <span class="function">JSON.stringify</span>(<span class="bracket">{</span></div>
                    <div class="line">        <span class="variable">email</span>: <span class="string">'your_email@example.com'</span>,</div>
                    <div class="line">        <span class="variable">password</span>: <span class="string">'your_password'</span></div>
                    <div class="line">    <span class="bracket">}</span>)</div>
                    <div class="line"><span class="bracket">}</span>);</div>
                    <div class="line"></div>
                    <div class="line"><span class="comment">// Yanıtı JSON formatına çevirin</span></div>
                    <div class="line"><span class="keyword">const</span> <span class="variable">loginData</span> <span class="operator">=</span> <span class="keyword">await</span> <span class="variable">loginResponse</span>.<span class="function">json</span>();</div>
                    <div class="line"><span class="keyword">const</span> <span class="variable">token</span> <span class="operator">=</span> <span class="variable">loginData</span>.<span class="variable">data</span>.<span class="variable">token</span>;</div>
                    <div class="line"></div>
                    <div class="line"><span class="comment">// 2. Token ile sitelerinizi getirin</span></div>
                    <div class="line"><span class="function">fetch</span>(<span class="string">'<?= $base_url ?>api/sites'</span>, <span class="bracket">{</span></div>
                    <div class="line">    <span class="variable">method</span>: <span class="string">'GET'</span>,</div>
                    <div class="line">    <span class="variable">headers</span>: <span class="bracket">{</span></div>
                    <div class="line">        <span class="string">'Content-Type'</span>: <span class="string">'application/json'</span>,</div>
                    <div class="line">        <span class="string">'Authorization'</span>: <span class="string">'Bearer '</span> <span class="operator">+</span> <span class="variable">token</span></div>
                    <div class="line">    <span class="bracket">}</span></div>
                    <div class="line"><span class="bracket">}</span>)</div>
                    <div class="line">.<span class="function">then</span>(<span class="variable">response</span> <span class="operator">=></span> <span class="variable">response</span>.<span class="function">json</span>())</div>
                    <div class="line">.<span class="function">then</span>(<span class="variable">data</span> <span class="operator">=></span> <span class="bracket">{</span></div>
                    <div class="line">    <span class="keyword">if</span> (<span class="variable">data</span>.<span class="variable">success</span>) <span class="bracket">{</span></div>
                    <div class="line">        <span class="function">console.log</span>(<span class="string">'Siteleriniz:'</span>, <span class="variable">data</span>.<span class="variable">data</span>);</div>
                    <div class="line">    <span class="bracket">}</span> <span class="keyword">else</span> <span class="bracket">{</span></div>
                    <div class="line">        <span class="function">console.error</span>(<span class="string">'Hata:'</span>, <span class="variable">data</span>.<span class="variable">message</span>);</div>
                    <div class="line">    <span class="bracket">}</span></div>
                    <div class="line"><span class="bracket">}</span>);</div>
                    <div class="line"></div>
                    <div class="line"><span class="comment">// 3. Yeni site ekleyin</span></div>
                    <div class="line"><span class="function">fetch</span>(<span class="string">'<?= $base_url ?>api/sites'</span>, <span class="bracket">{</span></div>
                    <div class="line">    <span class="variable">method</span>: <span class="string">'POST'</span>,</div>
                    <div class="line">    <span class="variable">headers</span>: <span class="bracket">{</span></div>
                    <div class="line">        <span class="string">'Content-Type'</span>: <span class="string">'application/json'</span>,</div>
                    <div class="line">        <span class="string">'Authorization'</span>: <span class="string">'Bearer '</span> <span class="operator">+</span> <span class="variable">token</span></div>
                    <div class="line">    <span class="bracket">}</span>,</div>
                    <div class="line">    <span class="variable">body</span>: <span class="function">JSON.stringify</span>(<span class="bracket">{</span></div>
                    <div class="line">        <span class="variable">name</span>: <span class="string">'Yeni Site'</span>,</div>
                    <div class="line">        <span class="variable">url</span>: <span class="string">'https://example.com'</span>,</div>
                    <div class="line">        <span class="variable">description</span>: <span class="string">'Test sitesi'</span></div>
                    <div class="line">    <span class="bracket">}</span>)</div>
                    <div class="line"><span class="bracket">}</span>)</div>
                    <div class="line">.<span class="function">then</span>(<span class="variable">response</span> <span class="operator">=></span> <span class="variable">response</span>.<span class="function">json</span>())</div>
                    <div class="line">.<span class="function">then</span>(<span class="variable">data</span> <span class="operator">=></span> <span class="bracket">{</span></div>
                    <div class="line">    <span class="function">console.log</span>(<span class="string">'Site eklendi:'</span>, <span class="variable">data</span>);</div>
                    <div class="line"><span class="bracket">}</span>);</div>
                </div>
            </div>
        </div>

        <h3>Python ile Kullanım</h3>
        <div class="code-editor collapsed" onclick="toggleCodeBlock(this)">
            <div class="code-header">
                <i class="fab fa-python"></i> Python - API Kullanım Örneği
            </div>
            <div class="code-content">
                <div class="line-numbers">
                    1<br>2<br>3<br>4<br>5<br>6<br>7<br>8<br>9<br>10<br>11<br>12<br>13<br>14<br>15<br>16<br>17<br>18<br>19<br>20<br>21<br>22<br>23<br>24<br>25<br>26<br>27<br>28<br>29<br>30<br>31<br>32<br>33<br>34<br>35<br>36<br>37<br>38<br>39<br>40
                </div>
                <div class="code-lines">
                    <div class="line"><span class="comment"># requests kütüphanesini import edin</span></div>
                    <div class="line"><span class="keyword">import</span> <span class="variable">requests</span></div>
                    <div class="line"></div>
                    <div class="line"><span class="comment"># 1. API'ye giriş yaparak token alın</span></div>
                    <div class="line"><span class="variable">login_response</span> <span class="operator">=</span> <span class="variable">requests</span>.<span class="function">post</span>(<span class="string">'<?= $base_url ?>api/auth/login'</span>, <span class="variable">json</span><span class="operator">=</span><span class="bracket">{</span></div>
                    <div class="line">    <span class="string">'email'</span>: <span class="string">'your_email@example.com'</span>,</div>
                    <div class="line">    <span class="string">'password'</span>: <span class="string">'your_password'</span></div>
                    <div class="line"><span class="bracket">}</span>)</div>
                    <div class="line"></div>
                    <div class="line"><span class="comment"># Yanıtı JSON formatına çevirin</span></div>
                    <div class="line"><span class="variable">login_data</span> <span class="operator">=</span> <span class="variable">login_response</span>.<span class="function">json</span>()</div>
                    <div class="line"><span class="variable">token</span> <span class="operator">=</span> <span class="variable">login_data</span>[<span class="string">'data'</span>][<span class="string">'token'</span>]</div>
                    <div class="line"></div>
                    <div class="line"><span class="comment"># 2. Token ile sitelerinizi getirin</span></div>
                    <div class="line"><span class="variable">response</span> <span class="operator">=</span> <span class="variable">requests</span>.<span class="function">get</span>(</div>
                    <div class="line">    <span class="string">'<?= $base_url ?>api/sites'</span>,</div>
                    <div class="line">    <span class="variable">headers</span><span class="operator">=</span><span class="bracket">{</span></div>
                    <div class="line">        <span class="string">'Content-Type'</span>: <span class="string">'application/json'</span>,</div>
                    <div class="line">        <span class="string">'Authorization'</span>: <span class="string">'Bearer '</span> <span class="operator">+</span> <span class="variable">token</span></div>
                    <div class="line">    <span class="bracket">}</span></div>
                    <div class="line">)</div>
                    <div class="line"></div>
                    <div class="line"><span class="comment"># Yanıtı işleyin</span></div>
                    <div class="line"><span class="variable">data</span> <span class="operator">=</span> <span class="variable">response</span>.<span class="function">json</span>()</div>
                    <div class="line"><span class="keyword">if</span> <span class="variable">data</span>[<span class="string">'success'</span>]:</div>
                    <div class="line">    <span class="function">print</span>(<span class="string">'Siteleriniz:'</span>, <span class="variable">data</span>[<span class="string">'data'</span>])</div>
                    <div class="line"><span class="keyword">else</span>:</div>
                    <div class="line">    <span class="function">print</span>(<span class="string">'Hata:'</span>, <span class="variable">data</span>[<span class="string">'message'</span>])</div>
                    <div class="line"></div>
                    <div class="line"><span class="comment"># 3. Yeni site ekleyin</span></div>
                    <div class="line"><span class="variable">new_site</span> <span class="operator">=</span> <span class="bracket">{</span></div>
                    <div class="line">    <span class="string">'name'</span>: <span class="string">'Yeni Site'</span>,</div>
                    <div class="line">    <span class="string">'url'</span>: <span class="string">'https://example.com'</span>,</div>
                    <div class="line">    <span class="string">'description'</span>: <span class="string">'Test sitesi'</span></div>
                    <div class="line"><span class="bracket">}</span></div>
                    <div class="line"></div>
                    <div class="line"><span class="variable">response</span> <span class="operator">=</span> <span class="variable">requests</span>.<span class="function">post</span>(</div>
                    <div class="line">    <span class="string">'<?= $base_url ?>api/sites'</span>,</div>
                    <div class="line">    <span class="variable">headers</span><span class="operator">=</span><span class="bracket">{</span></div>
                    <div class="line">        <span class="string">'Content-Type'</span>: <span class="string">'application/json'</span>,</div>
                    <div class="line">        <span class="string">'Authorization'</span>: <span class="string">'Bearer '</span> <span class="operator">+</span> <span class="variable">token</span></div>
                    <div class="line">    <span class="bracket">}</span>,</div>
                    <div class="line">    <span class="variable">json</span><span class="operator">=</span><span class="variable">new_site</span></div>
                    <div class="line">)</div>
                    <div class="line"></div>
                    <div class="line"><span class="function">print</span>(<span class="string">'Site eklendi:'</span>, <span class="variable">response</span>.<span class="function">json</span>())</div>
                </div>
            </div>
        </div>

        <h3>cURL ile Kullanım</h3>
        <div class="code-editor collapsed" onclick="toggleCodeBlock(this)">
            <div class="code-header">
                <i class="fas fa-terminal"></i> cURL - Terminal Komutları
            </div>
            <div class="code-content">
                <div class="line-numbers">
                    1<br>2<br>3<br>4<br>5<br>6<br>7<br>8<br>9<br>10<br>11<br>12<br>13<br>14<br>15<br>16<br>17<br>18<br>19<br>20<br>21<br>22<br>23<br>24<br>25
                </div>
                <div class="code-lines">
                    <div class="line"><span class="comment"># 1. API'ye giriş yaparak token alın</span></div>
                    <div class="line"><span class="variable">TOKEN</span><span class="operator">=</span>$(<span class="function">curl</span> -s -X <span class="method">POST</span> <span class="string">"<?= $base_url ?>api/auth/login"</span> \</div>
                    <div class="line">  -H <span class="string">"Content-Type: application/json"</span> \</div>
                    <div class="line">  -d <span class="string">'{</span></div>
                    <div class="line">    <span class="string">"email": "your_email@example.com",</span></div>
                    <div class="line">    <span class="string">"password": "your_password"</span></div>
                    <div class="line">  <span class="string">}'</span> | <span class="function">jq</span> -r <span class="string">'.data.token'</span>)</div>
                    <div class="line"></div>
                    <div class="line"><span class="comment"># Token'ı kontrol edin</span></div>
                    <div class="line"><span class="function">echo</span> <span class="string">"Token: $TOKEN"</span></div>
                    <div class="line"></div>
                    <div class="line"><span class="comment"># 2. Token ile sitelerinizi getirin</span></div>
                    <div class="line"><span class="function">curl</span> -X <span class="method">GET</span> <span class="string">"<?= $base_url ?>api/sites"</span> \</div>
                    <div class="line">  -H <span class="string">"Content-Type: application/json"</span> \</div>
                    <div class="line">  -H <span class="string">"Authorization: Bearer $TOKEN"</span></div>
                    <div class="line"></div>
                    <div class="line"><span class="comment"># 3. Yeni site ekleyin</span></div>
                    <div class="line"><span class="function">curl</span> -X <span class="method">POST</span> <span class="string">"<?= $base_url ?>api/sites"</span> \</div>
                    <div class="line">  -H <span class="string">"Content-Type: application/json"</span> \</div>
                    <div class="line">  -H <span class="string">"Authorization: Bearer $TOKEN"</span> \</div>
                    <div class="line">  -d <span class="string">'{</span></div>
                    <div class="line">    <span class="string">"name": "Yeni Site",</span></div>
                    <div class="line">    <span class="string">"url": "https://example.com",</span></div>
                    <div class="line">    <span class="string">"description": "Test sitesi"</span></div>
                    <div class="line">  <span class="string">}'</span></div>
                </div>
            </div>
        </div>

        <h3>PHP ile Kullanım</h3>
        <div class="code-editor collapsed" onclick="toggleCodeBlock(this)">
            <div class="code-header">
                <i class="fab fa-php"></i> PHP - cURL ile API Kullanım Örneği
            </div>
            <div class="code-content">
                <div class="line-numbers">
                    1<br>2<br>3<br>4<br>5<br>6<br>7<br>8<br>9<br>10<br>11<br>12<br>13<br>14<br>15<br>16<br>17<br>18<br>19<br>20<br>21<br>22<br>23<br>24<br>25<br>26<br>27<br>28<br>29<br>30<br>31<br>32<br>33<br>34<br>35<br>36<br>37<br>38<br>39<br>40<br>41<br>42<br>43<br>44<br>45<br>46<br>47<br>48<br>49<br>50<br>51<br>52<br>53<br>54<br>55<br>56<br>57<br>58<br>59<br>60<br>61<br>62<br>63<br>64<br>65<br>66<br>67<br>68<br>69<br>70<br>71<br>72<br>73<br>74<br>75<br>76<br>77<br>78<br>79<br>80
                </div>
                <div class="code-lines">
                    <div class="line"><span class="comment">// API base URL'ini tanımlayın</span></div>
                    <div class="line"><span class="variable">$base_url</span> <span class="operator">=</span> <span class="string">'<?= $base_url ?>'</span>;</div>
                    <div class="line"></div>
                    <div class="line"><span class="comment">// 1. API'ye giriş yaparak token alın</span></div>
                    <div class="line"><span class="variable">$login_data</span> <span class="operator">=</span> <span class="bracket">[</span></div>
                    <div class="line">    <span class="string">'email'</span> <span class="operator">=></span> <span class="string">'your_email@example.com'</span>,</div>
                    <div class="line">    <span class="string">'password'</span> <span class="operator">=></span> <span class="string">'your_password'</span></div>
                    <div class="line"><span class="bracket">]</span>;</div>
                    <div class="line"></div>
                    <div class="line"><span class="variable">$ch</span> <span class="operator">=</span> <span class="function">curl_init</span>();</div>
                    <div class="line"><span class="function">curl_setopt</span>(<span class="variable">$ch</span>, <span class="variable">CURLOPT_URL</span>, <span class="variable">$base_url</span> . <span class="string">'api/auth/login'</span>);</div>
                    <div class="line"><span class="function">curl_setopt</span>(<span class="variable">$ch</span>, <span class="variable">CURLOPT_RETURNTRANSFER</span>, <span class="keyword">true</span>);</div>
                    <div class="line"><span class="function">curl_setopt</span>(<span class="variable">$ch</span>, <span class="variable">CURLOPT_POST</span>, <span class="keyword">true</span>);</div>
                    <div class="line"><span class="function">curl_setopt</span>(<span class="variable">$ch</span>, <span class="variable">CURLOPT_HTTPHEADER</span>, <span class="bracket">[</span></div>
                    <div class="line">    <span class="string">'Content-Type: application/json'</span></div>
                    <div class="line"><span class="bracket">]</span>);</div>
                    <div class="line"><span class="function">curl_setopt</span>(<span class="variable">$ch</span>, <span class="variable">CURLOPT_POSTFIELDS</span>, <span class="function">json_encode</span>(<span class="variable">$login_data</span>));</div>
                    <div class="line"></div>
                    <div class="line"><span class="variable">$response</span> <span class="operator">=</span> <span class="function">curl_exec</span>(<span class="variable">$ch</span>);</div>
                    <div class="line"><span class="variable">$login_result</span> <span class="operator">=</span> <span class="function">json_decode</span>(<span class="variable">$response</span>, <span class="keyword">true</span>);</div>
                    <div class="line"></div>
                    <div class="line"><span class="keyword">if</span> (<span class="variable">$login_result</span>[<span class="string">'success'</span>]) <span class="bracket">{</span></div>
                    <div class="line">    <span class="variable">$token</span> <span class="operator">=</span> <span class="variable">$login_result</span>[<span class="string">'data'</span>][<span class="string">'token'</span>];</div>
                    <div class="line">    <span class="function">echo</span> <span class="string">"Token alındı: "</span> . <span class="variable">$token</span> . <span class="string">"\n"</span>;</div>
                    <div class="line"><span class="bracket">}</span> <span class="keyword">else</span> <span class="bracket">{</span></div>
                    <div class="line">    <span class="function">echo</span> <span class="string">"Giriş hatası: "</span> . <span class="variable">$login_result</span>[<span class="string">'message'</span>] . <span class="string">"\n"</span>;</div>
                    <div class="line">    <span class="function">curl_close</span>(<span class="variable">$ch</span>);</div>
                    <div class="line">    <span class="function">exit</span>();</div>
                    <div class="line"><span class="bracket">}</span></div>
                    <div class="line"></div>
                    <div class="line"><span class="comment">// 2. Token ile sitelerinizi getirin</span></div>
                    <div class="line"><span class="function">curl_setopt</span>(<span class="variable">$ch</span>, <span class="variable">CURLOPT_URL</span>, <span class="variable">$base_url</span> . <span class="string">'api/sites'</span>);</div>
                    <div class="line"><span class="function">curl_setopt</span>(<span class="variable">$ch</span>, <span class="variable">CURLOPT_POST</span>, <span class="keyword">false</span>);</div>
                    <div class="line"><span class="function">curl_setopt</span>(<span class="variable">$ch</span>, <span class="variable">CURLOPT_HTTPHEADER</span>, <span class="bracket">[</span></div>
                    <div class="line">    <span class="string">'Content-Type: application/json'</span>,</div>
                    <div class="line">    <span class="string">'Authorization: Bearer '</span> . <span class="variable">$token</span></div>
                    <div class="line"><span class="bracket">]</span>);</div>
                    <div class="line"></div>
                    <div class="line"><span class="variable">$response</span> <span class="operator">=</span> <span class="function">curl_exec</span>(<span class="variable">$ch</span>);</div>
                    <div class="line"><span class="variable">$sites_result</span> <span class="operator">=</span> <span class="function">json_decode</span>(<span class="variable">$response</span>, <span class="keyword">true</span>);</div>
                    <div class="line"></div>
                    <div class="line"><span class="keyword">if</span> (<span class="variable">$sites_result</span>[<span class="string">'success'</span>]) <span class="bracket">{</span></div>
                    <div class="line">    <span class="function">echo</span> <span class="string">"Siteleriniz:\n"</span>;</div>
                    <div class="line">    <span class="keyword">foreach</span> (<span class="variable">$sites_result</span>[<span class="string">'data'</span>] <span class="keyword">as</span> <span class="variable">$site</span>) <span class="bracket">{</span></div>
                    <div class="line">        <span class="function">echo</span> <span class="string">"- "</span> . <span class="variable">$site</span>[<span class="string">'name'</span>] . <span class="string">" ("</span> . <span class="variable">$site</span>[<span class="string">'url'</span>] . <span class="string">")\n"</span>;</div>
                    <div class="line">    <span class="bracket">}</span></div>
                    <div class="line"><span class="bracket">}</span> <span class="keyword">else</span> <span class="bracket">{</span></div>
                    <div class="line">    <span class="function">echo</span> <span class="string">"Siteler alınamadı: "</span> . <span class="variable">$sites_result</span>[<span class="string">'message'</span>] . <span class="string">"\n"</span>;</div>
                    <div class="line"><span class="bracket">}</span></div>
                    <div class="line"></div>
                    <div class="line"><span class="comment">// 3. Yeni site ekleyin</span></div>
                    <div class="line"><span class="variable">$new_site_data</span> <span class="operator">=</span> <span class="bracket">[</span></div>
                    <div class="line">    <span class="string">'name'</span> <span class="operator">=></span> <span class="string">'Yeni Site'</span>,</div>
                    <div class="line">    <span class="string">'url'</span> <span class="operator">=></span> <span class="string">'https://example.com'</span>,</div>
                    <div class="line">    <span class="string">'description'</span> <span class="operator">=></span> <span class="string">'Test sitesi'</span></div>
                    <div class="line"><span class="bracket">]</span>;</div>
                    <div class="line"></div>
                    <div class="line"><span class="function">curl_setopt</span>(<span class="variable">$ch</span>, <span class="variable">CURLOPT_URL</span>, <span class="variable">$base_url</span> . <span class="string">'api/sites'</span>);</div>
                    <div class="line"><span class="function">curl_setopt</span>(<span class="variable">$ch</span>, <span class="variable">CURLOPT_POST</span>, <span class="keyword">true</span>);</div>
                    <div class="line"><span class="function">curl_setopt</span>(<span class="variable">$ch</span>, <span class="variable">CURLOPT_POSTFIELDS</span>, <span class="function">json_encode</span>(<span class="variable">$new_site_data</span>));</div>
                    <div class="line"></div>
                    <div class="line"><span class="variable">$response</span> <span class="operator">=</span> <span class="function">curl_exec</span>(<span class="variable">$ch</span>);</div>
                    <div class="line"><span class="variable">$add_result</span> <span class="operator">=</span> <span class="function">json_decode</span>(<span class="variable">$response</span>, <span class="keyword">true</span>);</div>
                    <div class="line"></div>
                    <div class="line"><span class="keyword">if</span> (<span class="variable">$add_result</span>[<span class="string">'success'</span>]) <span class="bracket">{</span></div>
                    <div class="line">    <span class="function">echo</span> <span class="string">"Site başarıyla eklendi!\n"</span>;</div>
                    <div class="line">    <span class="function">echo</span> <span class="string">"Site ID: "</span> . <span class="variable">$add_result</span>[<span class="string">'data'</span>][<span class="string">'id'</span>] . <span class="string">"\n"</span>;</div>
                    <div class="line"><span class="bracket">}</span> <span class="keyword">else</span> <span class="bracket">{</span></div>
                    <div class="line">    <span class="function">echo</span> <span class="string">"Site eklenemedi: "</span> . <span class="variable">$add_result</span>[<span class="string">'message'</span>] . <span class="string">"\n"</span>;</div>
                    <div class="line"><span class="bracket">}</span></div>
                    <div class="line"></div>
                    <div class="line"><span class="comment">// cURL bağlantısını kapatın</span></div>
                    <div class="line"><span class="function">curl_close</span>(<span class="variable">$ch</span>);</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function toggleCodeBlock(element) {
            element.classList.toggle('collapsed');
        }
    </script>
</body>
</html>

