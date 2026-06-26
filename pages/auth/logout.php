<?php
require_once __DIR__ . '/../../config/database.php';

// Session'ı temizle
session_destroy();

// Login sayfasına yönlendir
header('Location: ' . $base_url . 'login');
exit;
?>
