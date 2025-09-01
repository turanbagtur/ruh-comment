<?php
if (!defined('ABSPATH')) exit;

// Sadece gerekli dosyaları include et
if (file_exists(RUH_COMMENT_PATH . 'includes/admin-comment-manager.php')) {
    require_once RUH_COMMENT_PATH . 'includes/admin-comment-manager.php';
}

if (file_exists(RUH_COMMENT_PATH . 'includes/admin-badge-manager.php')) {
    require_once RUH_COMMENT_PATH . 'includes/admin-badge-manager.php';
}

class Ruh_Comment_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Ruh Comment', 
            'Ruh Comment', 
            'manage_options', 
            'ruh-comment', 
            array($this, 'render_settings_page'), 
            'dashicons-comments', 
            25
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
        
        // WordPress'in varsayılan yorum menüsünü kaldır
        remove_menu_page('edit-comments.php');
    }
    
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'ruh-comment') === false) return;
        
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        wp_enqueue_script(
            'ruh-admin-js', 
            RUH_COMMENT_URL . 'assets/js/ruh-comment-admin.js', 
            array('jquery', 'wp-color-picker'), 
            RUH_COMMENT_VERSION, 
            true
        );
        
        wp_enqueue_style(
            'ruh-admin-css', 
            RUH_COMMENT_URL . 'assets/css/ruh-comment-admin.css', 
            array(), 
            RUH_COMMENT_VERSION
        );
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Ruh Comment Ayarları</h1>
            <form action="options.php" method="post">
                <?php 
                settings_fields('ruh_comment_options'); 
                do_settings_sections('ruh_comment_options'); 
                submit_button('Ayarları Kaydet'); 
                ?>
            </form>
        </div>
        <?php
    }

    public function settings_init() {
        register_setting('ruh_comment_options', 'ruh_comment_options', array($this, 'sanitize_settings'));
        
        add_settings_section(
            'ruh_general_section', 
            'Genel Ayarlar', 
            null, 
            'ruh_comment_options'
        );
        
        $this->add_field('checkbox', 'ruh_general_section', 'enable_reactions', 'Tepkileri Aktif Et');
        $this->add_field('checkbox', 'ruh_general_section', 'enable_likes', 'Beğenileri Aktif Et');
        $this->add_field('checkbox', 'ruh_general_section', 'enable_sorting', 'Sıralamayı Aktif Et');
        $this->add_field('checkbox', 'ruh_general_section', 'enable_reporting', 'Şikayet Etmeyi Aktif Et');
        $this->add_field('number', 'ruh_general_section', 'xp_per_comment', 'Yorum Başına XP', '', array('default' => 15));
    }

    public function sanitize_settings($input) {
        if (!is_array($input)) return array();
        
        $sanitized = array();
        
        foreach ($input as $key => $value) {
            switch ($key) {
                case 'enable_reactions':
                case 'enable_likes':
                case 'enable_sorting':
                case 'enable_reporting':
                    $sanitized[$key] = !empty($value) ? 1 : 0;
                    break;
                    
                case 'xp_per_comment':
                case 'profile_page_id':
                case 'login_page_id':
                case 'register_page_id':
                case 'spam_link_limit':
                case 'auto_moderate_reports':
                    $sanitized[$key] = absint($value);
                    break;
                    
                case 'profanity_filter_words':
                    $sanitized[$key] = sanitize_textarea_field($value);
                    break;
                    
                default:
                    $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }

    private function add_field($type, $section, $name, $title, $description = '', $args = array()) {
        add_settings_field(
            'ruh_field_' . $name, 
            $title, 
            array($this, 'render_field_callback'), 
            'ruh_comment_options', 
            $section, 
            array_merge($args, array(
                'type' => $type, 
                'name' => $name, 
                'description' => $description
            ))
        );
    }

    public function render_field_callback($args) {
        $options = get_option('ruh_comment_options', array());
        $name_attr = 'ruh_comment_options[' . $args['name'] . ']';
        $value = isset($options[$args['name']]) ? $options[$args['name']] : (isset($args['default']) ? $args['default'] : '');
        
        switch ($args['type']) {
            case 'checkbox':
                echo '<input type="checkbox" name="' . esc_attr($name_attr) . '" value="1" ' . checked(1, $value, false) . ' />';
                break;
                
            case 'number':
                echo '<input type="number" name="' . esc_attr($name_attr) . '" value="' . esc_attr($value) . '" class="small-text" min="0" />';
                break;
                
            case 'textarea':
                echo '<textarea name="' . esc_attr($name_attr) . '" rows="5" cols="50" class="large-text">' . esc_textarea($value) . '</textarea>';
                break;
        }
        
        if (!empty($args['description'])) {
            echo '<p class="description">' . esc_html($args['description']) . '</p>';
        }
    }
}

new Ruh_Comment_Admin();