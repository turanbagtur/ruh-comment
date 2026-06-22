<?php
if (!defined('ABSPATH')) exit;

class Ruh_Auth_Handler {
    
    private $max_login_attempts = 5;
    private $lockout_duration = 900; // 15 dakika
    
    /**
     * Dile göre mesaj döndür
     */
    private function msg($tr, $en) {
        $options = get_option('ruh_comment_options', array());
        $lang = $options['language'] ?? 'tr_TR';
        return $lang === 'en_US' ? $en : $tr;
    }
    
    public function __construct() {
        add_action('wp_ajax_ruh_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_ruh_login', array($this, 'handle_login'));
        add_action('wp_ajax_ruh_register', array($this, 'handle_register'));
        add_action('wp_ajax_nopriv_ruh_register', array($this, 'handle_register'));
        add_action('init', array($this, 'handle_logout'));
    }

    public function handle_login() {
        // Esnek nonce kontrolu - hem eski hem yeni format icin
        $nonce_valid = false;
        if (check_ajax_referer('ruh_auth_nonce', 'nonce', false)) {
            $nonce_valid = true;
        } elseif (check_ajax_referer('ruh-comment-nonce', 'nonce', false)) {
            $nonce_valid = true;
        }
        
        if (!$nonce_valid) {
            wp_send_json_error(array('message' => $this->msg('Güvenlik kontrolü başarısız. Sayfayı yenileyin ve tekrar deneyin.', 'Security check failed. Please refresh the page and try again.')));
        }
        
        // Brute force koruması
        $ip = $this->get_client_ip();
        if ($this->is_ip_locked($ip)) {
            wp_send_json_error(array('message' => $this->msg('Çok fazla başarısız giriş denemesi. Lütfen 15 dakika bekleyin.', 'Too many failed login attempts. Please wait 15 minutes.')));
        }

        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $remember = !empty($_POST['remember']);

        if (empty($username) || empty($password)) {
            wp_send_json_error(array('message' => $this->msg('Kullanıcı adı ve şifre gerekli.', 'Username and password are required.')));
        }

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            $this->record_failed_attempt($ip);
            // Genel hata mesajı - kullanıcı adı enumeration'ı önle
            wp_send_json_error(array('message' => $this->msg('Kullanıcı adı veya şifre hatalı.', 'Invalid username or password.')));
        }
        
        // Başarılı giriş - sayacı sıfırla
        $this->clear_failed_attempts($ip);

        wp_clear_auth_cookie();
        wp_set_auth_cookie($user->ID, $remember, is_ssl());
        wp_set_current_user($user->ID);

        wp_send_json_success(array(
            'message' => $this->msg('Başarıyla giriş yaptınız.', 'Successfully logged in.'),
            'redirect' => $this->validate_redirect_url($_POST['redirect_to'] ?? '')
        ));
    }

    public function handle_register() {
        // Esnek nonce kontrolu - hem eski hem yeni format icin
        $nonce_valid = false;
        if (check_ajax_referer('ruh_auth_nonce', 'nonce', false)) {
            $nonce_valid = true;
        } elseif (check_ajax_referer('ruh-comment-nonce', 'nonce', false)) {
            $nonce_valid = true;
        }
        
        if (!$nonce_valid) {
            wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız. Sayfayı yenileyin ve tekrar deneyin.'));
        }

        if (!get_option('users_can_register')) {
            wp_send_json_error(array('message' => $this->msg('Kayıt yapma özelliği kapalı.', 'Registration is disabled.')));
        }

        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];

        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Tüm alanları doldurunuz.'));
        }
        
        // Şifre güvenlik kontrolü
        if (strlen($password) < 8) {
            wp_send_json_error(array('message' => $this->msg('Şifre en az 8 karakter olmalıdır.', 'Password must be at least 8 characters.')));
        }

        // Kullanıcı adı format kontrolü
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            wp_send_json_error(array('message' => $this->msg('Kullanıcı adı sadece harf, rakam ve alt çizgi içerebilir.', 'Username can only contain letters, numbers, and underscores.')));
        }
        
        if (strlen($username) < 3 || strlen($username) > 30) {
            wp_send_json_error(array('message' => $this->msg('Kullanıcı adı 3-30 karakter arasında olmalıdır.', 'Username must be between 3-30 characters.')));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => $this->msg('Geçerli bir e-posta adresi girin.', 'Please enter a valid email address.')));
        }

        if (username_exists($username)) {
            wp_send_json_error(array('message' => $this->msg('Bu kullanıcı adı zaten kullanılıyor.', 'This username is already taken.')));
        }

        if (email_exists($email)) {
            wp_send_json_error(array('message' => $this->msg('Bu e-posta adresi zaten kayıtlı.', 'This email address is already registered.')));
        }

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        // Otomatik giriş yap
        wp_clear_auth_cookie();
        wp_set_auth_cookie($user_id, true, is_ssl());
        wp_set_current_user($user_id);

        wp_send_json_success(array(
            'message' => $this->msg('Hesabınız başarıyla oluşturuldu.', 'Your account has been created successfully.'),
            'redirect' => $this->validate_redirect_url($_POST['redirect_to'] ?? '')
        ));
    }

    public function handle_logout() {
        if (isset($_GET['ruh_logout']) && isset($_GET['nonce'])) {
            if (wp_verify_nonce($_GET['nonce'], 'ruh_logout')) {
                wp_logout();
                wp_safe_redirect(home_url());
                exit;
            }
        }
    }
    
    /**
     * Redirect URL güvenlik kontrolü - Open Redirect açığını önle
     */
    private function validate_redirect_url($url) {
        if (empty($url)) {
            return home_url();
        }
        
        // Sadece ayni domain'e izin ver
        $url = esc_url_raw($url);
        $home_host = parse_url(home_url(), PHP_URL_HOST);
        $redirect_host = parse_url($url, PHP_URL_HOST);
        
        if ($redirect_host && $redirect_host !== $home_host) {
            return home_url();
        }
        
        return $url;
    }
    
    /**
     * Client IP adresini guvenli sekilde al
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = explode(',', $_SERVER[$key])[0];
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * IP kilitli mi kontrol et
     */
    private function is_ip_locked($ip) {
        $transient_key = 'ruh_login_attempts_' . md5($ip);
        $attempts = get_transient($transient_key);
        
        return $attempts && $attempts >= $this->max_login_attempts;
    }
    
    /**
     * Başarısız giriş denemesini kaydet
     */
    private function record_failed_attempt($ip) {
        $transient_key = 'ruh_login_attempts_' . md5($ip);
        $attempts = get_transient($transient_key) ?: 0;
        set_transient($transient_key, $attempts + 1, $this->lockout_duration);
    }
    
    /**
     * Başarılı girişte sayacı sıfırla
     */
    private function clear_failed_attempts($ip) {
        delete_transient('ruh_login_attempts_' . md5($ip));
    }
}

new Ruh_Auth_Handler();

    // Profil güncelleme AJAX handler
add_action('wp_ajax_ruh_update_profile', 'ruh_handle_profile_update');
function ruh_handle_profile_update() {
    $options_lang = get_option('ruh_comment_options', array());
    $_auth_lang = $options_lang['language'] ?? 'tr_TR';
    $_is_en = ($_auth_lang === 'en_US');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => $_is_en ? 'You must be logged in.' : 'Giriş yapmalısınız.'));
    }
    
    $user_id = get_current_user_id();
    $action_type = sanitize_text_field($_POST['action_type'] ?? '');
    
    // Nonce kontrolü - birden fazla nonce formatını destekle
    $nonce = $_POST['nonce'] ?? '';
    $nonce_valid = wp_verify_nonce($nonce, 'ruh_update_profile_' . $user_id) || 
                   wp_verify_nonce($nonce, 'ruh-comment-nonce');
    
    if (!$nonce_valid) {
        wp_send_json_error(array('message' => $_is_en ? 'Security error.' : 'Güvenlik hatası.'));
    }
    
    switch ($action_type) {
        case 'basic_info':
            $display_name = sanitize_text_field($_POST['display_name'] ?? '');
            $description = sanitize_textarea_field($_POST['description'] ?? '');
            
            if (empty($display_name)) {
                wp_send_json_error(array('message' => $_is_en ? 'Display name cannot be empty.' : 'Görünen ad boş olamaz.'));
            }
            
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $display_name,
                'description' => $description
            ));
            
            wp_send_json_success(array('message' => $_is_en ? 'Information updated.' : 'Bilgiler güncellendi.'));
            break;
            
        case 'account_info':
            $email = sanitize_email($_POST['user_email'] ?? '');
            $url = esc_url_raw($_POST['user_url'] ?? '');
            
            if (!is_email($email)) {
                wp_send_json_error(array('message' => $_is_en ? 'Please enter a valid email address.' : 'Geçerli bir e-posta girin.'));
            }
            
            // Email başkasında var mı kontrol et
            $existing = get_user_by('email', $email);
            if ($existing && $existing->ID != $user_id) {
                wp_send_json_error(array('message' => $_is_en ? 'This email is already in use by another account.' : 'Bu e-posta başka bir hesapta kullanılıyor.'));
            }
            
            wp_update_user(array(
                'ID' => $user_id,
                'user_email' => $email,
                'user_url' => $url
            ));
            
            wp_send_json_success(array('message' => $_is_en ? 'Account information updated.' : 'Hesap bilgileri güncellendi.'));
            break;
            
        case 'update_avatar':
            $avatar_url = esc_url_raw($_POST['avatar_url'] ?? '');
            
            if (empty($avatar_url)) {
                wp_send_json_error(array('message' => $_is_en ? 'Avatar URL is empty.' : 'Avatar URL boş.'));
            }
            
            // Protokol kontrolü - sadece https ve http (göreceli URL yasak)
            $parsed = wp_parse_url($avatar_url);
            if (!isset($parsed['scheme']) || !in_array(strtolower($parsed['scheme']), array('http', 'https'))) {
                wp_send_json_error(array('message' => $_is_en ? 'Invalid URL. Only HTTP/HTTPS URLs are accepted.' : 'Geçersiz URL. Sadece HTTP/HTTPS URL kabul edilir.'));
            }
            
            // Lokal IP adresi ve localhost kontrolü (SSRF engellemesi)
            $host = $parsed['host'] ?? '';
            $blocked_patterns = array('localhost', '127.', '0.0.0.0', '192.168.', '10.', '172.16.', '::1', 'internal');
            foreach ($blocked_patterns as $pattern) {
                if (stripos($host, $pattern) !== false) {
                    wp_send_json_error(array('message' => $_is_en ? 'This URL cannot be used.' : 'Bu URL kullanılamaz.'));
                }
            }
            
            // Dosya uzantısı kontrolü - resim uzantısı olmalı
            $path = strtolower($parsed['path'] ?? '');
            $allowed_extensions = array('.jpg', '.jpeg', '.png', '.gif', '.webp', '.avif', '.svg');
            $has_image_ext = false;
            foreach ($allowed_extensions as $ext) {
                if (strpos($path, $ext) !== false) {
                    $has_image_ext = true;
                    break;
                }
            }
            // Uzantı yoksa HEAD request ile Content-Type kontrol et
            if (!$has_image_ext) {
                $response = wp_remote_head($avatar_url, array('timeout' => 5, 'sslverify' => true));
                if (!is_wp_error($response)) {
                    $content_type = wp_remote_retrieve_header($response, 'content-type');
                    if (strpos($content_type, 'image/') === false) {
                        wp_send_json_error(array('message' => $_is_en ? 'URL does not point to a valid image file.' : 'URL geçerli bir resim dosyasına işaret etmiyor.'));
                    }
                }
            }
            
            // Günlük avatar değişiklik limiti kontrolü (2 değişiklik/gün)
            $today = current_time('Y-m-d'); // WordPress timezone'u kullan
            $avatar_changes = get_user_meta($user_id, 'ruh_avatar_changes', true);
            
            if (!is_array($avatar_changes) || ($avatar_changes['date'] ?? '') !== $today) {
                $avatar_changes = array('date' => $today, 'count' => 0);
            }
            
            if ($avatar_changes['count'] >= 2) {
                wp_send_json_error(array('message' => $_is_en ? 'You have reached the daily avatar change limit (2). Try again tomorrow.' : 'Günlük profil fotoğrafı değiştirme limitine (2) ulaştınız. Yarın tekrar deneyin.'));
            }
            
            // Değişiklik sayısını artır
            $avatar_changes['count']++;
            update_user_meta($user_id, 'ruh_avatar_changes', $avatar_changes);
            
            update_user_meta($user_id, 'ruh_custom_avatar_url', $avatar_url);
            
            $remaining = 2 - $avatar_changes['count'];
            $avatar_msg = $_is_en
                ? "Profile picture updated. You have {$remaining} change(s) remaining today."
                : "Profil resmi güncellendi. Bugün {$remaining} değişiklik hakkınız kaldı.";
            wp_send_json_success(array(
                'message' => $avatar_msg,
                'avatar_url' => $avatar_url,
                'remaining_changes' => $remaining
            ));
            break;
            
        case 'change_password':
            $current = $_POST['current_password'] ?? '';
            $new_pass = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            
            if (empty($current) || empty($new_pass) || empty($confirm)) {
                wp_send_json_error(array('message' => $_is_en ? 'Please fill in all fields.' : 'Tüm alanları doldurun.'));
            }
            
            if ($new_pass !== $confirm) {
                wp_send_json_error(array('message' => $_is_en ? 'Passwords do not match.' : 'Şifreler eşleşmiyor.'));
            }
            
            if (strlen($new_pass) < 8) {
                wp_send_json_error(array('message' => $_is_en ? 'Password must be at least 8 characters.' : 'Şifre en az 8 karakter olmalıdır.'));
            }
            
            $user = get_user_by('id', $user_id);
            if (!wp_check_password($current, $user->user_pass, $user_id)) {
                wp_send_json_error(array('message' => $_is_en ? 'Current password is incorrect.' : 'Mevcut şifre yanlış.'));
            }
            
            wp_set_password($new_pass, $user_id);
            
            // Yeniden giriş yap
            wp_clear_auth_cookie();
            wp_set_auth_cookie($user_id, true, is_ssl());
            
            wp_send_json_success(array('message' => $_is_en ? 'Password updated.' : 'Şifre güncellendi.'));
            break;
            
        default:
            wp_send_json_error(array('message' => $_is_en ? 'Invalid action.' : 'Geçersiz işlem.'));
    }
}

// Görsel yükleme AJAX handler
add_action('wp_ajax_ruh_upload_image', 'ruh_handle_image_upload');
function ruh_handle_image_upload() {
    $upload_lang_opts = get_option('ruh_comment_options', array());
    $_upload_en = (($upload_lang_opts['language'] ?? 'tr_TR') === 'en_US');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => $_upload_en ? 'You must be logged in.' : 'Giriş yapmalısınız.'));
    }
    
    // Nonce kontrolü
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ruh-comment-nonce')) {
        wp_send_json_error(array('message' => $_upload_en ? 'Security error.' : 'Güvenlik hatası.'));
    }
    
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        wp_send_json_error(array('message' => $_upload_en ? 'File could not be uploaded.' : 'Dosya yüklenemedi.'));
    }
    
    $file = $_FILES['image'];
    $upload_type = sanitize_text_field($_POST['upload_type'] ?? 'avatar');
    
    // Dosya tipi kontrolu
    $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime_type, $allowed_types)) {
        wp_send_json_error(array('message' => $_upload_en ? 'Only JPEG, PNG, GIF and WebP files are allowed.' : 'Sadece JPEG, PNG, GIF ve WebP dosyaları yüklenebilir.'));
    }
    
    // Dosya boyutu kontrolü (5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        wp_send_json_error(array('message' => $_upload_en ? 'File size must be less than 5MB.' : 'Dosya boyutu 5MB\'dan küçük olmalı.'));
    }
    
    // WordPress medya yükleme
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    
    $attachment_id = media_handle_upload('image', 0);
    
    if (is_wp_error($attachment_id)) {
        wp_send_json_error(array('message' => ($_upload_en ? 'Upload error: ' : 'Yükleme hatası: ') . $attachment_id->get_error_message()));
    }
    
    $url = wp_get_attachment_url($attachment_id);
    
    wp_send_json_success(array(
        'message' => $_upload_en ? 'Image uploaded successfully.' : 'Görsel başarıyla yüklendi.',
        'url' => $url,
        'attachment_id' => $attachment_id
    ));
}

// Çıkış linki oluştur
function ruh_logout_url($redirect = '') {
    $redirect_url = $redirect ? esc_url($redirect) : home_url();
    
    // Redirect URL güvenlik kontrolü
    $home_host = parse_url(home_url(), PHP_URL_HOST);
    $redirect_host = parse_url($redirect_url, PHP_URL_HOST);
    
    if ($redirect_host && $redirect_host !== $home_host) {
        $redirect_url = home_url();
    }
    
    $logout_url = add_query_arg(array(
        'ruh_logout' => '1',
        'nonce' => wp_create_nonce('ruh_logout')
    ), $redirect_url);
    
    return $logout_url;
}
