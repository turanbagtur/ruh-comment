<?php
if (!defined('ABSPATH')) exit;

class Ruh_Badge_Manager {
    
    private $table_badges;
    private $table_user_badges;
    
    // Hazir SVG ikonlar
    private $badge_icons = array(
        'star' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/></svg>',
        'heart' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,21.35L10.55,20.03C5.4,15.36 2,12.27 2,8.5C2,5.41 4.42,3 7.5,3C9.24,3 10.91,3.81 12,5.08C13.09,3.81 14.76,3 16.5,3C19.58,3 22,5.41 22,8.5C22,12.27 18.6,15.36 13.45,20.03L12,21.35Z"/></svg>',
        'trophy' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M18,2H6V4H7V6H6V8L9,11V13H8V15H7V17H6V19H4V21H20V19H18V17H17V15H16V13H15V11L18,8V6H17V4H18V2M9,4H15V6H9V4Z"/></svg>',
        'medal' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,4A6,6 0 0,1 18,10C18,13.31 15.31,16 12,16C8.69,16 6,13.31 6,10A6,6 0 0,1 12,4M12,6A4,4 0 0,0 8,10A4,4 0 0,0 12,14A4,4 0 0,0 16,10A4,4 0 0,0 12,6M7,18A1,1 0 0,1 6,19A1,1 0 0,1 5,18H7M17,18A1,1 0 0,0 18,19A1,1 0 0,0 19,18H17M10,18H14V20H10V18Z"/></svg>',
        'crown' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M5,16L3,5L8.5,10L12,4L15.5,10L21,5L19,16H5M19,19A1,1 0 0,1 18,20H6A1,1 0 0,1 5,19V18H19V19Z"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1Z"/></svg>',
        'fire' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M17.66,11.2C17.43,10.9 17.15,10.64 16.89,10.38C16.22,9.78 15.46,9.35 14.82,8.72C13.33,7.26 13,4.85 13.95,3C13,3.23 12.17,3.75 11.46,4.32C8.87,6.4 7.85,10.07 9.07,13.22C9.11,13.32 9.15,13.42 9.15,13.55C9.15,13.77 9,13.97 8.8,14.05C8.57,14.15 8.33,14.09 8.14,13.93C8.08,13.88 8.04,13.83 8,13.76C6.87,12.33 6.69,10.28 7.45,8.64C5.78,10 4.87,12.3 5,14.47C5.06,14.97 5.12,15.47 5.29,15.97C5.43,16.57 5.7,17.17 6,17.7C7.08,19.43 8.95,20.67 10.96,20.92C13.1,21.19 15.39,20.8 17.03,19.32C18.86,17.66 19.5,15 18.56,12.72L18.43,12.46C18.22,12 17.66,11.2 17.66,11.2Z"/></svg>',
        'lightning' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M11,4L6,14H11V20L16,10H11V4Z"/></svg>',
        'diamond' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M6,2L2,8L12,22L22,8L18,2H6M6.5,4H8.5L7,7L6.5,4M9.5,4H14.5L15,7L12,10L9,7L9.5,4M16.5,4H17.5L17,7L15,4H16.5Z"/></svg>',
        'rocket' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,2.5L20.84,11.34C20.94,11.44 21,11.57 21,11.71V20.5A1.5,1.5 0 0,1 19.5,22H15V18.5A1.5,1.5 0 0,0 13.5,17H10.5A1.5,1.5 0 0,0 9,18.5V22H4.5A1.5,1.5 0 0,1 3,20.5V11.71C3,11.57 3.06,11.44 3.16,11.34L12,2.5M12,5.5L6,11.5V19H7V18.5A3.5,3.5 0 0,1 10.5,15H13.5A3.5,3.5 0 0,1 17,18.5V19H18V11.5L12,5.5Z"/></svg>',
        'book' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M18,22A2,2 0 0,0 20,20V4C20,2.89 19.1,2 18,2H12V9L9.5,7.5L7,9V2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18Z"/></svg>',
        'chat' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,3C6.5,3 2,6.58 2,11A7.18,7.18 0 0,0 2.64,14.34L1.17,18.83L5.66,17.36C7.38,18.39 9.61,19 12,19C17.5,19 22,15.42 22,11S17.5,3 12,3Z"/></svg>',
        'thumbsup' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M23,10C23,8.89 22.1,8 21,8H14.68L15.64,3.43C15.66,3.33 15.67,3.22 15.67,3.11C15.67,2.7 15.5,2.32 15.23,2.05L14.17,1L7.59,7.58C7.22,7.95 7,8.45 7,9V19A2,2 0 0,0 9,21H18C18.83,21 19.54,20.5 19.84,19.78L22.86,12.73C22.95,12.5 23,12.26 23,12V10M1,21H5V9H1V21Z"/></svg>',
        'eye' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z"/></svg>',
        'clock' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M16.2,16.2L11,13V7H12.5V12.2L17,14.7L16.2,16.2Z"/></svg>',
    );
    
    // Renk secenekleri
    private $badge_colors = array(
        '#667eea' => 'Mor',
        '#764ba2' => 'Koyu Mor',
        '#f59e0b' => 'Turuncu',
        '#10b981' => 'Yesil',
        '#ef4444' => 'Kirmizi',
        '#3b82f6' => 'Mavi',
        '#ec4899' => 'Pembe',
        '#8b5cf6' => 'Acik Mor',
        '#14b8a6' => 'Turkuaz',
        '#f97316' => 'Koyu Turuncu',
    );
    
    public function __construct() {
        global $wpdb;
        $this->table_badges = $wpdb->prefix . 'ruh_badges';
        $this->table_user_badges = $wpdb->prefix . 'ruh_user_badges';
        
        add_action('wp_ajax_ruh_badge_action', array($this, 'handle_ajax_requests'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'ruh-comment-badges') === false) return;
        
        wp_enqueue_script('jquery');
        
        // Inline script
        wp_add_inline_script('jquery', '
            var ruh_badge_ajax = {
                ajax_url: "' . admin_url('admin-ajax.php') . '",
                nonce: "' . wp_create_nonce('ruh_badge_nonce') . '"
            };
        ');
    }
    
    public function handle_ajax_requests() {
        // Nonce kontrolu
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ruh_badge_nonce')) {
            wp_send_json_error('Guvenlik hatasi!');
        }
        
        // Yetki kontrolu
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkisiz erisim!');
        }
        
        $action = isset($_POST['badge_action']) ? sanitize_text_field($_POST['badge_action']) : '';
        
        switch ($action) {
            case 'create':
                $this->create_badge();
                break;
            case 'delete':
                $this->delete_badge();
                break;
            case 'assign':
                $this->assign_badge();
                break;
            case 'remove':
                $this->remove_badge();
                break;
            default:
                wp_send_json_error('Gecersiz islem!');
        }
    }
    
    private function create_badge() {
        $name = isset($_POST['badge_name']) ? sanitize_text_field($_POST['badge_name']) : '';
        $icon = isset($_POST['badge_icon']) ? sanitize_key($_POST['badge_icon']) : 'star';
        $color = isset($_POST['badge_color']) ? sanitize_hex_color($_POST['badge_color']) : '#667eea';
        
        if (empty($name)) {
            wp_send_json_error('Rozet adi gerekli!');
        }
        
        // SVG olustur
        $svg = isset($this->badge_icons[$icon]) ? $this->badge_icons[$icon] : $this->badge_icons['star'];
        $svg = str_replace('{color}', $color, $svg);
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_badges,
            array(
                'badge_name' => $name,
                'badge_svg' => $svg,
                'is_automated' => 0
            ),
            array('%s', '%s', '%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Rozet kaydedilemedi! DB Hatasi: ' . $wpdb->last_error);
        }
        
        wp_send_json_success(array(
            'message' => 'Rozet basariyla eklendi!',
            'badge_id' => $wpdb->insert_id
        ));
    }
    
    private function delete_badge() {
        $badge_id = isset($_POST['badge_id']) ? intval($_POST['badge_id']) : 0;
        
        if (!$badge_id) {
            wp_send_json_error('Gecersiz rozet ID!');
        }
        
        global $wpdb;
        
        // Once kullanicilardan rozeti kaldir
        $wpdb->delete($this->table_user_badges, array('badge_id' => $badge_id), array('%d'));
        
        // Sonra rozeti sil
        $result = $wpdb->delete($this->table_badges, array('badge_id' => $badge_id), array('%d'));
        
        if ($result === false) {
            wp_send_json_error('Rozet silinemedi!');
        }
        
        wp_send_json_success('Rozet basariyla silindi!');
    }
    
    private function assign_badge() {
        $badge_id = isset($_POST['badge_id']) ? intval($_POST['badge_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$badge_id || !$user_id) {
            wp_send_json_error('Gecersiz parametreler!');
        }
        
        global $wpdb;
        
        // Zaten var mi kontrol et
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_user_badges} WHERE user_id = %d AND badge_id = %d",
            $user_id, $badge_id
        ));
        
        if ($exists) {
            wp_send_json_error('Bu rozet zaten atanmis!');
        }
        
        $result = $wpdb->insert(
            $this->table_user_badges,
            array(
                'user_id' => $user_id,
                'badge_id' => $badge_id
            ),
            array('%d', '%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Rozet atanamadi!');
        }
        
        wp_send_json_success('Rozet basariyla atandi!');
    }
    
    private function remove_badge() {
        $badge_id = isset($_POST['badge_id']) ? intval($_POST['badge_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$badge_id || !$user_id) {
            wp_send_json_error('Gecersiz parametreler!');
        }
        
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_user_badges,
            array('user_id' => $user_id, 'badge_id' => $badge_id),
            array('%d', '%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Rozet kaldirilmadi!');
        }
        
        wp_send_json_success('Rozet kaldirildi!');
    }
    
    public function get_all_badges() {
        global $wpdb;
        
        return $wpdb->get_results("
            SELECT b.*, 
                   COUNT(ub.user_id) as user_count
            FROM {$this->table_badges} b
            LEFT JOIN {$this->table_user_badges} ub ON b.badge_id = ub.badge_id
            GROUP BY b.badge_id
            ORDER BY b.badge_id DESC
        ");
    }
    
    public function render_admin_page() {
        $badges = $this->get_all_badges();
        $users = get_users(array('number' => 100, 'orderby' => 'display_name'));
        
        ?>
        <div class="wrap ruh-badge-wrap">
            <div class="ruh-badge-header">
                <div class="ruh-badge-logo">
                    <svg viewBox="0 0 24 24" width="40" height="40">
                        <path fill="#667eea" d="M12,4A6,6 0 0,1 18,10C18,13.31 15.31,16 12,16C8.69,16 6,13.31 6,10A6,6 0 0,1 12,4M12,6A4,4 0 0,0 8,10A4,4 0 0,0 12,14A4,4 0 0,0 16,10A4,4 0 0,0 12,6M7,18A1,1 0 0,1 6,19A1,1 0 0,1 5,18H7M17,18A1,1 0 0,0 18,19A1,1 0 0,0 19,18H17M10,18H14V20H10V18Z"/>
                    </svg>
                    <div>
                        <h1>Rozet Yonetimi</h1>
                        <p>Kullanici rozetlerini olusturun ve yonetin.</p>
                    </div>
                </div>
            </div>
            
            <div class="ruh-badge-grid">
                <!-- Yeni Rozet Formu -->
                <div class="ruh-badge-card">
                    <div class="ruh-badge-card-header">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/></svg>
                        <h2>Yeni Rozet Ekle</h2>
                    </div>
                    <div class="ruh-badge-card-body">
                        <form id="badge-form">
                            <div class="ruh-form-group">
                                <label>Rozet Adi</label>
                                <input type="text" id="badge-name" name="badge_name" required placeholder="Ornek: VIP Uye">
                            </div>
                            
                            <div class="ruh-form-group">
                                <label>Ikon Sec</label>
                                <div class="ruh-icon-grid">
                                    <?php foreach ($this->badge_icons as $key => $svg): ?>
                                        <label class="ruh-icon-option">
                                            <input type="radio" name="badge_icon" value="<?php echo esc_attr($key); ?>" <?php checked($key, 'star'); ?>>
                                            <span class="ruh-icon-preview"><?php echo str_replace('{color}', '#667eea', $svg); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="ruh-form-group">
                                <label>Renk Sec</label>
                                <div class="ruh-color-grid">
                                    <?php foreach ($this->badge_colors as $hex => $name): ?>
                                        <label class="ruh-color-option" title="<?php echo esc_attr($name); ?>">
                                            <input type="radio" name="badge_color" value="<?php echo esc_attr($hex); ?>" <?php checked($hex, '#667eea'); ?>>
                                            <span class="ruh-color-preview" style="background: <?php echo esc_attr($hex); ?>;"></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="ruh-form-group">
                                <label>Onizleme</label>
                                <div id="badge-preview" class="ruh-badge-preview">
                                    <?php echo str_replace('{color}', '#667eea', $this->badge_icons['star']); ?>
                                    <span>Rozet Adi</span>
                                </div>
                            </div>
                            
                            <button type="submit" class="ruh-btn ruh-btn-primary">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/></svg>
                                Rozet Ekle
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Mevcut Rozetler -->
                <div class="ruh-badge-card ruh-badge-card-wide">
                    <div class="ruh-badge-card-header">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M3,13H15V11H3M3,6V8H21V6M3,18H9V16H3V18Z"/></svg>
                        <h2>Mevcut Rozetler</h2>
                        <span class="ruh-badge-count"><?php echo count($badges); ?> rozet</span>
                    </div>
                    <div class="ruh-badge-card-body">
                        <?php if (empty($badges)): ?>
                            <div class="ruh-empty-state">
                                <svg viewBox="0 0 24 24" width="64" height="64"><path fill="#ccc" d="M12,4A6,6 0 0,1 18,10C18,13.31 15.31,16 12,16C8.69,16 6,13.31 6,10A6,6 0 0,1 12,4M12,6A4,4 0 0,0 8,10A4,4 0 0,0 12,14A4,4 0 0,0 16,10A4,4 0 0,0 12,6M7,18A1,1 0 0,1 6,19A1,1 0 0,1 5,18H7M17,18A1,1 0 0,0 18,19A1,1 0 0,0 19,18H17M10,18H14V20H10V18Z"/></svg>
                                <p>Henuz rozet eklenmemis.</p>
                            </div>
                        <?php else: ?>
                            <div class="ruh-badge-list">
                                <?php foreach ($badges as $badge): ?>
                                    <div class="ruh-badge-item" id="badge-row-<?php echo $badge->badge_id; ?>">
                                        <div class="ruh-badge-icon">
                                            <?php echo wp_kses($badge->badge_svg, array('svg' => array('viewBox' => true, 'width' => true, 'height' => true), 'path' => array('fill' => true, 'd' => true))); ?>
                                        </div>
                                        <div class="ruh-badge-info">
                                            <strong><?php echo esc_html($badge->badge_name); ?></strong>
                                            <span><?php echo intval($badge->user_count); ?> kullanici</span>
                                        </div>
                                        <div class="ruh-badge-actions">
                                            <button class="ruh-btn ruh-btn-sm ruh-btn-danger delete-badge" data-badge-id="<?php echo $badge->badge_id; ?>">
                                                <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/></svg>
                                                Sil
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Rozet Atama -->
                <div class="ruh-badge-card">
                    <div class="ruh-badge-card-header">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/></svg>
                        <h2>Rozet Ata</h2>
                    </div>
                    <div class="ruh-badge-card-body">
                        <form id="assign-badge-form">
                            <div class="ruh-form-group">
                                <label>Kullanici Sec</label>
                                <select id="assign-user-id" name="user_id" required>
                                    <option value="">-- Kullanici Secin --</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user->ID; ?>"><?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_login); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="ruh-form-group">
                                <label>Rozet Sec</label>
                                <select id="assign-badge-id" name="badge_id" required>
                                    <option value="">-- Rozet Secin --</option>
                                    <?php foreach ($badges as $badge): ?>
                                        <option value="<?php echo $badge->badge_id; ?>"><?php echo esc_html($badge->badge_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="ruh-btn ruh-btn-primary">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/></svg>
                                Rozet Ata
                            </button>
                        </form>
                        
                        <hr style="margin: 20px 0; border-color: #333;">
                        
                        <h4 style="margin-bottom: 10px;">Rozet Kaldir</h4>
                        <form id="remove-badge-form">
                            <div class="ruh-form-group">
                                <label>Kullanici Sec</label>
                                <select id="remove-user-id" name="user_id" required>
                                    <option value="">-- Kullanici Secin --</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user->ID; ?>"><?php echo esc_html($user->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="ruh-form-group">
                                <label>Rozet Sec</label>
                                <select id="remove-badge-id" name="badge_id" required>
                                    <option value="">-- Rozet Secin --</option>
                                    <?php foreach ($badges as $badge): ?>
                                        <option value="<?php echo $badge->badge_id; ?>"><?php echo esc_html($badge->badge_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="ruh-btn ruh-btn-danger">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/></svg>
                                Rozet Kaldir
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Ikon ve renk secimi - onizleme guncelle
            function updatePreview() {
                var icon = $('input[name="badge_icon"]:checked').val();
                var color = $('input[name="badge_color"]:checked').val();
                var name = $('#badge-name').val() || 'Rozet Adi';
                
                var icons = <?php echo json_encode($this->badge_icons); ?>;
                var svg = icons[icon] || icons['star'];
                svg = svg.replace(/{color}/g, color);
                
                $('#badge-preview').html(svg + '<span>' + name + '</span>');
            }
            
            $('input[name="badge_icon"], input[name="badge_color"]').on('change', updatePreview);
            $('#badge-name').on('input', updatePreview);
            
            // Yeni rozet ekleme
            $('#badge-form').on('submit', function(e) {
                e.preventDefault();
                
                var $btn = $(this).find('button[type="submit"]');
                var originalText = $btn.html();
                $btn.html('<span class="spinner"></span> Ekleniyor...').prop('disabled', true);
                
                $.post(ruh_badge_ajax.ajax_url, {
                    action: 'ruh_badge_action',
                    badge_action: 'create',
                    nonce: ruh_badge_ajax.nonce,
                    badge_name: $('#badge-name').val(),
                    badge_icon: $('input[name="badge_icon"]:checked').val(),
                    badge_color: $('input[name="badge_color"]:checked').val()
                })
                .done(function(response) {
                    if (response.success) {
                        alert('Rozet basariyla eklendi!');
                        location.reload();
                    } else {
                        alert('Hata: ' + (response.data || 'Bilinmeyen hata'));
                    }
                })
                .fail(function(xhr, status, error) {
                    alert('Baglanti hatasi: ' + error);
                })
                .always(function() {
                    $btn.html(originalText).prop('disabled', false);
                });
            });
            
            // Rozet silme
            $(document).on('click', '.delete-badge', function() {
                if (!confirm('Bu rozeti silmek istediginizden emin misiniz?')) {
                    return;
                }
                
                var badgeId = $(this).data('badge-id');
                var $row = $('#badge-row-' + badgeId);
                
                $.post(ruh_badge_ajax.ajax_url, {
                    action: 'ruh_badge_action',
                    badge_action: 'delete',
                    nonce: ruh_badge_ajax.nonce,
                    badge_id: badgeId
                })
                .done(function(response) {
                    if (response.success) {
                        $row.fadeOut(function() {
                            $row.remove();
                        });
                    } else {
                        alert('Hata: ' + (response.data || 'Bilinmeyen hata'));
                    }
                })
                .fail(function() {
                    alert('Baglanti hatasi!');
                });
            });
            
            // Rozet atama
            $('#assign-badge-form').on('submit', function(e) {
                e.preventDefault();
                
                var userId = $('#assign-user-id').val();
                var badgeId = $('#assign-badge-id').val();
                
                if (!userId || !badgeId) {
                    alert('Lutfen kullanici ve rozet secin.');
                    return;
                }
                
                var $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).text('Ataniyor...');
                
                $.post(ruh_badge_ajax.ajax_url, {
                    action: 'ruh_badge_action',
                    badge_action: 'assign',
                    nonce: ruh_badge_ajax.nonce,
                    user_id: userId,
                    badge_id: badgeId
                })
                .done(function(response) {
                    if (response.success) {
                        alert('Rozet basariyla atandi!');
                        location.reload();
                    } else {
                        alert('Hata: ' + (response.data || 'Bilinmeyen hata'));
                    }
                })
                .fail(function() {
                    alert('Baglanti hatasi!');
                })
                .always(function() {
                    $btn.prop('disabled', false).html('<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/></svg> Rozet Ata');
                });
            });
            
            // Rozet kaldirma
            $('#remove-badge-form').on('submit', function(e) {
                e.preventDefault();
                
                var userId = $('#remove-user-id').val();
                var badgeId = $('#remove-badge-id').val();
                
                if (!userId || !badgeId) {
                    alert('Lutfen kullanici ve rozet secin.');
                    return;
                }
                
                if (!confirm('Bu rozeti kullanicidan kaldirmak istediginizden emin misiniz?')) {
                    return;
                }
                
                var $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).text('Kaldiriliyor...');
                
                $.post(ruh_badge_ajax.ajax_url, {
                    action: 'ruh_badge_action',
                    badge_action: 'remove',
                    nonce: ruh_badge_ajax.nonce,
                    user_id: userId,
                    badge_id: badgeId
                })
                .done(function(response) {
                    if (response.success) {
                        alert('Rozet kaldirildi!');
                        location.reload();
                    } else {
                        alert('Hata: ' + (response.data || 'Bilinmeyen hata'));
                    }
                })
                .fail(function() {
                    alert('Baglanti hatasi!');
                })
                .always(function() {
                    $btn.prop('disabled', false).html('<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/></svg> Rozet Kaldir');
                });
            });
        });
        </script>
        
        <style>
        .ruh-badge-wrap {
            max-width: 1200px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .ruh-badge-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 24px;
        }
        
        .ruh-badge-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .ruh-badge-logo svg {
            background: white;
            padding: 8px;
            border-radius: 12px;
        }
        
        .ruh-badge-logo h1 {
            margin: 0;
            font-size: 28px;
            color: white;
        }
        
        .ruh-badge-logo p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        
        .ruh-badge-grid {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 24px;
        }
        
        @media (max-width: 1024px) {
            .ruh-badge-grid {
                grid-template-columns: 1fr;
            }
        }
        
        .ruh-badge-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        .ruh-badge-card-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 20px 24px;
            background: #f8f9fc;
            border-bottom: 1px solid #eee;
        }
        
        .ruh-badge-card-header svg {
            color: #667eea;
        }
        
        .ruh-badge-card-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            flex: 1;
        }
        
        .ruh-badge-count {
            background: #667eea;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
        }
        
        .ruh-badge-card-body {
            padding: 24px;
        }
        
        .ruh-form-group {
            margin-bottom: 20px;
        }
        
        .ruh-form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        
        .ruh-form-group input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e8e8ec;
            border-radius: 10px;
            font-size: 15px;
            transition: border-color 0.2s;
        }
        
        .ruh-form-group input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .ruh-icon-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
        }
        
        .ruh-icon-option {
            cursor: pointer;
        }
        
        .ruh-icon-option input {
            display: none;
        }
        
        .ruh-icon-preview {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            background: #f5f5f7;
            border: 2px solid transparent;
            border-radius: 10px;
            transition: all 0.2s;
        }
        
        .ruh-icon-option input:checked + .ruh-icon-preview {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        
        .ruh-color-grid {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .ruh-color-option {
            cursor: pointer;
        }
        
        .ruh-color-option input {
            display: none;
        }
        
        .ruh-color-preview {
            display: block;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 3px solid transparent;
            transition: all 0.2s;
        }
        
        .ruh-color-option input:checked + .ruh-color-preview {
            border-color: #333;
            transform: scale(1.1);
        }
        
        .ruh-badge-preview {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 16px;
            background: #f8f9fc;
            border-radius: 10px;
        }
        
        .ruh-badge-preview svg {
            width: 32px;
            height: 32px;
        }
        
        .ruh-badge-preview span {
            font-weight: 600;
            font-size: 16px;
        }
        
        .ruh-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .ruh-btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .ruh-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        
        .ruh-btn-sm {
            padding: 8px 14px;
            font-size: 13px;
        }
        
        .ruh-btn-danger {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .ruh-btn-danger:hover {
            background: #dc2626;
            color: white;
        }
        
        .ruh-empty-state {
            text-align: center;
            padding: 40px;
            color: #888;
        }
        
        .ruh-badge-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        
        .ruh-badge-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: #f8f9fc;
            border-radius: 12px;
            transition: all 0.2s;
        }
        
        .ruh-badge-item:hover {
            background: #f0f0f5;
        }
        
        .ruh-badge-icon {
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .ruh-badge-icon svg {
            width: 28px;
            height: 28px;
        }
        
        .ruh-badge-info {
            flex: 1;
        }
        
        .ruh-badge-info strong {
            display: block;
            font-size: 15px;
            margin-bottom: 4px;
        }
        
        .ruh-badge-info span {
            font-size: 13px;
            color: #888;
        }
        
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        </style>
        <?php
    }
}

// Global instance
global $ruh_badge_manager;
$ruh_badge_manager = new Ruh_Badge_Manager();

// Admin sayfa render fonksiyonu
function render_badges_page_content() {
    global $ruh_badge_manager;
    if ($ruh_badge_manager) {
        $ruh_badge_manager->render_admin_page();
    } else {
        echo '<div class="wrap"><h1>Rozet Yonetimi</h1><p>Rozet sistemi yuklenemedi.</p></div>';
    }
}

// Rozet helper fonksiyonlari - function_exists kontrolu ile
if (!function_exists('ruh_get_user_badges')) {
    function ruh_get_user_badges($user_id) {
        global $wpdb;
        $table_badges = $wpdb->prefix . 'ruh_badges';
        $table_user_badges = $wpdb->prefix . 'ruh_user_badges';
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT b.* 
            FROM {$table_badges} b 
            JOIN {$table_user_badges} ub ON b.badge_id = ub.badge_id 
            WHERE ub.user_id = %d 
            ORDER BY b.badge_id DESC
        ", $user_id));
    }
}

if (!function_exists('ruh_assign_badge_to_user')) {
    function ruh_assign_badge_to_user($user_id, $badge_id) {
        global $wpdb;
        $table_user_badges = $wpdb->prefix . 'ruh_user_badges';
        
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_user_badges} WHERE user_id = %d AND badge_id = %d",
            $user_id, $badge_id
        ));
        
        if ($exists) return false;
        
        return $wpdb->insert($table_user_badges, array(
            'user_id' => $user_id,
            'badge_id' => $badge_id,
            'assigned_at' => current_time('mysql')
        ), array('%d', '%d', '%s'));
    }
}

if (!function_exists('ruh_remove_badge_from_user')) {
    function ruh_remove_badge_from_user($user_id, $badge_id) {
        global $wpdb;
        $table_user_badges = $wpdb->prefix . 'ruh_user_badges';
        
        return $wpdb->delete($table_user_badges, array(
            'user_id' => $user_id,
            'badge_id' => $badge_id
        ), array('%d', '%d'));
    }
}
