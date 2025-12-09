# 🌐 Ruh Comment - REST API Dokümantasyonu

## 📋 Genel Bilgiler

**Base URL:** `https://yoursite.com/wp-json/ruh-comment/v1`

**Authentication:** WordPress standart authentication (Cookies, Application Passwords, JWT)

**Response Format:** JSON

## 🔐 Authentication

### Giriş Gerektiren Endpoint'ler
- POST `/comment` - Yorum oluştur

### Public Endpoint'ler
- GET `/comments/{post_id}` - Yorumları listele
- GET `/comment/{id}` - Tek yorum detayı
- GET `/user/{id}/stats` - Kullanıcı istatistikleri
- GET `/reactions/{post_id}` - Tepkiler
- GET `/leaderboard` - Liderlik tablosu

## 📚 Endpoint'ler

### 1. Yorumları Getir

**Endpoint:** `GET /comments/{post_id}`

**Açıklama:** Belirli bir post'un yorumlarını getirir.

**Parametreler:**
| Parametre | Tip | Zorunlu | Varsayılan | Açıklama |
|-----------|-----|---------|------------|----------|
| `post_id` | int | Evet | - | Post ID |
| `page` | int | Hayır | 1 | Sayfa numarası |
| `per_page` | int | Hayır | 10 | Sayfa başına yorum |

**Örnek İstek:**
```bash
curl -X GET "https://yoursite.com/wp-json/ruh-comment/v1/comments/123?page=1&per_page=20"
```

**Örnek Yanıt:**
```json
{
  "comments": [
    {
      "id": 456,
      "post_id": 123,
      "parent_id": 0,
      "author": {
        "id": 1,
        "name": "John Doe",
        "avatar": "https://..."
      },
      "content": "Harika bir yazı!",
      "date": "2025-11-09 10:30:00",
      "likes": 15,
      "dislikes": 2,
      "score": 13
    }
  ],
  "total": 45,
  "pages": 5
}
```

---

### 2. Tek Yorum Detayı

**Endpoint:** `GET /comment/{id}`

**Açıklama:** Tek bir yorumun detaylarını getirir.

**Örnek İstek:**
```bash
curl -X GET "https://yoursite.com/wp-json/ruh-comment/v1/comment/456"
```

**Örnek Yanıt:**
```json
{
  "id": 456,
  "post_id": 123,
  "parent_id": 0,
  "author": {
    "id": 1,
    "name": "John Doe",
    "avatar": "https://..."
  },
  "content": "Harika bir yazı!",
  "date": "2025-11-09 10:30:00",
  "likes": 15,
  "dislikes": 2,
  "score": 13
}
```

---

### 3. Yorum Oluştur

**Endpoint:** `POST /comment`

**Açıklama:** Yeni yorum oluşturur. **Authentication gerekli!**

**Body Parametreleri:**
| Parametre | Tip | Zorunlu | Açıklama |
|-----------|-----|---------|----------|
| `post_id` | int | Evet | Yorum yapılacak post ID |
| `content` | string | Evet | Yorum içeriği |
| `parent` | int | Hayır | Üst yorum ID (yanıt için) |

**Örnek İstek:**
```bash
curl -X POST "https://yoursite.com/wp-json/ruh-comment/v1/comment" \
  -H "Content-Type: application/json" \
  -u "username:password" \
  -d '{
    "post_id": 123,
    "content": "Muhteşem bir yazı!",
    "parent": 0
  }'
```

**Örnek Yanıt:**
```json
{
  "id": 789,
  "post_id": 123,
  "parent_id": 0,
  "author": {
    "id": 1,
    "name": "John Doe",
    "avatar": "https://..."
  },
  "content": "Muhteşem bir yazı!",
  "date": "2025-11-09 11:00:00",
  "likes": 0,
  "dislikes": 0,
  "score": 0
}
```

---

### 4. Kullanıcı İstatistikleri

**Endpoint:** `GET /user/{id}/stats`

**Açıklama:** Kullanıcının seviye, rozet ve istatistik bilgilerini getirir.

**Örnek İstek:**
```bash
curl -X GET "https://yoursite.com/wp-json/ruh-comment/v1/user/1/stats"
```

**Örnek Yanıt:**
```json
{
  "user_id": 1,
  "level": 25,
  "xp": 5430,
  "stats": {
    "comment_count": 156,
    "total_likes": 432,
    "avg_likes": 2.8,
    "badge_count": 8,
    "join_date": "2024-01-15 08:00:00",
    "last_activity": 1699520400,
    "days_active": 298
  },
  "badges": [
    {
      "badge_id": 1,
      "badge_name": "Aktif Üye",
      "badge_svg": "<svg>...</svg>",
      "is_automated": 1
    }
  ]
}
```

---

### 5. Tepkileri Getir

**Endpoint:** `GET /reactions/{post_id}`

**Açıklama:** Post'un aldığı tepkileri getirir.

**Örnek İstek:**
```bash
curl -X GET "https://yoursite.com/wp-json/ruh-comment/v1/reactions/123"
```

**Örnek Yanıt:**
```json
{
  "reactions": {
    "guzel": {
      "reaction": "guzel",
      "count": 45
    },
    "sevdim": {
      "reaction": "sevdim",
      "count": 32
    },
    "asik_oldum": {
      "reaction": "asik_oldum",
      "count": 28
    }
  },
  "total": 105
}
```

---

### 6. Liderlik Tablosu

**Endpoint:** `GET /leaderboard`

**Açıklama:** En aktif kullanıcıları getirir.

**Parametreler:**
| Parametre | Tip | Zorunlu | Varsayılan | Açıklama |
|-----------|-----|---------|------------|----------|
| `limit` | int | Hayır | 10 | Maksimum kullanıcı sayısı |

**Örnek İstek:**
```bash
curl -X GET "https://yoursite.com/wp-json/ruh-comment/v1/leaderboard?limit=5"
```

**Örnek Yanıt:**
```json
{
  "leaderboard": [
    {
      "user_id": 1,
      "display_name": "John Doe",
      "level": 45,
      "xp": 12500,
      "comment_count": 345,
      "total_likes": 890,
      "avatar": "https://..."
    },
    {
      "user_id": 2,
      "display_name": "Jane Smith",
      "level": 38,
      "xp": 9800,
      "comment_count": 289,
      "total_likes": 654,
      "avatar": "https://..."
    }
  ]
}
```

---

## 🔧 Hata Kodları

| Kod | Açıklama |
|-----|----------|
| 200 | Başarılı |
| 201 | Oluşturuldu |
| 400 | Geçersiz istek |
| 401 | Authentication gerekli |
| 403 | Yetki yok |
| 404 | Bulunamadı |
| 500 | Server hatası |

**Örnek Hata Yanıtı:**
```json
{
  "code": "not_found",
  "message": "Yorum bulunamadı.",
  "data": {
    "status": 404
  }
}
```

---

## 📝 Kullanım Örnekleri

### JavaScript (Fetch API)

```javascript
// Yorumları getir
fetch('https://yoursite.com/wp-json/ruh-comment/v1/comments/123')
  .then(response => response.json())
  .then(data => console.log(data));

// Yorum gönder (authentication gerekli)
fetch('https://yoursite.com/wp-json/ruh-comment/v1/comment', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Authorization': 'Basic ' + btoa('username:password')
  },
  body: JSON.stringify({
    post_id: 123,
    content: 'Harika yazı!',
    parent: 0
  })
})
.then(response => response.json())
.then(data => console.log(data));
```

### jQuery

```javascript
// Kullanıcı istatistikleri
$.ajax({
  url: 'https://yoursite.com/wp-json/ruh-comment/v1/user/1/stats',
  method: 'GET',
  success: function(data) {
    console.log('Level:', data.level);
    console.log('XP:', data.xp);
    console.log('Comments:', data.stats.comment_count);
  }
});
```

### PHP (WordPress)

```php
// wp_remote_get kullanarak
$response = wp_remote_get('https://yoursite.com/wp-json/ruh-comment/v1/comments/123');

if (!is_wp_error($response)) {
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    foreach ($data['comments'] as $comment) {
        echo $comment['content'];
    }
}
```

### Python

```python
import requests

# Yorumları getir
response = requests.get('https://yoursite.com/wp-json/ruh-comment/v1/comments/123')
data = response.json()

for comment in data['comments']:
    print(f"{comment['author']['name']}: {comment['content']}")

# Yorum gönder
auth = ('username', 'password')
comment_data = {
    'post_id': 123,
    'content': 'Python ile yorum!',
    'parent': 0
}

response = requests.post(
    'https://yoursite.com/wp-json/ruh-comment/v1/comment',
    json=comment_data,
    auth=auth
)

print(response.json())
```

---

## 🔄 Rate Limiting

API rate limiting aktif değil ancak WordPress'in kendi rate limiting'i geçerlidir.

**Önerilen Kullanım:**
- Maksimum 60 istek/dakika
- Burst: 100 istek
- Cache kullanın (GET istekleri için)

---

## 🎯 Best Practices

### 1. Cache Kullanın
GET istekleri için client-side cache kullanın.

```javascript
const cache = new Map();

async function getComments(postId) {
  const key = `comments_${postId}`;
  
  if (cache.has(key)) {
    return cache.get(key);
  }
  
  const response = await fetch(`/wp-json/ruh-comment/v1/comments/${postId}`);
  const data = await response.json();
  
  cache.set(key, data);
  setTimeout(() => cache.delete(key), 60000); // 1 dakika
  
  return data;
}
```

### 2. Error Handling
Her zaman hata kontrolü yapın.

```javascript
try {
  const response = await fetch(url);
  
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  
  const data = await response.json();
  return data;
  
} catch (error) {
  console.error('API Error:', error);
}
```

### 3. Pagination
Büyük veri setleri için pagination kullanın.

```javascript
async function getAllComments(postId) {
  let allComments = [];
  let page = 1;
  let hasMore = true;
  
  while (hasMore) {
    const response = await fetch(
      `/wp-json/ruh-comment/v1/comments/${postId}?page=${page}&per_page=50`
    );
    const data = await response.json();
    
    allComments = allComments.concat(data.comments);
    hasMore = data.pages > page;
    page++;
  }
  
  return allComments;
}
```

---

## 🔌 Webhook Entegrasyonu (Gelecek)

Version 5.2'de eklenecek:

```php
// Yorum gönderildiğinde webhook tetikle
add_action('wp_insert_comment', function($comment_id, $comment) {
    $webhook_url = get_option('ruh_webhook_url');
    
    if ($webhook_url) {
        wp_remote_post($webhook_url, array(
            'body' => json_encode(array(
                'event' => 'comment.created',
                'comment' => ruh_format_comment($comment)
            ))
        ));
    }
}, 10, 2);
```

---

## 📊 Rate Limits & Quotas

### Önerilen Limitler
- **Anonymous:** 10 istek/dakika
- **Authenticated:** 60 istek/dakika
- **Admin:** Sınırsız

### Headers
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1699520400
```

---

## 🐛 Debugging

### Debug Mode
WordPress debug mode'u aktifleştirin:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### API Test
```bash
# Health check
curl https://yoursite.com/wp-json/

# Ruh Comment check
curl https://yoursite.com/wp-json/ruh-comment/v1/
```

---

## 📞 Destek

API sorunları için:
- 📧 Email: api@ruh.dev
- 🐙 GitHub Issues: https://github.com/ruh-development/ruh-comment/issues
- 📚 Dokümantasyon: https://docs.ruh.dev

---

**Version:** 5.1  
**Last Updated:** November 9, 2025

