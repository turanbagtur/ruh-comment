<?php
if (!defined('ABSPATH')) exit;

// Gerekli dosyalari include et
if (file_exists(RUH_COMMENT_PATH . 'includes/admin-comment-manager.php')) {
    require_once RUH_COMMENT_PATH . 'includes/admin-comment-manager.php';
}

if (file_exists(RUH_COMMENT_PATH . 'includes/admin-badge-manager.php')) {
    require_once RUH_COMMENT_PATH . 'includes/admin-badge-manager.php';
}

if (file_exists(RUH_COMMENT_PATH . 'includes/admin-level-manager.php')) {
    require_once RUH_COMMENT_PATH . 'includes/admin-level-manager.php';
}

class Ruh_Comment_Admin {
    
    private $svg_icons = array();
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_menu', array($this, 'replace_comments_menu'), 999);
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_init', array($this, 'redirect_comments_page'));
        add_action('admin_init', array($this, 'handle_user_actions'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('admin_post_ruh_ban_ip', array($this, 'handle_ban_ip'));
        
        // SVG ikonları tanımla
        $this->svg_icons = array(
            'reactions' => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20M10,9.5C10,10.3 9.3,11 8.5,11C7.7,11 7,10.3 7,9.5C7,8.7 7.7,8 8.5,8C9.3,8 10,8.7 10,9.5M17,9.5C17,10.3 16.3,11 15.5,11C14.7,11 14,10.3 14,9.5C14,8.7 14.7,8 15.5,8C16.3,8 17,8.7 17,9.5M12,17.23C14.33,17.23 16.32,15.77 17.11,13.73H6.89C7.68,15.77 9.67,17.23 12,17.23Z"/></svg>',
            'likes' => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12,21.35L10.55,20.03C5.4,15.36 2,12.27 2,8.5C2,5.41 4.42,3 7.5,3C9.24,3 10.91,3.81 12,5.08C13.09,3.81 14.76,3 16.5,3C19.58,3 22,5.41 22,8.5C22,12.27 18.6,15.36 13.45,20.03L12,21.35Z"/></svg>',
            'sorting' => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M3,13H15V11H3M3,6V8H21V6M3,18H9V16H3V18Z"/></svg>',
            'xp' => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/></svg>',
            'comments' => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M9,22A1,1 0 0,1 8,21V18H4A2,2 0 0,1 2,16V4C2,2.89 2.9,2 4,2H20A2,2 0 0,1 22,4V16A2,2 0 0,1 20,18H13.9L10.2,21.71C10,21.9 9.75,22 9.5,22V22H9Z"/></svg>',
            'badges' => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12,4A6,6 0 0,1 18,10C18,13.31 15.31,16 12,16C8.69,16 6,13.31 6,10A6,6 0 0,1 12,4M12,6A4,4 0 0,0 8,10A4,4 0 0,0 12,14A4,4 0 0,0 16,10A4,4 0 0,0 12,6M7,18A1,1 0 0,1 6,19A1,1 0 0,1 5,18H7M17,18A1,1 0 0,0 18,19A1,1 0 0,0 19,18H17M10,18H14V20H10V18Z"/></svg>',
            'security' => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M12,7C13.4,7 14.8,8.1 14.8,9.5V11C15.4,11 16,11.6 16,12.3V15.8C16,16.4 15.4,17 14.7,17H9.2C8.6,17 8,16.4 8,15.7V12.2C8,11.6 8.6,11 9.2,11V9.5C9.2,8.1 10.6,7 12,7M12,8.2C11.2,8.2 10.5,8.7 10.5,9.5V11H13.5V9.5C13.5,8.7 12.8,8.2 12,8.2Z"/></svg>',
            'gif' => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M11.5,9H13V15H11.5V9M9,9V15H6A1.5,1.5 0 0,1 4.5,13.5V10.5A1.5,1.5 0 0,1 6,9H9M7.5,10.5H6V13.5H7.5V10.5M19,10.5V9H14.5V15H16V13H18V11.5H16V10.5H19Z"/></svg>',
            'report' => '<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M14.4,6L14,4H5V21H7V14H12.6L13,16H20V6H14.4Z"/></svg>',
        );
    }

    public function add_admin_menu() {
        add_menu_page(
            'Ruh Comment', 
            'Ruh Comment', 
            'manage_options', 
            'ruh-comment', 
            array($this, 'render_settings_page'), 
            'dashicons-format-chat',
            25
        );
        
        // Ayarlar alt menüsü
        add_submenu_page(
            'ruh-comment',
            'Ayarlar',
            'Ayarlar',
            'manage_options',
            'ruh-comment',
            array($this, 'render_settings_page')
        );
        
        if (function_exists('render_comment_manager_page_content')) {
            add_submenu_page(
                'ruh-comment', 
                'Yorum Yönetimi', 
                'Yorum Yönetimi', 
                'manage_options', 
                'ruh-comment-manager', 
                'render_comment_manager_page_content'
            );
        }
        
        if (function_exists('render_badges_page_content')) {
            add_submenu_page(
                'ruh-comment',
                'Rozet Yönetimi',
                'Rozet Yönetimi',
                'manage_options',
                'ruh-comment-badges',
                'render_badges_page_content'
            );
        }
        
        if (function_exists('render_level_manager_page_content')) {
            add_submenu_page(
                'ruh-comment',
                'Seviye Yönetimi',
                'Seviye Yönetimi',
                'manage_options',
                'ruh-comment-levels',
                'render_level_manager_page_content'
            );
        }
        
        // Şikayet Yönetimi
        add_submenu_page(
            'ruh-comment',
            'Şikayet Yönetimi',
            'Şikayetler',
            'manage_options',
            'ruh-comment-reports',
            array($this, 'render_reports_page')
        );
    }
    
    /**
     * WordPress varsayılan yorumlar menüsünü Ruh Comment ile değiştir
     */
    public function replace_comments_menu() {
        global $menu, $submenu;
        
        // Varsayılan Comments menüsünü kaldır
        remove_menu_page('edit-comments.php');
        
        // Comments menü yerine Ruh Comment'e yönlendir
        if (isset($submenu['edit-comments.php'])) {
            unset($submenu['edit-comments.php']);
        }
    }
    
    /**
     * edit-comments.php sayfasına erişimi Ruh Comment'e yönlendir
     */
    public function redirect_comments_page() {
        global $pagenow;
        
        if ($pagenow === 'edit-comments.php') {
            wp_redirect(admin_url('admin.php?page=ruh-comment-manager'));
            exit;
        }
    }
    
    public function render_reports_page() {
        global $wpdb;

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Bu sayfaya erişim izniniz yok.', 'ruh-comment'));
        }

        $reports_table = $wpdb->prefix . 'ruh_reports';
        
        // Şikayet işlemleri - CSRF koruması: nonce zorunlu
        if (isset($_POST['report_action']) && isset($_POST['report_id'])) {
            check_admin_referer('ruh_report_action', 'ruh_report_nonce');

            $report_id = intval($_POST['report_id']);
            $action = sanitize_text_field(wp_unslash($_POST['report_action']));
            
            if ($action === 'dismiss') {
                $wpdb->update($reports_table, array('status' => 'dismissed'), array('id' => $report_id), array('%s'), array('%d'));
            } elseif ($action === 'delete_comment') {
                $report = $wpdb->get_row($wpdb->prepare("SELECT comment_id FROM $reports_table WHERE id = %d", $report_id));
                if ($report) {
                    wp_delete_comment($report->comment_id, true);
                    $wpdb->update($reports_table, array('status' => 'resolved'), array('id' => $report_id), array('%s'), array('%d'));
                }
            }

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('İşlem başarıyla tamamlandı.', 'ruh-comment') . '</p></div>';
        }
        
        // Sikayetleri getir (çözülmüş/reddedilmiş olanları listenin sonuna at)
        $reports = $wpdb->get_results("
            SELECT r.*, c.comment_content, c.comment_author, u.display_name as reporter_name
            FROM $reports_table r
            LEFT JOIN {$wpdb->comments} c ON r.comment_id = c.comment_ID
            LEFT JOIN {$wpdb->users} u ON r.reporter_id = u.ID
            ORDER BY (r.status = 'pending') DESC, r.report_time DESC
            LIMIT 50
        ");
        
        ?>
        <div class="wrap ruh-admin-wrap">
            <div class="ruh-admin-header" style="background:linear-gradient(135deg,#ef4444,#b91c1c);padding:24px 28px;border-radius:16px;color:#fff;margin-bottom:20px;">
                <h1 style="margin:0;color:#fff;">Şikayet Yönetimi</h1>
                <p style="margin:8px 0 0;opacity:.9;">Kullanıcılar tarafından şikayet edilen yorumları buradan yönetebilirsiniz.</p>
            </div>
            
            <?php
            $pending_count = 0;
            foreach ((array) $reports as $r) {
                if (($r->status ?: 'pending') === 'pending') $pending_count++;
            }
            ?>
            <p style="margin:0 0 12px;color:#646970;"><?php echo intval($pending_count); ?> bekleyen şikayet / <?php echo count((array) $reports); ?> kayıt</p>
            <?php if (empty($reports)): ?>
                <div class="notice notice-info">
                    <p>Bekleyen şikayet bulunmuyor.</p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Yorum</th>
                            <th>Yazar</th>
                            <th>Şikayet Eden</th>
                            <th>Sebep</th>
                            <th>Tarih</th>
                            <th>Durum</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): 
                            $comment = get_comment($report->comment_id);
                            $comment_exists = $comment && $comment->comment_approved !== 'trash';
                            $comment_link = '';
                            if ($comment_exists && function_exists('ruh_get_comment_link')) {
                                $comment_link = ruh_get_comment_link($comment);
                            }
                        ?>
                            <tr>
                                <td>
                                    <?php if ($comment_exists): ?>
                                        <?php echo esc_html(wp_trim_words($comment->comment_content, 15)); ?>
                                        <?php if ($comment_link): ?>
                                            <br><a href="<?php echo esc_url($comment_link); ?>" target="_blank" class="button button-small" style="margin-top:5px;">Yoruma Git</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color:#999;"><em>Yorum silinmiş veya bulunamadı</em></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($report->comment_author ?: '-'); ?></td>
                                <td><?php echo esc_html($report->reporter_name ?: 'Misafir'); ?></td>
                                <td><?php echo esc_html($report->reason); ?></td>
                                <td><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($report->report_time))); ?></td>
                                <td>
                                    <?php
                                    $status = $report->status ?: 'pending';
                                    if (!$comment_exists) {
                                        echo '<span style="color:#d63638;">✗ Silinmiş</span>';
                                    } elseif ($status === 'dismissed') {
                                        echo '<span style="color:#8c8f94;">Reddedildi</span>';
                                    } elseif ($status === 'resolved') {
                                        echo '<span style="color:#d63638;">Çözüldü</span>';
                                    } else {
                                        echo '<span style="color:#00a32a;">✓ Bekliyor</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($comment_exists && $status === 'pending'): ?>
                                        <form method="post" style="display:inline;">
                                            <?php wp_nonce_field('ruh_report_action', 'ruh_report_nonce'); ?>
                                            <input type="hidden" name="report_id" value="<?php echo esc_attr($report->id); ?>">
                                            <button type="submit" name="report_action" value="dismiss" class="button">Reddet</button>
                                            <button type="submit" name="report_action" value="delete_comment" class="button button-primary" onclick="return confirm('Yorumu silmek istediğinizden emin misiniz?');">Yorumu Sil</button>
                                        </form>
                                    <?php elseif (!$comment_exists && $status === 'pending'): ?>
                                        <form method="post" style="display:inline;">
                                            <?php wp_nonce_field('ruh_report_action', 'ruh_report_nonce'); ?>
                                            <input type="hidden" name="report_id" value="<?php echo esc_attr($report->id); ?>">
                                            <button type="submit" name="report_action" value="dismiss" class="button">Şikayeti Kaldır</button>
                                        </form>
                                    <?php else: ?>
                                        <span style="color:#999;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function handle_user_actions() {
        if (!isset($_GET['page']) || !in_array($_GET['page'], array('ruh-comment', 'ruh-comment-settings'), true)) return;
        if (!current_user_can('manage_options')) return;
        
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        // Unban user
        if ($action === 'unban' && isset($_GET['user_id'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'ruh_unban_user')) {
                $user_id = intval($_GET['user_id']);
                delete_user_meta($user_id, 'ruh_ban_status');
                wp_redirect(admin_url('admin.php?page=ruh-comment&tab=users&message=unban_success'));
                exit;
            }
        }
        
        // Unmute user
        if ($action === 'unmute' && isset($_GET['user_id'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'ruh_unmute_user')) {
                $user_id = intval($_GET['user_id']);
                delete_user_meta($user_id, 'ruh_timeout_until');
                wp_redirect(admin_url('admin.php?page=ruh-comment&tab=users&message=unmute_success'));
                exit;
            }
        }
        
        // Unban IP
        if ($action === 'unban_ip' && isset($_GET['ip'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'ruh_unban_ip')) {
                $ip = sanitize_text_field(urldecode($_GET['ip']));
                $ip_bans = get_option('ruh_banned_ips', array());
                if (isset($ip_bans[$ip])) {
                    unset($ip_bans[$ip]);
                    update_option('ruh_banned_ips', $ip_bans);
                }
                wp_redirect(admin_url('admin.php?page=ruh-comment&tab=users&message=unban_ip_success'));
                exit;
            }
        }
    }
    
    public function handle_ban_ip() {
        if (!current_user_can('manage_options')) {
            wp_die('Yetkiniz yok.');
        }
        
        if (!wp_verify_nonce($_POST['_wpnonce'], 'ruh_ban_ip_nonce')) {
            wp_die('Güvenlik doğrulaması başarısız.');
        }
        
        $ip = isset($_POST['ip_address']) ? sanitize_text_field($_POST['ip_address']) : '';
        
        if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip_bans = get_option('ruh_banned_ips', array());
            $ip_bans[$ip] = array(
                'date' => current_time('timestamp'),
                'banned_by' => get_current_user_id()
            );
            update_option('ruh_banned_ips', $ip_bans);
            wp_redirect(admin_url('admin.php?page=ruh-comment&tab=users&message=ban_ip_success'));
        } else {
            wp_redirect(admin_url('admin.php?page=ruh-comment&tab=users&message=invalid_ip'));
        }
        exit;
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'ruh-comment') === false) return;
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Admin JS
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                function ruhActivateTab(target) {
                    if (!target || !$("#tab-" + target).length) return;
                    $(".ruh-admin-tab").removeClass("active");
                    $(".ruh-admin-tab[data-tab=\"" + target + "\"]").addClass("active");
                    $(".ruh-tab-content").removeClass("active");
                    $("#tab-" + target).addClass("active");
                    $(".ruh-submit-wrap").toggle(target !== "users");
                    $("#ruh-active-tab").val(target);
                    if (window.history && window.history.replaceState) {
                        var url = new URL(window.location.href);
                        url.searchParams.set("tab", target);
                        window.history.replaceState({}, "", url);
                        $("input[name=_wp_http_referer]").val(url.pathname + url.search);
                    }
                }
                var params = new URLSearchParams(window.location.search);
                var initialTab = params.get("tab");
                if (initialTab) ruhActivateTab(initialTab);
                $(".ruh-admin-tab").on("click", function(e) {
                    e.preventDefault();
                    ruhActivateTab($(this).data("tab"));
                });
                $(".ruh-theme-option input[type=radio]").on("change", function() {
                    $(".ruh-theme-option .ruh-theme-preview").css("border-color", "#ddd");
                    $(this).siblings(".ruh-theme-preview").css("border-color", "#667eea");
                });
                $(".ruh-theme-option").on("click", function() {
                    $(this).find("input[type=radio]").prop("checked", true).trigger("change");
                });
            });
        ');
    }
    
    public function render_settings_page() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('ruh_comment_messages', 'ruh_comment_message', 'Ayarlar kaydedildi!', 'success');
        }
        
        $options = get_option('ruh_comment_options', array());
        $user_messages = array(
            'unban_success' => 'Kullanıcı engeli kaldırıldı.',
            'unmute_success' => 'Kullanıcı susturması kaldırıldı.',
            'unban_ip_success' => 'IP engeli kaldırıldı.',
            'ban_ip_success' => 'IP adresi engellendi.',
            'invalid_ip' => 'Geçersiz IP adresi.',
        );
        if (isset($_GET['message']) && isset($user_messages[$_GET['message']])) {
            $notice_type = ($_GET['message'] === 'invalid_ip') ? 'error' : 'success';
            add_settings_error('ruh_comment_messages', 'ruh_comment_user_message', $user_messages[$_GET['message']], $notice_type);
        }
        ?>
        <div class="wrap ruh-admin-wrap">
            <div class="ruh-admin-header">
                <div class="ruh-admin-header-top">
                    <div class="ruh-admin-logo">
                        <svg viewBox="0 0 24 24" width="40" height="40">
                            <path fill="#667eea" d="M9,22A1,1 0 0,1 8,21V18H4A2,2 0 0,1 2,16V4C2,2.89 2.9,2 4,2H20A2,2 0 0,1 22,4V16A2,2 0 0,1 20,18H13.9L10.2,21.71C10,21.9 9.75,22 9.5,22V22H9Z"/>
                        </svg>
                        <div>
                            <h1>Ruh Comment</h1>
                            <span class="version">v<?php echo RUH_COMMENT_VERSION; ?></span>
                        </div>
                    </div>
                    <a href="https://ko-fi.com/solderet" target="_blank" rel="noopener noreferrer" class="ruh-kofi-btn">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                            <path d="M18,3H2V13A4,4 0 0,0 6,17H12A4,4 0 0,0 16,13V11H18A3,3 0 0,0 21,8V6A3,3 0 0,0 18,3M18,8H16V5H18A1,1 0 0,1 19,6V7A1,1 0 0,1 18,8M4,19H14V21H4V19Z"/>
                        </svg>
                        ☕ Bağış Yap &amp; Destek Ol
                    </a>
                </div>
                <p class="ruh-admin-desc">Modern ve güvenli yorum sisteminizi yapılandırın.</p>
            </div>
            
            <?php settings_errors('ruh_comment_messages'); ?>
            
            <div class="ruh-admin-tabs">
                <button class="ruh-admin-tab active" data-tab="general">
                    <?php echo $this->svg_icons['comments']; ?>
                    Genel
                </button>
                <button class="ruh-admin-tab" data-tab="features">
                    <?php echo $this->svg_icons['reactions']; ?>
                    Özellikler
                </button>
                <button class="ruh-admin-tab" data-tab="security">
                    <?php echo $this->svg_icons['security']; ?>
                    Güvenlik
                </button>
                <button class="ruh-admin-tab" data-tab="api">
                    <?php echo $this->svg_icons['gif']; ?>
                    API
                </button>
                <button class="ruh-admin-tab" data-tab="users">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/></svg>
                    Kullanıcılar
                </button>
                <button class="ruh-admin-tab" data-tab="integrations">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M10.59,13.41C11,13.8 11,14.4 10.59,14.81C10.2,15.2 9.6,15.2 9.19,14.81C7.22,12.84 7.22,9.16 9.19,7.19L12.71,3.66C14.68,1.69 18.36,1.69 20.33,3.66C22.3,5.63 22.3,9.31 20.33,11.28L18.83,12.78C18.42,12.37 17.81,12.37 17.4,12.78C17,13.18 17,13.79 17.4,14.2L18.9,12.7C21.27,10.33 21.27,6.6 18.9,4.24C16.54,1.87 12.81,1.87 10.44,4.24L6.91,7.78C4.54,10.14 4.54,13.87 6.91,16.24C7.32,16.65 7.93,16.65 8.33,16.24C8.74,15.84 8.74,15.23 8.33,14.83C6.76,13.26 6.76,10.74 8.33,9.17L10.59,13.41M13.41,9.17C13.81,8.76 14.41,8.76 14.81,9.17C16.78,11.14 16.78,14.82 14.81,16.79L11.29,20.32C9.32,22.29 5.64,22.29 3.67,20.32C1.7,18.35 1.7,14.67 3.67,12.7L5.17,11.2C5.58,11.61 6.19,11.61 6.6,11.2C7,10.8 7,10.19 6.6,9.78L5.1,11.28C2.73,13.65 2.73,17.38 5.1,19.74C7.46,22.11 11.19,22.11 13.56,19.74L17.09,16.2C19.46,13.84 19.46,10.11 17.09,7.74C16.68,7.33 16.07,7.33 15.67,7.74C15.26,8.14 15.26,8.75 15.67,9.15C17.24,10.72 17.24,13.24 15.67,14.81L13.41,9.17Z"/></svg>
                    Entegrasyon
                </button>
                <button class="ruh-admin-tab" data-tab="design">
                    <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M17.5,12A1.5,1.5 0 0,1 16,10.5A1.5,1.5 0 0,1 17.5,9A1.5,1.5 0 0,1 19,10.5A1.5,1.5 0 0,1 17.5,12M14.5,8A1.5,1.5 0 0,1 13,6.5A1.5,1.5 0 0,1 14.5,5A1.5,1.5 0 0,1 16,6.5A1.5,1.5 0 0,1 14.5,8M9.5,8A1.5,1.5 0 0,1 8,6.5A1.5,1.5 0 0,1 9.5,5A1.5,1.5 0 0,1 11,6.5A1.5,1.5 0 0,1 9.5,8M6.5,12A1.5,1.5 0 0,1 5,10.5A1.5,1.5 0 0,1 6.5,9A1.5,1.5 0 0,1 8,10.5A1.5,1.5 0 0,1 6.5,12M12,3A9,9 0 0,0 3,12A9,9 0 0,0 12,21A1.5,1.5 0 0,0 13.5,19.5C13.5,19.11 13.35,18.76 13.11,18.5C12.88,18.23 12.73,17.88 12.73,17.5A1.5,1.5 0 0,1 14.23,16H16A5,5 0 0,0 21,11C21,6.58 16.97,3 12,3Z"/></svg>
                    Tasarım
                </button>
            </div>
            
            <form method="post" action="options.php" class="ruh-admin-form" id="ruh-settings-form">
                <?php settings_fields('ruh_comment_options'); ?>
                <input type="hidden" name="_ruh_active_tab" id="ruh-active-tab" value="<?php echo esc_attr(isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general'); ?>">
                
                <!-- Genel Tab -->
                <div id="tab-general" class="ruh-tab-content active">
                    <div class="ruh-settings-card">
                        <div class="ruh-card-header">
                            <?php echo $this->svg_icons['comments']; ?>
                            <h2>Yorum Ayarları</h2>
                        </div>
                        <div class="ruh-card-body">
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Sayfa Başına Yorum</label>
                                    <span class="ruh-setting-desc">Bir sayfada gösterilecek yorum sayısı</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <input type="number" name="ruh_comment_options[comments_per_page]" 
                                           value="<?php echo esc_attr($options['comments_per_page'] ?? 10); ?>" 
                                           min="5" max="50" class="small-text">
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Yorum Başına XP</label>
                                    <span class="ruh-setting-desc">Her yorum için verilecek deneyim puanı</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <input type="number" name="ruh_comment_options[xp_per_comment]" 
                                           value="<?php echo esc_attr($options['xp_per_comment'] ?? 15); ?>" 
                                           min="0" max="100" class="small-text">
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Dil / Language</label>
                                    <span class="ruh-setting-desc">Yorum sistemi arayüz dili</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <select name="ruh_comment_options[language]">
                                        <option value="tr_TR" <?php selected($options['language'] ?? 'tr_TR', 'tr_TR'); ?>>Türkçe</option>
                                        <option value="en_US" <?php selected($options['language'] ?? 'tr_TR', 'en_US'); ?>>English</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Özellikler Tab -->
                <div id="tab-features" class="ruh-tab-content">
                    <div class="ruh-settings-card">
                        <div class="ruh-card-header">
                            <?php echo $this->svg_icons['reactions']; ?>
                            <h2>Özellik Ayarları</h2>
                        </div>
                        <div class="ruh-card-body">
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Tepki Sistemi</label>
                                    <span class="ruh-setting-desc">Kullanıcılar içeriğe tepki verebilir</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <label class="ruh-toggle">
                                        <input type="checkbox" name="ruh_comment_options[enable_reactions]" value="1" 
                                               <?php checked(1, $options['enable_reactions'] ?? 1); ?>>
                                        <span class="ruh-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Tepki Emojileri</label>
                                    <span class="ruh-setting-desc">Her tepki için emoji ve isim belirleyin</span>
                                </div>
                                <div class="ruh-setting-input ruh-emoji-grid">
                                    <?php
                                    $default_emojis = array(
                                        'begendim' => array('emoji' => '👍', 'label' => 'Beğendim'),
                                        'sinir_bozucu' => array('emoji' => '😡', 'label' => 'Sinir Bozucu'),
                                        'mukemmel' => array('emoji' => '🥰', 'label' => 'Mükemmel'),
                                        'sasirtici' => array('emoji' => '😳', 'label' => 'Şaşırtıcı'),
                                        'sakin' => array('emoji' => '🥺', 'label' => 'Üzücü'),
                                        'bitti' => array('emoji' => '😔', 'label' => 'Bitti')
                                    );
                                    foreach ($default_emojis as $key => $defaults) :
                                        $current_emoji = $options['emoji_' . $key] ?? $defaults['emoji'];
                                        $current_label = $options['emoji_label_' . $key] ?? $defaults['label'];
                                    ?>
                                    <div class="ruh-emoji-item">
                                        <input type="text" name="ruh_comment_options[emoji_<?php echo $key; ?>]" 
                                               value="<?php echo esc_attr($current_emoji); ?>" 
                                               class="ruh-emoji-input" maxlength="4" title="Emoji">
                                        <input type="text" name="ruh_comment_options[emoji_label_<?php echo $key; ?>]" 
                                               value="<?php echo esc_attr($current_label); ?>" 
                                               class="ruh-label-input" placeholder="İsim" title="İsim">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Beğeni Sistemi</label>
                                    <span class="ruh-setting-desc">Kullanıcılar yorumları beğenebilir</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <label class="ruh-toggle">
                                        <input type="checkbox" name="ruh_comment_options[enable_likes]" value="1" 
                                               <?php checked(1, $options['enable_likes'] ?? 1); ?>>
                                        <span class="ruh-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Sıralama</label>
                                    <span class="ruh-setting-desc">Yorumlar sıralanabilir</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <label class="ruh-toggle">
                                        <input type="checkbox" name="ruh_comment_options[enable_sorting]" value="1" 
                                               <?php checked(1, $options['enable_sorting'] ?? 1); ?>>
                                        <span class="ruh-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Rozet Sistemi</label>
                                    <span class="ruh-setting-desc">Kullanıcılar rozet kazanabilir</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <label class="ruh-toggle">
                                        <input type="checkbox" name="ruh_comment_options[enable_badges]" value="1" 
                                               <?php checked(1, $options['enable_badges'] ?? 1); ?>>
                                        <span class="ruh-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Şikayet Sistemi</label>
                                    <span class="ruh-setting-desc">Kullanıcılar yorumları şikayet edebilir</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <label class="ruh-toggle">
                                        <input type="checkbox" name="ruh_comment_options[enable_reporting]" value="1" 
                                               <?php checked(1, $options['enable_reporting'] ?? 1); ?>>
                                        <span class="ruh-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Yorum Kuralları</label>
                                    <span class="ruh-setting-desc">Yorum bölümünde kurallar butonu göster</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <label class="ruh-toggle">
                                        <input type="checkbox" name="ruh_comment_options[enable_comment_rules]" value="1" 
                                               <?php checked(1, $options['enable_comment_rules'] ?? 0); ?>>
                                        <span class="ruh-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Yorum Kuralları Metni</label>
                                    <span class="ruh-setting-desc">Her satır bir kural olarak gösterilir</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <textarea name="ruh_comment_options[comment_rules_text]" rows="6" class="large-text" placeholder="Nazik ve saygılı olun.&#10;Spoiler içeren yorumları etiketleyin.&#10;Reklam ve spam yasaktır."><?php echo esc_textarea($options['comment_rules_text'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Güvenlik Tab -->
                <div id="tab-security" class="ruh-tab-content">
                    <div class="ruh-settings-card">
                        <div class="ruh-card-header">
                            <?php echo $this->svg_icons['security']; ?>
                            <h2>Güvenlik Ayarları</h2>
                        </div>
                        <div class="ruh-card-body">
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Otomatik Moderasyon Eşiği</label>
                                    <span class="ruh-setting-desc">Bu kadar şikayet alan yorumlar otomatik gizlenir</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <input type="number" name="ruh_comment_options[auto_moderate_reports]" 
                                           value="<?php echo esc_attr($options['auto_moderate_reports'] ?? 3); ?>" 
                                           min="1" max="10" class="small-text">
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Maksimum Karakter Limiti</label>
                                    <span class="ruh-setting-desc">Bir yorumda izin verilen maksimum karakter sayısı (500-2000)</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <input type="number" name="ruh_comment_options[max_comment_length]" 
                                           value="<?php echo esc_attr($options['max_comment_length'] ?? 1000); ?>" 
                                           min="500" max="2000" class="small-text">
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Maksimum Link Sayısı</label>
                                    <span class="ruh-setting-desc">Bir yorumda izin verilen maksimum link sayısı</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <input type="number" name="ruh_comment_options[spam_link_limit]" 
                                           value="<?php echo esc_attr($options['spam_link_limit'] ?? 2); ?>" 
                                           min="0" max="10" class="small-text">
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Yorum Hız Limiti</label>
                                    <span class="ruh-setting-desc">Belirtilen sürede gönderilebilecek maksimum yorum sayısı</span>
                                </div>
                                <div class="ruh-setting-input" style="display: flex; gap: 10px; align-items: center;">
                                    <input type="number" name="ruh_comment_options[comment_rate_limit]" 
                                           value="<?php echo esc_attr($options['comment_rate_limit'] ?? 3); ?>" 
                                           min="1" max="20" class="small-text"> yorum /
                                    <input type="number" name="ruh_comment_options[comment_rate_window]" 
                                           value="<?php echo esc_attr($options['comment_rate_window'] ?? 60); ?>" 
                                           min="10" max="600" class="small-text"> saniye
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Yasaklı Kelimeler</label>
                                    <span class="ruh-setting-desc">Virgülle ayrılmış yasaklı kelimeler</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <textarea name="ruh_comment_options[profanity_filter_words]" rows="3" class="large-text"><?php echo esc_textarea($options['profanity_filter_words'] ?? ''); ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- API Tab -->
                <div id="tab-api" class="ruh-tab-content">
                    <div class="ruh-settings-card">
                        <div class="ruh-card-header">
                            <?php echo $this->svg_icons['gif']; ?>
                            <h2>API Ayarları</h2>
                        </div>
                        <div class="ruh-card-body">
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Giphy API Key</label>
                                    <span class="ruh-setting-desc">GIF özelliği için Giphy API anahtarı. <a href="https://developers.giphy.com/" target="_blank">Buradan alın</a></span>
                                </div>
                                <div class="ruh-setting-input">
                                    <input type="text" name="ruh_comment_options[giphy_api_key]" 
                                           value="<?php echo esc_attr($options['giphy_api_key'] ?? ''); ?>" 
                                           class="regular-text" placeholder="API anahtarınızı girin...">
                                </div>
                            </div>
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Tenor API Key</label>
                                    <span class="ruh-setting-desc">Giphy yoksa Tenor kullanılır. <a href="https://developers.google.com/tenor" target="_blank">Buradan alın</a></span>
                                </div>
                                <div class="ruh-setting-input">
                                    <input type="text" name="ruh_comment_options[tenor_api_key]" 
                                           value="<?php echo esc_attr($options['tenor_api_key'] ?? ''); ?>" 
                                           class="regular-text" placeholder="Tenor API anahtarı...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="tab-integrations" class="ruh-tab-content">
                    <div class="ruh-settings-card">
                        <div class="ruh-card-header">
                            <h2>Bildirim ve Entegrasyon</h2>
                        </div>
                        <div class="ruh-card-body">
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label"><label>Site içi bildirimler</label><span class="ruh-setting-desc">Yanıt, mention ve rozet bildirimleri</span></div>
                                <div class="ruh-setting-input"><label class="ruh-toggle"><input type="checkbox" name="ruh_comment_options[enable_notifications]" value="1" <?php checked(1, $options['enable_notifications'] ?? 1); ?>><span class="ruh-toggle-slider"></span></label></div>
                            </div>
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label"><label>Yorum arama</label><span class="ruh-setting-desc">Yorum listesinde arama ve kullanıcı filtresi</span></div>
                                <div class="ruh-setting-input"><label class="ruh-toggle"><input type="checkbox" name="ruh_comment_options[enable_comment_search]" value="1" <?php checked(1, $options['enable_comment_search'] ?? 1); ?>><span class="ruh-toggle-slider"></span></label></div>
                            </div>
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label"><label>Öne çıkan yorumlar</label><span class="ruh-setting-desc">En çok beğenilen yorumları göster</span></div>
                                <div class="ruh-setting-input"><label class="ruh-toggle"><input type="checkbox" name="ruh_comment_options[enable_highlights]" value="1" <?php checked(1, $options['enable_highlights'] ?? 1); ?>><span class="ruh-toggle-slider"></span></label></div>
                            </div>
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label"><label>Spam skoru</label><span class="ruh-setting-desc">Şüpheli yorumları otomatik reddet</span></div>
                                <div class="ruh-setting-input"><label class="ruh-toggle"><input type="checkbox" name="ruh_comment_options[enable_spam_score]" value="1" <?php checked(1, $options['enable_spam_score'] ?? 1); ?>><span class="ruh-toggle-slider"></span></label></div>
                            </div>
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label"><label>Discord Webhook</label><span class="ruh-setting-desc">Yeni yorumları Discord’a gönder</span></div>
                                <div class="ruh-setting-input"><input type="url" name="ruh_comment_options[discord_webhook_url]" value="<?php echo esc_attr($options['discord_webhook_url'] ?? ''); ?>" class="regular-text" placeholder="https://discord.com/api/webhooks/..."></div>
                            </div>
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label"><label>Telegram Bot Token</label></div>
                                <div class="ruh-setting-input"><input type="text" name="ruh_comment_options[telegram_bot_token]" value="<?php echo esc_attr($options['telegram_bot_token'] ?? ''); ?>" class="regular-text"></div>
                            </div>
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label"><label>Telegram Chat ID</label></div>
                                <div class="ruh-setting-input"><input type="text" name="ruh_comment_options[telegram_chat_id]" value="<?php echo esc_attr($options['telegram_chat_id'] ?? ''); ?>" class="regular-text"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tasarım Tab -->
                <div id="tab-design" class="ruh-tab-content">
                    <div class="ruh-settings-card">
                        <div class="ruh-card-header">
                            <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M17.5,12A1.5,1.5 0 0,1 16,10.5A1.5,1.5 0 0,1 17.5,9A1.5,1.5 0 0,1 19,10.5A1.5,1.5 0 0,1 17.5,12M14.5,8A1.5,1.5 0 0,1 13,6.5A1.5,1.5 0 0,1 14.5,5A1.5,1.5 0 0,1 16,6.5A1.5,1.5 0 0,1 14.5,8M9.5,8A1.5,1.5 0 0,1 8,6.5A1.5,1.5 0 0,1 9.5,5A1.5,1.5 0 0,1 11,6.5A1.5,1.5 0 0,1 9.5,8M6.5,12A1.5,1.5 0 0,1 5,10.5A1.5,1.5 0 0,1 6.5,9A1.5,1.5 0 0,1 8,10.5A1.5,1.5 0 0,1 6.5,12M12,3A9,9 0 0,0 3,12A9,9 0 0,0 12,21A1.5,1.5 0 0,0 13.5,19.5C13.5,19.11 13.35,18.76 13.11,18.5C12.88,18.23 12.73,17.88 12.73,17.5A1.5,1.5 0 0,1 14.23,16H16A5,5 0 0,0 21,11C21,6.58 16.97,3 12,3Z"/></svg>
                            <h2>Yorum Arayüzü Tasarımı</h2>
                        </div>
                        <div class="ruh-card-body">
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Tema Seçimi</label>
                                    <span class="ruh-setting-desc">Yorum alanı için tasarım teması seçin</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <div class="ruh-theme-options" style="display:flex;gap:20px;flex-wrap:wrap;">
                                        <?php $current_theme = $options['comment_theme'] ?? 'modern'; ?>
                                        
                                        <label class="ruh-theme-option" style="cursor:pointer;text-align:center;">
                                            <input type="radio" name="ruh_comment_options[comment_theme]" value="modern" <?php checked($current_theme, 'modern'); ?>>
                                            <div class="ruh-theme-preview" style="border:3px solid <?php echo $current_theme === 'modern' ? '#667eea' : '#ddd'; ?>;border-radius:12px;padding:15px;width:200px;background:#f8f9fa;margin-top:8px;">
                                                <div style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;padding:10px;border-radius:8px;margin-bottom:10px;font-size:12px;">
                                                    Modern Glassmorphism
                                                </div>
                                                <div style="background:#fff;padding:8px;border-radius:6px;font-size:11px;color:#666;">
                                                    Şeffaf arka plan, yumuşak gölgeler
                                                </div>
                                            </div>
                                            <strong style="display:block;margin-top:8px;">Modern (Varsayılan)</strong>
                                        </label>
                                        
                                        <label class="ruh-theme-option" style="cursor:pointer;text-align:center;">
                                            <input type="radio" name="ruh_comment_options[comment_theme]" value="disqus" <?php checked($current_theme, 'disqus'); ?>>
                                            <div class="ruh-theme-preview" style="border:3px solid <?php echo $current_theme === 'disqus' ? '#667eea' : '#ddd'; ?>;border-radius:12px;padding:15px;width:200px;background:#fff;margin-top:8px;">
                                                <div style="border-left:3px solid #2e9fff;padding-left:10px;margin-bottom:10px;">
                                                    <div style="font-size:12px;font-weight:bold;color:#333;">Disqus Tarzı</div>
                                                </div>
                                                <div style="background:#f5f5f5;padding:8px;border-radius:4px;font-size:11px;color:#666;">
                                                    Sol kenar çizgisi, minimal görünüm
                                                </div>
                                            </div>
                                             <strong style="display:block;margin-top:8px;">Disqus Tarzı</strong>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Renk modu</label>
                                    <span class="ruh-setting-desc">Otomatik sistem teması, koyu veya açık</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <?php $color_mode = $options['color_mode'] ?? 'auto'; ?>
                                    <select name="ruh_comment_options[color_mode]">
                                        <option value="auto" <?php selected($color_mode, 'auto'); ?>>Otomatik</option>
                                        <option value="dark" <?php selected($color_mode, 'dark'); ?>>Koyu</option>
                                        <option value="light" <?php selected($color_mode, 'light'); ?>>Açık</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="ruh-submit-wrap">
                    <?php submit_button('Ayarları Kaydet', 'primary', 'submit', false); ?>
                </div>
            </form>
            
            <!-- Kullanıcılar Tab - Form dışında -->
            <div id="tab-users" class="ruh-tab-content">
                    <div class="ruh-settings-card">
                        <div class="ruh-card-header">
                            <svg viewBox="0 0 24 24" width="24" height="24"><path fill="currentColor" d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/></svg>
                            <h2>Engellenmiş ve Susturulmuş Kullanıcılar</h2>
                        </div>
                        <div class="ruh-card-body">
                            <?php
                            // Engelli ve susturulmuş kullanıcıları al
                            $banned_users = get_users(array(
                                'meta_key' => 'ruh_ban_status',
                                'meta_value' => 'banned'
                            ));
                            
                            $timed_out_users = get_users(array(
                                'meta_key' => 'ruh_timeout_until',
                                'meta_compare' => '>',
                                'meta_value' => current_time('timestamp')
                            ));
                            
                            $ip_bans = get_option('ruh_banned_ips', array());
                            ?>
                            
                            <h3 style="margin-top:0;display:flex;align-items:center;gap:8px;"><svg viewBox="0 0 24 24" width="20" height="20"><path fill="#e74c3c" d="M12,2C17.53,2 22,6.47 22,12C22,17.53 17.53,22 12,22C6.47,22 2,17.53 2,12C2,6.47 6.47,2 12,2M15.59,7L12,10.59L8.41,7L7,8.41L10.59,12L7,15.59L8.41,17L12,13.41L15.59,17L17,15.59L13.41,12L17,8.41L15.59,7Z"/></svg> Engellenmiş Kullanıcılar</h3>
                            <?php if (empty($banned_users)): ?>
                                <p style="color:#888;">Engellenmiş kullanıcı yok.</p>
                            <?php else: ?>
                                <table class="wp-list-table widefat fixed striped" style="margin-bottom:20px;">
                                    <thead>
                                        <tr><th>Kullanıcı</th><th>E-posta</th><th>İşlem</th></tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($banned_users as $user): ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($user->display_name); ?></strong> (<?php echo esc_html($user->user_login); ?>)</td>
                                            <td><?php echo esc_html($user->user_email); ?></td>
                                            <td>
                                                 <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ruh-comment&action=unban&user_id=' . $user->ID), 'ruh_unban_user'); ?>" class="button button-small">Engeli Kaldır</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                            
                            <h3 style="display:flex;align-items:center;gap:8px;"><svg viewBox="0 0 24 24" width="20" height="20"><path fill="#f39c12" d="M12,4L9.91,6.09L12,8.18M4.27,3L3,4.27L7.73,9H3V15H7L12,20V13.27L16.25,17.53C15.58,18.04 14.83,18.46 14,18.7V20.77C15.38,20.45 16.63,19.82 17.68,18.96L19.73,21L21,19.73L12,10.73M19,12C19,12.94 18.8,13.82 18.46,14.64L19.97,16.15C20.62,14.91 21,13.5 21,12C21,7.72 18,4.14 14,3.23V5.29C16.89,6.15 19,8.83 19,12M16.5,12C16.5,10.23 15.5,8.71 14,7.97V10.18L16.45,12.63C16.5,12.43 16.5,12.21 16.5,12Z"/></svg> Susturulmuş Kullanıcılar (24 Saat)</h3>
                            <?php if (empty($timed_out_users)): ?>
                                <p style="color:#888;">Susturulmuş kullanıcı yok.</p>
                            <?php else: ?>
                                <table class="wp-list-table widefat fixed striped" style="margin-bottom:20px;">
                                    <thead>
                                        <tr><th>Kullanıcı</th><th>E-posta</th><th>Bitiş Zamanı</th><th>İşlem</th></tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($timed_out_users as $user): 
                                        $timeout_until = get_user_meta($user->ID, 'ruh_timeout_until', true);
                                    ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($user->display_name); ?></strong></td>
                                            <td><?php echo esc_html($user->user_email); ?></td>
                                            <td><?php echo date_i18n('d.m.Y H:i', $timeout_until); ?></td>
                                            <td>
                                                 <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ruh-comment&action=unmute&user_id=' . $user->ID), 'ruh_unmute_user'); ?>" class="button button-small">Susturmayı Kaldır</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                            
                            <h3 style="display:flex;align-items:center;gap:8px;"><svg viewBox="0 0 24 24" width="20" height="20"><path fill="#3498db" d="M16.36,14C16.44,13.34 16.5,12.68 16.5,12C16.5,11.32 16.44,10.66 16.36,10H19.74C19.9,10.64 20,11.31 20,12C20,12.69 19.9,13.36 19.74,14M14.59,19.56C15.19,18.45 15.65,17.25 15.97,16H18.92C17.96,17.65 16.43,18.93 14.59,19.56M14.34,14H9.66C9.56,13.34 9.5,12.68 9.5,12C9.5,11.32 9.56,10.65 9.66,10H14.34C14.43,10.65 14.5,11.32 14.5,12C14.5,12.68 14.43,13.34 14.34,14M12,19.96C11.17,18.76 10.5,17.43 10.09,16H13.91C13.5,17.43 12.83,18.76 12,19.96M8,8H5.08C6.03,6.34 7.57,5.06 9.4,4.44C8.8,5.55 8.35,6.75 8,8M5.08,16H8C8.35,17.25 8.8,18.45 9.4,19.56C7.57,18.93 6.03,17.65 5.08,16M4.26,14C4.1,13.36 4,12.69 4,12C4,11.31 4.1,10.64 4.26,10H7.64C7.56,10.66 7.5,11.32 7.5,12C7.5,12.68 7.56,13.34 7.64,14M12,4.03C12.83,5.23 13.5,6.57 13.91,8H10.09C10.5,6.57 11.17,5.23 12,4.03M18.92,8H15.97C15.65,6.75 15.19,5.55 14.59,4.44C16.43,5.07 17.96,6.34 18.92,8M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/></svg> IP Ban Listesi</h3>
                            <?php if (empty($ip_bans)): ?>
                                <p style="color:#888;">Engellenmiş IP adresi yok.</p>
                            <?php else: ?>
                                <table class="wp-list-table widefat fixed striped" style="margin-bottom:20px;">
                                    <thead>
                                        <tr><th>IP Adresi</th><th>Ban Tarihi</th><th>İşlem</th></tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($ip_bans as $ip => $data): ?>
                                        <tr>
                                            <td><code><?php echo esc_html($ip); ?></code></td>
                                            <td><?php echo isset($data['date']) ? date_i18n('d.m.Y H:i', $data['date']) : '-'; ?></td>
                                            <td>
                                                 <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=ruh-comment&action=unban_ip&ip=' . urlencode($ip)), 'ruh_unban_ip'); ?>" class="button button-small">IP Engelini Kaldır</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                            
                            <hr style="margin:20px 0;">
                            <h3 style="display:flex;align-items:center;gap:8px;"><svg viewBox="0 0 24 24" width="20" height="20"><path fill="#27ae60" d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/></svg> Yeni IP Engelle</h3>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:flex;gap:10px;align-items:center;">
                                <input type="hidden" name="action" value="ruh_ban_ip">
                                <?php wp_nonce_field('ruh_ban_ip_nonce'); ?>
                                <input type="text" name="ip_address" placeholder="IP adresi (örn: 192.168.1.1)" class="regular-text" required pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$">
                                <button type="submit" class="button button-primary">IP Engelle</button>
                            </form>
                        </div>
                    </div>
                </div>
        </div>
        
        <style>
        .ruh-admin-wrap {
            max-width: 1100px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .ruh-admin-wrap .ruh-admin-header {
            background: linear-gradient(135deg, #5b6eea 0%, #7c3aed 100%);
            color: white;
            padding: 28px 30px;
            border-radius: 18px;
            margin-bottom: 24px;
            box-shadow: 0 10px 30px rgba(91, 110, 234, 0.25);
        }
        
        .ruh-admin-header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 12px;
        }
        
        .ruh-kofi-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #ff5e5b;
            color: #fff !important;
            text-decoration: none !important;
            padding: 10px 20px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.3px;
            box-shadow: 0 4px 15px rgba(255, 94, 91, 0.45);
            transition: all 0.25s ease;
            white-space: nowrap;
        }
        
        .ruh-kofi-btn:hover {
            background: #ff3b38;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 94, 91, 0.6);
            color: #fff !important;
        }
        
        .ruh-kofi-btn svg {
            flex-shrink: 0;
        }
        
        .ruh-admin-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .ruh-admin-logo svg {
            background: white;
            padding: 8px;
            border-radius: 12px;
        }
        
        .ruh-admin-logo h1 {
            margin: 0;
            font-size: 28px;
            color: white;
        }
        
        .ruh-admin-logo .version {
            background: rgba(255,255,255,0.2);
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
        }
        
        .ruh-admin-desc {
            margin: 0;
            opacity: 0.9;
        }
        
        .ruh-admin-tabs {
            display: flex;
            gap: 6px;
            margin-bottom: 24px;
            background: #fff;
            padding: 6px;
            border-radius: 14px;
            border: 1px solid #ececf2;
            box-shadow: 0 1px 8px rgba(15, 23, 42, 0.04);
            overflow-x: auto;
        }
        
        .ruh-admin-tab {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: transparent;
            border: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
        }
        
        .ruh-admin-tab:hover {
            background: #f4f4ff;
            color: #4338ca;
        }
        
        .ruh-admin-tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.28);
        }
        .ruh-admin-tab.active svg { color: #fff; }
        
        .ruh-admin-tab svg {
            width: 20px;
            height: 20px;
        }
        
        .ruh-tab-content {
            display: none;
        }
        
        .ruh-tab-content.active {
            display: block;
        }
        
        .ruh-settings-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
            overflow: hidden;
            margin-bottom: 20px;
            border: 1px solid #eef0f6;
        }
        
        .ruh-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 24px;
            background: #f8f9fc;
            border-bottom: 1px solid #eee;
        }
        
        .ruh-card-header svg {
            color: #667eea;
        }
        
        .ruh-card-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .ruh-card-body {
            padding: 24px;
        }
        
        .ruh-setting-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #f0f0f5;
        }
        
        .ruh-setting-row:last-child {
            border-bottom: none;
        }
        
        .ruh-setting-label label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }
        
        .ruh-setting-desc {
            font-size: 13px;
            color: #888;
        }
        
        .ruh-setting-desc a {
            color: #667eea;
        }
        
        .ruh-setting-input input[type="number"],
        .ruh-setting-input input[type="text"] {
            padding: 10px 14px;
            border: 2px solid #e8e8ec;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s ease;
            min-width: 200px;
        }
        
        .ruh-setting-input input[type="text"].regular-text,
        .ruh-setting-input input[type="text"].large-text {
            min-width: 350px;
            width: 100%;
            max-width: 500px;
        }
        
        .ruh-setting-input input[type="number"].small-text {
            min-width: 80px;
            width: auto;
        }
        
        .ruh-setting-input input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .ruh-setting-input textarea {
            padding: 12px 14px;
            border: 2px solid #e8e8ec;
            border-radius: 8px;
            font-size: 14px;
            width: 100%;
            resize: vertical;
        }
        
        .ruh-setting-input textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        /* Toggle Switch */
        .ruh-toggle {
            position: relative;
            display: inline-block;
            width: 52px;
            height: 28px;
        }
        
        .ruh-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .ruh-toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.3s;
            border-radius: 28px;
        }
        
        .ruh-toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        
        .ruh-toggle input:checked + .ruh-toggle-slider {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .ruh-toggle input:checked + .ruh-toggle-slider:before {
            transform: translateX(24px);
        }
        
        /* Emoji Grid */
        .ruh-emoji-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        
        .ruh-emoji-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 10px;
            background: #f8f9fc;
            border: 1px solid #eef0f6;
            border-radius: 10px;
        }
        
        .ruh-emoji-item label {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }
        
        .ruh-emoji-input {
            width: 60px;
            height: 44px;
            font-size: 24px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: #f9f9f9;
            transition: all 0.2s ease;
        }
        
        .ruh-emoji-input:focus {
            border-color: #667eea;
            background: white;
            outline: none;
        }
        
        .ruh-label-input {
            width: 100%;
            padding: 8px 10px;
            font-size: 13px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            background: #f9f9f9;
            transition: all 0.2s ease;
        }
        
        .ruh-label-input:focus {
            border-color: #667eea;
            background: white;
            outline: none;
        }
        
        .ruh-submit-wrap {
            margin-top: 24px;
        }
        .ruh-admin-wrap:has(#tab-users.active) .ruh-submit-wrap {
            display: none;
        }
        
        .ruh-submit-wrap .button-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px 30px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .ruh-submit-wrap .button-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        /* Mobil Uyumluluk */
        @media (max-width: 782px) {
            .ruh-admin-wrap {
                margin: 10px;
                max-width: 100%;
            }
            
            .ruh-admin-header {
                padding: 20px 15px;
                border-radius: 12px;
            }
            
            .ruh-admin-header-top {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .ruh-kofi-btn {
                align-self: flex-start;
                font-size: 13px;
                padding: 9px 16px;
            }
            
            .ruh-admin-logo {
                flex-wrap: wrap;
            }
            
            .ruh-admin-logo h1 {
                font-size: 22px;
            }
            
            .ruh-admin-tabs {
                flex-wrap: wrap;
                gap: 6px;
                padding: 6px;
            }
            
            .ruh-admin-tab {
                padding: 10px 14px;
                font-size: 13px;
                flex: 1 1 auto;
                justify-content: center;
            }
            
            .ruh-admin-tab span {
                display: none;
            }
            
            .ruh-settings-card {
                border-radius: 12px;
            }
            
            .ruh-card-header {
                padding: 15px;
            }
            
            .ruh-card-body {
                padding: 15px;
            }
            
            .ruh-setting-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .ruh-setting-input {
                width: 100%;
            }
            
            .ruh-setting-input input[type="number"],
            .ruh-setting-input input[type="text"] {
                width: 100%;
                box-sizing: border-box;
            }
            
            .ruh-emoji-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .ruh-emoji-item {
                padding: 12px 8px;
            }
            
            .ruh-submit-wrap .button-primary {
                width: 100%;
                text-align: center;
            }
        }
        </style>
        <?php
    }

    public function settings_init() {
        register_setting('ruh_comment_options', 'ruh_comment_options', array($this, 'sanitize_settings'));
    }
    
    public function sanitize_settings($input) {
        if (!is_array($input)) return get_option('ruh_comment_options', array());
        
        $sanitized = get_option('ruh_comment_options', array());
        if (!is_array($sanitized)) $sanitized = array();
        
        // Checkbox fields
        $checkboxes = array('enable_reactions', 'enable_likes', 'enable_sorting', 'enable_badges', 'enable_reporting', 'enable_comment_rules', 'enable_notifications', 'enable_comment_search', 'enable_highlights', 'enable_spam_score');
        foreach ($checkboxes as $key) {
            $sanitized[$key] = !empty($input[$key]) ? 1 : 0;
        }
        
        // Number fields
        $sanitized['xp_per_comment'] = isset($input['xp_per_comment']) ? max(0, min(100, absint($input['xp_per_comment']))) : 15;
        $sanitized['comments_per_page'] = isset($input['comments_per_page']) ? max(5, min(50, absint($input['comments_per_page']))) : 10;
        $sanitized['auto_moderate_reports'] = isset($input['auto_moderate_reports']) ? max(1, min(10, absint($input['auto_moderate_reports']))) : 3;
        $sanitized['spam_link_limit'] = isset($input['spam_link_limit']) ? max(0, min(10, absint($input['spam_link_limit']))) : 2;
        $sanitized['comment_rate_limit'] = isset($input['comment_rate_limit']) ? max(1, min(20, absint($input['comment_rate_limit']))) : 3;
        $sanitized['comment_rate_window'] = isset($input['comment_rate_window']) ? max(10, min(600, absint($input['comment_rate_window']))) : 60;
        $sanitized['max_comment_length'] = isset($input['max_comment_length']) ? max(500, min(2000, absint($input['max_comment_length']))) : 1000;
        
        // Text fields
        $sanitized['giphy_api_key'] = isset($input['giphy_api_key']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $input['giphy_api_key']) : '';
        $sanitized['tenor_api_key'] = isset($input['tenor_api_key']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $input['tenor_api_key']) : '';
        $sanitized['discord_webhook_url'] = isset($input['discord_webhook_url']) ? esc_url_raw($input['discord_webhook_url']) : '';
        $sanitized['telegram_bot_token'] = isset($input['telegram_bot_token']) ? sanitize_text_field($input['telegram_bot_token']) : '';
        $sanitized['telegram_chat_id'] = isset($input['telegram_chat_id']) ? sanitize_text_field($input['telegram_chat_id']) : '';
        $sanitized['color_mode'] = isset($input['color_mode']) && in_array($input['color_mode'], array('auto','dark','light'), true) ? $input['color_mode'] : 'auto';
        $sanitized['profanity_filter_words'] = isset($input['profanity_filter_words']) ? sanitize_textarea_field($input['profanity_filter_words']) : '';
        $sanitized['comment_rules_text'] = isset($input['comment_rules_text']) ? sanitize_textarea_field($input['comment_rules_text']) : '';
        
        // Emoji fields
        $emoji_keys = array('begendim', 'sinir_bozucu', 'mukemmel', 'sasirtici', 'sakin', 'bitti');
        $default_emojis = array('👍', '😡', '🥰', '😳', '🥺', '😔');
        $default_labels = array('Beğendim', 'Sinir Bozucu', 'Mükemmel', 'Şaşırtıcı', 'Üzücü', 'Bitti');
        foreach ($emoji_keys as $index => $key) {
            // Emoji
            $emoji_field = 'emoji_' . $key;
            if (isset($input[$emoji_field]) && !empty(trim($input[$emoji_field]))) {
                $sanitized[$emoji_field] = mb_substr(trim($input[$emoji_field]), 0, 4);
            } else {
                $sanitized[$emoji_field] = $default_emojis[$index];
            }
            // Label
            $label_field = 'emoji_label_' . $key;
            if (isset($input[$label_field]) && !empty(trim($input[$label_field]))) {
                $sanitized[$label_field] = sanitize_text_field(mb_substr(trim($input[$label_field]), 0, 20));
            } else {
                $sanitized[$label_field] = $default_labels[$index];
            }
        }
        
        // Language field
        $allowed_languages = array('tr_TR', 'en_US');
        $sanitized['language'] = isset($input['language']) && in_array($input['language'], $allowed_languages) ? $input['language'] : 'tr_TR';
        
        // Theme field
        $allowed_themes = array('modern', 'disqus');
        $sanitized['comment_theme'] = isset($input['comment_theme']) && in_array($input['comment_theme'], $allowed_themes) ? $input['comment_theme'] : 'modern';
        
        return $sanitized;
    }
}

new Ruh_Comment_Admin();
