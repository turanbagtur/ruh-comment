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
        '#10b981' => 'Yeşil',
        '#ef4444' => 'Kırmızı',
        '#3b82f6' => 'Mavi',
        '#ec4899' => 'Pembe',
        '#8b5cf6' => 'Açık Mor',
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
        // Nonce kontrolü
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ruh_badge_nonce')) {
            wp_send_json_error('Güvenlik hatası!');
        }
        
        // Yetki kontrolü
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Yetkisiz erişim!');
        }
        
        $action = isset($_POST['badge_action']) ? sanitize_text_field($_POST['badge_action']) : '';
        
        switch ($action) {
            case 'create':
                $this->create_badge();
                break;
            case 'create_auto':
                $this->create_auto_badge();
                break;
            case 'create_custom':
                $this->create_custom_badge();
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
            case 'save_tag':
                $this->save_user_tag();
                break;
            case 'update':
                $this->update_badge();
                break;
            default:
                wp_send_json_error('Geçersiz işlem!');
        }
    }
    
    private function create_badge() {
        $name = isset($_POST['badge_name']) ? sanitize_text_field($_POST['badge_name']) : '';
        $icon = isset($_POST['badge_icon']) ? sanitize_key($_POST['badge_icon']) : 'star';
        $color = isset($_POST['badge_color']) ? sanitize_hex_color($_POST['badge_color']) : '#667eea';
        
        if (empty($name)) {
            wp_send_json_error('Rozet adı gerekli!');
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
            wp_send_json_error('Rozet kaydedilemedi! DB Hatası: ' . $wpdb->last_error);
        }
        
        wp_send_json_success(array(
            'message' => 'Rozet başarıyla eklendi!',
            'badge_id' => $wpdb->insert_id
        ));
    }
    
    private function create_auto_badge() {
        $name = isset($_POST['badge_name']) ? sanitize_text_field($_POST['badge_name']) : '';
        $icon = isset($_POST['badge_icon']) ? sanitize_key($_POST['badge_icon']) : 'star';
        $color = isset($_POST['badge_color']) ? sanitize_hex_color($_POST['badge_color']) : '#667eea';
        $condition_type = isset($_POST['condition_type']) ? sanitize_key($_POST['condition_type']) : 'level';
        $condition_value = isset($_POST['condition_value']) ? intval($_POST['condition_value']) : 1;
        
        if (empty($name)) {
            wp_send_json_error('Rozet adı gerekli!');
        }
        
        if ($condition_value < 1) {
            wp_send_json_error('Koşul değeri en az 1 olmalıdır!');
        }
        
        // SVG oluştur
        $svg = isset($this->badge_icons[$icon]) ? $this->badge_icons[$icon] : $this->badge_icons['star'];
        $svg = str_replace('{color}', $color, $svg);
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_badges,
            array(
                'badge_name' => $name,
                'badge_svg' => $svg,
                'is_automated' => 1,
                'auto_condition_type' => $condition_type,
                'auto_condition_value' => $condition_value
            ),
            array('%s', '%s', '%d', '%s', '%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Rozet kaydedilemedi! DB Hatası: ' . $wpdb->last_error);
        }
        
        wp_send_json_success(array(
            'message' => 'Otomatik rozet başarıyla eklendi!',
            'badge_id' => $wpdb->insert_id
        ));
    }
    
    private function create_custom_badge() {
        $name = isset($_POST['badge_name']) ? sanitize_text_field($_POST['badge_name']) : '';
        
        if (empty($name)) {
            wp_send_json_error('Rozet adı gerekli!');
        }
        
        // Dosya kontrolü
        if (!isset($_FILES['badge_image']) || $_FILES['badge_image']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error('Görsel yüklenmedi veya hata oluştu!');
        }
        
        $file = $_FILES['badge_image'];
        
        // Dosya boyutu kontrolü (512KB max)
        if ($file['size'] > 512 * 1024) {
            wp_send_json_error('Dosya boyutu 512KB\'dan büyük olamaz!');
        }
        
        // Dosya tipi kontrolü
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
        $file_type = wp_check_filetype($file['name']);
        $mime_type = $file['type'];
        
        if (!in_array($mime_type, $allowed_types)) {
            wp_send_json_error('Sadece JPG, PNG ve GIF dosyaları kabul edilir!');
        }
        
        // WordPress upload işlemi
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_send_json_error('Yükleme hatası: ' . $upload['error']);
        }
        
        $image_url = $upload['url'];
        
        // IMG tag olarak kaydet
        $badge_html = '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($name) . '" style="width:24px;height:24px;object-fit:contain;">';
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_badges,
            array(
                'badge_name' => $name,
                'badge_svg' => $badge_html,
                'is_automated' => 0,
                'auto_condition_type' => 'custom_image',
                'auto_condition_value' => 0
            ),
            array('%s', '%s', '%d', '%s', '%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Rozet kaydedilemedi! DB Hatası: ' . $wpdb->last_error);
        }
        
        wp_send_json_success(array(
            'message' => 'Özel rozet başarıyla eklendi!',
            'badge_id' => $wpdb->insert_id
        ));
    }
    
    private function delete_badge() {
        $badge_id = isset($_POST['badge_id']) ? intval($_POST['badge_id']) : 0;
        
        if (!$badge_id) {
            wp_send_json_error('Geçersiz rozet ID!');
        }
        
        global $wpdb;
        
        // Önce kullanıcılardan rozeti kaldır
        $wpdb->delete($this->table_user_badges, array('badge_id' => $badge_id), array('%d'));
        
        // Sonra rozeti sil
        $result = $wpdb->delete($this->table_badges, array('badge_id' => $badge_id), array('%d'));
        
        if ($result === false) {
            wp_send_json_error('Rozet silinemedi!');
        }
        
        wp_send_json_success('Rozet başarıyla silindi!');
    }
    
    private function assign_badge() {
        $badge_id = isset($_POST['badge_id']) ? intval($_POST['badge_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$badge_id || !$user_id) {
            wp_send_json_error('Geçersiz parametreler!');
        }
        
        global $wpdb;
        
        // Zaten var mı kontrol et
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_user_badges} WHERE user_id = %d AND badge_id = %d",
            $user_id, $badge_id
        ));
        
        if ($exists) {
            wp_send_json_error('Bu rozet zaten atanmış!');
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
            wp_send_json_error('Rozet atanamadı!');
        }
        
        wp_send_json_success('Rozet başarıyla atandı!');
    }
    
    private function remove_badge() {
        $badge_id = isset($_POST['badge_id']) ? intval($_POST['badge_id']) : 0;
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if (!$badge_id || !$user_id) {
            wp_send_json_error('Geçersiz parametreler!');
        }
        
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->table_user_badges,
            array('user_id' => $user_id, 'badge_id' => $badge_id),
            array('%d', '%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Rozet kaldırılamadı!');
        }
        
        wp_send_json_success('Rozet kaldırıldı!');
    }
    
    private function save_user_tag() {
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $tag_text = isset($_POST['tag_text']) ? sanitize_text_field($_POST['tag_text']) : '';
        $tag_color = isset($_POST['tag_color']) ? sanitize_hex_color($_POST['tag_color']) : '#6366f1';
        
        if (!$user_id) {
            wp_send_json_error('Geçersiz kullanıcı!');
        }
        
        if (empty($tag_text)) {
            // Tag'ı kaldır
            delete_user_meta($user_id, 'ruh_user_tag');
            delete_user_meta($user_id, 'ruh_user_tag_color');
            wp_send_json_success('Tag kaldırıldı!');
        } else {
            // Tag'ı kaydet
            update_user_meta($user_id, 'ruh_user_tag', $tag_text);
            update_user_meta($user_id, 'ruh_user_tag_color', $tag_color);
            wp_send_json_success('Tag başarıyla kaydedildi!');
        }
    }
    
    private function update_badge() {
        $badge_id = isset($_POST['badge_id']) ? intval($_POST['badge_id']) : 0;
        $name = isset($_POST['badge_name']) ? sanitize_text_field($_POST['badge_name']) : '';
        
        if (!$badge_id) {
            wp_send_json_error('Geçersiz rozet ID!');
        }
        
        if (empty($name)) {
            wp_send_json_error('Rozet adı gerekli!');
        }
        
        global $wpdb;
        
        $result = $wpdb->update(
            $this->table_badges,
            array('badge_name' => $name),
            array('badge_id' => $badge_id),
            array('%s'),
            array('%d')
        );
        
        if ($result === false) {
            wp_send_json_error('Rozet güncellenemedi!');
        }
        
        wp_send_json_success('Rozet güncellendi!');
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
                        <h1>Rozet Yönetimi</h1>
                        <p>Kullanıcı rozetlerini oluşturun ve yönetin.</p>
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
                                <label>Rozet Adı</label>
                                <input type="text" id="badge-name" name="badge_name" required placeholder="Örnek: VIP Üye">
                            </div>
                            
                            <div class="ruh-form-group">
                                <label>İkon Seç</label>
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
                                <label>Renk Seç</label>
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
                                <label>Önizleme</label>
                                <div id="badge-preview" class="ruh-badge-preview">
                                    <?php echo str_replace('{color}', '#667eea', $this->badge_icons['star']); ?>
                                    <span>Rozet Adı</span>
                                </div>
                            </div>
                            
                            <button type="submit" class="ruh-btn ruh-btn-primary">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/></svg>
                                Manuel Rozet Ekle
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Özel Görsel Rozet Formu -->
                <div class="ruh-badge-card">
                    <div class="ruh-badge-card-header">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M19,19H5V5H19M19,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3M13.96,12.29L11.21,15.83L9.25,13.47L6.5,17H17.5L13.96,12.29Z"/></svg>
                        <h2>Özel Görsel Rozet</h2>
                    </div>
                    <div class="ruh-badge-card-body">
                        <p style="color:#888;font-size:12px;margin-bottom:15px;">JPG, PNG veya GIF formatında özel rozet görseli yükleyin.</p>
                        <form id="custom-badge-form" enctype="multipart/form-data">
                            <div class="ruh-form-group">
                                <label>Rozet Adı</label>
                                <input type="text" id="custom-badge-name" name="badge_name" required placeholder="Örnek: Özel VIP">
                            </div>
                            
                            <div class="ruh-form-group">
                                <label>Rozet Görseli</label>
                                <div class="ruh-upload-area" id="badge-upload-area">
                                    <input type="file" id="badge-image" name="badge_image" accept="image/jpeg,image/png,image/gif" style="display:none;">
                                    <div class="ruh-upload-placeholder" id="upload-placeholder">
                                        <svg viewBox="0 0 24 24" width="48" height="48"><path fill="#ccc" d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/></svg>
                                        <p>Görsel yüklemek için tıklayın</p>
                                        <small>Max: 512KB, 128x128px önerilir</small>
                                    </div>
                                    <img id="badge-image-preview" src="" alt="Önizleme" style="display:none;max-width:64px;max-height:64px;border-radius:8px;">
                                </div>
                            </div>
                            
                            <button type="submit" class="ruh-btn ruh-btn-primary" style="background:#10b981;">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/></svg>
                                Özel Rozet Ekle
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Otomatik Rozet Formu -->
                <div class="ruh-badge-card">
                    <div class="ruh-badge-card-header">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,12.5A1.5,1.5 0 0,1 10.5,11A1.5,1.5 0 0,1 12,9.5A1.5,1.5 0 0,1 13.5,11A1.5,1.5 0 0,1 12,12.5M12,7.2C9.9,7.2 8.2,8.9 8.2,11C8.2,14 12,17.5 12,17.5C12,17.5 15.8,14 15.8,11C15.8,8.9 14.1,7.2 12,7.2Z"/></svg>
                        <h2>Otomatik Rozet Ekle</h2>
                    </div>
                    <div class="ruh-badge-card-body">
                        <p style="color:#888;font-size:12px;margin-bottom:15px;">Belirli koşulları karşılayan kullanıcılara otomatik verilir.</p>
                        <form id="auto-badge-form">
                            <div class="ruh-form-group">
                                <label>Rozet Adı</label>
                                <input type="text" id="auto-badge-name" name="badge_name" required placeholder="Örnek: Seviye 10 Ustası">
                            </div>
                            
                            <div class="ruh-form-group">
                                <label>İkon Seç</label>
                                <div class="ruh-icon-grid">
                                    <?php foreach ($this->badge_icons as $key => $svg): ?>
                                        <label class="ruh-icon-option">
                                            <input type="radio" name="auto_badge_icon" value="<?php echo esc_attr($key); ?>" <?php checked($key, 'trophy'); ?>>
                                            <span class="ruh-icon-preview"><?php echo str_replace('{color}', '#f59e0b', $svg); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="ruh-form-group">
                                <label>Renk Seç</label>
                                <div class="ruh-color-grid">
                                    <?php foreach ($this->badge_colors as $hex => $name): ?>
                                        <label class="ruh-color-option" title="<?php echo esc_attr($name); ?>">
                                            <input type="radio" name="auto_badge_color" value="<?php echo esc_attr($hex); ?>" <?php checked($hex, '#f59e0b'); ?>>
                                            <span class="ruh-color-preview" style="background: <?php echo esc_attr($hex); ?>;"></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="ruh-form-group">
                                <label>Koşul Türü</label>
                                <select id="auto-condition-type" name="condition_type" required>
                                    <option value="level">Seviye</option>
                                    <option value="comment_count">Yorum Sayısı</option>
                                    <option value="like_count">Toplam Beğeni</option>
                                </select>
                            </div>
                            
                            <div class="ruh-form-group">
                                <label>Koşul Değeri (≥)</label>
                                <input type="number" id="auto-condition-value" name="condition_value" min="1" value="10" required>
                                <small style="color:#888;">Örn: "10" girilirse, seviye 10 ve üzeri kullanıcılara verilir.</small>
                            </div>
                            
                            <button type="submit" class="ruh-btn ruh-btn-primary" style="background:#f59e0b;">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/></svg>
                                Otomatik Rozet Ekle
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
                                <p>Henüz rozet eklenmemiş.</p>
                            </div>
                        <?php else: ?>
                            <div class="ruh-badge-list">
                                <?php foreach ($badges as $badge): 
                                    $is_auto = !empty($badge->is_automated);
                                    $condition_label = '';
                                    if ($is_auto && !empty($badge->auto_condition_type)) {
                                        $types = array('level' => 'Seviye', 'comment_count' => 'Yorum Sayısı', 'like_count' => 'Beğeni Sayısı');
                                        $condition_label = isset($types[$badge->auto_condition_type]) ? $types[$badge->auto_condition_type] : $badge->auto_condition_type;
                                        $condition_label .= ' ≥ ' . intval($badge->auto_condition_value);
                                    }
                                ?>
                                    <div class="ruh-badge-item" id="badge-row-<?php echo $badge->badge_id; ?>">
                                        <div class="ruh-badge-icon">
                                            <?php echo wp_kses($badge->badge_svg, array(
                                                'svg' => array('viewBox' => true, 'width' => true, 'height' => true),
                                                'path' => array('fill' => true, 'd' => true),
                                                'img' => array('src' => true, 'alt' => true, 'style' => true, 'width' => true, 'height' => true, 'class' => true)
                                            )); ?>
                                        </div>
                                        <div class="ruh-badge-info">
                                            <strong><?php echo esc_html($badge->badge_name); ?></strong>
                                            <span><?php echo intval($badge->user_count); ?> kullanıcı</span>
                                            <?php if ($is_auto): ?>
                                                <span class="ruh-badge-type auto" title="<?php echo esc_attr($condition_label); ?>">Otomatik</span>
                                            <?php else: ?>
                                                <span class="ruh-badge-type manual">Manuel</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ruh-badge-actions">
                                            <button class="ruh-btn ruh-btn-sm edit-badge" data-badge-id="<?php echo $badge->badge_id; ?>" data-badge-name="<?php echo esc_attr($badge->badge_name); ?>">
                                                <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z"/></svg>
                                                Düzenle
                                            </button>
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
                                <label>Kullanıcı Seç</label>
                                <select id="assign-user-id" name="user_id" required>
                                    <option value="">-- Kullanıcı Seçin --</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user->ID; ?>"><?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_login); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="ruh-form-group">
                                <label>Rozet Seç</label>
                                <select id="assign-badge-id" name="badge_id" required>
                                    <option value="">-- Rozet Seçin --</option>
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
                        
                        <h4 style="margin-bottom: 10px;">Rozet Kaldır</h4>
                        <form id="remove-badge-form">
                            <div class="ruh-form-group">
                                <label>Kullanıcı Seç</label>
                                <select id="remove-user-id" name="user_id" required>
                                    <option value="">-- Kullanıcı Seçin --</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user->ID; ?>"><?php echo esc_html($user->display_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="ruh-form-group">
                                <label>Rozet Seç</label>
                                <select id="remove-badge-id" name="badge_id" required>
                                    <option value="">-- Rozet Seçin --</option>
                                    <?php foreach ($badges as $badge): ?>
                                        <option value="<?php echo $badge->badge_id; ?>"><?php echo esc_html($badge->badge_name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="ruh-btn ruh-btn-danger">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/></svg>
                                Rozet Kaldır
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Kullanıcı Tag Atama -->
                <div class="ruh-badge-card">
                    <div class="ruh-badge-card-header">
                        <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M5.5,7A1.5,1.5 0 0,1 4,5.5A1.5,1.5 0 0,1 5.5,4A1.5,1.5 0 0,1 7,5.5A1.5,1.5 0 0,1 5.5,7M21.41,11.58L12.41,2.58C12.05,2.22 11.55,2 11,2H4C2.89,2 2,2.89 2,4V11C2,11.55 2.22,12.05 2.59,12.41L11.58,21.41C11.95,21.77 12.45,22 13,22C13.55,22 14.05,21.77 14.41,21.41L21.41,14.41C21.78,14.05 22,13.55 22,13C22,12.44 21.77,11.94 21.41,11.58Z"/></svg>
                        <h2>Kullanıcı Tag</h2>
                    </div>
                    <div class="ruh-badge-card-body">
                        <p style="color:#888;font-size:12px;margin-bottom:15px;">Editör, Çevirmen gibi özel etiketler atayın.</p>
                        <form id="user-tag-form">
                            <div class="ruh-form-group">
                                <label>Kullanıcı Seç</label>
                                <select id="tag-user-id" name="user_id" required>
                                    <option value="">-- Kullanıcı Seçin --</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?php echo $user->ID; ?>"><?php echo esc_html($user->display_name); ?> (<?php echo esc_html($user->user_login); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="ruh-form-group">
                                <label>Tag Metni</label>
                                <input type="text" id="tag-text" name="tag_text" placeholder="Örn: Editör, Çevirmen, Admin" maxlength="20">
                                <small style="color:#888;">Boş bırakırsanız tag kaldırılır.</small>
                            </div>
                            
                            <div class="ruh-form-group">
                                <label>Tag Rengi</label>
                                <div class="ruh-color-grid">
                                    <?php 
                                    $tag_colors = array(
                                        '#6366f1' => 'İndigo',
                                        '#8b5cf6' => 'Mor',
                                        '#ec4899' => 'Pembe',
                                        '#ef4444' => 'Kırmızı',
                                        '#f59e0b' => 'Turuncu',
                                        '#10b981' => 'Yeşil',
                                        '#06b6d4' => 'Cyan',
                                        '#3b82f6' => 'Mavi',
                                    );
                                    foreach ($tag_colors as $hex => $name): ?>
                                        <label class="ruh-color-option" title="<?php echo esc_attr($name); ?>">
                                            <input type="radio" name="tag_color" value="<?php echo esc_attr($hex); ?>" <?php checked($hex, '#6366f1'); ?>>
                                            <span class="ruh-color-preview" style="background: <?php echo esc_attr($hex); ?>;"></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <button type="submit" class="ruh-btn ruh-btn-primary" style="background:#6366f1;">
                                <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19,13H13V19H11V13H5V11H11V5H13V11H19V13Z"/></svg>
                                Tag Kaydet
                            </button>
                        </form>
                        
                        <!-- Tag verilen kullanıcılar listesi -->
                        <?php
                        $tagged_users = get_users(array(
                            'meta_key' => 'ruh_user_tag',
                            'meta_compare' => 'EXISTS'
                        ));
                        if (!empty($tagged_users)): ?>
                        <div style="margin-top:20px;border-top:1px solid #eee;padding-top:15px;">
                            <h4 style="margin:0 0 10px;font-size:14px;color:#333;">Tag Verilen Kullanıcılar</h4>
                            <div style="max-height:200px;overflow-y:auto;">
                                <?php foreach ($tagged_users as $tuser): 
                                    $tag = get_user_meta($tuser->ID, 'ruh_user_tag', true);
                                    $color = get_user_meta($tuser->ID, 'ruh_user_tag_color', true) ?: '#6366f1';
                                ?>
                                <div style="display:flex;align-items:center;justify-content:space-between;padding:8px;background:#f8f9fc;border-radius:6px;margin-bottom:6px;">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <span style="font-weight:500;"><?php echo esc_html($tuser->display_name); ?></span>
                                        <span style="background:<?php echo esc_attr($color); ?>;color:#fff;padding:2px 8px;border-radius:4px;font-size:11px;"><?php echo esc_html($tag); ?></span>
                                    </div>
                                    <button type="button" class="remove-tag-btn" data-user-id="<?php echo $tuser->ID; ?>" style="background:none;border:none;color:#ef4444;cursor:pointer;padding:4px;" title="Tag Kaldır">
                                        <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/></svg>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Özel Popup Modal -->
        <div id="ruh-popup-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:99999;align-items:center;justify-content:center;">
            <div style="background:#fff;padding:30px;border-radius:12px;max-width:400px;text-align:center;box-shadow:0 10px 40px rgba(0,0,0,0.2);">
                <div id="ruh-popup-icon" style="margin-bottom:15px;"></div>
                <p id="ruh-popup-message" style="font-size:16px;color:#333;margin:0 0 20px;"></p>
                <button id="ruh-popup-ok" style="background:#667eea;color:#fff;border:none;padding:10px 30px;border-radius:8px;font-size:14px;cursor:pointer;">Tamam</button>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Özel popup fonksiyonu - alert yerine
            window.ruhPopup = function(message, type) {
                type = type || 'info';
                var icons = {
                    'success': '<svg viewBox="0 0 24 24" width="48" height="48"><path fill="#10b981" d="M12,2C17.52,2 22,6.48 22,12C22,17.52 17.52,22 12,22C6.48,22 2,17.52 2,12C2,6.48 6.48,2 12,2M11,16.5L18,9.5L16.59,8.09L11,13.67L7.91,10.59L6.5,12L11,16.5Z"/></svg>',
                    'error': '<svg viewBox="0 0 24 24" width="48" height="48"><path fill="#ef4444" d="M12,2C17.53,2 22,6.47 22,12C22,17.53 17.53,22 12,22C6.47,22 2,17.53 2,12C2,6.47 6.47,2 12,2M15.59,7L12,10.59L8.41,7L7,8.41L10.59,12L7,15.59L8.41,17L12,13.41L15.59,17L17,15.59L13.41,12L17,8.41L15.59,7Z"/></svg>',
                    'info': '<svg viewBox="0 0 24 24" width="48" height="48"><path fill="#3b82f6" d="M13,9H11V7H13M13,17H11V11H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/></svg>'
                };
                $('#ruh-popup-icon').html(icons[type] || icons['info']);
                $('#ruh-popup-message').text(message);
                $('#ruh-popup-modal').css('display', 'flex');
            };
            
            $('#ruh-popup-ok, #ruh-popup-modal').on('click', function(e) {
                if (e.target === this || $(e.target).is('#ruh-popup-ok')) {
                    $('#ruh-popup-modal').hide();
                }
            });
            
            // İkon ve renk seçimi - önizleme güncelle
            function updatePreview() {
                var icon = $('input[name="badge_icon"]:checked').val();
                var color = $('input[name="badge_color"]:checked').val();
                var name = $('#badge-name').val() || 'Rozet Adı';
                
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
                        ruhPopup('Rozet başarıyla eklendi!', 'success');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        ruhPopup('Hata: ' + (response.data || 'Bilinmeyen hata'), 'error');
                    }
                })
                .fail(function(xhr, status, error) {
                    ruhPopup('Bağlantı hatası: ' + error, 'error');
                })
                .always(function() {
                    $btn.html(originalText).prop('disabled', false);
                });
            });
            
            // Özel görsel rozet - Dosya seçimi ve sürükle bırak
            var $uploadArea = $('#badge-upload-area');
            var $fileInput = $('#badge-image');
            
            // Tıklama ile dosya seçimi
            $uploadArea.on('click', function(e) {
                if (e.target.tagName !== 'INPUT') {
                    $fileInput.trigger('click');
                }
            });
            
            // Sürükle bırak
            $uploadArea.on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('drag-over');
            });
            
            $uploadArea.on('dragleave drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
            });
            
            $uploadArea.on('drop', function(e) {
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    $fileInput[0].files = files;
                    $fileInput.trigger('change');
                }
            });
            
            // Dosya değişikliği
            $fileInput.on('change', function() {
                var file = this.files[0];
                if (file) {
                    if (file.size > 512 * 1024) {
                        ruhPopup('Dosya boyutu 512KB\'dan büyük olamaz!', 'error');
                        this.value = '';
                        return;
                    }
                    if (!file.type.match(/^image\/(jpeg|png|gif)$/)) {
                        ruhPopup('Sadece JPG, PNG veya GIF formatları desteklenir!', 'error');
                        this.value = '';
                        return;
                    }
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        $('#badge-image-preview').attr('src', e.target.result).show();
                        $('#upload-placeholder').hide();
                    };
                    reader.readAsDataURL(file);
                }
            });
            
            // Özel görsel rozet ekleme
            $('#custom-badge-form').on('submit', function(e) {
                e.preventDefault();
                
                var $btn = $(this).find('button[type="submit"]');
                var originalText = $btn.html();
                var formData = new FormData();
                
                formData.append('action', 'ruh_badge_action');
                formData.append('badge_action', 'create_custom');
                formData.append('nonce', ruh_badge_ajax.nonce);
                formData.append('badge_name', $('#custom-badge-name').val());
                
                var fileInput = $('#badge-image')[0];
                if (fileInput.files.length === 0) {
                    ruhPopup('Lütfen bir görsel seçin!', 'error');
                    return;
                }
                formData.append('badge_image', fileInput.files[0]);
                
                $btn.html('<span class="spinner"></span> Yükleniyor...').prop('disabled', true);
                
                $.ajax({
                    url: ruh_badge_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false
                })
                .done(function(response) {
                    if (response.success) {
                        ruhPopup('Özel rozet başarıyla eklendi!', 'success');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        ruhPopup('Hata: ' + (response.data || 'Bilinmeyen hata'), 'error');
                    }
                })
                .fail(function(xhr, status, error) {
                    ruhPopup('Bağlantı hatası: ' + error, 'error');
                })
                .always(function() {
                    $btn.html(originalText).prop('disabled', false);
                });
            });
            
            // Otomatik rozet ekleme
            $('#auto-badge-form').on('submit', function(e) {
                e.preventDefault();
                
                var $btn = $(this).find('button[type="submit"]');
                var originalText = $btn.html();
                $btn.html('<span class="spinner"></span> Ekleniyor...').prop('disabled', true);
                
                $.post(ruh_badge_ajax.ajax_url, {
                    action: 'ruh_badge_action',
                    badge_action: 'create_auto',
                    nonce: ruh_badge_ajax.nonce,
                    badge_name: $('#auto-badge-name').val(),
                    badge_icon: $('input[name="auto_badge_icon"]:checked').val(),
                    badge_color: $('input[name="auto_badge_color"]:checked').val(),
                    condition_type: $('#auto-condition-type').val(),
                    condition_value: $('#auto-condition-value').val()
                })
                .done(function(response) {
                    if (response.success) {
                        ruhPopup('Otomatik rozet başarıyla eklendi!', 'success');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        ruhPopup('Hata: ' + (response.data || 'Bilinmeyen hata'), 'error');
                    }
                })
                .fail(function(xhr, status, error) {
                    ruhPopup('Bağlantı hatası: ' + error, 'error');
                })
                .always(function() {
                    $btn.html(originalText).prop('disabled', false);
                });
            });
            
            // Rozet silme
            $(document).on('click', '.delete-badge', function() {
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
                        ruhPopup('Hata: ' + (response.data || 'Bilinmeyen hata'), 'error');
                    }
                })
                .fail(function() {
                    ruhPopup('Bağlantı hatası!', 'error');
                });
            });
            
            // Rozet düzenleme (inline)
            $(document).on('click', '.edit-badge', function() {
                var $btn = $(this);
                var badgeId = $btn.data('badge-id');
                var badgeName = $btn.data('badge-name');
                var $row = $('#badge-row-' + badgeId);
                var $nameEl = $row.find('.ruh-badge-info strong');
                
                if ($row.find('.badge-edit-input').length) return;
                
                var $input = $('<input type="text" class="badge-edit-input" style="padding:6px 10px;border:2px solid #667eea;border-radius:6px;font-size:14px;width:150px;">');
                $input.val(badgeName);
                $nameEl.hide().after($input);
                $input.focus().select();
                
                function saveName() {
                    var newName = $input.val().trim();
                    if (newName && newName !== badgeName) {
                        $input.prop('disabled', true);
                        $.post(ruh_badge_ajax.ajax_url, {
                            action: 'ruh_badge_action',
                            badge_action: 'update',
                            nonce: ruh_badge_ajax.nonce,
                            badge_id: badgeId,
                            badge_name: newName
                        })
                        .done(function(response) {
                            if (response.success) {
                                $nameEl.text(newName).show();
                                $btn.data('badge-name', newName);
                                $input.remove();
                            } else {
                                $input.prop('disabled', false);
                            }
                        })
                        .fail(function() {
                            $input.prop('disabled', false);
                        });
                    } else {
                        $nameEl.show();
                        $input.remove();
                    }
                }
                
                $input.on('keydown', function(e) {
                    if (e.key === 'Enter') { e.preventDefault(); saveName(); }
                    if (e.key === 'Escape') { $nameEl.show(); $input.remove(); }
                });
                $input.on('blur', saveName);
            });
            
            // Rozet atama
            $('#assign-badge-form').on('submit', function(e) {
                e.preventDefault();
                
                var userId = $('#assign-user-id').val();
                var badgeId = $('#assign-badge-id').val();
                
                if (!userId || !badgeId) {
                    ruhPopup('Lütfen kullanıcı ve rozet seçin.', 'error');
                    return;
                }
                
                var $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).text('Atanıyor...');
                
                $.post(ruh_badge_ajax.ajax_url, {
                    action: 'ruh_badge_action',
                    badge_action: 'assign',
                    nonce: ruh_badge_ajax.nonce,
                    user_id: userId,
                    badge_id: badgeId
                })
                .done(function(response) {
                    if (response.success) {
                        ruhPopup('Rozet başarıyla atandı!', 'success');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        ruhPopup('Hata: ' + (response.data || 'Bilinmeyen hata'), 'error');
                    }
                })
                .fail(function() {
                    ruhPopup('Bağlantı hatası!', 'error');
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
                    ruhPopup('Lütfen kullanıcı ve rozet seçin.', 'error');
                    return;
                }
                
                var $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).text('Kaldırılıyor...');
                
                $.post(ruh_badge_ajax.ajax_url, {
                    action: 'ruh_badge_action',
                    badge_action: 'remove',
                    nonce: ruh_badge_ajax.nonce,
                    user_id: userId,
                    badge_id: badgeId
                })
                .done(function(response) {
                    if (response.success) {
                        ruhPopup('Rozet kaldırıldı!', 'success');
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        ruhPopup('Hata: ' + (response.data || 'Bilinmeyen hata'), 'error');
                    }
                })
                .fail(function() {
                    ruhPopup('Bağlantı hatası!', 'error');
                })
                .always(function() {
                    $btn.prop('disabled', false).html('<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/></svg> Rozet Kaldır');
                });
            });
            
            // User Tag kaydetme
            $('#user-tag-form').on('submit', function(e) {
                e.preventDefault();
                
                var userId = $('#tag-user-id').val();
                
                if (!userId) {
                    ruhPopup('Lütfen bir kullanıcı seçin.', 'error');
                    return;
                }
                
                var $btn = $(this).find('button[type="submit"]');
                var originalText = $btn.html();
                $btn.html('<span class="spinner"></span> Kaydediliyor...').prop('disabled', true);
                
                $.post(ruh_badge_ajax.ajax_url, {
                    action: 'ruh_badge_action',
                    badge_action: 'save_tag',
                    nonce: ruh_badge_ajax.nonce,
                    user_id: userId,
                    tag_text: $('#tag-text').val(),
                    tag_color: $('input[name="tag_color"]:checked').val()
                })
                .done(function(response) {
                    if (response.success) {
                        ruhPopup(response.data || 'Tag kaydedildi!', 'success');
                        $('#tag-text').val('');
                    } else {
                        ruhPopup('Hata: ' + (response.data || 'Bilinmeyen hata'), 'error');
                    }
                })
                .fail(function() {
                    ruhPopup('Bağlantı hatası!', 'error');
                })
                .always(function() {
                    $btn.html(originalText).prop('disabled', false);
                });
            });
            
            // Tag kaldırma
            $(document).on('click', '.remove-tag-btn', function() {
                var $btn = $(this);
                var userId = $btn.data('user-id');
                
                $btn.prop('disabled', true);
                
                $.post(ruh_badge_ajax.ajax_url, {
                    action: 'ruh_badge_action',
                    badge_action: 'save_tag',
                    nonce: ruh_badge_ajax.nonce,
                    user_id: userId,
                    tag_text: '',
                    tag_color: ''
                })
                .done(function(response) {
                    if (response.success) {
                        $btn.closest('div[style*="display:flex"]').fadeOut(function() {
                            $(this).remove();
                        });
                        ruhPopup('Tag kaldırıldı!', 'success');
                    } else {
                        ruhPopup('Hata: ' + (response.data || 'Bilinmeyen hata'), 'error');
                    }
                })
                .fail(function() {
                    ruhPopup('Bağlantı hatası!', 'error');
                })
                .always(function() {
                    $btn.prop('disabled', false);
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
        
        .ruh-upload-area {
            border: 2px dashed #e8e8ec;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .ruh-upload-area:hover {
            border-color: #667eea;
            background: #f8f9fc;
        }
        
        .ruh-upload-placeholder p {
            margin: 10px 0 5px;
            color: #666;
        }
        
        .ruh-upload-placeholder small {
            color: #888;
            font-size: 12px;
        }
        
        .ruh-upload-area.drag-over {
            border-color: #667eea;
            background: #f0f4ff;
        }
        
        .ruh-upload-area.drag-over .ruh-upload-placeholder svg path {
            fill: #667eea;
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
        
        .ruh-badge-type {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }
        
        .ruh-badge-type.auto {
            background: #fef3c7;
            color: #d97706;
        }
        
        .ruh-badge-type.manual {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .ruh-form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e8e8ec;
            border-radius: 10px;
            font-size: 15px;
            background: white;
            cursor: pointer;
        }
        
        .ruh-form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .ruh-form-group input[type="number"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e8e8ec;
            border-radius: 10px;
            font-size: 15px;
        }
        
        .ruh-form-group input[type="number"]:focus {
            outline: none;
            border-color: #667eea;
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
        echo '<div class="wrap"><h1>Rozet Yönetimi</h1><p>Rozet sistemi yuklenemedi.</p></div>';
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
