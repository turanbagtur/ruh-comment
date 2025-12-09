<?php
/**
 * Advanced Features - Mention, Markdown, Search, Syntax Highlighting
 * 
 * @package RuhComment
 * @version 5.1
 */

if (!defined('ABSPATH')) exit;

/**
 * Mention sistemi - @kullaniciadi
 */
class Ruh_Mention_System {
    
    public function __construct() {
        add_filter('comment_text', array($this, 'convert_mentions'), 20);
        add_action('wp_ajax_ruh_search_users', array($this, 'ajax_search_users'));
        add_action('wp_ajax_nopriv_ruh_search_users', array($this, 'ajax_search_users'));
    }
    
    /**
     * @mention'ları linklere çevir
     */
    public function convert_mentions($text) {
        return preg_replace_callback(
            '/@([a-zA-Z0-9_]+)/',
            function($matches) {
                $username = $matches[1];
                $user = get_user_by('login', $username);
                
                if ($user) {
                    $profile_url = ruh_get_user_profile_url($user->ID);
                    return '<a href="' . esc_url($profile_url) . '" class="ruh-mention" data-user-id="' . $user->ID . '">@' . esc_html($username) . '</a>';
                }
                
                return $matches[0];
            },
            $text
        );
    }
    
    /**
     * Kullanıcı arama AJAX
     */
    public function ajax_search_users() {
        check_ajax_referer('ruh-comment-nonce', 'nonce');
        
        $search = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
        
        if (strlen($search) < 2) {
            wp_send_json_success(array('users' => array()));
        }
        
        $users = get_users(array(
            'search' => '*' . $search . '*',
            'search_columns' => array('user_login', 'display_name'),
            'number' => 10,
            'orderby' => 'display_name'
        ));
        
        $results = array();
        foreach ($users as $user) {
            $level_info = ruh_get_user_level_info($user->ID);
            $results[] = array(
                'id' => $user->ID,
                'username' => $user->user_login,
                'display_name' => $user->display_name,
                'avatar' => get_avatar_url($user->ID, array('size' => 32)),
                'level' => $level_info->level
            );
        }
        
        wp_send_json_success(array('users' => $results));
    }
}

/**
 * Markdown desteği
 */
class Ruh_Markdown_Support {
    
    public function __construct() {
        add_filter('comment_text', array($this, 'parse_markdown'), 15);
    }
    
    /**
     * Basit markdown parsing
     */
    public function parse_markdown($text) {
        // **bold**
        $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);
        
        // *italic*
        $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);
        
        // `code`
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
        
        // ```code block```
        $text = preg_replace_callback(
            '/```([\s\S]*?)```/',
            function($matches) {
                $code = htmlspecialchars(trim($matches[1]));
                return '<pre><code>' . $code . '</code></pre>';
            },
            $text
        );
        
        // > quote
        $text = preg_replace('/^&gt;\s*(.*)$/m', '<blockquote>$1</blockquote>', $text);
        
        return $text;
    }
}

/**
 * Yorum arama sistemi
 */
class Ruh_Comment_Search {
    
    public function __construct() {
        add_action('wp_ajax_ruh_search_comments', array($this, 'ajax_search_comments'));
        add_action('wp_ajax_nopriv_ruh_search_comments', array($this, 'ajax_search_comments'));
    }
    
    /**
     * Yorum arama AJAX
     */
    public function ajax_search_comments() {
        check_ajax_referer('ruh-comment-nonce', 'nonce');
        
        $search = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
        $post_id = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        
        if (strlen($search) < 3) {
            wp_send_json_error(array('message' => 'En az 3 karakter girin.'));
        }
        
        $args = array(
            'search' => $search,
            'status' => 'approve',
            'number' => 20,
            'orderby' => 'comment_date_gmt',
            'order' => 'DESC'
        );
        
        if ($post_id) {
            $args['post_id'] = $post_id;
        }
        
        $comments = get_comments($args);
        
        $results = array();
        foreach ($comments as $comment) {
            $results[] = array(
                'id' => $comment->comment_ID,
                'post_id' => $comment->comment_post_ID,
                'post_title' => get_the_title($comment->comment_post_ID),
                'author' => $comment->comment_author,
                'author_id' => $comment->user_id,
                'content' => wp_trim_words(strip_tags($comment->comment_content), 20),
                'date' => human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' önce',
                'link' => get_comment_link($comment->comment_ID)
            );
        }
        
        wp_send_json_success(array('comments' => $results, 'total' => count($results)));
    }
}

/**
 * Syntax highlighting desteği (Prism.js ile)
 */
class Ruh_Syntax_Highlighting {
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('comment_text', array($this, 'add_language_class'), 25);
    }
    
    /**
     * Prism.js yükle
     */
    public function enqueue_scripts() {
        if (is_singular() && comments_open()) {
            wp_enqueue_style('prism-css', 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css', array(), '1.29.0');
            wp_enqueue_script('prism-js', 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js', array(), '1.29.0', true);
            wp_enqueue_script('prism-autoloader', 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js', array('prism-js'), '1.29.0', true);
        }
    }
    
    /**
     * Code block'lara dil class'ı ekle
     */
    public function add_language_class($text) {
        // ```javascript gibi dil belirtilmişse
        $text = preg_replace_callback(
            '/```([a-z]+)\n([\s\S]*?)```/',
            function($matches) {
                $lang = strtolower($matches[1]);
                $code = htmlspecialchars(trim($matches[2]));
                return '<pre><code class="language-' . $lang . '">' . $code . '</code></pre>';
            },
            $text
        );
        
        return $text;
    }
}

/**
 * Gelişmiş spam koruması
 */
class Ruh_Advanced_Spam_Protection {
    
    private $honeypot_fields = array('website_url', 'company_name', 'phone_number');
    
    public function __construct() {
        add_filter('preprocess_comment', array($this, 'check_advanced_spam'), 5);
        add_action('comment_form', array($this, 'add_honeypot_fields'));
    }
    
    /**
     * Gelişmiş spam kontrolü
     */
    public function check_advanced_spam($commentdata) {
        // Honeypot kontrolü - çoklu alan
        foreach ($this->honeypot_fields as $field) {
            if (isset($_POST[$field]) && !empty($_POST[$field])) {
                wp_die('Spam detected. [HP-' . $field . ']');
            }
        }
        
        // Yorum gönderim hızı kontrolü (JavaScript timestamp)
        if (isset($_POST['ruh_form_load_time'])) {
            $form_load_time = intval($_POST['ruh_form_load_time']);
            $current_time = time() * 1000; // milliseconds
            $time_diff = ($current_time - $form_load_time) / 1000; // seconds
            
            // 3 saniyeden hızlı gönderim = bot
            if ($time_diff < 3) {
                wp_die('Spam detected. [SPEED]');
            }
        }
        
        // Mouse/keyboard aktivitesi kontrolü
        if (isset($_POST['ruh_has_interaction']) && $_POST['ruh_has_interaction'] !== '1') {
            wp_die('Spam detected. [NO-INTERACTION]');
        }
        
        return $commentdata;
    }
    
    /**
     * Görünmez honeypot alanları ekle
     */
    public function add_honeypot_fields() {
        foreach ($this->honeypot_fields as $field) {
            echo '<div style="position:absolute;left:-9999px;opacity:0;pointer-events:none;" aria-hidden="true">';
            echo '<input type="text" name="' . esc_attr($field) . '" value="" tabindex="-1" autocomplete="off">';
            echo '</div>';
        }
        
        echo '<input type="hidden" name="ruh_form_load_time" id="ruh_form_load_time" value="">';
        echo '<input type="hidden" name="ruh_has_interaction" id="ruh_has_interaction" value="0">';
        
        echo '<script>
        document.getElementById("ruh_form_load_time").value = Date.now();
        
        ["mousemove", "keydown", "touchstart"].forEach(function(event) {
            document.addEventListener(event, function() {
                document.getElementById("ruh_has_interaction").value = "1";
            }, {once: true});
        });
        </script>';
    }
}

// Sınıfları güvenli şekilde başlat
if (class_exists('Ruh_Mention_System')) {
    new Ruh_Mention_System();
}

if (class_exists('Ruh_Markdown_Support')) {
    new Ruh_Markdown_Support();
}

if (class_exists('Ruh_Comment_Search')) {
    new Ruh_Comment_Search();
}

if (class_exists('Ruh_Syntax_Highlighting')) {
    new Ruh_Syntax_Highlighting();
}

if (class_exists('Ruh_Advanced_Spam_Protection')) {
    new Ruh_Advanced_Spam_Protection();
}

