# Uptime Monitor API

Mobil uygulama için REST API endpoint'leri.

## Base URL
```
http://localhost/uptime/api
```

## Authentication
API'ye erişim için Bearer token kullanılır:
```
Authorization: Bearer <token>
```

## Endpoints

### 1. Authentication

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "token": "base64_encoded_token",
    "user": {
        "id": 1,
        "email": "user@example.com",
        "role": "user"
    }
}
```

#### Register
```http
POST /api/auth/register
Content-Type: application/json

{
    "email": "newuser@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Kayıt başarılı"
}
```

### 2. Sites

#### Get User Sites
```http
GET /api/sites
Authorization: Bearer <token>
```

**Response:**
```json
{
    "sites": [
        {
            "id": 1,
            "url": "https://example.com",
            "monitor_path": "/abc.php",
            "name": "My Site",
            "description": "Site açıklaması",
            "status": "active",
            "last_status": "up",
            "last_check": "2025-09-29 12:00:00",
            "response_time": 150,
            "uptime_24h": 99.5,
            "uptime_7d": 98.2,
            "uptime_30d": 97.8,
            "notification_emails": "admin@example.com",
            "created_at": "2025-09-29 10:00:00"
        }
    ]
}
```

#### Add New Site
```http
POST /api/sites
Authorization: Bearer <token>
Content-Type: application/json

{
    "url": "https://example.com",
    "monitor_path": "/abc.php",
    "name": "My Site",
    "description": "Site açıklaması",
    "notification_emails": "admin@example.com"
}
```

**Response:**
```json
{
    "success": true,
    "site_id": 123
}
```

#### Delete Site
```http
POST /api/sites/delete
Authorization: Bearer <token>
Content-Type: application/json

{
    "site_id": 123
}
```

**Response:**
```json
{
    "success": true
}
```

### 3. Dashboard

#### Get Dashboard Data
```http
GET /api/dashboard
Authorization: Bearer <token>
```

**Response:**
```json
{
    "stats": {
        "total_sites": 5,
        "active_sites": 4,
        "avg_uptime": 98.5
    },
    "sites": [
        {
            "id": 1,
            "url": "https://example.com",
            "monitor_path": "/abc.php",
            "name": "My Site",
            "status": "active",
            "last_status": "up",
            "uptime_24h": 99.5,
            "uptime_7d": 98.2,
            "uptime_30d": 97.8
        }
    ]
}
```

### 4. Notifications

#### Get Notifications
```http
GET /api/notifications
Authorization: Bearer <token>
```

**Response:**
```json
{
    "notifications": [
        {
            "id": 1,
            "type": "site_down",
            "message": "Site kesintide: example.com",
            "timestamp": "2025-09-29 12:00:00",
            "read": false
        }
    ]
}
```

## Error Responses

### 400 Bad Request
```json
{
    "error": "URL ve site adı gerekli"
}
```

### 401 Unauthorized
```json
{
    "error": "Authorization token gerekli"
}
```

### 404 Not Found
```json
{
    "error": "Endpoint bulunamadı"
}
```

### 500 Internal Server Error
```json
{
    "error": "Sunucu hatası"
}
```

## Mobile App Integration

### Flutter/Dart Example
```dart
class UptimeApiService {
  static const String baseUrl = 'http://localhost/uptime/api';
  String? _token;
  
  Future<bool> login(String email, String password) async {
    final response = await http.post(
      Uri.parse('$baseUrl/auth/login'),
      headers: {'Content-Type': 'application/json'},
      body: jsonEncode({
        'email': email,
        'password': password,
      }),
    );
    
    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      _token = data['token'];
      return true;
    }
    return false;
  }
  
  Future<List<Site>> getSites() async {
    final response = await http.get(
      Uri.parse('$baseUrl/sites'),
      headers: {
        'Authorization': 'Bearer $_token',
      },
    );
    
    if (response.statusCode == 200) {
      final data = jsonDecode(response.body);
      return (data['sites'] as List)
          .map((site) => Site.fromJson(site))
          .toList();
    }
    throw Exception('Failed to load sites');
  }
}
```

### React Native Example
```javascript
class UptimeApiService {
  constructor() {
    this.baseUrl = 'http://localhost/uptime/api';
    this.token = null;
  }
  
  async login(email, password) {
    const response = await fetch(`${this.baseUrl}/auth/login`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ email, password }),
    });
    
    if (response.ok) {
      const data = await response.json();
      this.token = data.token;
      return true;
    }
    return false;
  }
  
  async getSites() {
    const response = await fetch(`${this.baseUrl}/sites`, {
      headers: {
        'Authorization': `Bearer ${this.token}`,
      },
    });
    
    if (response.ok) {
      const data = await response.json();
      return data.sites;
    }
    throw new Error('Failed to load sites');
  }
}
```

## Push Notifications

Gelecekte push notification sistemi eklenebilir:
- Firebase Cloud Messaging (FCM)
- Apple Push Notification Service (APNs)
- Web Push API

## Security Notes

- Token'lar 24 saat geçerlidir
- HTTPS kullanımı önerilir
- Rate limiting eklenebilir
- JWT token sistemi geliştirilebilir
