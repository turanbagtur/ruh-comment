# Changelog - Ruh Comment

## [8.0] - 2026-08-31

### ✨ Yeni Özellikler
- **En çok tartışılan sıralama** — Yorumlar yanıt sayısına göre sıralanabilir.
- **Tenor GIF** — Giphy yoksa Tenor API ile GIF arama.
- **Site içi bildirimler** — Yanıt, mention ve rozet bildirimleri (zil paneli).
- **Renk modu** — Otomatik / koyu / açık tema (admin + kullanıcı tercihi).
- **Öne çıkan yorumlar** — En çok beğenilen yorumlar bölüm özeti.
- **Spam skoru** — Şüpheli yorumları otomatik reddetme.
- **Rozet nadirlik** — Common / Rare / Legendary / Auto, admin panelinden seçilir.
- **Yorum arama + kullanıcı filtresi** — Liste üzerinde anlık arama.
- **Discord / Telegram webhook** — Yeni yorumları kanala iletme.
- **Yorum listesi cache** — HTML cache + yorum değişince invalidation.

### 🎨 Arayüz
- Yorum arka planı şeffaf; tema ile uyumlu.
- Tepki butonları ve mobil dokunma boyutları büyütüldü.
- Beğeni yeşil, beğenmeme kırmızı.
- Glassmorphism ve Disqus temaları elden geçirildi.
- Yanıt aç/kapa hatası düzeltildi.

### 🛠️ Admin
- Ayarlar, yorum, rozet, seviye, şikayet, analytics, moderasyon ve içe/dışa aktar panelleri yenilendi.
- Moderasyon toplu işlem formu düzeltildi.
- Disqus XML import post eşleştirmesi iyileştirildi.

## [7.1] - 2026-08-17

### 🔒 Kritik Güvenlik Düzeltmeleri

#### 🚨 AJAX/REST Yorum Gönderiminde Spam Korumaları Bypass Ediliyordu
- `preprocess_comment` filtresi (honeypot, IP ban, link limiti, küfür filtresi,
  tekrarlı yorum kontrolü) sadece klasik WordPress yorum formunda (`wp_new_comment()`)
  tetiklenir. Eklentinin asıl kullandığı AJAX (`wp_submit_comment`) ve REST API
  akışları `wp_insert_comment()` kullandığından bu filtre **hiç çalışmıyordu**.
- Tüm güvenlik kontrolleri `ruh_run_comment_security_checks()` fonksiyonuna
  çıkarıldı ve artık AJAX handler ile REST API tarafından da doğrudan çağrılıyor.
- Honeypot alanı artık AJAX isteğine de dahil ediliyor.

#### 🛡️ Şikayet Yönetimi CSRF Açığı
- Admin panelindeki "Şikayet Yönetimi" sayfasında yorum silme/şikayet reddetme
  işlemleri nonce kontrolü olmadan çalışıyordu. `wp_nonce_field()` + 
  `check_admin_referer()` eklendi.

#### 🗄️ Veritabanı Şeması Düzeltmeleri
- `ruh_reports` tablosuna eksik `status` kolonu eklendi (admin panel bu kolonu
  kullanıyordu ama tablo şemasında yoktu, güncellemeler sessizce başarısız oluyordu).
- `ruh_reactions` tablosuna `UNIQUE KEY` eklendi - eşzamanlı isteklerde (double-click,
  bot) aynı kullanıcı için mükerrer tepki kaydı oluşabiliyordu (race condition).
- Mevcut kurulumlar için otomatik migration eklendi (`ruh_comment_maybe_upgrade_db()`,
  `plugins_loaded` üzerinde çalışır, `RUH_COMMENT_DB_VERSION` ile takip edilir).

#### 📤 Rozet Görseli Yükleme - MIME Doğrulaması
- Custom rozet görseli yüklerken sadece istemcinin gönderdiği `Content-Type`
  header'ına güveniliyordu. `finfo_file()` ile gerçek dosya içeriği kontrolü eklendi.

#### 🔐 Diğer Yetkilendirme/Onay Düzeltmeleri
- "Tüm Seviyeleri Sıfırla" işlemi artık backend'de de onay kutusu kontrolü yapıyor
  (önceden sadece JS ile devre dışı bırakılan bir butona dayanıyordu, bypass edilebilirdi).
- Şikayet ve Seviye Yönetimi sayfalarına `current_user_can('manage_options')` kontrolü eklendi.

### 🐛 Hata Düzeltmeleri

- **Çift mention bildirimi**: `@kullanıcı` etiketlendiğinde e-posta bildirimi iki kez
  gönderiliyordu (`wp_insert_comment` hook'u + manuel çağrı). Manuel çağrı kaldırıldı.
- Admin ayarlar sayfasındaki "Bağış Yap" butonundaki bozuk SVG path (render hatası) düzeltildi.
- `includes/activation.php` - ana dosyadaki `ruh_comment_activate()` ile çakışan,
  hiçbir yerden kullanılmayan eski/tutarsız dosya kaldırıldı.
- `includes/rest-api.php` içindeki mojibake (bozuk karakter kodlaması) yorum satırları düzeltildi.

### ⚡ Performans İyileştirmeleri

- Yorum listelemede kullanıcı seviyesi artık cache'li `ruh_get_user_level_info()`
  ile çekiliyor (önceden her yorum için ayrı sorgu atılıyordu - N+1 sorunu).
- Yanıt sayısı sorgusu (`ruh_get_comment_reply_count()`) object cache ile 5 dakika
  önbelleğe alınıyor, yeni yanıt eklendiğinde otomatik invalidate ediliyor.
- Eksik rate limit'ler eklendi: `get_comments`, `flag_comment`, `edit_comment`,
  `delete_comment`, `load_replies`, `load_more_profile_comments`.
- Kullanılmayan CSS/JS dosyaları kaldırıldı (hiçbir yerden enqueue edilmiyordu):
  `ruh-comment-modern.css`, `ruh-admin-modern.css`, `ruh-comment-admin.css`,
  `ruh-comment-admin.js` (bu son dosya ayrıca çalışmayan gömülü PHP kodu içeriyordu).

### ♿ Erişilebilirlik (a11y) İyileştirmeleri

- Tüm modallere (`role="dialog"`, `aria-modal="true"`) ve kapatma butonlarına
  `aria-label` eklendi.
- Modal açıldığında odak otomatik olarak modal içine taşınıyor, Tab/Shift+Tab
  ile odak modal dışına çıkamıyor (focus trap), modal kapandığında odak
  tetikleyici öğeye geri dönüyor (WCAG 2.4.3).
- İkon-only butonlara (`like-btn`, `dislike-btn`, `more-btn`) `aria-label` eklendi.
- `.comment-form textarea` için kaybolan focus göstergesi (outline:none) yerine
  görünür bir box-shadow eklendi (WCAG 2.4.7).
- `.more-btn` metin rengi (#666) WCAG AA kontrast oranını karşılamıyordu, tema
  değişkeni (`--text-muted`) ile düzeltildi.

### 🧹 Kod Kalitesi

- GIF whitelist doğrulama mantığı (`ruh_is_allowed_gif_host()`) merkezileştirildi;
  önceden `ajax-handlers.php` ve `template-helpers.php`'de birbirinden bağımsız,
  farklı güvenlik seviyelerinde tekrarlanıyordu.
- Ölü kod yollarına (`ruh_comment_format()`) açıklayıcı dokümantasyon eklendi.

## [7.0] - 2026-06-22

### 🚀 Yeni Özellikler

#### 📨 Mention Bildirim Sistemi
- `@kullaniciadi` ile etiketlenen kullanıcılara otomatik e-posta bildirimi gönderilir
- Hem Türkçe hem İngilizce dil desteği
- Kullanıcılar bildirimleri kapatabilir (`ruh_disable_mention_notifications` meta)
- Hem `wp_insert_comment` hem `comment_approved_comment` hook'larında çalışır

#### 📝 Yorum Düzenleme Geçmişi
- Her yorum için düzenleme geçmişi tutulur (`_edit_history` meta)
- En fazla 10 düzenleme geçmişi saklanır
- `ruh_is_comment_edited()` ve `ruh_get_edit_history()` yardımcı fonksiyonları eklendi
- Düzenlenen yorumlara otomatik "düzenlendi" işareti eklenir

#### 🎨 Inline CSS Ayıklama
- Badge glow efektleri ayrı bir CSS dosyasına taşındı (`ruh-comment-badges.css`)
- `wp_add_inline_style()` yerine bağımsız CSS dosyası kullanılıyor
- Daha temiz kod yapısı ve daha kolay özelleştirme

### 🔒 Güvenlik İyileştirmeleri

#### 🔑 Giphy API Key Gizleme (Server-Side Proxy)
- API key artık client-side JavaScript'e gönderilmiyor
- `ruh_gif_search_proxy()` fonksiyonu ile server-side proxy eklendi
- AJAX üzerinden güvenli GIF araması
- Hardcoded fallback API key kaldırıldı
- **ÖNEMLİ:** Admin panelinden Giphy API key'inizi yapılandırdığınızdan emin olun

#### 📦 Uninstall Script
- `uninstall.php` dosyası oluşturuldu
- Eklenti kaldırıldığında tüm veriler temizlenir:
  - Özel veritabanı tabloları
  - Comment meta verileri (likes, dislikes, votes, pinned vb.)
  - User meta verileri (ban, timeout, avatar vb.)
  - Transient'lar ve cache
  - WordPress options
  - Cron job'lar

#### 💾 Options Singleton Cache
- `Ruh_Options_Cache` sınıfı eklendi
- Her istekte sadece bir kez `get_option()` çağrılır
- `ruh_get_options()`, `ruh_option()`, `ruh_update_option()` yardımcı fonksiyonları
- `ruh_translate()` ve `ruh_t()` dil yardımcı fonksiyonları

### ⚡ Performans İyileştirmeleri

#### 📊 N+1 Sorgu Optimizasyonu
- Yanıt sayıları batch sorgu ile çekiliyor
- Yorum render edilirken gereksiz meta sorguları azaltıldı
- `reply_counts_cache` parametresi ile optimize edilmiş

#### 🎯 Tutarlı kses Kullanımı
- Yorum gönderme ve düzenleme işlemlerinde tutarlı `$allowed_tags` kullanılıyor
- `wp_kses_post()` yerine `wp_kses()` ile aynı tag set'i

### 📝 Diğer

- **Sürüm:** 7.0
- **Tarih:** 22 Haziran 2026
- **WordPress Uyumluluğu:** 6.7'ye güncellendi
- **Yeni Dosyalar:**
  - `uninstall.php` - Temiz kaldırma scripti
  - `includes/class-options-cache.php` - Options cache sınıfı
  - `assets/css/ruh-comment-badges.css` - Badge efektleri CSS

---

## [6.5] - 2026-04-21

### 🐛 Hata Düzeltmeleri

#### 🔒 Güvenlik Düzeltmeleri

- **XSS Koruması** — [`includes/template-helpers.php`](includes/template-helpers.php) içindeki yorum zaman bağlantısında `htmlspecialchars()` yerine WordPress standardı `esc_url()` kullanıldı. `htmlspecialchars()` URL'leri doğru encode etmiyor, bu durum belirli senaryolarda XSS vektörüne yol açabiliyordu.
- **SSL Cookie Güvenliği** — [`includes/auth-handler.php`](includes/auth-handler.php) şifre değiştirme işleminde `wp_set_auth_cookie()` çağrısına eksik olan `is_ssl()` parametresi eklendi. HTTPS sitelerinde oturum cookie'si güvensiz (non-secure) olarak set ediliyordu.

#### ⚙️ PHP Düzeltmeleri

- **Deprecated API Kullanımı** — [`includes/ajax-handlers.php`](includes/ajax-handlers.php) içinde WP 5.3+ sürümlerinde deprecated olan `current_time('timestamp')` çağrısı kaldırıldı; timeout/ban kontrolünde yerine `time()` + `intval()` kullanıldı.
- **`define()` Guard Eksikliği** — Aynı PHP request içinde `RUH_AJAX_RATE_CHECKED` sabitinin birden fazla kez tanımlanmaya çalışılması PHP `Notice: Constant already defined` hatası üretiyordu. `if (!defined(...))` koruması eklendi.

#### 📧 Bildirim Sistemi

- **Çifte E-posta Bildirimi** — [`includes/filters-and-actions.php`](includes/filters-and-actions.php) içinde `ruh_send_reply_notification` fonksiyonu hem `comment_approved_comment` hem de `wp_insert_comment` hook'una bağlıydı. Onaylanan yanıtlar için alıcıya iki ayrı bildirim e-postası gönderiliyordu. `comment_approved_comment` hook bağlantısı kaldırıldı, yalnızca `wp_insert_comment` bırakıldı.

#### 🗄️ Veritabanı / Performans

- **Gereksiz Tekrarlanan DB Sorgusu** — [`includes/filters-and-actions.php`](includes/filters-and-actions.php) içinde `ruh_comment_checks()` fonksiyonunun başında (satır 33) alınan `$options` değişkeni, fonksiyonun ilerleyen satırlarında (satır 108) tekrar `get_option('ruh_comment_options')` ile çekiliyordu. İkinci sorgu kaldırılarak sayfa başına 1 gereksiz DB sorgusu azaltıldı.

### 📝 Diğer

- **Versiyon Tutarsızlığı** — Plugin başlığında `Version: 6.4` yazarken PHPDoc bloğu `@version 6.2` gösteriyordu. Her iki alan da `6.5` olarak güncellendi.
- **Sürüm:** 6.5
- **Tarih:** 21 Nisan 2026

---

## [6.4] - 2025-12-20

### 🚀 Yeni Özellikler

#### 🏅 Otomatik Rozet Sistemi
- **Koşul Tabanlı** - Seviye, yorum sayısı veya beğeni sayısına göre otomatik rozet
- **Admin Arayüzü** - Yeni otomatik rozet ekleme formu
- **Rozet Türü Gösterimi** - Listede "Otomatik" ve "Manuel" etiketleri
- **Koşul Türleri** - Seviye (≥), Yorum Sayısı (≥), Toplam Beğeni (≥)

#### 🖼️ Özel Görsel Rozet
- **JPG/PNG/GIF Yükleme** - Özel rozet görseli yükleme desteği
- **Boyut Kontrolü** - Maksimum 512KB dosya boyutu
- **Önizleme** - Yükleme öncesi görsel önizleme

#### 🏷️ Kullanıcı Tag Sistemi
- **Özel Etiketler** - Editör, Çevirmen, Admin gibi etiketler
- **Renk Seçimi** - 8 farklı renk seçeneği
- **Seviye Yanında Gösterim** - Tag'lar seviye rozetinin yanında görünür

#### ⚙️ Karakter Limiti Ayarı
- **Admin Paneli** - Güvenlik sekmesinde maksimum karakter limiti ayarı
- **Dinamik Limit** - 500-2000 arası ayarlanabilir (varsayılan: 1000)
- **Frontend Entegrasyonu** - Textarea ve karakter sayacı dinamik güncellenir

### ⚡ Performans İyileştirmeleri

#### 🖼️ GIF Arama Hızlandırma
- **Debounce Süresi** - 500ms'den 300ms'ye düşürüldü (daha hızlı yanıt)
- **Loading Gösterimi** - Arama sırasında "Aranıyor..." mesajı
- **Küçük Önizleme** - `fixed_width_small` ile daha hızlı thumbnail yükleme
- **Lazy Loading** - GIF görsellerinde `loading="lazy"` desteği
- **Timeout** - 5 saniye timeout ile bağlantı kontrolü
- **Browser Cache** - Tarayıcı önbelleği kullanımı
- **Daha Fazla Sonuç** - 12'den 15'e çıkarıldı

### 🐛 Hata Düzeltmeleri

#### 🗑️ Yorum Silme Düzeltmesi
- **Takılma Sorunu** - Yorum silme sonrası sayfa takılması düzeltildi
- **Görsel Geri Bildirim** - Silme işlemi sırasında opacity değişimi
- **Fail Handler** - Hata durumunda kullanıcıya bilgi mesajı
- **Timeout** - 10 saniye timeout ile bağlantı kontrolü

### 🎨 Admin Panel İyileştirmeleri

#### 📱 Mobil Uyumluluk
- **Responsive Tasarım** - Admin ayarlar sayfası artık mobilde düzgün görünüyor
- **Tab Düzeni** - Mobilde wrap ile alt alta geçiş
- **Form Elemanları** - Input'lar %100 genişlik
- **Emoji Grid** - Mobilde 2 sütun düzeni

#### 🏷️ Rozet Yönetimi UI
- **Manuel/Otomatik Etiket** - Her rozette tür gösterimi
- **Koşul Bilgisi** - Otomatik rozetlerde koşul tooltip'i
- **Form Stilleri** - Select ve number input stilleri
- **Görsel Yükleme Alanı** - Drag & drop benzeri arayüz
- **Rozet Düzenleme** - Mevcut rozetlerin adını düzenleme özelliği

#### 👥 Kullanıcı Yönetimi (Yeni Sekme)
- **Engellenmiş Kullanıcılar** - Ban'lı kullanıcıları listele ve kaldır
- **Susturulmuş Kullanıcılar** - 24 saat susturulmuş kullanıcıları listele ve kaldır
- **IP Ban Listesi** - Engellenmiş IP adreslerini görüntüle ve yönet
- **Yeni IP Engelleme** - Manuel IP adresi engelleme formu

#### 🎨 Tasarım Seçeneği (Yeni Sekme)
- **Modern Tema** - Varsayılan glassmorphism tasarım
- **Disqus Tarzı** - Minimal, sol kenar çizgili temiz tasarım
- **Admin Panelinden Seçim** - Tema değişikliği tek tıkla

#### 🌐 IP Ban Sistemi
- **Yorum Engelleme** - Engelli IP'lerden yorum gönderimini engelle
- **Admin Yönetimi** - IP engelleme ve kaldırma
- **Ban Tarihi** - Her IP için engelleme tarihi kaydı

### 🐛 Oto Rozet Düzeltmesi
- **Koşul Türü Düzeltmesi** - `comment_count` ve `like_count` değerleri düzeltildi
- **Otomatik Atama** - Yorum gönderiminde rozet kontrolü çalışıyor

---

## [6.3] - 2025-12-17

### 🐛 Hata Düzeltmeleri

#### 📌 Sabitlenmiş Yorum Düzeltmesi
- **En Üstte Kalma** - Yeni yorum eklendiğinde sabitlenmiş yorumlar artık en üstte kalıyor
- **Dinamik Sıralama** - JS tarafında pinned yorumların altına ekleme mantığı

#### 📱 Mobil Avatar Düzeltmesi
- **Görünürlük** - Mobilde profil fotoğrafları artık görünüyor (32x32px)
- **CSS Düzeltme** - `display: none` kaldırıldı, responsive boyut eklendi

#### 🔗 Admin Panel Link Düzeltmeleri
- **Yorum Yönetimi** - "Görüntüle" butonu artık doğru URL'ye yönlendiriyor
- **Şikayet Yönetimi** - "Yoruma Git" butonu eklendi, yorum durumu gösterimi
- **URL Metadata** - Yorum kaydedilirken orijinal URL otomatik kaydediliyor

#### 👤 Profil Linki Düzeltmesi
- **Fonksiyon Adı** - `ruh_get_user_profile_url` doğru fonksiyon kullanılıyor
- **PC ve Mobil** - Her iki platformda sorunsuz çalışıyor

### 🚀 Yeni Özellikler

#### 👎 Beğenmeme (Dislike) Butonu
- **Thumbs Down** - Yorumlara beğenmeme butonu eklendi
- **Karşılıklı Kontrol** - Like tıklanınca dislike kaldırılır ve tersi
- **Sayaç Görünümü** - Ayrı like ve dislike sayaçları

#### ⏱️ Yorum Hız Limiti (Admin Ayarlı)
- **Admin Paneli** - Spam önleme için ayarlanabilir yorum hız limiti
- **İki Parametre** - Maksimum yorum sayısı ve zaman penceresi (saniye)
- **Varsayılan** - 3 yorum / 60 saniye

#### 👤 Profil Linki
- **Tıklanabilir Avatar** - Kullanıcı avatarına tıklayınca profile git
- **Tıklanabilir İsim** - Kullanıcı adına tıklayınca profile git
- **Hover Efekti** - Link hover stilleri eklendi

#### 🖼️ Avatar Değişiklik Limiti
- **Günlük 2 Hak** - Profil fotoğrafı günde en fazla 2 kez değiştirilebilir
- **Abuse Koruması** - Sunucu yükünü azaltmak için limit
- **Kalan Hak Bildirimi** - Değişiklik sonrası kalan hak gösterimi

### 🎨 Stil Güncellemeleri

#### ↑↓ Like/Dislike Ok İconları
- **Yukarı Ok (↑)** - Beğenme için yukarı ok iconu
- **Aşağı Ok (↓)** - Beğenmeme için aşağı ok iconu
- **Kutu Yok** - Sadece ok ve sayı, temiz görünüm
- **Renkler** - Beğen: yeşil (#22c55e), Beğenme: kırmızı (#ef4444)
- **Boyut** - 16px optimum boyut

#### 🗑️ Yorum Silme Popup
- **Modal Tasarım** - Sistem confirm() yerine özel popup modal
- **Kırmızı Gradient** - Uyarı temalı başlık
- **Animasyonlu** - Slide-up animasyonu ile açılış

---

## [6.2] - 2025-12-15

### 🎨 UI/UX İyileştirmeleri

#### ✨ Animasyonlu Tepkiler
- **Pop Efekti** - Emoji tıklandığında büyüyüp küçülme animasyonu
- **Parçacık Efekti** - 6 mini emoji dağılma efekti
- **Tıklama Geri Bildirimi** - Buton scale animasyonu

#### 💀 Skeleton Loading
- **İskelet Yükleme** - Yorumlar yüklenirken 3 adet iskelet kart
- **Shimmer Animasyonu** - Parlayan yükleme efekti
- **Profesyonel Görünüm** - Avatar, isim, tarih, metin alanları

#### 🖱️ Gelişmiş Hover Efektleri
- **Yorum Kartı** - Hafif aydınlatma hover efekti
- **Butonlar** - translateY yukarı kaldırma efekti
- **Gönder Butonu** - Mor gölge efekti
- **Daha Fazla Yükle** - Hover shadow efekti

### 🚀 Yeni Özellikler

#### 👤 Misafir Tepki Sistemi
- **Giriş Gerektirmez** - Giriş yapmamış kullanıcılar da emoji tepki verebilir
- **IP Bazlı Takip** - Misafir tepkileri IP adresi ile takip edilir
- **Çift Tepki Engeli** - Aynı IP'den tekrar tepki verilirse güncellenir/kaldırılır
- **Rate Limiting** - Spam koruması aktif

### 🛡️ Moderasyon

#### 🔤 Regex Yasaklı Kelime Desteği
- **Normal Mod** - Virgülle ayrılmış kelimeler
- **Regex Mod** - `/pattern/` formatında regex desteği
- **Örnekler** - `/k[e3]l[i1]m[e3]/`, `/ba?d\s*word/`
- **Akıllı Flag** - Flag yoksa otomatik `iu` eklenir

#### 😀 Emoji İsim Özelleştirme
- **İsim Değiştirme** - Her emoji için özel isim belirleme
- **Kolay Düzenleme** - Admin panelinden emoji + isim

### � Düzeltmeler
- **Şikayet Modal X Butonu** - Kapatma butonu artık çalışıyor
- **Modal Dışı Tıklama** - Şikayet modalı dışına tıklayınca kapanıyor
- **BOM Karakter Hatası** - Plugin aktivasyonunda "3 characters unexpected output" hatası düzeltildi
- **Türkçe Karakter Hataları** - Kod içindeki `limıt` → `limit` gibi hatalar düzeltildi

### �📝 Diğer
- **Sürüm:** 6.2
- **Tarih:** 15 Aralık 2025

---

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

