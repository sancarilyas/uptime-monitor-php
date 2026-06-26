# 🧪 Gerçek Zamanlı Güncelleme Testi

## Test Adımları

### 1. Dashboard'u Açın
```
http://localhost/uptime/dashboard
```

### 2. Üst Kısımdaki Göstergeyi Kontrol Edin
Mavi bir bildirim kutusunda şunları görmelisiniz:
```
🛰️ Canlı yayın aktif - Her 10 saniyede otomatik güncelleniyor
```

### 3. Konsolu Açın
Tarayıcınızın geliştirici araçlarını açın (F12) ve Console sekmesine gidin.

Her 10 saniyede bir şunu görmelisiniz:
```
Dashboard güncellendi: 14:25:30
Dashboard güncellendi: 14:25:40
Dashboard güncellendi: 14:25:50
```

### 4. Güncelleme Animasyonlarını İzleyin

#### İlk Güncelleme (5 saniye sonra):
- Gösterge maviden yeşile döner
- "Dashboard güncellendi!" mesajı gösterilir
- 3 saniye sonra tekrar mavi olur

#### Sürekli Güncellemeler (Her 10 saniye):
- Veriler değiştiğinde kartlar hafifçe yanıp söner
- Sayılar yumuşak bir şekilde değişir
- Durum badge'leri güncellenir

### 5. Site Durumu Değişikliklerini Test Edin

#### Simüle Etmek İçin:
1. Veritabanında bir sitenin `last_status` değerini değiştirin:
   ```sql
   UPDATE sites SET last_status = 'down' WHERE id = 1;
   ```

2. 10 saniye bekleyin

3. Dashboard'da şunları görmelisiniz:
   - Kart/satır kırmızıya döner
   - Status "Kapalı" olarak güncellenir
   - Uptime yüzdesi değişir
   - Yanıp sönme efekti

#### Geri Almak İçin:
```sql
UPDATE sites SET last_status = 'up' WHERE id = 1;
```

### 6. Sayfa Gizleme Testini Yapın

1. Dashboard açıkken başka bir sekmeye geçin
2. Console'da şunu görmelisiniz:
   ```
   Gerçek zamanlı dashboard güncellemesi durduruldu
   ```

3. Tekrar dashboard sekmesine dönün:
   ```
   Dashboard güncellendi: 14:26:15
   Gerçek zamanlı dashboard güncellemesi başlatıldı (10 saniye aralıklarla)
   ```

### 7. Network İsteklerini Kontrol Edin

Geliştirici araçlarında Network sekmesine gidin:

Her 10 saniyede bir şunu görmelisiniz:
```
GET /uptime/pages/ajax/get_dashboard_data.php
Status: 200
Size: ~2KB
Time: ~50ms
```

### 8. Göstergeyi Kapatma Testi

1. Göstergedeki X butonuna tıklayın
2. Gösterge kaybolur
3. Sayfayı yenileyin (F5)
4. Gösterge tekrar gelmez (tercih kaydedildi)

#### Göstergeyi Tekrar Göstermek İçin:
Console'da:
```javascript
localStorage.removeItem('updateIndicatorDismissed');
location.reload();
```

## ✅ Başarı Kriterleri

- [ ] Gösterge görünüyor
- [ ] Her 10 saniyede konsola log yazılıyor
- [ ] Gösterge yeşile dönüp tekrar maviye geçiyor
- [ ] Site durumu değişiklikleri 10 saniye içinde yansıyor
- [ ] Sayfa gizlenince güncellemeler duruyor
- [ ] Sayfa görününce güncellemeler devam ediyor
- [ ] Network istekleri düzenli olarak gidiyor
- [ ] Animasyonlar düzgün çalışıyor

## 🐛 Sorun Giderme

### Güncellemeler Çalışmıyor
1. Console'da hata var mı kontrol edin
2. Network sekmesinde istekler gidiyor mu?
3. `/pages/ajax/get_dashboard_data.php` dosyası var mı?
4. PHP oturumu aktif mi?

### Veriler Güncellenmiyor
1. Monitor daemon çalışıyor mu?
   ```bash
   php monitor_daemon.php
   ```
2. Veritabanında veriler değişiyor mu?
   ```sql
   SELECT last_check FROM sites ORDER BY last_check DESC LIMIT 1;
   ```

### Animasyonlar Yavaş
1. Tarayıcı performansı düşük olabilir
2. Çok fazla site varsa yavaşlayabilir
3. Güncelleme aralığını artırın (10 → 15 saniye)

### Console'da "CORS" Hatası
1. Dosya yollarını kontrol edin
2. `/uptime/` base path'i doğru mu?

## 📊 Beklenen Davranış

### Normal Durum:
```
[14:25:30] Dashboard güncellendi
[14:25:40] Dashboard güncellendi
[14:25:50] Dashboard güncellendi
```

### Hata Durumu:
```
[14:25:30] Dashboard güncellendi
[14:25:40] Dashboard güncelleme hatası: Failed to fetch
[14:25:50] Dashboard güncellendi (hata sonrası tekrar deneme)
```

### Sayfa Gizli:
```
[14:25:30] Dashboard güncellendi
[14:25:32] Gerçek zamanlı dashboard güncellemesi durduruldu
[Hiçbir güncelleme yok]
[14:26:15] (Sayfa tekrar görünür)
[14:26:15] Dashboard güncellendi
```

## 🎉 Test Başarılı!

Tüm kontroller geçtiyse, sisteminiz artık gerçek zamanlı çalışıyor!

Site kesintilerini anında görebilecek, sayfa yenilemeden tüm verileri takip edebileceksiniz.

---

**İyi İzlemeler! 🚀**
