# 🔄 Ruh Comment - Yükseltme Kılavuzu

## 📦 5.0 → 5.1 Yükseltme

### ⚠️ Önemli Notlar

1. **Yedek Alın!** 
   - Veritabanınızı yedekleyin
   - Plugin dosyalarını yedekleyin
   - `Ruh Comment → İçe/Dışa Aktar → JSON Yedek` ile tam yedek alın

2. **Minimum Gereksinimler**
   - WordPress 5.0+
   - PHP 7.4+
   - MySQL 5.6+

3. **Cache Temizliği**
   - WordPress object cache'i temizleyin
   - CDN cache'i temizleyin
   - Browser cache'i temizleyin (Ctrl+F5)

### 🚀 Yükseltme Adımları

#### Adım 1: Mevcut Eklentiyi Deaktive Edin
1. **Eklentiler** menüsüne gidin
2. **Ruh Comment** altında **Devre Dışı Bırak** tıklayın
3. ⚠️ **Verileri silmeyin!** Sadece deaktive edin

#### Adım 2: Eski Dosyaları Yedekleyin
```bash
# FTP/SSH ile
mv wp-content/plugins/ruh-comment wp-content/plugins/ruh-comment-backup-5.0
```

#### Adım 3: Yeni Versiyonu Yükleyin
1. **Eklentiler → Yeni Ekle → Eklenti Yükle**
2. `ruh-comment-5.1.zip` dosyasını yükleyin
3. **Şimdi Yükle** butonuna tıklayın

#### Adım 4: Eklentiyi Etkinleştirin
1. **Eklentiler** menüsüne gidin
2. **Ruh Comment** altında **Etkinleştir** tıklayın
3. ✅ Veritabanı otomatik güncellenecektir

#### Adım 5: Cache Temizliği
```bash
# WP-CLI ile (opsiyonel)
wp cache flush
wp transient delete --all
```

#### Adım 6: Ayarları Kontrol Edin
1. **Ruh Comment → Ayarlar** menüsüne gidin
2. Tüm ayarların korunduğunu kontrol edin
3. Gerekirse ayarları güncelleyin

### 🎨 Yeni Özellikler - Kurulum

#### Modern Glassmorphism Tasarım
Otomatik aktif! Herhangi bir ayar gerekmez.

#### Mention Sistemi
1. Otomatik aktif
2. Kullanıcılar yorumlarda `@kullaniciadi` yazabilir
3. Autocomplete otomatik çalışır

#### Markdown Desteği
1. Otomatik aktif
2. Kullanıcılar markdown yazabilir:
   - `**bold**`
   - `*italic*`
   - `` `code` ``
   - ``` ```code block``` ```

#### Syntax Highlighting
1. Otomatik aktif (Prism.js CDN'den yüklenir)
2. ``` işaretleri arasına kod yazın
3. Dil belirtmek için: ``` ```javascript ```

#### Yorum Arama
1. Otomatik aktif
2. Yorum başlığının altında arama kutusu görünür

#### Analytics Dashboard
1. **Ruh Comment → Analytics** menüsüne gidin
2. Grafikler otomatik yüklenecektir
3. Chart.js CDN'den yüklenir

#### REST API
1. Otomatik aktif
2. Endpoint'ler: `/wp-json/ruh-comment/v1/*`
3. Test: `https://yoursite.com/wp-json/ruh-comment/v1/`

#### Import/Export
1. **Ruh Comment → İçe/Dışa Aktar** menüsüne gidin
2. Disqus XML import yapabilirsiniz
3. CSV/JSON export alabilirsiniz

### 🔐 Güvenlik Güncellemeleri

#### Otomatik Güvenlik İyileştirmeleri
- ✅ SQL injection koruması aktif
- ✅ XSS filtreleme güçlendirildi
- ✅ File upload güvenliği artırıldı
- ✅ Rate limiting iyileştirildi
- ✅ Gelişmiş spam koruması aktif

#### Manuel Kontroller
1. **Ayarlar → Tartışma** - Spam ayarlarını kontrol edin
2. **Ruh Comment → Ayarlar → Spam Koruması**
   - Küfür filtresi güncelleyin
   - Link limiti ayarlayın (önerilen: 2)
   - Otomatik moderasyon limiti (önerilen: 3)

### ⚡ Performance Optimizasyonu

#### Cache Sistemi
Otomatik aktif! İlave ayar gerekmez.

**Object Cache Önerilir:**
```bash
# Redis (önerilen)
apt-get install redis php-redis
systemctl enable redis

# Memcached
apt-get install memcached php-memcached
systemctl enable memcached
```

#### Database Index'leri
Otomatik oluşturulur! Manuel kontrol:

```sql
-- Index'leri kontrol et
SHOW INDEX FROM wp_ruh_reactions;
SHOW INDEX FROM wp_ruh_user_levels;
SHOW INDEX FROM wp_ruh_badges;
SHOW INDEX FROM wp_ruh_user_badges;
SHOW INDEX FROM wp_ruh_reports;
```

### 🐛 Sorun Giderme

#### CSS Görünmüyor / Eski Görünüm
```bash
# Cache temizle
1. Browser cache: Ctrl+F5 (Windows) / Cmd+Shift+R (Mac)
2. WordPress cache: WP Rocket → Temizle
3. CDN cache: Cloudflare → Purge Everything
```

#### Database Hataları
```bash
# WP-CLI ile
wp ruh-comment fix-database

# Manuel
# Plugin'i deaktive edip tekrar aktive edin
```

#### AJAX Çalışmıyor
1. Tarayıcı console'u açın (F12)
2. Hataları kontrol edin
3. jQuery yüklü mü kontrol edin
4. Plugin çakışması test edin (diğerlerini deaktive edin)

#### Rozetler Görünmüyor
```sql
-- Rozet cache'i temizle
DELETE FROM wp_options WHERE option_name LIKE '_transient_ruh_user_badges_%';

-- PHP'de
wp_cache_flush();
```

### 🔄 Rollback (Geri Alma)

Sorun yaşıyorsanız 5.0'a geri dönebilirsiniz:

1. **Eklentiler** menüsünden **Ruh Comment** deaktive edin
2. Plugin klasörünü silin
3. Yedek aldığınız 5.0 versiyonunu geri yükleyin
4. Eklentiyi tekrar aktive edin

**Not:** Veritabanı değişiklikleri geri alınmaz!

### 📊 Yükseltme Sonrası Kontroller

✅ **Kontrol Listesi:**
- [ ] Yorumlar görünüyor mu?
- [ ] Tepkiler çalışıyor mu?
- [ ] Beğeni butonu aktif mi?
- [ ] GIF sistemi çalışıyor mu?
- [ ] Mention autocomplete çalışıyor mu?
- [ ] Arama fonksiyonu aktif mi?
- [ ] Profil sayfası açılıyor mu?
- [ ] Analytics dashboard yükleniyor mu?
- [ ] Admin panel erişilebilir mi?
- [ ] Rozetler görünüyor mu?

### 🎨 Tasarım Özelleştirme

#### Renkleri Değiştirme
Theme'inizin CSS'ine ekleyin:

```css
/* Özel renkler */
.comments-area {
    --primary-gradient: linear-gradient(135deg, #your-color-1, #your-color-2);
    --accent-purple: #your-purple;
    --accent-pink: #your-pink;
}
```

#### Glassmorphism'i Kapatma
```css
/* Blur'ü azalt */
.comments-area {
    --blur: 5px; /* Varsayılan: 20px */
}

/* Veya tamamen kapat */
.glass-card {
    backdrop-filter: none !important;
}
```

### 📈 Versiyon Karşılaştırması

| Özellik | 5.0 | 5.1 |
|---------|-----|-----|
| Tasarım | Basit dark | Glassmorphism |
| Güvenlik | Temel | Gelişmiş |
| Performance | Normal | Optimize edilmiş |
| Cache | Yok | WordPress Object Cache |
| API | Yok | REST API |
| Analytics | Yok | Full dashboard |
| Mention | Yok | ✅ |
| Markdown | Yok | ✅ |
| Search | Yok | ✅ |
| Import/Export | Yok | ✅ |

### 🆘 Acil Destek

Yükseltme sırasında sorun yaşıyorsanız:

1. **Debug Mode Açın:**
```php
// wp-config.php'ye ekleyin
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

2. **Loglara Bakın:**
```bash
tail -f wp-content/debug.log
```

3. **Destek İsteyin:**
- 📧 Email: support@ruh.dev
- 🐙 GitHub: https://github.com/ruh-development/ruh-comment/issues

---

**✅ Yükseltme başarılı! Yeni özelliklerin keyfini çıkarın.**

