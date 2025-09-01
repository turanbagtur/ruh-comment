<?php
if (!defined('ABSPATH')) exit;

class Ruh_Auth_Handler {
    
    public function __construct() {
        add_action('wp_ajax_ruh_login', array($this, 'handle_login'));
        add_action('wp_ajax_nopriv_ruh_login', array($this, 'handle_login'));
        add_action('wp_ajax_ruh_register', array($this, 'handle_register'));
        add_action('wp_ajax_nopriv_ruh_register', array($this, 'handle_register'));
        add_action('init', array($this, 'handle_logout'));
    }

    public function handle_login() {
        if (!check_ajax_referer('ruh_auth_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız.'));
        }

        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $remember = !empty($_POST['remember']);

        if (empty($username) || empty($password)) {
            wp_send_json_error(array('message' => 'Kullanıcı adı ve şifre gerekli.'));
        }

        $user = wp_authenticate($username, $password);

        if (is_wp_error($user)) {
            wp_send_json_error(array('message' => $user->get_error_message()));
        }

        wp_clear_auth_cookie();
        wp_set_auth_cookie($user->ID, $remember, is_ssl());
        wp_set_current_user($user->ID);

        wp_send_json_success(array(
            'message' => 'Başarıyla giriş yaptınız.',
            'redirect' => isset($_POST['redirect_to']) ? $_POST['redirect_to'] : home_url()
        ));
    }

    public function handle_register() {
        if (!check_ajax_referer('ruh_auth_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız.'));
        }

        if (!get_option('users_can_register')) {
            wp_send_json_error(array('message' => 'Kayıt yapma özelliği kapalı.'));
        }

        $username = sanitize_user($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = $_POST['password'];

        if (empty($username) || empty($email) || empty($password)) {
            wp_send_json_error(array('message' => 'Tüm alanları doldurunuz.'));
        }

        if (!is_email($email)) {
            wp_send_json_error(array('message' => 'Geçerli bir e-posta adresi girin.'));
        }

        if (username_exists($username)) {
            wp_send_json_error(array('message' => 'Bu kullanıcı adı zaten kullanılıyor.'));
        }

        if (email_exists($email)) {
            wp_send_json_error(array('message' => 'Bu e-posta adresi zaten kayıtlı.'));
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
            'message' => 'Hesabınız başarıyla oluşturuldu.',
            'redirect' => isset($_POST['redirect_to']) ? $_POST['redirect_to'] : home_url()
        ));
    }

    public function handle_logout() {
        if (isset($_GET['ruh_logout']) && isset($_GET['nonce'])) {
            if (wp_verify_nonce($_GET['nonce'], 'ruh_logout')) {
                wp_logout();
                wp_redirect(home_url());
                exit;
            }
        }
    }
}

new Ruh_Auth_Handler();

// Çıkış linki oluştur
function ruh_logout_url($redirect = '') {
    $logout_url = add_query_arg(array(
        'ruh_logout' => '1',
        'nonce' => wp_create_nonce('ruh_logout')
    ), $redirect ? $redirect : home_url());
    
    return $logout_url;
}