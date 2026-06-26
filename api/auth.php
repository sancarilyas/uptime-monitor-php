<?php
require_once __DIR__ . '/../config/database.php';

// JSON response header
header('Content-Type: application/json');

// HTTP method kontrolü
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'POST':
            // POST /api/auth/login - Token al
            login($pdo);
            break;
            
        case 'GET':
            // GET /api/auth/verify - Token doğrula
            verifyToken($pdo);
            break;
            
        default:
            throw new Exception('Desteklenmeyen HTTP method');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'REQUEST_ERROR'
    ]);
}

// Login - Token al
function login($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Geçersiz JSON verisi');
    }
    
    // Gerekli alanları kontrol et
    if (empty($input['email']) || empty($input['password'])) {
        throw new Exception('Email ve şifre gerekli');
    }
    
    $email = trim($input['email']);
    $password = trim($input['password']);
    
    // Kullanıcıyı bul
    $stmt = $pdo->prepare("SELECT id, email, password_hash, role, group_id, first_name, last_name FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Geçersiz email veya şifre',
            'error_code' => 'INVALID_CREDENTIALS'
        ]);
        return;
    }
    
    // Token oluştur
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Token'ı veritabanına kaydet
    $stmt = $pdo->prepare("
        INSERT INTO api_tokens (user_id, token, expires_at, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    
    if ($stmt->execute([$user['id'], $token, $expires_at])) {
        echo json_encode([
            'success' => true,
            'message' => 'Giriş başarılı',
            'data' => [
                'token' => $token,
                'expires_at' => $expires_at,
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'group_id' => $user['group_id'],
                    'first_name' => $user['first_name'],
                    'last_name' => $user['last_name']
                ]
            ]
        ]);
    } else {
        throw new Exception('Token oluşturulurken hata oluştu');
    }
}

// Token doğrula
function verifyToken($pdo) {
    $headers = getallheaders();
    $token = null;
    
    // Authorization header'dan token al
    if (isset($headers['Authorization'])) {
        $auth_header = $headers['Authorization'];
        if (preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
            $token = $matches[1];
        }
    }
    
    if (!$token) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Token gerekli',
            'error_code' => 'MISSING_TOKEN'
        ]);
        return;
    }
    
    // Token'ı doğrula
    $stmt = $pdo->prepare("
        SELECT t.*, u.email, u.role, u.group_id, u.first_name, u.last_name 
        FROM api_tokens t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.token = ? AND t.expires_at > NOW()
    ");
    $stmt->execute([$token]);
    $token_data = $stmt->fetch();
    
    if (!$token_data) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Geçersiz veya süresi dolmuş token',
            'error_code' => 'INVALID_TOKEN'
        ]);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Token geçerli',
        'data' => [
            'user' => [
                'id' => $token_data['user_id'],
                'email' => $token_data['email'],
                'role' => $token_data['role'],
                'group_id' => $token_data['group_id'],
                'first_name' => $token_data['first_name'],
                'last_name' => $token_data['last_name']
            ],
            'token' => [
                'expires_at' => $token_data['expires_at'],
                'created_at' => $token_data['created_at']
            ]
        ]
    ]);
}
?>
