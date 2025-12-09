<?php
if (!defined('ABSPATH')) exit;

class Ruh_Auth_Handler {
    
    private $max_login_attempts = 5;
    private $lockout_duration = 900; // 15 dakika
    
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
            wp_send_json_error(array('message' => 'Guvenlik kontrolu basarisiz. Sayfayi yenileyin ve tekrar deneyin.'));
        }
        
        // Brute force korumasi
        $ip = $this->get_client_ip();
        if ($this->is_ip_locked($ip)) {
            wp_send_json_error(array('message' => 'Cok fazla basarisiz giris denemesi. Lutfen 15 dakika bekleyin.'));
        }

        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $remember = !empty($_POST['remember']);

        if (empty($username) || empty($password)) {
            wp_send_json_error(array('message' => 'Kullanici adi ve sifre gerekli.'));
        }

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            $this->record_failed_attempt($ip);
            // Genel hata mesaji - kullanici adi enumeration'i onle
            wp_send_json_error(array('message' => 'Kullanici adi veya sifre hatali.'));
        }
        
        // Basarili giris - sayaci sifirla
        $this->clear_failed_attempts($ip);

        wp_clear_auth_cookie();
        wp_set_auth_cookie($user->ID, $remember, is_ssl());
        wp_set_current_user($user->ID);

        wp_send_json_success(array(
            'message' => 'Basariyla giris yaptiniz.',
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
            wp_send_json_error(array('message' => 'Guvenlik kontrolu basarisiz. Sayfayi yenileyin ve tekrar deneyin.'));
        }

        if (!get_option('users_can_register')) {
            wp_send_json_error(array('message' => 'Kayit yapma ozelligi kapali.'));
        }

        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];

        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Tum alanlari doldurunuz.'));
        }
        
        // Sifre guvenlik kontrolu
        if (strlen($password) < 8) {
            wp_send_json_error(array('message' => 'Sifre en az 8 karakter olmalidir.'));
        }
        
        // Kullanici adi format kontrolu
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            wp_send_json_error(array('message' => 'Kullanici adi sadece harf, rakam ve alt cizgi icerebilir.'));
        }
        
        if (strlen($username) < 3 || strlen($username) > 30) {
            wp_send_json_error(array('message' => 'Kullanici adi 3-30 karakter arasinda olmalidir.'));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Gecerli bir e-posta adresi girin.'));
        }

        if (username_exists($username)) {
            wp_send_json_error(array('message' => 'Bu kullanici adi zaten kullaniliyor.'));
        }

        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Bu e-posta adresi zaten kayitli.'));
        }

        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            wp_send_json_error(array('message' => $user_id->get_error_message()));
        }

        // Otomatik giris yap
        wp_clear_auth_cookie();
        wp_set_auth_cookie($user_id, true, is_ssl());
        wp_set_current_user($user_id);

        wp_send_json_success(array(
            'message' => 'Hesabiniz basariyla olusturuldu.',
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
     * Redirect URL guvenlik kontrolu - Open Redirect acigini onle
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
     * Basarisiz giris denemesini kaydet
     */
    private function record_failed_attempt($ip) {
        $transient_key = 'ruh_login_attempts_' . md5($ip);
        $attempts = get_transient($transient_key) ?: 0;
        set_transient($transient_key, $attempts + 1, $this->lockout_duration);
    }
    
    /**
     * Basarili giriste sayaci sifirla
     */
    private function clear_failed_attempts($ip) {
        delete_transient('ruh_login_attempts_' . md5($ip));
    }
}

new Ruh_Auth_Handler();

// Profil guncelleme AJAX handler
add_action('wp_ajax_ruh_update_profile', 'ruh_handle_profile_update');
function ruh_handle_profile_update() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giris yapmalisiniz.'));
    }
    
    $user_id = get_current_user_id();
    $action_type = sanitize_text_field($_POST['action_type'] ?? '');
    
    // Nonce kontrolu
    if (!wp_verify_nonce($_POST['nonce'] ?? '', 'ruh_update_profile_' . $user_id)) {
        wp_send_json_error(array('message' => 'Guvenlik hatasi.'));
    }
    
    switch ($action_type) {
        case 'basic_info':
            $display_name = sanitize_text_field($_POST['display_name'] ?? '');
            $description = sanitize_textarea_field($_POST['description'] ?? '');
            
            if (empty($display_name)) {
                wp_send_json_error(array('message' => 'Gorunen ad bos olamaz.'));
            }
            
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $display_name,
                'description' => $description
            ));
            
            wp_send_json_success(array('message' => 'Bilgiler guncellendi.'));
            break;
            
        case 'account_info':
            $email = sanitize_email($_POST['user_email'] ?? '');
            $url = esc_url_raw($_POST['user_url'] ?? '');
            
            if (!is_email($email)) {
                wp_send_json_error(array('message' => 'Gecerli bir e-posta girin.'));
            }
            
            // Email baskasinda var mi kontrol et
            $existing = get_user_by('email', $email);
            if ($existing && $existing->ID != $user_id) {
                wp_send_json_error(array('message' => 'Bu e-posta baska bir hesapta kullaniliyor.'));
            }
            
            wp_update_user(array(
                'ID' => $user_id,
                'user_email' => $email,
                'user_url' => $url
            ));
            
            wp_send_json_success(array('message' => 'Hesap bilgileri guncellendi.'));
            break;
            
        case 'change_password':
            $current = $_POST['current_password'] ?? '';
            $new_pass = $_POST['new_password'] ?? '';
            $confirm = $_POST['confirm_password'] ?? '';
            
            if (empty($current) || empty($new_pass) || empty($confirm)) {
                wp_send_json_error(array('message' => 'Tum alanlari doldurun.'));
            }
            
            if ($new_pass !== $confirm) {
                wp_send_json_error(array('message' => 'Sifreler eslesmiyor.'));
            }
            
            if (strlen($new_pass) < 6) {
                wp_send_json_error(array('message' => 'Sifre en az 6 karakter olmali.'));
            }
            
            $user = get_user_by('id', $user_id);
            if (!wp_check_password($current, $user->user_pass, $user_id)) {
                wp_send_json_error(array('message' => 'Mevcut sifre yanlis.'));
            }
            
            wp_set_password($new_pass, $user_id);
            
            // Yeniden giris yap
            wp_clear_auth_cookie();
            wp_set_auth_cookie($user_id, true);
            
            wp_send_json_success(array('message' => 'Sifre guncellendi.'));
            break;
            
        default:
            wp_send_json_error(array('message' => 'Gecersiz islem.'));
    }
}

// Cikis linki olustur
function ruh_logout_url($redirect = '') {
    $redirect_url = $redirect ? esc_url($redirect) : home_url();
    
    // Redirect URL guvenlik kontrolu
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
