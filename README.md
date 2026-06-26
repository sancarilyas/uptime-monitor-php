# Uptime Monitor - Site İzleme Sistemi

Web sitelerinizi 7/24 izleyen, kesintileri anında bildiren modern bir uptime monitoring sistemi.

## 🚀 Özellikler

- ✅ **7/24 Site İzleme**: Her dakika otomatik kontrol
- 📧 **E-posta Bildirimleri**: Kesinti ve düzelme anında haber
- 📊 **Detaylı Raporlar**: 24 saat, 7 gün, 30 günlük uptime yüzdeleri
- 👥 **Çok Kullanıcı Desteği**: Her kullanıcı kendi sitelerini yönetir
- 🔐 **Güvenli Giriş**: Session tabanlı kimlik doğrulama
- 📱 **Mobil Uyumlu**: Responsive tasarım
- 🎨 **Modern Arayüz**: Bootstrap 5 ile güzel tasarım
- 📈 **JSON Loglama**: Aylık log dosyaları ile veri saklama
- ⚡ **Hızlı**: PHP ile optimize edilmiş performans

## 📋 Gereksinimler

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Web sunucusu (Apache/Nginx)
- cURL desteği
- Cron job desteği

## 🛠️ Kurulum

### 1. Dosyaları Yükleyin

Tüm dosyaları web sunucunuzun root dizinine yükleyin.

### 2. Veritabanı Ayarları

`config/database.php` dosyasını düzenleyin:

```php
$host = 'localhost';        // Veritabanı sunucu adresi
$dbname = 'uptime_monitor'; // Veritabanı adı
$username = 'your_username'; // Veritabanı kullanıcı adı
$password = 'your_password'; // Veritabanı şifresi
```

### 3. Veritabanını Oluşturun

MySQL'de yeni bir veritabanı oluşturun:

```sql
CREATE DATABASE uptime_monitor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 4. İzinleri Ayarlayın

`logs` klasörüne yazma izni verin:

```bash
chmod 755 logs/
```

### 5. Cron Job Kurulumu

Site izleme sisteminin çalışması için cron job ekleyin:

```bash
# Her dakika çalıştır
* * * * * /usr/bin/php /path/to/your/uptime/monitor.php

# veya cPanel'de:
# Komut: /usr/bin/php /home/username/public_html/monitor.php
# Sıklık: Her dakika
```

### 6. İlk Admin Kullanıcısı

Veritabanında manuel olarak admin kullanıcısı oluşturun:

```sql
INSERT INTO users (email, password_hash, role) VALUES 
('admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Şifre: password
```

## 📁 Dosya Yapısı

```
uptime/
├── index.php              # Ana giriş sayfası
├── dashboard.php          # Kullanıcı dashboard'u
├── admin.php             # Admin paneli
├── monitor.php           # Site izleme scripti (cron)
├── config/
│   └── database.php      # Veritabanı ayarları
├── includes/
│   └── functions.php     # Yardımcı fonksiyonlar
├── logs/                 # JSON log dosyaları
└── README.md            # Bu dosya
```

## 🔧 Kullanım

### Kullanıcı Girişi
1. `index.php` sayfasına gidin
2. Kayıt olun veya giriş yapın
3. Dashboard'a erişin

### Site Ekleme
1. Dashboard'da "Yeni Site Ekle" butonuna tıklayın
2. Site bilgilerini doldurun:
   - Site adı
   - URL (http:// veya https:// ile)
   - Açıklama (opsiyonel)
   - Bildirim e-postaları (virgülle ayırarak birden fazla)

### Admin Paneli
- Admin kullanıcıları `/admin.php` sayfasından tüm kullanıcıları ve siteleri yönetebilir

## 📊 İzleme Sistemi

- **Kontrol Sıklığı**: Her dakika
- **İzleme Yöntemi**: HTTP status code kontrolü
- **Yanıt Süresi**: Milisaniye cinsinden ölçüm
- **Log Saklama**: Son 30 günlük veri
- **Bildirim**: Site durumu değiştiğinde e-posta

## 🔔 Bildirim Sistemi

Sistem aşağıdaki durumlarda e-posta gönderir:
- Site kesintiye girdiğinde
- Site tekrar çalışmaya başladığında

E-posta ayarları hosting sağlayıcınızın SMTP ayarlarına bağlıdır.

## 🛡️ Güvenlik

- Şifreler bcrypt ile hash'lenir
- Session tabanlı kimlik doğrulama
- SQL injection koruması (PDO prepared statements)
- XSS koruması (htmlspecialchars)
- Rate limiting (siteler arası 1 saniye bekleme)

## 📱 Mobil Uyumluluk

- Bootstrap 5 responsive tasarım
- Mobil cihazlarda optimize edilmiş arayüz
- Touch-friendly butonlar ve formlar

## 🔮 Gelecek Özellikler

- Webhook desteği (Slack, Discord, Telegram)
- Site düzenleme özelliği
- Daha detaylı grafikler
- Çoklu dil desteği
- Dark mode
- REST API

## 🐛 Sorun Giderme

### Cron Job Çalışmıyor
- Cron job'un doğru yolda olduğunu kontrol edin
- PHP'nin CLI modunda çalıştığını kontrol edin
- Log dosyalarını kontrol edin

### E-posta Bildirimleri Gelmiyor
- Hosting sağlayıcınızın SMTP ayarlarını kontrol edin
- Spam klasörünü kontrol edin
- E-posta adreslerinin doğru olduğunu kontrol edin

### Veritabanı Bağlantı Hatası
- `config/database.php` dosyasındaki ayarları kontrol edin
- Veritabanı sunucusunun çalıştığını kontrol edin
- Kullanıcı izinlerini kontrol edin

## 📞 Destek

Herhangi bir sorun yaşarsanız:
1. README dosyasını tekrar okuyun
2. Log dosyalarını kontrol edin
3. Hosting sağlayıcınızın dokümantasyonunu inceleyin

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır.

---

**Not**: Bu sistem hosting sağlayıcınıza direkt yüklenebilir ve çalışmaya hazırdır. Herhangi bir build işlemi gerektirmez.
