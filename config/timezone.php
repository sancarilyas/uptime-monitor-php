<?php
/**
 * Saat Dilimi Konfigürasyonu
 * Türkiye saat dilimi ayarları
 */

// Türkiye saat dilimini ayarla
date_default_timezone_set('Europe/Istanbul');

// MySQL saat dilimini de ayarla
function setMySQLTimezone($pdo) {
    try {
        $pdo->exec("SET time_zone = '+03:00'");
    } catch (Exception $e) {
        // Hata durumunda sessizce geç
    }
}

// Saat dilimi bilgilerini al
function getTimezoneInfo() {
    return [
        'timezone' => date_default_timezone_get(),
        'offset' => date('P'),
        'current_time' => date('Y-m-d H:i:s'),
        'formatted_time' => date('d.m.Y H:i:s')
    ];
}

// Türkçe tarih formatı
function formatTurkishDate($timestamp = null) {
    if ($timestamp === null) {
        $timestamp = time();
    }
    
    $months = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan',
        5 => 'Mayıs', 6 => 'Haziran', 7 => 'Temmuz', 8 => 'Ağustos',
        9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
    ];
    
    $days = [
        0 => 'Pazar', 1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba',
        4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi'
    ];
    
    $day = $days[date('w', $timestamp)];
    $day_num = date('d', $timestamp);
    $month = $months[date('n', $timestamp)];
    $year = date('Y', $timestamp);
    $time = date('H:i:s', $timestamp);
    
    return "$day, $day_num $month $year $time";
}

// Göreceli zaman (örn: "2 dakika önce")
function getRelativeTime($timestamp) {
    $now = time();
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'Az önce';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return "$minutes dakika önce";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "$hours saat önce";
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return "$days gün önce";
    } else {
        return formatTurkishDate($timestamp);
    }
}
?>
