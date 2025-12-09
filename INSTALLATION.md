# 📦 Ruh Comment - Kurulum Kılavuzu

## 🚀 Hızlı Başlangıç

### Adım 1: Plugin'i Yükleyin

#### Yöntem A: WordPress Admin Panel (Önerilen)
1. WordPress admin paneline giriş yapın
2. **Eklentiler → Yeni Ekle** menüsüne gidin
3. **Eklenti Yükle** butonuna tıklayın
4. `ruh-comment.zip` dosyasını seçin
5. **Şimdi Yükle** butonuna tıklayın
6. **Eklentiyi Etkinleştir** butonuna tıklayın

#### Yöntem B: FTP/SFTP
1. `ruh-comment` klasörünü `/wp-content/plugins/` dizinine yükleyin
2. WordPress admin panelden **Eklentiler** menüsüne gidin
3. **Ruh Comment** eklentisini bulun ve **Etkinleştir** butonuna tıklayın

### Adım 2: Veritabanı Kurulumu

Plugin etkinleştirildiğinde otomatik olarak gerekli tablolar oluşturulur:
- ✅ `wp_ruh_reactions` - Tepki sistemi
- ✅ `wp_ruh_user_levels` - Kullanıcı seviyeleri
- ✅ `wp_ruh_badges` - Rozet sistemi
- ✅ `wp_ruh_user_badges` - Kullanıcı-rozet ilişkisi
- ✅ `wp_ruh_reports` - Şikayet sistemi

### Adım 3: Temel Ayarlar

1. WordPress admin panelden **Ruh Comment → Ayarlar** menüsüne gidin
2. Aşağıdaki ayarları yapılandırın:

#### Genel Ayarlar
- ✅ Tepkileri Aktif Et
- ✅ Beğenileri Aktif Et
- ✅ Sıralamayı Aktif Et
- ✅ Şikayet Etmeyi Aktif Et
- 📝 Yorum Başına XP: **15** (önerilen)

#### Spam Koruması
- 📝 Küfür Filtresi: Yasaklı kelimeleri virgülle ayırarak yazın
- 📝 Maksimum Link Sayısı: **2** (önerilen)
- 📝 Otomatik Moderasyon Limiti: **3** şikayet (önerilen)

### Adım 4: Sayfa Oluşturma

#### Profil Sayfası
1. **Sayfalar → Yeni Ekle** menüsüne gidin
2. Sayfa başlığı: **Profil** veya **Kullanıcı Profili**
3. İçeriğe şu shortcode'u ekleyin:
```php
[ruh_user_profile]
```
4. **Yayımla** butonuna tıklayın
5. URL'i not alın (örn: `https://yoursite.com/profil/`)

#### Giriş & Kayıt Sayfası
1. **Sayfalar → Yeni Ekle** menüsüne gidin
2. Sayfa başlığı: **Giriş** veya **Üye Girişi**
3. İçeriğe şu shortcode'u ekleyin:
```php
[ruh_auth]
```
4. **Yayımla** butonuna tıklayın

### Adım 5: WordPress Ayarları

#### Yorum Ayarları
1. **Ayarlar → Tartışma** menüsüne gidin
2. Aşağıdaki ayarları yapın:
   - ✅ "Yeni makaleler için yorum kutusunu varsayılan olarak aç" - **Aktif**
   - ✅ "Kullanıcılar yorumlarını ... içinde düzenleyebilsin" - **İsteğe bağlı**
   - ⚠️ "Yorum sahibi, daha önce onaylı bir yorumu olmalı" - **Pasif** (Ruh Comment otomatik onaylıyor)

#### Kullanıcı Kayıt
1. **Ayarlar → Genel** menüsüne gidin
2. "Herkes kaydolabilir" - **Aktif** (kayıt izni için)

## 🎯 İleri Seviye Kurulum

### Rozet Sistemi Kurulumu

1. **Ruh Comment → Rozet Yönetimi** menüsüne gidin

#### Manuel Rozet Oluşturma
1. Rozet şablonlarından birini seçin
2. Rozet adını girin (örn: "VIP Üye", "Moderatör")
3. Renk seçin
4. **Rozeti Oluştur** butonuna tıklayın

#### Otomatik Rozet Oluşturma
1. Rozet şablonu seçin
2. Rozet adını girin (örn: "50 Yorum Rozeti")
3. Koşul türünü seçin:
   - **Yorum Sayısı** - Belirli sayıda yorum
   - **Toplam Beğeni** - Toplam beğeni sayısı
   - **Kullanıcı Seviyesi** - Seviye milestone
4. Gerekli değeri girin
5. **Otomatik Rozeti Oluştur**

### Analytics Kurulumu

1. **Ruh Comment → Analytics** menüsüne gidin
2. Dashboard otomatik olarak yüklenecektir
3. Grafikleri görüntüleyin:
   - 📈 Yorum trendi
   - 😍 Tepki dağılımı
   - 🏅 En aktif kullanıcılar
   - 🔥 En popüler yorumlar

### REST API Kullanımı

API endpoint'leri otomatik aktiftir:

```bash
# Yorumları getir
curl https://yoursite.com/wp-json/ruh-comment/v1/comments/123

# Kullanıcı istatistikleri
curl https://yoursite.com/wp-json/ruh-comment/v1/user/1/stats

# Leaderboard
curl https://yoursite.com/wp-json/ruh-comment/v1/leaderboard?limit=10
```

## 🎨 Tasarım Özelleştirme

### CSS Değişkenleri

`wp-content/themes/your-theme/style.css` veya child theme'e ekleyin:

```css
/* Ruh Comment özelleştirmeleri */
.comments-area {
    /* Ana renkleri değiştir */
    --primary-gradient: linear-gradient(135deg, #your-color-1, #your-color-2);
    --accent-purple: #your-purple;
    
    /* Efektleri ayarla */
    --blur: 15px;
    --radius: 16px;
}

/* Tepki butonlarını özelleştir */
.reaction {
    /* Özel stiller */
}
```

### JavaScript Hook'ları

```javascript
// Yorum gönderildikten sonra
jQuery(document).on('ruh_comment_submitted', function(e, data) {
    console.log('Yeni yorum:', data);
});

// Seviye atlandığında
jQuery(document).on('ruh_level_up', function(e, level) {
    console.log('Yeni seviye:', level);
});
```

## 🔧 Sorun Giderme

### Yorumlar Görünmüyor
1. **Ayarlar → Tartışma** - Yorumlar açık mı kontrol edin
2. Tema'nızda `comments_template()` çağrısı var mı kontrol edin
3. **Ruh Comment → Yorum Yönetimi** - Yorumlar onaylı mı kontrol edin

### CSS Yüklenmiyor
1. Tarayıcı cache'ini temizleyin (Ctrl+F5)
2. WordPress cache plugin'i kullanıyorsanız temizleyin
3. **Ruh Comment** menüsünde debug modunu açın (`?ruh_debug=1`)
4. Dosya izinlerini kontrol edin (755 klasörler, 644 dosyalar)

### AJAX Çalışmıyor
1. Tarayıcı console'u açın (F12) ve hataları kontrol edin
2. jQuery yüklendiğinden emin olun
3. Başka plugin'lerle çakışma olabilir - devre dışı bırakıp test edin

### Database Hataları
Plugin'i deaktive edip tekrar aktive edin. Bu, tabloları yeniden oluşturacaktır.

## 📊 Performance Önerileri

### Cache Plugin'leri
Uyumlu cache plugin'leri:
- ✅ WP Rocket
- ✅ W3 Total Cache
- ✅ WP Super Cache
- ✅ LiteSpeed Cache

**Not:** Object cache'i aktifleştirin (Redis/Memcached önerilir)

### CDN Kullanımı
Statik dosyalar için CDN kullanın:
- Cloudflare
- KeyCDN
- BunnyCDN

### Database Optimizasyonu
```sql
-- Eski reports temizleme (30 günden eski)
DELETE FROM wp_ruh_reports WHERE report_time < DATE_SUB(NOW(), INTERVAL 30 DAY);

-- Cache temizleme
DELETE FROM wp_options WHERE option_name LIKE '_transient_ruh_%';
```

## 🔐 Güvenlik Önerileri

### Önerilen Ayarlar
- Rate limiting aktif
- Honeypot aktif
- Küfür filtresi yapılandırılmış
- Link limiti: 2
- Otomatik moderasyon: 3 şikayet

### SSL Kullanın
HTTPS kullanımı şiddetle önerilir.

### Düzenli Yedek
- Veritabanı yedeği alın
- **Ruh Comment → İçe/Dışa Aktar** - JSON yedek indirin

## 📞 Destek

### Dokümantasyon
- 📚 README.md - Genel bilgiler
- 📝 CHANGELOG.md - Versiyon geçmişi
- 🔧 Bu dosya - Kurulum kılavuzu

### İletişim
- 🌐 Website: https://ruh.dev
- 📧 Email: info@ruh.dev
- 🐙 GitHub: https://github.com/ruh-development/ruh-comment

### Sık Sorulan Sorular

**S: Disqus'tan nasıl geçiş yapabilirim?**
C: Ruh Comment → İçe/Dışa Aktar → Disqus XML dosyanızı yükleyin.

**S: Rozetler otomatik verilmiyor?**
C: Kullanıcıların yorum yapması ve koşulları sağlaması gerekiyor. Mevcut kullanıcılar için admin panelden manuel atayın.

**S: Mobile responsive sorunlar var?**
C: Modern CSS yüklü mü kontrol edin. Cache temizleyin. Tema'nız viewport meta tag'i içermeli.

---

**✅ Kurulum tamamlandı! Başarılar dileriz.**

