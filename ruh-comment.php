<?php
/**
 * Plugin Name:       Ruh Comment
 * Description:       Disqus benzeri, tepki, seviye ve tam teşekkülü topluluk sistemine sahip gelişmiş bir yorum eklentisi.
 * Version:           4.0.2
 * Author:            Ruh Development
 * Text Domain:       ruh-comment
 * Domain Path:       /languages
 */

if (!defined('ABSPATH')) exit;

define('RUH_COMMENT_VERSION', '4.0.2');
define('RUH_COMMENT_PATH', plugin_dir_path(__FILE__));
define('RUH_COMMENT_URL', plugin_dir_url(__FILE__));

// Output buffering kontrolü
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Tüm modülleri dahil et - sıralama önemli!
require_once RUH_COMMENT_PATH . 'includes/activation.php';
require_once RUH_COMMENT_PATH . 'includes/template-helpers.php';
require_once RUH_COMMENT_PATH . 'includes/auth-handler.php';
require_once RUH_COMMENT_PATH . 'includes/ajax-handlers.php';
require_once RUH_COMMENT_PATH . 'includes/filters-and-actions.php';
require_once RUH_COMMENT_PATH . 'includes/shortcodes.php';
require_once RUH_COMMENT_PATH . 'includes/admin-page.php';

register_activation_hook(__FILE__, 'ruh_comment_activate');
register_deactivation_hook(__FILE__, 'ruh_comment_deactivate');

function ruh_comment_enqueue_scripts() {
    $is_profile_page = isset($GLOBALS['post']) && has_shortcode($GLOBALS['post']->post_content, 'ruh_user_profile');
    $is_auth_page = isset($GLOBALS['post']) && (has_shortcode($GLOBALS['post']->post_content, 'ruh_login') || has_shortcode($GLOBALS['post']->post_content, 'ruh_register') || has_shortcode($GLOBALS['post']->post_content, 'ruh_auth'));
    
    if ((is_singular() && comments_open()) || $is_profile_page || $is_auth_page) {
        // CSS dosyasını doğru şekilde enqueue et
        wp_enqueue_style('ruh-comment-style', RUH_COMMENT_URL . 'assets/css/ruh-comment-style.css', [], RUH_COMMENT_VERSION);
        
        // WordPress'in varsayılan yanıt script'ini kaldır
        wp_deregister_script('comment-reply');
        wp_enqueue_script('ruh-comment-script', RUH_COMMENT_URL . 'assets/js/ruh-comment-script.js', ['jquery'], RUH_COMMENT_VERSION, true);

        wp_localize_script('ruh-comment-script', 'ruh_comment_ajax', [
            'ajax_url'    => admin_url('admin-ajax.php'),
            'nonce'       => wp_create_nonce('ruh-comment-nonce'),
            'post_id'     => get_the_ID(),
            'logged_in'   => is_user_logged_in(),
            'total_comments' => get_comments_number(),
            'comments_per_page' => get_option('comments_per_page', 10),
            'user_id'     => get_current_user_id(),
            'text'        => [
                'error'           => __('Bir hata oluştu. Lütfen tekrar deneyin.', 'ruh-comment'),
                'login_required'  => __('Bu işlemi yapmak için giriş yapmalısınız.', 'ruh-comment'),
                'report_confirm'  => __('Bu yorumu gerçekten şikayet etmek istiyor musunuz?', 'ruh-comment'),
                'load_more'       => __('Daha Fazla Yorum Yükle', 'ruh-comment'),
                'no_more_comments'=> __('Gösterilecek başka yorum yok.', 'ruh-comment'),
                'comment_empty'   => __('Yorum alanı boş olamaz.', 'ruh-comment'),
                'commenting'      => __('Gönderiliyor...', 'ruh-comment'),
                'reply_cancel'    => __('İptal', 'ruh-comment'),
                'reply_send'      => __('Yanıtla', 'ruh-comment'),
                'delete_confirm'  => __('Bu yorumu silmek istediğinizden emin misiniz?', 'ruh-comment'),
                'success'         => __('İşlem başarılı!', 'ruh-comment'),
                'reply_placeholder' => __('Yanıtınızı yazın...', 'ruh-comment'),
            ]
        ]);
    }
}
add_action('wp_enqueue_scripts', 'ruh_comment_enqueue_scripts');

// WordPress'in varsayılan yorum sistemini devre dışı bırak
function ruh_comment_disable_default_comments() {
    // Admin menüsünden yorum sayfasını kaldır
    remove_menu_page('edit-comments.php');
    
    // Dashboard widget'ını kaldır
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    
    // Admin bar'dan yorumları kaldır
    add_action('wp_before_admin_bar_render', function() {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu('comments');
    });
}
add_action('admin_menu', 'ruh_comment_disable_default_comments', 999);

function ruh_comment_override_template($comment_template) {
    if (is_singular() && comments_open()) {
        $options = get_option('ruh_comment_options', []);
        $profile_page_id = $options['profile_page_id'] ?? 0;
        
        // Profil sayfasında comment template kullanmayalım
        if (is_page() && get_the_ID() == $profile_page_id && $profile_page_id != 0) {
            return $comment_template;
        }
        
        // Auth sayfalarında da comment template kullanmayalım
        if (is_page() && has_shortcode(get_post()->post_content, 'ruh_auth')) {
            return $comment_template;
        }
        
        // Ruh Comment template'ini kullan
        $custom_template = RUH_COMMENT_PATH . 'includes/comment-template.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $comment_template;
}
add_filter('comments_template', 'ruh_comment_override_template');

function ruh_comment_load_textdomain() {
    load_plugin_textdomain('ruh-comment', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('plugins_loaded', 'ruh_comment_load_textdomain');

// Deaktivation işlemi
function ruh_comment_deactivate() {
    // Geçici verileri temizle
    wp_cache_flush();
}

// CSS dosyasının varlığını kontrol et ve debug bilgisi ekle
function ruh_comment_debug_css() {
    if (current_user_can('manage_options') && isset($_GET['ruh_debug'])) {
        $css_file = RUH_COMMENT_PATH . 'assets/css/ruh-comment-style.css';
        $css_url = RUH_COMMENT_URL . 'assets/css/ruh-comment-style.css';
        
        echo '<div style="background: #000; color: #0f0; padding: 10px; font-family: monospace; position: fixed; top: 0; right: 0; z-index: 9999; max-width: 300px;">';
        echo '<strong>RUH COMMENT DEBUG:</strong><br>';
        echo 'CSS Dosyası: ' . (file_exists($css_file) ? '✓ VAR' : '✗ YOK') . '<br>';
        echo 'CSS URL: ' . $css_url . '<br>';
        echo 'Plugin Path: ' . RUH_COMMENT_PATH . '<br>';
        echo 'Plugin URL: ' . RUH_COMMENT_URL . '<br>';
        echo 'Version: ' . RUH_COMMENT_VERSION . '<br>';
        echo 'Template Override: ' . (has_filter('comments_template', 'ruh_comment_override_template') ? '✓' : '✗') . '<br>';
        echo 'Current Post: ' . get_the_ID() . '<br>';
        echo 'Comments Open: ' . (comments_open() ? '✓' : '✗') . '<br>';
        echo '</div>';
    }
}
add_action('wp_footer', 'ruh_comment_debug_css');

// CSS dosyasının doğru yüklendiğini kontrol et
function ruh_comment_check_assets() {
    $css_file = RUH_COMMENT_PATH . 'assets/css/ruh-comment-style.css';
    $js_file = RUH_COMMENT_PATH . 'assets/js/ruh-comment-script.js';
    
    if (!file_exists($css_file)) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Ruh Comment:</strong> CSS dosyası bulunamadı: assets/css/ruh-comment-style.css</p></div>';
        });
    }
    
    if (!file_exists($js_file)) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>Ruh Comment:</strong> JS dosyası bulunamadı: assets/js/ruh-comment-script.js</p></div>';
        });
    }
}
add_action('admin_init', 'ruh_comment_check_assets');

// Admin için yorum listesini göster
function ruh_comment_show_comments_in_admin() {
    if (is_admin() && current_user_can('moderate_comments')) {
        add_action('admin_bar_menu', function($wp_admin_bar) {
            $wp_admin_bar->add_node([
                'id' => 'ruh-comments',
                'title' => 'Ruh Yorumlar',
                'href' => admin_url('admin.php?page=ruh-comment-manager'),
                'meta' => ['title' => 'Ruh Comment Yönetimi']
            ]);
        }, 100);
    }
}
add_action('wp_loaded', 'ruh_comment_show_comments_in_admin');