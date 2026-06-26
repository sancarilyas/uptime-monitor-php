# Gerçek Zamanlı Dashboard Güncellemeleri

## 📡 Özellikler

Dashboard artık **gerçek zamanlı** olarak güncelleniyor! Site kesintilerini anında görebilirsiniz.

### ✨ Yeni Özellikler:

1. **Otomatik Güncelleme**: Dashboard her 10 saniyede bir otomatik olarak güncellenir
2. **Canlı Durum Göstergesi**: Üstteki bildirim, güncelleme durumunu gösterir
3. **Akıllı Performans**: Sayfa gizli olduğunda güncellemeler durur
4. **Smooth Animasyonlar**: Değişiklikler yumuşak geçişlerle gösterilir
5. **Görsel Geri Bildirim**: Değişen veriler yanıp sönerek vurgulanır

## 🔧 Teknik Detaylar

### Kullanılan Teknoloji: AJAX Polling

**Neden AJAX Polling?**
- Mevcut altyapıya kolay entegrasyon
- Ek sunucu gereksinimleri yok
- Basit ve güvenilir
- Tüm modern tarayıcılarda çalışır

### Alternatifleri:
- **WebSocket**: Daha gelişmiş, ancak ek sunucu kurulumu gerektirir
- **Server-Sent Events (SSE)**: Tek yönlü iletişim için iyi, ancak AJAX Polling yeterli

## 📂 Yeni Dosyalar

### 1. `/pages/ajax/get_dashboard_data.php`
Dashboard için tüm verileri JSON formatında döner:
```json
{
  "success": true,
  "data": {
    "stats": {
      "total_sites": 5,
      "active_sites": 4,
      "avg_uptime": "99.85%",
      "last_check": "Az önce"
    },
    "sites": [
      {
        "id": 1,
        "name": "Site Adı",
        "status": "up",
        "uptime_24h": 99.85,
        ...
      }
    ]
  }
}
```

## 🔄 Güncelleme Döngüsü

```
Sayfa Yüklendi
    ↓
İlk Güncelleme (5 saniye sonra)
    ↓
Her 10 Saniyede Bir:
    ├─ Sunucudan veri çek (AJAX)
    ├─ İstatistikleri güncelle
    ├─ Site kartlarını güncelle
    ├─ Tablo görünümünü güncelle
    └─ Göstergeyi güncelle
```

## 🎨 Görsel Efektler

### 1. **Sayı Animasyonları**
İstatistikler değiştiğinde sayılar yumuşak bir şekilde artar/azalır.

### 2. **Flash Efekti**
Güncellenen veriler kısa süreliğine yanıp söner (yeşil için success, kırmızı için danger).

### 3. **Durum Geçişleri**
Site durumu değiştiğinde (up ↔ down) kart/satır rengi yumuşak bir şekilde değişir.

## ⚙️ Ayarlar

### Güncelleme Aralığını Değiştirme

`/pages/dashboard/index.php` dosyasında:

```javascript
// Her 10 saniyede bir güncelle (varsayılan)
updateInterval = setInterval(updateDashboard, 10000);

// Her 5 saniyede bir güncelle (daha sık)
updateInterval = setInterval(updateDashboard, 5000);

// Her 30 saniyede bir güncelle (daha az sık)
updateInterval = setInterval(updateDashboard, 30000);
```

### Göstergeyi Kapatma

Kullanıcılar göstergeyi kapatabilir. Tercih localStorage'a kaydedilir:
```javascript
localStorage.setItem('updateIndicatorDismissed', 'true');
```

Göstergeyi tekrar göstermek için tarayıcı console'unda:
```javascript
localStorage.removeItem('updateIndicatorDismissed');
location.reload();
```

## 📊 Güncellenen Bileşenler

### 1. İstatistik Kartları (Üstte)
- Toplam Site Sayısı
- Aktif Siteler
- Ortalama Uptime
- Son Kontrol Zamanı

### 2. Site Kartları (Kart Görünümü)
- Durum göstergesi (up/down)
- Status badge
- Uptime yüzdesi
- Son kontrol zamanı

### 3. Tablo Görünümü
- Tüm satır verileri
- Progress bar'lar
- Status badge'ler
- Son kontrol zamanları

### 4. Mini Grafikler
Zaten mevcut olan mini grafikler de kendi güncelleme mekanizmasına sahip (5 saniyede bir).

## 🚀 Performans Optimizasyonları

### 1. **Sayfa Görünürlük Kontrolü**
Sayfa gizli olduğunda (başka sekme açıldığında) güncellemeler durur:
```javascript
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        stopAutoUpdate();
    } else {
        startAutoUpdate();
    }
});
```

### 2. **Throttling**
Aynı anda birden fazla güncelleme isteği gönderilmez:
```javascript
if (isUpdatingDashboard) return;
```

### 3. **Selective Updates**
Sadece değişen veriler DOM'da güncellenir, tüm sayfa yeniden render edilmez.

## 🐛 Hata Yönetimi

### Bağlantı Hatası
Sunucuya ulaşılamazsa:
- Gösterge sarıya döner
- Hata mesajı gösterilir
- 10 saniye sonra otomatik olarak tekrar dener

### Timeout
Cevap gelmezse:
- Tarayıcı otomatik olarak isteği iptal eder
- Bir sonraki döngüde tekrar dener

## 📱 Mobil Uyumluluk

Tüm gerçek zamanlı özellikler mobil cihazlarda da çalışır:
- Responsive tasarım
- Touch-friendly
- Düşük veri kullanımı

## 🔐 Güvenlik

### Oturum Kontrolü
Her AJAX isteği oturum kontrolü yapar:
```php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum gerekli']);
    exit;
}
```

### Kullanıcı İzolasyonu
Her kullanıcı sadece kendi sitelerini görebilir:
```php
$stmt = $pdo->prepare("SELECT * FROM sites WHERE user_id = ?");
$stmt->execute([$user_id]);
```

## 📈 Gelecek Geliştirmeler

### Olası İyileştirmeler:
1. **WebSocket Desteği**: Gerçek zamanlı push notifications
2. **Ses Bildirimleri**: Site düştüğünde ses çalma
3. **Desktop Notifications**: Tarayıcı bildirimleri
4. **Özelleştirilebilir Aralıklar**: Kullanıcı kendi güncelleme süresini seçebilir
5. **Veri Sıkıştırma**: Daha az bant genişliği kullanımı

## 🎯 Sonuç

Artık dashboard'unuz **gerçek zamanlı** çalışıyor! Site kesintilerini anında görebilir, sayfa yenilemeden tüm verileri takip edebilirsiniz.

**Önemli:** Monitor daemon'un (`monitor_daemon.php`) düzenli olarak çalıştığından emin olun. Aksi takdirde veriler güncellenmez.

---

**Oluşturma Tarihi:** <?= date('d.m.Y H:i') ?>
**Versiyon:** 1.0
