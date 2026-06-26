<?php
// Test sayfası - Modern bildirim sistemi testi
require_once __DIR__ . '/includes/layout/header.php';
?>

<div class="container p-3">
    <div class="row">
        <div class="col-12">
            <h2 class="section-title"><i class="fas fa-bell"></i> Bildirim Sistemi Testi</h2>
            <p class="text-muted">Modern toast bildirimlerini test edin</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 mb-3">
            <button class="btn btn-success w-100" onclick="showSuccess('Başarılı!', 'İşlem başarıyla tamamlandı')">
                <i class="fas fa-check"></i> Başarı Bildirimi
            </button>
        </div>
        <div class="col-md-3 mb-3">
            <button class="btn btn-danger w-100" onclick="showError('Hata!', 'Bir hata oluştu')">
                <i class="fas fa-times"></i> Hata Bildirimi
            </button>
        </div>
        <div class="col-md-3 mb-3">
            <button class="btn btn-warning w-100" onclick="showWarning('Uyarı!', 'Dikkatli olun')">
                <i class="fas fa-exclamation"></i> Uyarı Bildirimi
            </button>
        </div>
        <div class="col-md-3 mb-3">
            <button class="btn btn-info w-100" onclick="showInfo('Bilgi', 'Bu bir bilgi mesajıdır')">
                <i class="fas fa-info"></i> Bilgi Bildirimi
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <button class="btn btn-primary w-100" onclick="testMultipleNotifications()">
                <i class="fas fa-layer-group"></i> Çoklu Bildirim Testi
            </button>
        </div>
        <div class="col-md-6 mb-3">
            <button class="btn btn-secondary w-100" onclick="notifications.hideAll()">
                <i class="fas fa-eye-slash"></i> Tüm Bildirimleri Kapat
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <button class="btn btn-outline-primary w-100" onclick="testLongMessage()">
                <i class="fas fa-align-left"></i> Uzun Mesaj Testi
            </button>
        </div>
        <div class="col-md-6 mb-3">
            <button class="btn btn-outline-secondary w-100" onclick="testPageReload()">
                <i class="fas fa-refresh"></i> Sayfa Yenileme Testi
            </button>
        </div>
    </div>

    <!-- Eski Alert Testleri -->
    <div class="row mt-5">
        <div class="col-12">
            <h3>Eski Alert Testleri (Otomatik Toast'a Dönüştürülür)</h3>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Bu eski bir başarı alert'idir
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> Bu eski bir hata alert'idir
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> Bu eski bir uyarı alert'idir
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <i class="fas fa-info-circle"></i> Bu eski bir bilgi alert'idir
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
</div>

<script>
function testMultipleNotifications() {
    showSuccess('Test 1', 'İlk bildirim');
    setTimeout(() => showError('Test 2', 'İkinci bildirim'), 500);
    setTimeout(() => showWarning('Test 3', 'Üçüncü bildirim'), 1000);
    setTimeout(() => showInfo('Test 4', 'Dördüncü bildirim'), 1500);
}

function testLongMessage() {
    showInfo('Uzun Mesaj Testi', 'Bu çok uzun bir mesajdır. Bu mesajın toast kutusunun boyutunu nasıl etkilediğini görmek için tasarlandı. Eğer bu mesaj toast kutusunu çok büyütüyorsa veya ekranı kaplıyorsa, CSS ayarlarında bir sorun var demektir.');
}

function testPageReload() {
    showSuccess('Sayfa Yenileme Testi', 'Bu mesajdan sonra sayfa yenilenecek');
    setTimeout(() => {
        window.location.reload();
    }, 2000);
}
</script>

<?php
require_once __DIR__ . '/includes/layout/footer.php';
?>
