<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/language.php';

requireLogin();

// Admin kontrolü
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => __('access_denied')]);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => __('invalid_request')]);
    exit;
}

$email = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');
$confirm_password = trim($input['confirm_password'] ?? '');
$first_name = trim($input['first_name'] ?? '');
$last_name = trim($input['last_name'] ?? '');
$role = $input['role'] ?? 'user';
$group_id = $input['group_id'] ?? null;

// Validasyon
if (empty($email) || empty($password) || empty($confirm_password) || empty($first_name) || empty($last_name)) {
    echo json_encode(['success' => false, 'message' => __('required_fields')]);
    exit;
}

if ($password !== $confirm_password) {
    echo json_encode(['success' => false, 'message' => __('password_mismatch')]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => __('invalid_email')]);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => __('password_too_short')]);
    exit;
}

// E-posta kontrolü
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => __('email_already_exists')]);
    exit;
}

// Kullanıcı ekle
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, group_id, first_name, last_name, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");

if ($stmt->execute([$email, $hashed_password, $role, $group_id, $first_name, $last_name])) {
    echo json_encode(['success' => true, 'message' => __('user_added_successfully')]);
} else {
    echo json_encode(['success' => false, 'message' => __('user_add_failed')]);
}
?>
