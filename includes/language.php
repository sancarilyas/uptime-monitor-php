<?php
// Dil Yönetimi Helper Dosyası

// Desteklenen diller
$supported_languages = [
    'tr' => 'Türkçe',
    'en' => 'English',
    'de' => 'Deutsch',
    'fr' => 'Français',
    'ar' => 'العربية'
];

// Varsayılan dil
$default_language = 'tr';

// Mevcut dili belirle
function getCurrentLanguage() {
    global $default_language;
    
    // Session'dan dil al
    if (isset($_SESSION['language'])) {
        return $_SESSION['language'];
    }
    
    // Cookie'den dil al
    if (isset($_COOKIE['language'])) {
        return $_COOKIE['language'];
    }
    
    // Browser dilini kontrol et
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if (in_array($browser_lang, ['tr', 'en', 'de', 'fr', 'ar'])) {
            return $browser_lang;
        }
    }
    
    return $default_language;
}

// Dil dosyasını yükle
function loadLanguage($lang = null) {
    global $default_language;
    
    if ($lang === null) {
        $lang = getCurrentLanguage();
    }
    
    $lang_file = __DIR__ . '/../languages/' . $lang . '.php';
    
    if (file_exists($lang_file)) {
        return require $lang_file;
    }
    
    // Fallback olarak varsayılan dili yükle
    $default_file = __DIR__ . '/../languages/' . $default_language . '.php';
    return require $default_file;
}

// Çeviri fonksiyonu
function __($key, $lang = null) {
    static $translations = null;
    
    if ($translations === null) {
        $translations = loadLanguage($lang);
    }
    
    return $translations[$key] ?? $key;
}

// Dil değiştir
function setLanguage($lang) {
    global $supported_languages;
    
    if (in_array($lang, array_keys($supported_languages))) {
        $_SESSION['language'] = $lang;
        setcookie('language', $lang, time() + (365 * 24 * 60 * 60), '/'); // 1 yıl
        return true;
    }
    
    return false;
}

// Desteklenen dilleri al
function getSupportedLanguages() {
    global $supported_languages;
    return $supported_languages;
}

// Dil seçici HTML oluştur
function getLanguageSelector($current_lang = null) {
    if ($current_lang === null) {
        $current_lang = getCurrentLanguage();
    }
    
    $languages = getSupportedLanguages();
    $html = '<div class="language-selector dropdown">';
    $html .= '<button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">';
    $html .= '<i class="fas fa-globe"></i> ' . $languages[$current_lang];
    $html .= '</button>';
    $html .= '<ul class="dropdown-menu">';
    
    foreach ($languages as $code => $name) {
        $active = ($code === $current_lang) ? 'active' : '';
        $html .= '<li><a class="dropdown-item ' . $active . '" href="?lang=' . $code . '">' . $name . '</a></li>';
    }
    
    $html .= '</ul>';
    $html .= '</div>';
    
    return $html;
}

// Dil değişikliğini işle
if (isset($_GET['lang'])) {
    setLanguage($_GET['lang']);
    // Sayfayı yenile
    $current_url = strtok($_SERVER["REQUEST_URI"], '?');
    header('Location: ' . $current_url);
    exit;
}
?>
