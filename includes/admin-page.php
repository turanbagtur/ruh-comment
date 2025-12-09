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
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // SVG ikonlari tanimla
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
        
        // Ayarlar alt menusu
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
                'Yorum Yonetimi', 
                'Yorum Yonetimi', 
                'manage_options', 
                'ruh-comment-manager', 
                'render_comment_manager_page_content'
            );
        }
        
        if (function_exists('render_badges_page_content')) {
            add_submenu_page(
                'ruh-comment',
                'Rozet Yonetimi',
                'Rozet Yonetimi',
                'manage_options',
                'ruh-comment-badges',
                'render_badges_page_content'
            );
        }
        
        if (function_exists('render_level_manager_page_content')) {
            add_submenu_page(
                'ruh-comment',
                'Seviye Yonetimi',
                'Seviye Yonetimi',
                'manage_options',
                'ruh-comment-levels',
                'render_level_manager_page_content'
            );
        }
        
        // Sikayet Yonetimi
        add_submenu_page(
            'ruh-comment',
            'Sikayet Yonetimi',
            'Sikayetler',
            'manage_options',
            'ruh-comment-reports',
            array($this, 'render_reports_page')
        );
    }
    
    /**
     * WordPress varsayilan yorumlar menusunu Ruh Comment ile degistir
     */
    public function replace_comments_menu() {
        global $menu, $submenu;
        
        // Varsayilan Comments menusunu kaldir
        remove_menu_page('edit-comments.php');
        
        // Comments menu yerine Ruh Comment'e yonlendir
        if (isset($submenu['edit-comments.php'])) {
            unset($submenu['edit-comments.php']);
        }
    }
    
    /**
     * edit-comments.php sayfasina erisimi Ruh Comment'e yonlendir
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
        $reports_table = $wpdb->prefix . 'ruh_reports';
        
        // Sikayet islemleri
        if (isset($_POST['report_action']) && isset($_POST['report_id'])) {
            $report_id = intval($_POST['report_id']);
            $action = sanitize_text_field($_POST['report_action']);
            
            if ($action === 'dismiss') {
                $wpdb->update($reports_table, array('status' => 'dismissed'), array('id' => $report_id));
            } elseif ($action === 'delete_comment') {
                $report = $wpdb->get_row($wpdb->prepare("SELECT comment_id FROM $reports_table WHERE id = %d", $report_id));
                if ($report) {
                    wp_delete_comment($report->comment_id, true);
                    $wpdb->update($reports_table, array('status' => 'resolved'), array('id' => $report_id));
                }
            }
        }
        
        // Sikayetleri getir
        $reports = $wpdb->get_results("
            SELECT r.*, c.comment_content, c.comment_author, u.display_name as reporter_name
            FROM $reports_table r
            LEFT JOIN {$wpdb->comments} c ON r.comment_id = c.comment_ID
            LEFT JOIN {$wpdb->users} u ON r.reporter_id = u.ID
            ORDER BY r.report_time DESC
            LIMIT 50
        ");
        
        ?>
        <div class="wrap">
            <h1>Sikayet Yonetimi</h1>
            <p>Kullanicilar tarafindan sikayet edilen yorumlari buradan yonetebilirsiniz.</p>
            
            <?php if (empty($reports)): ?>
                <div class="notice notice-info">
                    <p>Bekleyen sikayet bulunmuyor.</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Yorum</th>
                            <th>Yazar</th>
                            <th>Sikayet Eden</th>
                            <th>Sebep</th>
                            <th>Tarih</th>
                            <th>Islemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo esc_html(wp_trim_words($report->comment_content ?: 'Silinmis yorum', 15)); ?></td>
                                <td><?php echo esc_html($report->comment_author ?: '-'); ?></td>
                                <td><?php echo esc_html($report->reporter_name ?: 'Misafir'); ?></td>
                                <td><?php echo esc_html($report->reason); ?></td>
                                <td><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($report->created_at))); ?></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="report_id" value="<?php echo $report->id; ?>">
                                        <button type="submit" name="report_action" value="dismiss" class="button">Reddet</button>
                                        <button type="submit" name="report_action" value="delete_comment" class="button button-primary" onclick="return confirm('Yorumu silmek istediginizden emin misiniz?');">Yorumu Sil</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'ruh-comment') === false) return;
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Admin JS
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                // Tab navigation
                $(".ruh-admin-tab").on("click", function(e) {
                    e.preventDefault();
                    var target = $(this).data("tab");
                    $(".ruh-admin-tab").removeClass("active");
                    $(this).addClass("active");
                    $(".ruh-tab-content").removeClass("active");
                    $("#tab-" + target).addClass("active");
                });
            });
        ');
    }
    
    public function render_settings_page() {
        if (isset($_GET['settings-updated'])) {
            add_settings_error('ruh_comment_messages', 'ruh_comment_message', 'Ayarlar kaydedildi!', 'success');
        }
        
        $options = get_option('ruh_comment_options', array());
        ?>
        <div class="wrap ruh-admin-wrap">
            <div class="ruh-admin-header">
                <div class="ruh-admin-logo">
                    <svg viewBox="0 0 24 24" width="40" height="40">
                        <path fill="#667eea" d="M9,22A1,1 0 0,1 8,21V18H4A2,2 0 0,1 2,16V4C2,2.89 2.9,2 4,2H20A2,2 0 0,1 22,4V16A2,2 0 0,1 20,18H13.9L10.2,21.71C10,21.9 9.75,22 9.5,22V22H9Z"/>
                    </svg>
                    <div>
                        <h1>Ruh Comment</h1>
                        <span class="version">v<?php echo RUH_COMMENT_VERSION; ?></span>
                    </div>
                </div>
                <p class="ruh-admin-desc">Modern ve guvenli yorum sisteminizi yapilandirin.</p>
            </div>
            
            <?php settings_errors('ruh_comment_messages'); ?>
            
            <div class="ruh-admin-tabs">
                <button class="ruh-admin-tab active" data-tab="general">
                    <?php echo $this->svg_icons['comments']; ?>
                    Genel
                </button>
                <button class="ruh-admin-tab" data-tab="features">
                    <?php echo $this->svg_icons['reactions']; ?>
                    Ozellikler
                </button>
                <button class="ruh-admin-tab" data-tab="security">
                    <?php echo $this->svg_icons['security']; ?>
                    Guvenlik
                </button>
                <button class="ruh-admin-tab" data-tab="api">
                    <?php echo $this->svg_icons['gif']; ?>
                    API
                </button>
            </div>
            
            <form method="post" action="options.php" class="ruh-admin-form">
                <?php settings_fields('ruh_comment_options'); ?>
                
                <!-- Genel Tab -->
                <div id="tab-general" class="ruh-tab-content active">
                    <div class="ruh-settings-card">
                        <div class="ruh-card-header">
                            <?php echo $this->svg_icons['comments']; ?>
                            <h2>Yorum Ayarlari</h2>
                        </div>
                        <div class="ruh-card-body">
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Sayfa Basina Yorum</label>
                                    <span class="ruh-setting-desc">Bir sayfada gosterilecek yorum sayisi</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <input type="number" name="ruh_comment_options[comments_per_page]" 
                                           value="<?php echo esc_attr($options['comments_per_page'] ?? 10); ?>" 
                                           min="5" max="50" class="small-text">
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Yorum Basina XP</label>
                                    <span class="ruh-setting-desc">Her yorum icin verilecek deneyim puani</span>
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
                                    <span class="ruh-setting-desc">Yorum sistemi arayuz dili</span>
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
                
                <!-- Ozellikler Tab -->
                <div id="tab-features" class="ruh-tab-content">
                    <div class="ruh-settings-card">
                        <div class="ruh-card-header">
                            <?php echo $this->svg_icons['reactions']; ?>
                            <h2>Ozellik Ayarlari</h2>
                        </div>
                        <div class="ruh-card-body">
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Tepki Sistemi</label>
                                    <span class="ruh-setting-desc">Kullanicilar icerige tepki verebilir</span>
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
                                    <label>Begeni Sistemi</label>
                                    <span class="ruh-setting-desc">Kullanicilar yorumlari begenebilir</span>
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
                                    <label>Siralama</label>
                                    <span class="ruh-setting-desc">Yorumlar siralanabilir</span>
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
                                    <span class="ruh-setting-desc">Kullanicilar rozet kazanabilir</span>
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
                                    <label>Sikayet Sistemi</label>
                                    <span class="ruh-setting-desc">Kullanicilar yorumlari sikayet edebilir</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <label class="ruh-toggle">
                                        <input type="checkbox" name="ruh_comment_options[enable_reporting]" value="1" 
                                               <?php checked(1, $options['enable_reporting'] ?? 1); ?>>
                                        <span class="ruh-toggle-slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Guvenlik Tab -->
                <div id="tab-security" class="ruh-tab-content">
                    <div class="ruh-settings-card">
                        <div class="ruh-card-header">
                            <?php echo $this->svg_icons['security']; ?>
                            <h2>Guvenlik Ayarlari</h2>
                        </div>
                        <div class="ruh-card-body">
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Otomatik Moderasyon Esigi</label>
                                    <span class="ruh-setting-desc">Bu kadar sikayet alan yorumlar otomatik gizlenir</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <input type="number" name="ruh_comment_options[auto_moderate_reports]" 
                                           value="<?php echo esc_attr($options['auto_moderate_reports'] ?? 3); ?>" 
                                           min="1" max="10" class="small-text">
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Maksimum Link Sayisi</label>
                                    <span class="ruh-setting-desc">Bir yorumda izin verilen maksimum link sayisi</span>
                                </div>
                                <div class="ruh-setting-input">
                                    <input type="number" name="ruh_comment_options[spam_link_limit]" 
                                           value="<?php echo esc_attr($options['spam_link_limit'] ?? 2); ?>" 
                                           min="0" max="10" class="small-text">
                                </div>
                            </div>
                            
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Yasakli Kelimeler</label>
                                    <span class="ruh-setting-desc">Virgulle ayrilmis yasakli kelimeler</span>
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
                            <h2>API Ayarlari</h2>
                        </div>
                        <div class="ruh-card-body">
                            <div class="ruh-setting-row">
                                <div class="ruh-setting-label">
                                    <label>Giphy API Key</label>
                                    <span class="ruh-setting-desc">GIF ozelligi icin Giphy API anahtari. <a href="https://developers.giphy.com/" target="_blank">Buradan alin</a></span>
                                </div>
                                <div class="ruh-setting-input">
                                    <input type="text" name="ruh_comment_options[giphy_api_key]" 
                                           value="<?php echo esc_attr($options['giphy_api_key'] ?? ''); ?>" 
                                           class="regular-text" placeholder="API anahtarinizi girin...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="ruh-submit-wrap">
                    <?php submit_button('Ayarlari Kaydet', 'primary', 'submit', false); ?>
                </div>
            </form>
        </div>
        
        <style>
        .ruh-admin-wrap {
            max-width: 900px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .ruh-admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 24px;
        }
        
        .ruh-admin-logo {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
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
            gap: 8px;
            margin-bottom: 24px;
            background: #f5f5f7;
            padding: 8px;
            border-radius: 12px;
        }
        
        .ruh-admin-tab {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: transparent;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .ruh-admin-tab:hover {
            background: white;
            color: #333;
        }
        
        .ruh-admin-tab.active {
            background: white;
            color: #667eea;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
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
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 20px;
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
        
        .ruh-submit-wrap {
            margin-top: 24px;
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
        </style>
        <?php
    }

    public function settings_init() {
        register_setting('ruh_comment_options', 'ruh_comment_options', array($this, 'sanitize_settings'));
    }
    
    public function sanitize_settings($input) {
        if (!is_array($input)) return array();
        
        $sanitized = array();
        
        // Checkbox fields
        $checkboxes = array('enable_reactions', 'enable_likes', 'enable_sorting', 'enable_badges', 'enable_reporting');
        foreach ($checkboxes as $key) {
            $sanitized[$key] = !empty($input[$key]) ? 1 : 0;
        }
        
        // Number fields
        $sanitized['xp_per_comment'] = isset($input['xp_per_comment']) ? max(0, min(100, absint($input['xp_per_comment']))) : 15;
        $sanitized['comments_per_page'] = isset($input['comments_per_page']) ? max(5, min(50, absint($input['comments_per_page']))) : 10;
        $sanitized['auto_moderate_reports'] = isset($input['auto_moderate_reports']) ? max(1, min(10, absint($input['auto_moderate_reports']))) : 3;
        $sanitized['spam_link_limit'] = isset($input['spam_link_limit']) ? max(0, min(10, absint($input['spam_link_limit']))) : 2;
        
        // Text fields
        $sanitized['giphy_api_key'] = isset($input['giphy_api_key']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $input['giphy_api_key']) : '';
        $sanitized['profanity_filter_words'] = isset($input['profanity_filter_words']) ? sanitize_textarea_field($input['profanity_filter_words']) : '';
        
        // Language field
        $allowed_languages = array('tr_TR', 'en_US');
        $sanitized['language'] = isset($input['language']) && in_array($input['language'], $allowed_languages) ? $input['language'] : 'tr_TR';
        
        return $sanitized;
    }
}

new Ruh_Comment_Admin();
