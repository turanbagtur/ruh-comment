<?php
/**
 * Plugin Name:       Ruh Comment
 * Plugin URI:        https://mangaruhu.com
 * Description:       Ultra modern glassmorphism tasarımlı yorum sistemi. Mention, markdown, GIF, syntax highlighting, analytics, REST API, seviye/rozet sistemi, gelişmiş güvenlik ve spam koruması. Manga siteleri için optimize edilmiş.
 * Version:           7.1
 * Author:            Solderet
 * Author URI:        https://mangaruhu.com
 * Text Domain:       ruh-comment
 * Domain Path:       /languages
 * Requires at least: 5.0
 * Tested up to:      6.7
 * Requires PHP:      7.4
 * Network:           false
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * @package RuhComment
 * @version 7.1
 * @author Solderet <info@mangaruhu.com>
 * @copyright 2025 Solderet
 * @license GPL-2.0+
 */

if (!defined('ABSPATH')) exit;

define('RUH_COMMENT_VERSION', '7.1');
define('RUH_COMMENT_DB_VERSION', '3');
define('RUH_COMMENT_PATH', plugin_dir_path(__FILE__));
define('RUH_COMMENT_URL', plugin_dir_url(__FILE__));

/**
 * Veritabanı tablolarını oluşturur/günceller.
 * Hem aktivasyonda hem de sürüm değişikliğinde (plugins_loaded) çalışır,
 * böylece mevcut kurulumlar da yeni kolonlara (ör. reports.status) sahip olur.
 */
function ruh_comment_install_tables() {
    global $wpdb;

    try {
        $charset_collate = $wpdb->get_charset_collate();
        
        // User levels table - FIX: INT yerine BIGINT
        $table_user_levels = $wpdb->prefix . 'ruh_user_levels';
        $sql_user_levels = "CREATE TABLE IF NOT EXISTS $table_user_levels (
            user_id bigint(20) NOT NULL,
            xp bigint(20) NOT NULL DEFAULT 0,
            level int(11) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (user_id),
            KEY idx_level (level),
            KEY idx_xp (xp)
        ) $charset_collate;";
        
        // Badges table - FIX: AUTO_INCREMENT düzeltmesi
        $table_badges = $wpdb->prefix . 'ruh_badges';
        $sql_badges = "CREATE TABLE IF NOT EXISTS $table_badges (
            badge_id int(11) NOT NULL AUTO_INCREMENT,
            badge_name varchar(255) NOT NULL,
            badge_svg text NOT NULL,
            is_automated tinyint(1) NOT NULL DEFAULT 0,
            auto_condition_type varchar(50) DEFAULT NULL,
            auto_condition_value int(11) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (badge_id),
            KEY idx_automated (is_automated)
        ) $charset_collate;";

        // User badges table - FIX: Unique constraint düzeltmesi
        $table_user_badges = $wpdb->prefix . 'ruh_user_badges';
        $sql_user_badges = "CREATE TABLE IF NOT EXISTS $table_user_badges (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            badge_id int(11) NOT NULL,
            assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_badge (user_id, badge_id),
            KEY idx_user (user_id),
            KEY idx_badge (badge_id)
        ) $charset_collate;";
        
        // Reports table - FIX: status ve created_at kolonları eklendi (admin panel bunlara ihtiyaç duyuyor)
        $table_reports = $wpdb->prefix . 'ruh_reports';
        $sql_reports = "CREATE TABLE IF NOT EXISTS $table_reports (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            comment_id bigint(20) NOT NULL,
            reporter_id bigint(20) NOT NULL,
            report_time datetime DEFAULT CURRENT_TIMESTAMP,
            reason varchar(255) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            PRIMARY KEY (id),
            UNIQUE KEY unique_report (comment_id, reporter_id),
            KEY idx_comment (comment_id),
            KEY idx_reporter (reporter_id),
            KEY idx_status (status)
        ) $charset_collate;";

        // Reactions table - visitor_ip eklendi (giriş yapmamış kullanıcılar için)
        // FIX: UNIQUE key eklendi - eşzamanlı isteklerde tekrarlı kayıt (race condition) oluşmasını önler
        $table_reactions = $wpdb->prefix . 'ruh_reactions';
        $sql_reactions = "CREATE TABLE IF NOT EXISTS $table_reactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL DEFAULT 0,
            visitor_ip varchar(45) NOT NULL DEFAULT '',
            reaction varchar(20) NOT NULL DEFAULT 'like',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_user_reaction (post_id, user_id, visitor_ip),
            KEY idx_post (post_id),
            KEY idx_user (user_id),
            KEY idx_visitor_ip (visitor_ip)
        ) $charset_collate;";
        
        // WordPress dbDelta kullanarak tabloları oluştur
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_user_levels);
        dbDelta($sql_badges);  
        dbDelta($sql_user_badges);
        dbDelta($sql_reports);

        // Reactions tablosuna UNIQUE key eklenmeden önce mevcut kurulumlarda
        // olası NULL visitor_ip ve mükerrer kayıtları temizle (dbDelta, veri
        // çakışması varsa UNIQUE index'i sessizce eklemeyebilir).
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_reactions'") === $table_reactions) {
            $wpdb->query("UPDATE $table_reactions SET visitor_ip = '' WHERE visitor_ip IS NULL");
            $wpdb->query("
                DELETE r1 FROM $table_reactions r1
                INNER JOIN $table_reactions r2
                ON r1.post_id = r2.post_id
                AND r1.user_id = r2.user_id
                AND r1.visitor_ip = r2.visitor_ip
                AND r1.id < r2.id
            ");
        }
        dbDelta($sql_reactions);

        // Varsayılan ayarları ekle
        if (get_option('ruh_comment_options') === false) {
            $default_options = array(
                'enable_reactions' => 1,
                'enable_likes' => 1,
                'enable_sorting' => 1,
                'enable_reporting' => 1,
                'xp_per_comment' => 15,
                'spam_link_limit' => 2,
                'auto_moderate_reports' => 3,
                'giphy_api_key' => '',
                'tenor_api_key' => '',
                'enable_notifications' => 1,
                'enable_comment_search' => 1,
                'enable_highlights' => 1,
                'enable_spam_score' => 1,
                'color_mode' => 'auto',
                'discord_webhook_url' => '',
                'telegram_bot_token' => '',
                'telegram_chat_id' => ''
            );
            update_option('ruh_comment_options', $default_options);
        }

        $notify_table = $wpdb->prefix . 'ruh_notifications';
        dbDelta("CREATE TABLE IF NOT EXISTS $notify_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(20) NOT NULL DEFAULT 'reply',
            actor_id bigint(20) NOT NULL DEFAULT 0,
            comment_id bigint(20) NOT NULL DEFAULT 0,
            post_id bigint(20) NOT NULL DEFAULT 0,
            message varchar(255) NOT NULL DEFAULT '',
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_read (user_id, is_read),
            KEY idx_created (created_at)
        ) $charset_collate;");

        if ($wpdb->get_var("SHOW TABLES LIKE '$table_badges'") === $table_badges) {
            $badge_col = $wpdb->get_results("SHOW COLUMNS FROM $table_badges LIKE 'rarity'");
            if (empty($badge_col)) {
                $wpdb->query("ALTER TABLE $table_badges ADD rarity varchar(20) NOT NULL DEFAULT 'common'");
            }
        }

        update_option('ruh_comment_db_version', RUH_COMMENT_DB_VERSION);

    } catch (Exception $e) {
        error_log('[Ruh Comment] DB Install Error: ' . $e->getMessage());
    }
}

// Geriye dönük uyumluluk için eski fonksiyon adı
function ruh_comment_activate() {
    ruh_comment_install_tables();
    flush_rewrite_rules();
}

/**
 * Mevcut kurulumlarda eklenti güncellendiğinde (dosyalar değişse de aktivasyon
 * hook'u tekrar çalışmaz) tablo şemasını senkron tutar.
 */
function ruh_comment_maybe_upgrade_db() {
    if (get_option('ruh_comment_db_version') !== RUH_COMMENT_DB_VERSION) {
        ruh_comment_install_tables();
    }
}
add_action('plugins_loaded', 'ruh_comment_maybe_upgrade_db', 5);

function ruh_comment_deactivate() {
    wp_cache_flush();
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'ruh_comment_activate');
register_deactivation_hook(__FILE__, 'ruh_comment_deactivate');
register_uninstall_hook(__FILE__, 'ruh_comment_uninstall');

function ruh_comment_uninstall() {
    global $wpdb;
    
    // Özel tabloları sil
    $custom_tables = array(
        $wpdb->prefix . 'ruh_reactions',
        $wpdb->prefix . 'ruh_user_levels',
        $wpdb->prefix . 'ruh_badges',
        $wpdb->prefix . 'ruh_user_badges',
        $wpdb->prefix . 'ruh_reports',
        $wpdb->prefix . 'ruh_notifications',
    );
    
    foreach ($custom_tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
    
    // Comment meta'ları temizle
    $wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE '_likes'");
    $wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE '_dislikes'");
    $wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE '_user_vote_%'");
    $wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE 'ruh_pinned'");
    $wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE 'ruh_pinned_date'");
    $wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE meta_key LIKE '_original_post_url'");
    
    // User meta'ları temizle
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ruh_ban_status'");
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ruh_timeout_until'");
    $wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'ruh_avatar_%'");
    
    // Transient'ları temizle
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%ruh_rate_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%ruh_api_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ruh_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ruh_%'");
    
    // Options'ı sil
    delete_option('ruh_comment_options');
    delete_option('ruh_comment_db_version');
    
    // Cron job'ları temizle
    wp_clear_scheduled_hook('ruh_check_badges_cron');
    
    // Object cache'i temizle
    wp_cache_flush();
}

// Options cache sınıfını yükle (diğer modüllerden önce)
require_once RUH_COMMENT_PATH . 'includes/class-options-cache.php';

// Tüm modülleri yükle - güvenli şekilde
$required_files = array(
    'includes/template-helpers.php',
    'includes/community-features.php',
    'includes/auth-handler.php', 
    'includes/ajax-handlers.php',
    'includes/filters-and-actions.php',
    'includes/shortcodes.php'
    // NOT: comment-template.php burada yuklenmiyor, comments_template filtresi ile yukleniyor
);

// Admin dosyaları
if (is_admin()) {
    $required_files[] = 'includes/admin-page.php';
}

// Opsiyonel dosyalar
$optional_files = array(
    'includes/advanced-features.php',
    'includes/rest-api.php',
    'includes/analytics-dashboard.php',
    'includes/import-export.php'
);

// Gerekli dosyaları yükle
foreach ($required_files as $file) {
    $file_path = RUH_COMMENT_PATH . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    } else {
        error_log('[Ruh Comment] Missing required file: ' . $file);
    }
}

// Opsiyonel dosyaları yükle
foreach ($optional_files as $file) {
    $file_path = RUH_COMMENT_PATH . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}

// CSS/JS yükleme
function ruh_comment_enqueue_scripts() {
    if (!is_singular() || !comments_open()) return;
    
    $options = get_option('ruh_comment_options', array());
    $theme = isset($options['comment_theme']) ? $options['comment_theme'] : 'modern';
    
    $css_path = RUH_COMMENT_URL . 'assets/css/ruh-comment-style.css';
    $js_path = RUH_COMMENT_URL . 'assets/js/ruh-comment-script.js';
    
    // Geliştirme sırasında cache'i engellemek için WP_DEBUG kontrolü
    $version = (defined('WP_DEBUG') && WP_DEBUG) ? RUH_COMMENT_VERSION . '.' . time() : RUH_COMMENT_VERSION;
    
    wp_enqueue_style('ruh-comment-style', $css_path, array(), $version);
    wp_enqueue_style('ruh-comment-badges', RUH_COMMENT_URL . 'assets/css/ruh-comment-badges.css', array('ruh-comment-style'), $version);
    wp_enqueue_script('ruh-comment-script', $js_path, array('jquery'), $version, true);
    
    // Disqus tema için ek CSS
    if ($theme === 'disqus') {
        wp_enqueue_style('ruh-comment-disqus', RUH_COMMENT_URL . 'assets/css/ruh-comment-disqus.css', array('ruh-comment-style'), RUH_COMMENT_VERSION);
    }
    
    // Dinamik post ID - ONCE URL'den al, sonra WordPress ID kullan
    $post_id = 0;
    if (function_exists('ruh_get_dynamic_post_id')) {
        $post_id = ruh_get_dynamic_post_id();
    }
    if (!$post_id) {
        $post_id = get_the_ID();
    }
    
    // Dil ayarı ($options zaten yukarıda tanımlandı)
    $lang = $options['language'] ?? 'tr_TR';
    $texts = array(
        'reply' => $lang === 'en_US' ? 'Reply' : 'Yanıtla',
        'like' => $lang === 'en_US' ? 'Like' : 'Beğen',
        'report' => $lang === 'en_US' ? 'Report' : 'Şikayet Et',
        'edit' => $lang === 'en_US' ? 'Edit' : 'Düzenle',
        'delete' => $lang === 'en_US' ? 'Delete' : 'Sil',
        'level' => $lang === 'en_US' ? 'Level' : 'Seviye',
        'login_required' => $lang === 'en_US' ? 'You must be logged in.' : 'Giriş yapmalısınız.',
        'report_sent' => $lang === 'en_US' ? 'Report submitted. Thank you!' : 'Şikayet gönderildi. Teşekkürler!',
        'error' => $lang === 'en_US' ? 'An error occurred.' : 'Hata oluştu.',
        'load_more' => $lang === 'en_US' ? 'Load More' : 'Daha Fazla',
        'no_comments' => $lang === 'en_US' ? 'No comments yet.' : 'Henüz yorum yok.',
        'comment_sent' => $lang === 'en_US' ? 'Comment sent!' : 'Yorum gönderildi!',
        'confirm_delete' => $lang === 'en_US' ? 'Delete this comment?' : 'Bu yorum silinsin mi?',
        'replying_to' => $lang === 'en_US' ? 'Replying to' : 'Yanıtlanıyor:',
        'comment_empty' => $lang === 'en_US' ? 'Comment cannot be empty.' : 'Yorum boş olamaz.',
        'reply_empty' => $lang === 'en_US' ? 'Reply cannot be empty.' : 'Yanıt boş olamaz.',
        'reply_sent' => $lang === 'en_US' ? 'Reply sent!' : 'Yanıt gönderildi!',
        'reply_placeholder' => $lang === 'en_US' ? 'Write your reply...' : 'Yanıtınızı yazın...',
        'reply_failed' => $lang === 'en_US' ? 'Could not load replies.' : 'Yanıtlar yüklenemedi.',
        'comment_deleted' => $lang === 'en_US' ? 'Comment deleted!' : 'Yorum silindi!',
        'report_type_required' => $lang === 'en_US' ? 'Please select a report type.' : 'Şikayet türü seçin.',
        'send' => $lang === 'en_US' ? 'Send' : 'Gönder',
        'replies_count' => $lang === 'en_US' ? 'replies' : 'yanıt',
        'show_replies' => $lang === 'en_US' ? 'Show Replies' : 'Yanıtları Göster',
        'hide_replies' => $lang === 'en_US' ? 'Hide Replies' : 'Yanıtları Gizle',
        'connection_error' => $lang === 'en_US' ? 'Connection error.' : 'Bağlantı hatası.',
        'network_error' => $lang === 'en_US' ? 'Network error.' : 'Ağ hatası.',
        'sending' => $lang === 'en_US' ? 'Sending...' : 'Gönderiliyor...',
        'pin' => $lang === 'en_US' ? 'Pin' : 'Sabitle',
        'unpin' => $lang === 'en_US' ? 'Unpin' : 'Sabitlemeyi Kaldır',
        'pinned' => $lang === 'en_US' ? 'Pinned' : 'Sabitlendi',
        'view_comment' => $lang === 'en_US' ? 'View Comment' : 'Yorumu Görüntüle',
        'comment_rules' => $lang === 'en_US' ? 'Comment Rules' : 'Yorum Kuralları',
        'discussed' => $lang === 'en_US' ? 'Most discussed' : 'En çok tartışılan',
        'search_comments' => $lang === 'en_US' ? 'Search comments...' : 'Yorumlarda ara...',
        'filter_user' => $lang === 'en_US' ? 'Filter by user' : 'Kullanıcıya göre filtrele',
        'highlights' => $lang === 'en_US' ? 'Top comments' : 'Öne çıkan yorumlar',
        'notifications' => $lang === 'en_US' ? 'Notifications' : 'Bildirimler',
        'no_notifications' => $lang === 'en_US' ? 'No notifications yet.' : 'Henüz bildirim yok.',
        'mark_read' => $lang === 'en_US' ? 'Mark all read' : 'Tümünü okundu işaretle',
        'theme_auto' => $lang === 'en_US' ? 'Auto' : 'Otomatik',
        'theme_dark' => $lang === 'en_US' ? 'Dark' : 'Koyu',
        'theme_light' => $lang === 'en_US' ? 'Light' : 'Açık',
    );
    
     // AJAX verilerini JS'e aktar - Giphy API key client-side'a gönderilmez
     $max_comment_length = isset($options['max_comment_length']) ? intval($options['max_comment_length']) : 1000;
     wp_localize_script('ruh-comment-script', 'ruh_comment_ajax', array(
         'ajax_url' => admin_url('admin-ajax.php'),
         'nonce' => wp_create_nonce('ruh-comment-nonce'),
         'post_id' => $post_id,
         'logged_in' => is_user_logged_in(),
         'user_id' => get_current_user_id(),
         'lang' => $lang,
         'texts' => $texts,
         'max_comment_length' => $max_comment_length,
         'gif_proxy' => admin_url('admin-ajax.php?action=ruh_gif_search'),
         'logged_in' => is_user_logged_in(),
         'color_mode' => isset($options['color_mode']) ? $options['color_mode'] : 'auto',
         'enable_notifications' => is_user_logged_in() && (!isset($options['enable_notifications']) || !empty($options['enable_notifications'])) ? 1 : 0,
     ));
}
add_action('wp_enqueue_scripts', 'ruh_comment_enqueue_scripts');

// Comment template override
function ruh_comment_override_template($template) {
    if (is_singular() && comments_open()) {
        $custom_template = RUH_COMMENT_PATH . 'includes/comment-template.php';
        if (file_exists($custom_template)) {
            return $custom_template;
        }
    }
    return $template;
}
add_filter('comments_template', 'ruh_comment_override_template');

// Text domain yükle
add_action('plugins_loaded', function() {
    load_plugin_textdomain('ruh-comment', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

// Rozet cron hook kaydı
add_action('ruh_check_badges_cron', 'ruh_run_badge_check_cron');
function ruh_run_badge_check_cron($user_id) {
    if ($user_id && function_exists('ruh_check_and_assign_auto_badges')) {
        ruh_check_and_assign_auto_badges(intval($user_id));
    }
}