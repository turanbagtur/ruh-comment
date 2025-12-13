# Changelog - Ruh Comment

## [6.1] - 2025-12-13

### 🔧 Yeni Özellikler

#### 📌 Yorum Sabitleme (Admin)
- **Sabitleme Butonu** - Admin kullanıcılar yorumları sabitleyebilir
- **Üstte Gösterim** - Sabitlenmiş yorumlar her zaman en üstte
- **Özel Tasarım** - Mor kenarlık, gradient arka plan, yuvarlak köşeler
- **Rozet** - "Sabitlendi" rozeti ile görsel belirginlik
- **Kolay Yönetim** - 3 nokta menüsünden sabitle/kaldır

#### 📋 Yorum Kuralları
- **Admin Ayarı** - Özellikler sekmesinden açılıp kapatılabilir
- **Özel Metin** - Her satır bir kural olarak görüntülenir
- **Dropdown UI** - Tıklanınca açılır/kapanır menü
- **Modern Tasarım** - Mor gradient bullet noktaları

#### 😀 Özelleştirilebilir Tepki Emojileri
- **Admin Ayarı** - Özellikler sekmesinden 6 emoji değiştirilebilir
- **Kolay Düzenleme** - Her tepki için ayrı input alanı
- **Anında Güncelleme** - Kaydet ve hemen yansır
- **Varsayılan Emojiler** - 👍😡🥰😳🥺😔

### 🔐 Giriş/Kayıt Popup
- **Modal Form** - Ayrı sayfa yerine popup modal
- **Tab Sistemi** - Giriş/Kayıt sekmeleri
- **AJAX İşlem** - Sayfa yenilenmeden giriş/kayıt
- **Güvenlik** - Nonce doğrulama, rate limiting

### 🐛 Düzeltmeler
- Şifre doğrulama uyumsuzluğu düzeltildi (JS: 6 → 8 karakter)
- `$lang` undefined hatası düzeltildi (`$ruh_lang` olarak güncellendi)
- Dropdown menü z-index sorunu çözüldü
- Ayar kaydetme sorunu düzeltildi (sanitize_settings güncellendi)

### 📝 Diğer
- **Sürüm:** 6.1
- **Tarih:** 13 Aralık 2025

---

## [6.0] - 2025-12-09

### 🎨 Tepki Emoji Sistemi - Tamamen Yeniden Tasarlandı
- **Yeni HTML Yapısı** - `.content-reactions` > `.reaction-item` > `.content-reaction-btn` yapısı
- **Modern Tasarım** - Emoji karakterleri ile şık görünüm
- **Yuvarlak Seçim** - Mor (#667EEA) outline ile circular selection
- **PC Görünümü** - Flex layout, 20px gap ile yan yana
- **Mobil Görünümü** - 3x2 grid layout, responsive tasarım
- **Optimistik UI** - Tıklama anında görsel güncelleme, kasma yok
- **Kalıcı Seçim** - Sayfa yenilendiğinde seçili tepki korunuyor

### 🌍 Çoklu Dil Desteği
- **Dil Ayarı** - Admin panelinde Türkçe/İngilizce seçimi
- **Tam Çeviri** - Tüm arayüz metinleri çevrildi:
  - Tepki isimleri (Like, Angry, Love, Wow, Sad, Episode End)
  - Yorum arayüzü (Comments, Reply, Edit, Delete, Submit)
  - Sıralama butonları (Newest, Oldest, Best)
  - Hata ve bilgi mesajları
  - GIF ve Şikayet modalları
- **POT Dosyası** - `languages/ruh-comment.pot` şablon dosyası
- **İngilizce PO** - `languages/ruh-comment-en_US.po` çeviri dosyası

### 🏷️ Rozet Sistemi İyileştirmeleri
- **İsim Görünümü** - Rozetler artık ikon + isim olarak görünüyor
- **Fallback İkon** - SVG boşsa yıldız ikonu gösteriliyor
- **Yorumlarda Rozet** - Yorum yazarının rozetleri görüntüleniyor

### 👤 Profil ve Avatar
- **Varsayılan Avatar** - Profil resmi yoksa ismin ilk harfi gösteriliyor
- **Gradient Arka Plan** - Modern gradient ile avatar fallback

### 🛡️ Şikayet Sistemi
- **Otomatik Gizleme** - Şikayet limiti aşılınca yorum DOM'dan kaldırılıyor
- **Yorum Sayacı** - Gizlenen yorumda sayaç güncelleniyor
- **Slide Animasyonu** - Yumuşak geçiş ile yorum gizleme

### ⚡ Seviye/XP Sistemi
- **Anlık XP** - Yorum yapınca XP hemen güncelleniyor
- **Otomatik Rozet** - Seviye atlayınca rozet kontrolü

### 🔧 Teknik İyileştirmeler
- **WordPress Yönlendirme** - Yorumlar menüsü Ruh Comment'e yönlendiriliyor
- **Performans** - JavaScript optimizasyonları, debounce eklendi
- **CSS Temizliği** - Çakışan stiller düzeltildi

### 🐛 Düzeltmeler
- Yorum sayacı artık doğru çalışıyor
- Tepki emoji dikdörtgen/daire çakışması düzeltildi
- Bold/italic formatlama düzeltildi (hem ana form hem yanıt formu)
- Mobil 3x2 grid düzeni düzeltildi
- "Silinmiş yorum" sorunu çözüldü

### 📝 Diğer
- **Sürüm:** 6.0
- **Yazar:** Solderet
- **Site:** mangaruhu.com

---

## [5.1.1] - 2025-12-07

### Security Fixes
- **Brute Force Protection** - Login rate limiting (5 attempts/15 min lockout)
- **Open Redirect Prevention** - Redirect URL validation for same-domain only
- **User Enumeration Prevention** - Generic error messages on login failure
- **Password Policy** - Minimum 8 character requirement for registration
- **Username Validation** - Alphanumeric + underscore only, 3-30 chars
- **GIF URL Whitelist** - Only Giphy/Tenor domains allowed
- **Nonce Verification** - Added to user profile badge updates

### Bug Fixes
- **Regex Fix** - Link counting regex was malformed (`/<a |http:|https:/i` -> `/<a\s|https?:\/\//i`)
- **Rate Limit Response** - Proper HTTP 429 status codes
- **Parent Comment Validation** - Check if parent exists before reply
- **Self-Like Prevention** - Users cannot like their own comments
- **Self-Report Prevention** - Users cannot report their own comments
- **Comment Validation** - Minimum 3 character requirement

### Performance Improvements
- **REST API Caching** - 5-30 minute cache for API responses
- **Removed init update_option** - Was updating options on every page load (major fix)
- **Transient-based Duplicate Check** - Replaced database query with transient
- **Rate Limiting Optimization** - Transient-based instead of user meta
- **Cache Invalidation** - Proper cache clearing on comment/user updates

### Code Quality
- **Input Sanitization** - sanitize_textarea_field for comment content
- **Type Casting** - Explicit intval() for all numeric inputs
- **Error Responses** - Proper HTTP status codes (400, 403, 404, 429)
- **mb_strlen** - Multibyte string length for Turkish characters

---

## [5.1] - 2025-11-09

### 🎨 Tasarım - Ultra Modern Yenilenme
- ✅ **Glassmorphism Tasarım** - Blur efektleri, yarı saydam kartlar
- ✅ **Gradient Sistem** - Modern gradient color palette (#667eea → #764ba2)
- ✅ **Yeni Seviye Rozetleri** - Futuristik gradient rozetler, shimmer animasyonları
- ✅ **3D Rozet Efektleri** - Hover'da rotate ve scale animasyonları
- ✅ **Modern Butonlar** - Bounce efektleri, glow animasyonları
- ✅ **Tepki Sistemi Yenilendi** - Büyük emoji'ler, floating animasyonlar
- ✅ **Responsive İyileştirmeler** - Mobile-first, touch-friendly
- ✅ **Dark Mode Optimizasyonu** - Tam dark theme uyumu

### 🔐 Güvenlik İyileştirmeleri
- ✅ **SQL Injection Koruması** - Tüm sorgular prepared statements ile
- ✅ **XSS Koruması İyileştirildi** - wp_kses güncellemesi, URL sanitization
- ✅ **File Upload Güvenliği** - getimagesize() ile MIME type doğrulama
- ✅ **Nonce Sistemi Güçlendirildi** - Özel nonce'lar her işlem için
- ✅ **Rate Limiting İyileştirildi** - Transient tabanlı kontrol

### ⚡ Performance Optimizasyonu
- ✅ **Cache Sistemi Eklendi** - WordPress Object Cache entegrasyonu
- ✅ **Database İndeksleri** - Tüm tablolara performans index'leri
- ✅ **N+1 Query Problemi Çözüldü** - Batch queries
- ✅ **Lazy Loading** - Görseller için lazy loading
- ✅ **Cache Temizleme** - Otomatik cache invalidation

### 🚀 Yeni Özellikler
- ✅ **Mention Sistemi** - @kullaniciadi ile etiketleme, autocomplete
- ✅ **Markdown Desteği** - **bold**, *italic*, `code`, ```code blocks```
- ✅ **Syntax Highlighting** - Prism.js entegrasyonu
- ✅ **Yorum Arama** - Gerçek zamanlı arama sistemi
- ✅ **REST API** - WordPress REST API endpoints
- ✅ **Import/Export** - Disqus import, CSV/JSON export
- ✅ **Analytics Dashboard** - Chart.js ile grafikler
- ✅ **Gelişmiş Moderasyon** - Toplu işlemler, şikayet yönetimi

### 🛡️ Spam Koruması
- ✅ **Çoklu Honeypot** - 3 görünmez alan
- ✅ **Bot Detection** - Hız ve etkileşim kontrolü
- ✅ **Gelişmiş Rate Limiting** - IP ve kullanıcı bazlı
- ✅ **Form Timing** - Minimum süre kontrolü
- ✅ **Mouse/Keyboard Detection** - İnsan doğrulama

### 📊 Analytics & Raporlama
- ✅ **Analytics Dashboard** - Grafikler ve metrikler
- ✅ **Yorum Trendi** - 30 günlük grafik
- ✅ **Tepki İstatistikleri** - Pie chart
- ✅ **Top Users** - En aktif kullanıcılar
- ✅ **Popular Comments** - En beğenilen yorumlar
- ✅ **Weekly Stats** - Haftalık özetler

### 🔧 Geliştirici İyileştirmeleri
- ✅ **DocBlocks** - Tüm fonksiyonlara eksiksiz dokümantasyon
- ✅ **Code Organization** - Modüler yapı
- ✅ **Error Handling** - İyileştirilmiş hata yönetimi
- ✅ **Backward Compatibility** - Eski CSS de yükleniyor

### 🐛 Bug Fixes
- ✅ Profile page GIF taşma sorunu düzeltildi
- ✅ Dropdown menu z-index sorunu çözüldü
- ✅ Mobile responsive sorunları giderildi
- ✅ Cache senkronizasyon sorunları düzeltildi

---

## [5.0] - 2024

### Initial Release
- ✅ Temel yorum sistemi
- ✅ Tepki sistemi
- ✅ Seviye/rozet sistemi
- ✅ GIF desteği
- ✅ Spoiler sistemi
- ✅ Profil sayfası
- ✅ Auth sistemi

---

## Gelecek Güncellemeler (Roadmap)

### [5.2] - Planlanan
- 📧 E-posta bildirimleri
- 🔔 Push notifications
- 🤖 Discord/Slack webhook'ları
- 📱 PWA desteği
- 🎥 Video upload desteği
- 🖼️ Galeri sistemi
- 🔗 Link preview

### [5.3] - Uzun Vadeli
- 🤖 AI moderasyon (OpenAI)
- 🌍 Çoklu dil desteği (WPML)
- 📊 Advanced analytics (Google Analytics entegrasyonu)
- 🎮 Gamification (başarımlar, görevler)
- 💬 Real-time chat
- 📱 Mobile app API

---

**Not:** Semantic versioning kullanılmaktadır (MAJOR.MINOR.PATCH)

