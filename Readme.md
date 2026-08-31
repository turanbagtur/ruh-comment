# Ruh Comment - Ultra Modern WordPress Yorum Sistemi

[![Version](https://img.shields.io/badge/version-8.0-blue.svg)](https://mangaruhu.com)
[![WordPress](https://img.shields.io/badge/wordpress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/php-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0-green.svg)](LICENSE)

Disqus benzeri, **glassmorphism tasarımlı**, tepki sistemi, seviye/rozet mekanizması ve tam teşekküllü topluluk özelliklerine sahip ultra modern WordPress yorum eklentisi.

## ✨ Özellikler

### 🎯 Temel Özellikler
- ✅ **Modern Glassmorphism Tasarım** - Gradient'lar, blur efektleri, 3D animasyonlar
- ✅ **AJAX Tabanlı** - Sayfa yenilemeden yorum sistemi
- ✅ **Tepki Sistemi** - 6 farklı emoji tepkisi, yuvarlak seçim, kalıcı durum
- ✅ **Beğeni/Beğenmeme** - Modern kalp butonu
- ✅ **İç İçe Yanıtlar** - Sınırsız derinlikte yanıt sistemi
- ✅ **GIF Desteği** - Giphy ve Tenor entegrasyonu
- ✅ **Yorum Arama** - Metin ve kullanıcı filtresi
- ✅ **Yorum Rozetleri** - En çok beğenilen ve en çok tartışılan yorumlara otomatik rozet
- ✅ **Sıralama** - En yeni, en eski, en iyi, en çok tartışılan
- ✅ **Spoiler Sistemi** - Tıkla-göster spoiler etiketleri
- ✅ **Mention Sistemi** - @kullaniciadi ile kullanıcıları etiketle
- ✅ **Markdown Desteği** - Kolay metin formatlama
- ✅ **Çoklu Dil Desteği** - Türkçe ve İngilizce arayüz

### 🎮 Seviye & Rozet Sistemi
- 🏆 **XP Sistemi** - Yorum yaptıkça XP kazan, anlık güncelleme
- 📊 **Seviye Sistemi** - Otomatik seviye atlama
- 🎖️ **Rozet Sistemi** - Manuel/otomatik rozetler, nadirlik (common/rare/legendary)
- 🔔 **Bildirimler** - Yanıt, mention ve rozet bildirimleri
- 🌟 **Profil Sayfası** - Kullanıcı profilleri ve istatistikler
- 👤 **Avatar Fallback** - Profil resmi yoksa isim baş harfi

### 🛡️ Güvenlik
- ✅ **SQL Injection Koruması** - Prepared statements
- ✅ **XSS Koruması** - wp_kses ile temizleme
- ✅ **CSRF Koruması** - Nonce sistemleri
- ✅ **Rate Limiting** - IP ve kullanıcı bazlı
- ✅ **Spam Koruması** - Honeypot, küfür filtresi, spam skoru
- ✅ **Güvenli File Upload** - MIME type validation
- ✅ **API Key Gizleme** - Giphy/Tenor anahtarları sunucu tarafında
- ✅ **Webhook** - Discord ve Telegram yeni yorum bildirimi
- ✅ **Uninstall Script** - Eklenti kaldırıldığında tam veri temizliği

### ⚡ Performance
- 🚀 **Cache Sistemi** - WordPress Object Cache
- 🚀 **Database İndeksleri** - Optimize edilmiş sorgular
- 🚀 **Lazy Loading** - GIF ve görsel lazy loading
- 🚀 **Minification** - CSS/JS optimizasyonu

### 🔧 Gelişmiş Özellikler
- 📊 **Analytics Dashboard** - Detaylı istatistikler ve grafikler
- 🔍 **Yorum Arama** - Gerçek zamanlı arama
- 📱 **Responsive** - Mobil-first tasarım, koyu/açık/otomatik renk modu
- 🎨 **Temalar** - Glassmorphism ve Disqus tarzı
- 🌐 **REST API** - WordPress REST API desteği
- 📥 **Import/Export** - Disqus import, CSV/JSON export
- 🎨 **Syntax Highlighting** - Prism.js ile kod renklendirme
- 🔨 **Gelişmiş Moderasyon** - Toplu işlemler, otomatik moderasyon

## 🚀 Kurulum

1. Plugin'i WordPress'in `wp-content/plugins/` klasörüne yükleyin
2. WordPress admin panelden eklentiyi etkinleştirin
3. "Ruh Comment" menüsünden ayarları yapılandırın
4. Shortcode'lar ile profil ve auth sayfalarını oluşturun

## 📋 Shortcode'lar

### Kullanıcı Profili
```php
[ruh_user_profile]
```
Kullanıcı profil sayfası için. Seviye, rozetler, yorumlar, istatistikler gösterir.

### Giriş & Kayıt
```php
[ruh_auth]
```
Kombine giriş/kayıt formu. Modern tabbed design ile.

## 🎨 Tasarım Özellikleri

### Glassmorphism Efektleri
- Blur efektli yarı saydam kartlar
- Gradient border'lar
- Backdrop filter desteği
- Modern gölgelendirmeler

### Animasyonlar
- Fade in/out animasyonları
- Hover efektleri
- Scale ve rotate transformasyonlar
- Gradient shimmer efektleri
- Pulse animasyonları

### Renk Paleti
```css
Primary Gradient: #667eea → #764ba2
Accent Purple: #a855f7
Accent Pink: #ec4899
Accent Blue: #3b82f6
Accent Cyan: #06b6d4
```

## 🔐 Güvenlik Özellikleri

### Spam Koruması
- **Honeypot Alanları** - Çoklu görünmez alan
- **Rate Limiting** - IP ve kullanıcı bazlı
- **Küfür Filtresi** - Özelleştirilebilir kelime listesi
- **Link Limiti** - Spam link engelleme
- **Duplicate Check** - Aynı yorum engelleme
- **Bot Detection** - Hız ve etkileşim kontrolü

### Kullanıcı Yönetimi
- **Ban Sistemi** - Kalıcı engelleme
- **Timeout Sistemi** - Geçici susturma (24 saat)
- **Otomatik Moderasyon** - Şikayet bazlı

## 📊 REST API Endpoints

```
GET  /wp-json/ruh-comment/v1/comments/{post_id}
GET  /wp-json/ruh-comment/v1/comment/{id}
POST /wp-json/ruh-comment/v1/comment
GET  /wp-json/ruh-comment/v1/user/{id}/stats
GET  /wp-json/ruh-comment/v1/reactions/{post_id}
GET  /wp-json/ruh-comment/v1/leaderboard
```

## 🎯 Rozet Sistemi

### Manuel Rozetler
Admin panelden oluşturun ve kullanıcılara manuel atayın.

### Otomatik Rozetler
Koşullar:
- **Yorum Sayısı** - X adet yorum yapan kullanıcılar
- **Beğeni Sayısı** - Toplam X beğeni alan kullanıcılar
- **Seviye** - Seviye X'e ulaşan kullanıcılar

### Rozet Şablonları
- 🛡️ Shield
- ⭐ Star
- ❤️ Heart
- 🏆 Trophy
- 💎 Diamond
- 🔥 Flame
- 👑 Crown
- 🥇 Medal
- 🚀 Rocket
- ⚡ Lightning
- 💎 Gem
- 🔔 Bell
- ✨ Magic
- 🎯 Target
- 🕐 Clock
- 👁️ Eye
- 📚 Book
- 🧩 Puzzle

## 📈 Analytics

### Dashboard Metrikleri
- Toplam yorum sayısı
- Aktif kullanıcı sayısı
- Tepki istatistikleri
- Haftalık trendler
- En aktif kullanıcılar
- En popüler yorumlar

### Grafikler
- Yorum trendi (30 gün)
- Tepki dağılımı (pie chart)
- Kullanıcı aktivitesi
- Rozet kazanım oranları

## 🛠️ Teknik Detaylar

### Veritabanı Tabloları
```sql
wp_ruh_reactions        - Tepkiler
wp_ruh_user_levels      - Kullanıcı seviyeleri
wp_ruh_badges           - Rozetler
wp_ruh_user_badges      - Kullanıcı-rozet ilişkisi
wp_ruh_reports          - Şikayetler
```

### Cache Sistemi
- WordPress Object Cache kullanımı
- 1 saat cache (user level/badges)
- Otomatik cache temizleme
- Transient API desteği

### Performance
- Database index'leri
- Optimize edilmiş sorgular
- N+1 query problemi çözüldü
- Lazy loading
- Minification hazır

## 🎨 Özelleştirme

### CSS Variables
```css
--primary-gradient: Temel gradient
--glass-bg: Glassmorphism arkaplan
--blur: Blur miktarı
--radius: Border radius
```

### Hooks & Filters
```php
// Yorum içeriğini özelleştir
add_filter('comment_text', 'my_custom_function', 30);

// XP miktarını değiştir
add_filter('ruh_xp_per_comment', function($xp) {
    return $xp * 2; // 2 katına çıkar
});

// Seviye hesaplamasını özelleştir
add_filter('ruh_calculate_level', 'my_level_function', 10, 2);
```

## 📱 Responsive Tasarım

- **Mobile First** yaklaşım
- Touch-friendly butonlar (min 44px)
- Optimize edilmiş grid sistemleri
- Adaptive font sizes
- Mobil menüler ve modallar

## 🔄 Yükseltme

### 6.5 → 7.0 Değişiklikleri
- ✅ **Mention Bildirim Sistemi** - @kullaniciadi etiketleme bildirimleri
- ✅ **Yorum Düzenleme Geçmişi** - Düzenleme kayıtları tutuluyor
- ✅ **Giphy API Key Gizleme** - Server-side proxy ile güvenlik
- ✅ **Uninstall Script** - Temiz kaldırma desteği
- ✅ **Options Cache** - Performans için singleton cache sınıfı
- ✅ **CSS Ayıklama** - Inline stiller ayrı dosyaya taşındı
- ✅ **WordPress 6.7 Uyumluluğu**

### 5.1.1 → 6.0 Değişiklikleri
- ✅ **Tepki Sistemi Yenilendi** - Modern emoji tasarım, yuvarlak seçim (#667EEA)
- ✅ **Çoklu Dil Desteği** - Admin panelden Türkçe/İngilizce seçimi
- ✅ **Rozet İyileştirmeleri** - İkon + isim görünümü, fallback ikon
- ✅ **Avatar Fallback** - İsim baş harfi ile gradient avatar
- ✅ **Optimistik UI** - Tepki tıklamada anlık görsel güncelleme
- ✅ **Mobil Grid** - 3x2 tepki grid düzeni
- ✅ **Şikayet Sistemi** - Otomatik yorum gizleme
- ✅ **XP Anlık** - Yorum sonrası anında XP güncelleme
- ✅ **Performans** - Debounce, CSS optimizasyonu

### 5.0 → 5.1 Değişiklikleri
- ✅ Glassmorphism tasarım sistemi
- ✅ Güvenlik iyileştirmeleri (SQL injection, XSS)
- ✅ Cache sistemi eklendi
- ✅ REST API desteği
- ✅ Mention sistemi
- ✅ Markdown desteği
- ✅ Syntax highlighting
- ✅ Analytics dashboard
- ✅ Import/Export sistemi
- ✅ Gelişmiş spam koruması
- ✅ Database optimization

## 🤝 Katkıda Bulunma

Pull request'ler memnuniyetle karşılanır!

## 📄 Lisans

GPL v2 veya üzeri

## 👨‍💻 Geliştirici

**Solderet**
- Website: https://mangaruhu.com
- E-posta: info@mangaruhu.com

## 🙏 Teşekkürler

- Chart.js - Grafikler için
- Prism.js - Syntax highlighting için
- Giphy API - GIF desteği için

---

**⭐ Bu projeyi beğendiyseniz yıldız vermeyi unutmayın!**
