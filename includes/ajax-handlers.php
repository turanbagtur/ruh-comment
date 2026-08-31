<?php
if (!defined('ABSPATH')) exit;

class Ruh_Comment_Ajax_Handlers {
    
    public function __construct() {
        $actions = array(
            'get_initial_data', 'handle_reaction', 'get_comments', 
            'handle_like', 'handle_dislike', 'flag_comment', 'submit_comment', 
            'edit_comment', 'delete_comment', 'load_replies', 'pin_comment',
            'load_more_profile_comments'
        );
        
        foreach ($actions as $action) {
            add_action('wp_ajax_ruh_' . $action, array($this, $action . '_callback'));
            
            // Public actions (giriş yapmadan erişilebilir)
            $public_actions = array('get_initial_data', 'get_comments', 'load_more_profile_comments', 'handle_reaction', 'load_replies');
            if (in_array($action, $public_actions)) {
                add_action('wp_ajax_nopriv_ruh_' . $action, array($this, $action . '_callback'));
            }
        }
    }

    /**
     * Dile göre mesaj döndür - tek seferlik options okumak için cache kullanır
     */
    private function is_en() {
        static $is_en = null;
        if ($is_en === null) {
            $opts = get_option('ruh_comment_options', array());
            $is_en = (($opts['language'] ?? 'tr_TR') === 'en_US');
        }
        return $is_en;
    }
    
    private function msg($tr, $en) {
        return $this->is_en() ? $en : $tr;
    }

    private function verify_nonce($action = 'ruh-comment-nonce') {
        if (!check_ajax_referer($action, 'nonce', false)) {
            wp_send_json_error(array('message' => $this->msg('Güvenlik kontrolü başarısız.', 'Security check failed.')));
        }
    }

    private function require_login() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => $this->msg('Bu işlem için giriş yapmalısınız.', 'You must be logged in to perform this action.')));
        }
    }
    
    /**
     * Rate limiting kontrolu - Performans ve güvenlik
     * Yorum limiti admin panelinden ayarlanabilir
     */
    private function check_rate_limit($action_type = 'comment') {
        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        
        // Admin ayarlarından yorum limitini al
        $options = get_option('ruh_comment_options', array());
        $comment_limit = isset($options['comment_rate_limit']) ? intval($options['comment_rate_limit']) : 3;
        $comment_window = isset($options['comment_rate_window']) ? intval($options['comment_rate_window']) : 60;
        
        $limits = array(
            'comment' => array('count' => $comment_limit, 'window' => $comment_window),
            'like' => array('count' => 30, 'window' => 60),
            'dislike' => array('count' => 30, 'window' => 60),
            'reaction' => array('count' => 20, 'window' => 60),
            'get_comments' => array('count' => 60, 'window' => 60),
            'flag_comment' => array('count' => 10, 'window' => 60),
            'edit_comment' => array('count' => 15, 'window' => 60),
            'delete_comment' => array('count' => 15, 'window' => 60),
            'load_replies' => array('count' => 60, 'window' => 60),
            'load_more_profile_comments' => array('count' => 30, 'window' => 60),
        );
        
        $limit = isset($limits[$action_type]) ? $limits[$action_type] : $limits['comment'];
        $cache_key = 'ruh_rate_' . $action_type . '_' . ($user_id ?: md5($ip));
        
        $current = get_transient($cache_key) ?: 0;
        
        if ($current >= $limit['count']) {
            $wait_time = $limit['window'];
            wp_send_json_error(array('message' => "Çok hızlı işlem yapıyorsunuz. {$wait_time} saniye bekleyin."));
        }
        
        set_transient($cache_key, $current + 1, $limit['window']);
    }
    
    /**
     * Client IP adresi
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

    // YORUM GONDERME SISTEMI - GUVENLIK IYILESTIRILMIS
    public function submit_comment_callback() {
        $this->verify_nonce();
        $this->require_login();
        $this->check_rate_limit('comment');
        
        // filters-and-actions.php'deki çift rate limit'i devre dışı bırak
        // Bu AJAX handler zaten kendi rate limitini uyguladı
        if (!defined('RUH_AJAX_RATE_CHECKED')) {
            define('RUH_AJAX_RATE_CHECKED', true);
        }
        
        // Veri alma ve sanitization
        $post_id = intval($_POST['post_id'] ?? $_POST['comment_post_ID'] ?? 0);
        $comment_content = trim(sanitize_textarea_field($_POST['comment'] ?? ''));
        $comment_parent = intval($_POST['comment_parent'] ?? 0);
        
        // Temel validasyon
        if (empty($comment_content)) {
            wp_send_json_error(array('message' => $this->msg('Yorum içeriği boş olamaz.', 'Comment content cannot be empty.')));
        }
        
        $options = get_option('ruh_comment_options', array());
        $max_length = isset($options['max_comment_length']) ? intval($options['max_comment_length']) : 1000;
        if (strlen($comment_content) > $max_length) {
            wp_send_json_error(array('message' => $this->msg(
                sprintf('Yorum çok uzun. Maksimum %d karakter.', $max_length),
                sprintf('Comment too long. Maximum %d characters.', $max_length)
            )));
        }
        
        if (strlen($comment_content) < 3) {
            wp_send_json_error(array('message' => $this->msg('Yorum en az 3 karakter olmalıdır.', 'Comment must be at least 3 characters.')));
        }
        
        // Post ID yoksa global post'u kullan
        if (!$post_id) {
            global $post;
            if (isset($post) && $post->ID) {
                $post_id = $post->ID;
            }
        }
        
        // Hala post ID yoksa, mevcut sayfadan cikar
        if (!$post_id) {
            $current_url = isset($_POST['current_url']) ? esc_url_raw($_POST['current_url']) : '';
            if ($current_url && function_exists('ruh_get_dynamic_post_id_from_url')) {
                // URL güvenlik kontrolu - sadece aynı domain
                $home_host = parse_url(home_url(), PHP_URL_HOST);
                $url_host = parse_url($current_url, PHP_URL_HOST);
                
                if ($url_host === $home_host) {
                    // Manga chapter/seri URL'leri için dinamık ID
                    $dynamic_id = ruh_get_dynamic_post_id_from_url($current_url);
                    if ($dynamic_id > 0) {
                        $post_id = $dynamic_id;
                    }
                }
            }
        }
        
        // Son care - URL hash'i kullan
        if (!$post_id) {
            $current_url = isset($_POST['current_url']) ? esc_url_raw($_POST['current_url']) : '';
            if ($current_url) {
                // URL'den benzersiz ID oluştür
                $post_id = abs(crc32($current_url)) % 2000000000 + 100;
            } else {
                $post_id = 999999; // Fallback
            }
        }
        
        // Parent comment varsa, gerçekten var mı kontrol et ve post_id'yi parent'tan al
        if ($comment_parent > 0) {
            $parent_comment = get_comment($comment_parent);
            if (!$parent_comment || $parent_comment->comment_approved != 1) {
                wp_send_json_error(array('message' => 'Yanıt verilen yorum bulunamadı.'));
            }
            // ÖNEMLİ: Yanıt için post_id'yi parent yorumdan al - tutarlılık için
            $post_id = $parent_comment->comment_post_ID;
        }
        
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        // Kullanıcı banlı mı kontrol et
        $ban_status = get_user_meta($user_id, 'ruh_ban_status', true);
        if ($ban_status === 'banned') {
            wp_send_json_error(array('message' => $this->msg('Yorum yapma yetkiniz bulunmuyor.', 'You are not authorized to comment.')));
        }
        
        // Timeout kontrolu
        $timeout_until = get_user_meta($user_id, 'ruh_timeout_until', true);
        if ($timeout_until && time() < intval($timeout_until)) {
            wp_send_json_error(array('message' => $this->msg('Geçici olarak yorum yapamazsınız.', 'You are temporarily restricted from commenting.')));
        }

        // Güvenlik/spam kontrolleri (honeypot, IP ban, link limiti, küfür filtresi,
        // tekrarlı yorum kontrolü). Bu kontroller daha önce sadece klasik WP yorum
        // formunda (preprocess_comment) çalışıyordu ve AJAX üzerinden tamamen
        // bypass ediliyordu - bkz. filters-and-actions.php ruh_run_comment_security_checks()
        if (function_exists('ruh_run_comment_security_checks')) {
            $security_check = ruh_run_comment_security_checks($comment_content, $user_id, $post_id, 'ajax');
            if (is_wp_error($security_check)) {
                wp_send_json_error(array('message' => $security_check->get_error_message()));
            }
        }
        
        // İzin verilen HTML tagleri
        $allowed_tags = array(
            'b' => array(),
            'i' => array(),
            'strong' => array(),
            'em' => array(),
            'a' => array('href' => array(), 'title' => array(), 'target' => array()),
            'br' => array(),
            'p' => array(),
        );
        
        // Yorum verisi - HTML tagleri koru
        $comment_data = array(
            'comment_post_ID' => $post_id,
            'comment_content' => wp_kses($comment_content, $allowed_tags),
            'comment_parent' => $comment_parent,
            'user_id' => $user_id,
            'comment_author' => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_author_url' => '',
            'comment_approved' => 1
        );
        
        // Yorumu ekle
        $comment_id = wp_insert_comment($comment_data);
        
        if (is_wp_error($comment_id) || !$comment_id) {
            wp_send_json_error(array('message' => $this->msg('Yorum kaydedilemedi. Lütfen tekrar deneyin.', 'Comment could not be saved. Please try again.')));
        }
        
        // Orijinal URL'yi metadata olarak kaydet (admin panelinde yoruma git için)
        if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
            $original_url = esc_url_raw($_SERVER['HTTP_REFERER']);
            update_comment_meta($comment_id, '_original_post_url', $original_url);
        }
        
        // XP ve seviye güncelle
        if (function_exists('ruh_update_user_xp_and_level')) {
            ruh_update_user_xp_and_level($user_id);
        }
        
        // Otomatik rozetleri arka planda kontrol et (cron job ile - AJAX yanıt süresini etkilemez)
        if (!wp_next_scheduled('ruh_check_badges_cron', array($user_id))) {
            wp_schedule_single_event(time() + 5, 'ruh_check_badges_cron', array($user_id));
        }
        
        // NOT: Mention bildirimleri burada manuel tetiklenmiyor.
        // ruh_process_mentions() zaten 'wp_insert_comment' hook'una bağlı
        // (bkz. template-helpers.php) ve wp_insert_comment() çağrısıyla otomatik
        // çalışır. Burada tekrar çağırmak kullanıcıya çift bildirim e-postası
        // gönderilmesine sebep oluyordu.
        
        // Yorumu al ve HTML oluştür
        $comment = get_comment($comment_id);
        $html = $this->generate_comment_html($comment);
        
        wp_send_json_success(array(
            'html' => $html,
            'comment_id' => $comment_id,
            'parent_id' => $comment->comment_parent,
            'message' => $this->msg('Yorum başarıyla gönderildi.', 'Comment submitted successfully.')
        ));
    }

    // YORUM HTML OLUSTURMA - YENI TASARIM
    private function generate_comment_html($comment, $reply_counts_cache = array()) {
        if (!$comment) return '';
        
        global $wpdb;
        $user = get_userdata($comment->user_id);
        
        // Avatar - user_id veya email ile al (custom avatar destekli)
        if ($comment->user_id) {
            $avatar = ruh_get_avatar($comment->user_id, 40);
        } else {
            $avatar = ruh_get_avatar($comment->comment_author_email, 40);
        }
        
        $time_ago = (function_exists('ruh_human_time_diff_tr') ? ruh_human_time_diff_tr(strtotime($comment->comment_date), current_time('timestamp')) : human_time_diff(strtotime($comment->comment_date), current_time('timestamp'))) . ' önce';
        $author_name = $user ? $user->display_name : ($comment->comment_author ?: 'Anonim');
        $current_user_id = get_current_user_id();
        
        // Beğeni bilgisi
        $likes = intval(get_comment_meta($comment->comment_ID, '_likes', true));
        $dislikes = intval(get_comment_meta($comment->comment_ID, '_dislikes', true));
        $user_vote = get_comment_meta($comment->comment_ID, '_user_vote_' . $current_user_id, true);
        $liked_class = ($user_vote === 'liked') ? 'liked' : '';
        $disliked_class = ($user_vote === 'disliked') ? 'disliked' : '';
        
        // Kullanıcı seviyesi - cache'li fonksiyon kullanılıyor (N+1 sorgu önlemi)
        $user_level = 1;
        if ($comment->user_id && function_exists('ruh_get_user_level_info')) {
            $level_data = ruh_get_user_level_info($comment->user_id);
            if ($level_data) {
                $user_level = $level_data->level;
            }
        }
        
        // Kullanıcı Tag (Editör, Çevirmen vb.)
        $user_tag_html = '';
        if (function_exists('ruh_get_user_tag') && $comment->user_id) {
            $user_tag_html = ruh_get_user_tag($comment->user_id);
        }
        
        // Rozetler
        $badges_html = '';
        if (function_exists('ruh_get_user_badges') && $comment->user_id) {
            $badges = ruh_get_user_badges($comment->user_id);
            if (!empty($badges)) {
                $badges_html = '<span class="comment-badges">';
                $fallback_svg = '<svg viewBox="0 0 24 24" width="14" height="14"><path fill="#667eea" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/></svg>';
                foreach (array_slice($badges, 0, 2) as $badge) {
                    $svg = !empty($badge->badge_svg) ? $badge->badge_svg : $fallback_svg;
                    $rarity = function_exists('ruh_get_badge_rarity') ? ruh_get_badge_rarity($badge) : 'common';
                    $badges_html .= '<span class="comment-badge-item" data-rarity="' . esc_attr($rarity) . '">';
                    $badges_html .= '<span class="comment-badge">' . $svg . '</span>';
                    $badges_html .= '<span class="comment-badge-name">' . esc_html($badge->badge_name) . '</span>';
                    $badges_html .= '</span>';
                }
                $badges_html .= '</span>';
            }
        }
        
        // Spoiler ve format işlemleri
        $content = $comment->comment_content;
        $content = $this->process_comment_formatting($content);
        
        // Sabitlenme durumu
        $is_pinned = get_comment_meta($comment->comment_ID, 'ruh_pinned', true);
        $pinned_class = $is_pinned ? ' pinned is-pinned' : '';
        
        $html = '<li class="comment comment-item' . $pinned_class . '" id="comment-' . esc_attr($comment->comment_ID) . '" data-comment-id="' . esc_attr($comment->comment_ID) . '">';
        $html .= '<div class="comment-body">';
        
        // Avatar ve kullanıcı adı için profil linki
        $profile_url = '';
        if ($comment->user_id && function_exists('ruh_get_user_profile_url')) {
            $profile_url = ruh_get_user_profile_url($comment->user_id);
        }
        
        if ($profile_url) {
            $html .= '<a href="' . esc_url($profile_url) . '" class="comment-avatar-link">';
            $html .= '<div class="comment-avatar">' . $avatar . '</div>';
            $html .= '</a>';
        } else {
            $html .= '<div class="comment-avatar">' . $avatar . '</div>';
        }
        
        $html .= '<div class="comment-main">';
        
        // Header
        $html .= '<div class="comment-header">';
        if ($profile_url) {
            $html .= '<a href="' . esc_url($profile_url) . '" class="comment-author-link"><span class="comment-author">' . esc_html($author_name) . '</span></a>';
        } else {
            $html .= '<span class="comment-author">' . esc_html($author_name) . '</span>';
        }
        
        // Sabitlenme rozeti (dil bilgisi henüz yok, sonra okunuyor)
        if ($is_pinned) {
            $_pinned_label = ((get_option('ruh_comment_options', array())['language'] ?? 'tr_TR') === 'en_US') ? 'Pinned' : 'Sabitlendi';
            $html .= '<span class="pinned-badge"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M16,12V4H17V2H7V4H8V12L6,14V16H11.2V22H12.8V16H18V14L16,12Z"/></svg> ' . esc_html($_pinned_label) . '</span>';
        }
        
        $html .= $user_tag_html;
        $level_color = function_exists('ruh_get_level_color') ? ruh_get_level_color($user_level) : '#6b7280';
        $level_tier = function_exists('ruh_get_level_tier') ? ruh_get_level_tier($user_level) : 'novice';
        $level_title = function_exists('ruh_get_level_title') ? ruh_get_level_title($user_level) : '';
        $html .= '<span class="comment-level level-tier-' . esc_attr($level_tier) . '" data-level="' . intval($user_level) . '" style="--level-color:' . esc_attr($level_color) . '" title="' . esc_attr($level_title) . '">Lv.' . intval($user_level) . '</span>';
        $html .= $badges_html;
        $html .= '<span class="comment-date">' . esc_html($time_ago) . '</span>';
        $html .= '</div>';
        
        // Content
        $html .= '<div class="comment-text">' . $content . '</div>';
        
        // Actions
        $html .= '<div class="comment-actions">';
        
        // Dil ayarını al (aria-label'lar için erken taşındı)
        $_opts = get_option('ruh_comment_options', array());
        $_lang = $_opts['language'] ?? 'tr_TR';
        $_is_en = ($_lang === 'en_US');

        // Beğeni butonu (thumbs up)
        $html .= '<button class="action-btn like-btn ' . $liked_class . '" data-comment-id="' . esc_attr($comment->comment_ID) . '" aria-label="' . esc_attr($_is_en ? 'Like' : 'Beğen') . '">';
        $html .= '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M23,10C23,8.89 22.1,8 21,8H14.68L15.64,3.43C15.66,3.33 15.67,3.22 15.67,3.11C15.67,2.7 15.5,2.32 15.23,2.05L14.17,1L7.59,7.58C7.22,7.95 7,8.45 7,9V19A2,2 0 0,0 9,21H18C18.83,21 19.54,20.5 19.84,19.78L22.86,12.73C22.95,12.5 23,12.26 23,12V10M1,21H5V9H1V21Z"/></svg>';
        $html .= '<span class="like-count">' . $likes . '</span>';
        $html .= '</button>';
        
        // Beğenmeme butonu (thumbs down)
        $html .= '<button class="action-btn dislike-btn ' . $disliked_class . '" data-comment-id="' . esc_attr($comment->comment_ID) . '" aria-label="' . esc_attr($_is_en ? 'Dislike' : 'Beğenme') . '">';
        $html .= '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M19,15H23V3H19M15,3H6C5.17,3 4.46,3.5 4.16,4.22L1.14,11.27C1.05,11.5 1,11.74 1,12V14A2,2 0 0,0 3,16H9.31L8.36,20.57C8.34,20.67 8.33,20.77 8.33,20.88C8.33,21.3 8.5,21.67 8.77,21.94L9.83,23L16.41,16.41C16.78,16.05 17,15.55 17,15V5C17,3.89 16.1,3 15,3Z"/></svg>';
        $html .= '<span class="dislike-count">' . $dislikes . '</span>';
        $html .= '</button>';
        
        // Yanıtla butonu
        if (is_user_logged_in()) {
            $html .= '<button class="action-btn reply-btn" data-comment-id="' . esc_attr($comment->comment_ID) . '" data-author="' . esc_attr($author_name) . '">';
            $html .= '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M10,9V5L3,12L10,19V14.9C15,14.9 18.5,16.5 21,20C20,15 17,10 10,9Z"/></svg>';
            $html .= $_is_en ? 'Reply' : 'Yanıt';
            $html .= '</button>';
        }
        
        // 3 Nokta Menü - sadece giriş yapmış kullanıcılar için göster
        if (is_user_logged_in()) {
            $html .= '<div class="comment-more-menu">';
            $html .= '<button class="more-btn" data-comment-id="' . esc_attr($comment->comment_ID) . '" aria-label="' . esc_attr($_is_en ? 'More options' : 'Diğer seçenekler') . '" aria-haspopup="true">';
            $html .= '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12,16A2,2 0 0,1 14,18A2,2 0 0,1 12,20A2,2 0 0,1 10,18A2,2 0 0,1 12,16M12,10A2,2 0 0,1 14,12A2,2 0 0,1 12,14A2,2 0 0,1 10,12A2,2 0 0,1 12,10M12,4A2,2 0 0,1 14,6A2,2 0 0,1 12,8A2,2 0 0,1 10,6A2,2 0 0,1 12,4Z"/></svg>';
            $html .= '</button>';
            $html .= '<div class="more-dropdown">';
            
            // Yorumu Görüntüle - her zaman göster
            $comment_link = get_comment_link($comment->comment_ID);
            $html .= '<a href="' . esc_url($comment_link) . '" class="view-comment-btn" target="_blank">';
            $html .= '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M3.9,12C3.9,10.29 5.29,8.9 7,8.9H11V7H7A5,5 0 0,0 2,12A5,5 0 0,0 7,17H11V15.1H7C5.29,15.1 3.9,13.71 3.9,12M8,13H16V11H8V13M17,7H13V8.9H17C18.71,8.9 20.1,10.29 20.1,12C20.1,13.71 18.71,15.1 17,15.1H13V17H17A5,5 0 0,0 22,12A5,5 0 0,0 17,7Z"/></svg>';
            $html .= $_is_en ? 'View Comment' : 'Yorumu Görüntüle';
            $html .= '</a>';
            
            // Düzenle/Sil - sadece yorum sahibi veya admin
            if ($comment->user_id == $current_user_id || current_user_can('moderate_comments')) {
                $html .= '<button class="edit-btn" data-comment-id="' . esc_attr($comment->comment_ID) . '">';
                $html .= '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z"/></svg>';
                $html .= $_is_en ? 'Edit' : 'Düzenle';
                $html .= '</button>';
                
                $html .= '<button class="delete-btn" data-comment-id="' . esc_attr($comment->comment_ID) . '">';
                $html .= '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/></svg>';
                $html .= $_is_en ? 'Delete' : 'Sil';
                $html .= '</button>';
            }
            
            // Şikayet - kendi yorumu değilse
            if ($comment->user_id != $current_user_id) {
                $html .= '<button class="report-btn" data-comment-id="' . esc_attr($comment->comment_ID) . '">';
                $html .= '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M14.4,6L14,4H5V21H7V14H12.6L13,16H20V6H14.4Z"/></svg>';
                $html .= $_is_en ? 'Report' : 'Şikayet Et';
                $html .= '</button>';
            }
            
            // Sabitleme - sadece adminler için
            if (current_user_can('manage_options')) {
                $pin_text = $is_pinned ? ($_is_en ? 'Unpin' : 'Sabitlemeyi Kaldır') : ($_is_en ? 'Pin' : 'Sabitle');
                $pin_class = $is_pinned ? ' pinned' : '';
                $html .= '<button class="pin-btn' . $pin_class . '" data-comment-id="' . esc_attr($comment->comment_ID) . '">';
                $html .= '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M16,12V4H17V2H7V4H8V12L6,14V16H11.2V22H12.8V16H18V14L16,12Z"/></svg>';
                $html .= '<span class="pin-text">' . esc_html($pin_text) . '</span>';
                $html .= '</button>';
            }
            
            $html .= '</div>'; // more-dropdown
            $html .= '</div>'; // comment-more-menu
        }
        
        $html .= '</div>'; // comment-actions
        
        $cid = intval($comment->comment_ID);
        if (isset($reply_counts_cache[$cid])) {
            $reply_count = $reply_counts_cache[$cid];
        } else {
            global $wpdb;
            $reply_count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_parent = %d AND comment_approved = '1'",
                $cid
            ));
        }
        
        if ($reply_count > 0) {
            $show_text = $_is_en
                ? $reply_count . ' ' . ($reply_count === 1 ? 'reply' : 'replies')
                : $reply_count . ' yanıtı göster';
            $hide_text = $_is_en
                ? 'Hide ' . $reply_count . ' ' . ($reply_count === 1 ? 'reply' : 'replies')
                : $reply_count . ' yanıtı gizle';
            $html .= '<div class="replies-toggle-container">';
            $html .= '<button type="button" class="replies-toggle-btn" data-comment-id="' . esc_attr($comment->comment_ID) . '" data-replies-count="' . esc_attr($reply_count) . '" data-parent-id="' . esc_attr($comment->comment_ID) . '" data-show-text="' . esc_attr($show_text) . '" data-hide-text="' . esc_attr($hide_text) . '">';
            $html .= '<svg class="toggle-icon" viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z"/></svg>';
            $html .= '<span class="toggle-text">' . esc_html($show_text) . '</span>';
            $html .= '</button>';
            $html .= '</div>';
        }
        
        $html .= '</div>'; // comment-main
        $html .= '</div>'; // comment-body
        $html .= '<ol class="children replies-container is-collapsed" id="replies-' . esc_attr($comment->comment_ID) . '" hidden data-parent-id="' . esc_attr($comment->comment_ID) . '" data-loaded="false"></ol>';
        $html .= '</li>';
        
        return $html;
    }
    
    // BASIT FORMAT ISLEME - DISCORD TARZI
    private function process_comment_formatting($content) {
        // HTML entities decode et
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        
        // Discord tarzı kalın text: **text**
        $content = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $content);
        
        // Discord tarzı italik text: *text* (** olmadan)
        $content = preg_replace('/(?<!\*)\*([^\*]+)\*(?!\*)/s', '<em>$1</em>', $content);
        
        // Discord tarzı spoiler: ||text||
        $content = preg_replace('/\|\|(.+?)\|\|/s', '<span class="spoiler">$1</span>', $content);
        
        // Eski format desteği
        $content = preg_replace('/\[spoiler\](.*?)\[\/spoiler\]/is', '<span class="spoiler">$1</span>', $content);
        
        // GIF - güvenlik: sadece izin verilen kaynaklardan (giphy/tenor) kabul edilir.
        // Whitelist kontrolü ortak ruh_is_allowed_gif_host() fonksiyonunda (template-helpers.php)
        $content = preg_replace_callback(
            '/!\[GIF\]\((https?:\/\/[^\)]+)\)/',
            function($matches) {
                $url = esc_url($matches[1]);
                if (function_exists('ruh_is_allowed_gif_host') && ruh_is_allowed_gif_host($url)) {
                    return '<div class="gif-container"><img src="' . $url . '" alt="GIF" loading="lazy" class="comment-gif"></div>';
                }
                return '';
            },
            $content
        );
        
        return $content;
    }

    // YORUMLARI GETIRME
    public function get_comments_callback() {
        $this->verify_nonce();
        $this->check_rate_limit('get_comments');

        $post_id = intval($_POST['post_id'] ?? 0);
        $page = max(1, intval($_POST['page'] ?? 1));
        $sort = sanitize_key($_POST['sort'] ?? 'newest');
        $parent_id = intval($_POST['parent_id'] ?? 0);
        
        // Post ID yoksa URL'den al
        if (!$post_id) {
            $current_url = isset($_POST['current_url']) ? esc_url_raw($_POST['current_url']) : '';
            if ($current_url && function_exists('ruh_get_dynamic_post_id_from_url')) {
                $post_id = ruh_get_dynamic_post_id_from_url($current_url);
            }
            if (!$post_id && $current_url) {
                $post_id = abs(crc32($current_url)) % 2000000000 + 100;
            }
        }
        
        // Sayfa basina yorum limiti
        $options_temp = get_option('ruh_comment_options', array());
        $comments_per_page = min(50, max(5, intval($options_temp['comments_per_page'] ?? 10)));
        
        $args = array(
            'post_id' => $post_id,
            'status' => 'approve',
            'number' => $comments_per_page,
            'offset' => ($page - 1) * $comments_per_page,
            'parent' => $parent_id,
            'orderby' => 'comment_date_gmt',
            'order' => ($sort === 'oldest') ? 'ASC' : 'DESC'
        );
        
        $comments = get_comments($args);
        $total_count = wp_count_comments($post_id)->approved;
        
        // Sabitlenmıs yorumlari en üste taşı (sadece ilk sayfada)
        if ($page === 1 && $parent_id === 0) {
            $pinned_comments = array();
            $regular_comments = array();
            
            foreach ($comments as $comment) {
                if (get_comment_meta($comment->comment_ID, 'ruh_pinned', true)) {
                    $pinned_comments[] = $comment;
                } else {
                    $regular_comments[] = $comment;
                }
            }
            
            // Sabitlenmıs yorumlari öne al
            $comments = array_merge($pinned_comments, $regular_comments);
        }
        
        // N+1 sorgu optimizasyonu: tüm yanıt sayılarını tek sorguda çek
        $comment_ids = wp_list_pluck($comments, 'comment_ID');
        $reply_counts_cache = array();
        if (!empty($comment_ids)) {
            global $wpdb;
            $ids_placeholder = implode(',', array_map('intval', $comment_ids));
            $reply_rows = $wpdb->get_results(
                "SELECT comment_parent, COUNT(*) as cnt
                 FROM {$wpdb->comments}
                 WHERE comment_parent IN ({$ids_placeholder})
                   AND comment_approved = '1'
                 GROUP BY comment_parent"
            );
            foreach ($reply_rows as $row) {
                $reply_counts_cache[intval($row->comment_parent)] = intval($row->cnt);
            }
        }

        $html = '';
        foreach ($comments as $comment) {
            $html .= $this->generate_comment_html($comment, $reply_counts_cache);
        }
        
        // Daha fazla yorum var mı?
        $next_args = $args;
        $next_args['offset'] = $page * $comments_per_page;
        $next_args['number'] = 1;
        $has_more = !empty(get_comments($next_args));
        
        wp_send_json_success(array(
            'html' => $html,
            'has_more' => $has_more,
            'total' => count($comments),
            'comment_count' => $total_count,
            'current_page' => $page,
            'sort_type' => $sort
        ));
    }

    // TEPKILER - Giriş yapmamış kullanıcılar da tepki verebilir
    public function handle_reaction_callback() {
        $this->verify_nonce();
        $this->check_rate_limit('reaction');
        
        global $wpdb;
        $post_id = intval($_POST['post_id']);
        $reaction = sanitize_key($_POST['reaction']);
        $user_id = get_current_user_id();
        $reactions_table = $wpdb->prefix . 'ruh_reactions';
        
        // Giriş yapmamış kullanıcılar için IP bazlı tanımlama
        $visitor_ip = '';
        if (!$user_id) {
            $visitor_ip = $this->get_client_ip();
        }
        
        $valid_reactions = array('begendim', 'sinir_bozucu', 'mukemmel', 'sasirtici', 'sakin', 'bitti', 'uzucu', 'kalp');
        if (!in_array($reaction, $valid_reactions)) {
            wp_send_json_error(array('message' => 'Geçersiz tepki türü: ' . $reaction));
        }
        
        // Mevcut tepki - user_id veya IP ile kontrol
        if ($user_id) {
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id, reaction FROM $reactions_table WHERE post_id = %d AND user_id = %d",
                $post_id, $user_id
            ));
        } else {
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id, reaction FROM $reactions_table WHERE post_id = %d AND visitor_ip = %s AND user_id = 0",
                $post_id, $visitor_ip
            ));
        }
        
        if ($existing) {
            if ($existing->reaction == $reaction) {
                // Aynı tepki - kaldır
                $wpdb->delete($reactions_table, array('id' => $existing->id), array('%d'));
            } else {
                // Farklı tepki - güncelle
                $wpdb->update($reactions_table, array('reaction' => $reaction), array('id' => $existing->id), array('%s'), array('%d'));
            }
        } else {
            // Yeni tepki ekle - ON DUPLICATE KEY UPDATE ile duplicate hatasını önle
            $visitor_ip_value = (!$user_id && $visitor_ip) ? $visitor_ip : '';
            
            $wpdb->query($wpdb->prepare(
                "INSERT INTO $reactions_table (post_id, user_id, reaction, visitor_ip) 
                 VALUES (%d, %d, %s, %s) 
                 ON DUPLICATE KEY UPDATE reaction = VALUES(reaction), visitor_ip = VALUES(visitor_ip)",
                $post_id, $user_id, $reaction, $visitor_ip_value
            ));
        }
        
        // Güncel sayıları al
        $counts = $wpdb->get_results($wpdb->prepare(
            "SELECT reaction, COUNT(id) as count FROM $reactions_table WHERE post_id = %d GROUP BY reaction",
            $post_id
        ), OBJECT_K);
        
        wp_send_json_success(array('counts' => $counts));
    }

    // BEGENI
    public function handle_like_callback() {
        $this->verify_nonce();
        $this->require_login();
        $this->check_rate_limit('like');
        
        $comment_id = intval($_POST['comment_id']);
        $user_id = get_current_user_id();
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => 'Yorum bulunamadı.'));
        }
        
        // Kendi yorumunu begenemez
        if ($comment->user_id == $user_id) {
            wp_send_json_error(array('message' => 'Kendi yorumunuzu beğenemezsiniz.'));
        }
        
        $likes = intval(get_comment_meta($comment_id, '_likes', true));
        $user_vote = get_comment_meta($comment_id, '_user_vote_' . $user_id, true);
        
        $dislikes = intval(get_comment_meta($comment_id, '_dislikes', true));
        
        if ($user_vote == 'liked') {
            // Beğeniyi kaldır
            update_comment_meta($comment_id, '_likes', max(0, $likes - 1));
            delete_comment_meta($comment_id, '_user_vote_' . $user_id);
            $new_user_vote = '';
        } else {
            // Önceki dislike varsa kaldır
            if ($user_vote == 'disliked') {
                update_comment_meta($comment_id, '_dislikes', max(0, $dislikes - 1));
            }
            // Beğeni ekle
            update_comment_meta($comment_id, '_likes', $likes + 1);
            update_comment_meta($comment_id, '_user_vote_' . $user_id, 'liked');
            $new_user_vote = 'liked';
        }
        
        $new_likes = intval(get_comment_meta($comment_id, '_likes', true));
        
        wp_send_json_success(array(
            'likes' => $new_likes,
            'dislikes' => intval(get_comment_meta($comment_id, '_dislikes', true)),
            'user_vote' => $new_user_vote
        ));
    }

    // BEGENMEME
    public function handle_dislike_callback() {
        $this->verify_nonce();
        $this->require_login();
        $this->check_rate_limit('dislike');
        
        $comment_id = intval($_POST['comment_id']);
        $user_id = get_current_user_id();
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => 'Yorum bulunamadı.'));
        }
        
        // Kendi yorumunu begenemez
        if ($comment->user_id == $user_id) {
            wp_send_json_error(array('message' => 'Kendi yorumunuzu beğenemezsiniz.'));
        }
        
        $dislikes = intval(get_comment_meta($comment_id, '_dislikes', true));
        $likes = intval(get_comment_meta($comment_id, '_likes', true));
        $user_vote = get_comment_meta($comment_id, '_user_vote_' . $user_id, true);
        
        if ($user_vote == 'disliked') {
            // Beğenmemeyi kaldır
            update_comment_meta($comment_id, '_dislikes', max(0, $dislikes - 1));
            delete_comment_meta($comment_id, '_user_vote_' . $user_id);
            $new_user_vote = '';
        } else {
            // Önceki like varsa kaldır
            if ($user_vote == 'liked') {
                update_comment_meta($comment_id, '_likes', max(0, $likes - 1));
            }
            // Dislike ekle
            update_comment_meta($comment_id, '_dislikes', $dislikes + 1);
            update_comment_meta($comment_id, '_user_vote_' . $user_id, 'disliked');
            $new_user_vote = 'disliked';
        }
        
        wp_send_json_success(array(
            'likes' => intval(get_comment_meta($comment_id, '_likes', true)),
            'dislikes' => intval(get_comment_meta($comment_id, '_dislikes', true)),
            'user_vote' => $new_user_vote
        ));
    }

    // YORUM DUZENLEME
    public function edit_comment_callback() {
        $this->verify_nonce();
        $this->require_login();
        $this->check_rate_limit('edit_comment');
        
        $comment_id = intval($_POST['comment_id']);
        $content = trim(sanitize_textarea_field($_POST['content']));
        $user_id = get_current_user_id();
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => 'Yorum bulunamadı.'));
        }
        
        // Sadece kendi yorumunu düzenleyebilir (veya admin)
        if ($comment->user_id != $user_id && !current_user_can('moderate_comments')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok.'));
        }
        
        if (empty($content)) {
            wp_send_json_error(array('message' => 'İçerik boş olamaz.'));
        }
        
        $options = get_option('ruh_comment_options', array());
        $max_length = isset($options['max_comment_length']) ? intval($options['max_comment_length']) : 1000;
        if (strlen($content) > $max_length) {
            wp_send_json_error(array('message' => sprintf('Yorum çok uzun. Maksimum %d karakter.', $max_length)));
        }
        
        // Düzenleme geçmişini kaydet (ruhun_gecici_kaydet fonksiyonu template-helpers.php'de tanımlı)
        if (function_exists('ruh_save_edit_history')) {
            ruh_save_edit_history($comment_id, $content);
        }
        
        // İzin verilen HTML tagleri (submit ile tutarlı)
        $allowed_tags = array(
            'b' => array(),
            'i' => array(),
            'strong' => array(),
            'em' => array(),
            'a' => array('href' => array(), 'title' => array(), 'target' => array()),
            'br' => array(),
            'p' => array(),
        );
        
        $result = wp_update_comment(array(
            'comment_ID' => $comment_id,
            'comment_content' => wp_kses($content, $allowed_tags)
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => 'Güncelleme başarısız.'));
        }
        
        // Düzenlendi işaretini ekle
        $is_edited = function_exists('ruh_is_comment_edited') ? ruh_is_comment_edited($comment_id) : true;
        
        wp_send_json_success(array(
            'content' => $this->process_comment_formatting(wp_kses($content, $allowed_tags)),
            'message' => $this->msg('Yorum güncellendi.', 'Comment updated.'),
            'is_edited' => $is_edited
        ));
    }

    // YORUM SILME
    public function delete_comment_callback() {
        $this->verify_nonce();
        $this->require_login();
        $this->check_rate_limit('delete_comment');
        
        $comment_id = intval($_POST['comment_id']);
        $user_id = get_current_user_id();
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => 'Yorum bulunamadı.'));
        }
        
        // Sadece kendi yorumunu silebilir (veya admin)
        if ($comment->user_id != $user_id && !current_user_can('moderate_comments')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok.'));
        }
        
        $result = wp_trash_comment($comment_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => 'Silme başarısız.'));
        }
        
        wp_send_json_success(array('message' => $this->msg('Yorum silindi.', 'Comment deleted.')));
    }

    // YANITLARI YUKLE
    public function load_replies_callback() {
        $this->verify_nonce();
        $this->check_rate_limit('load_replies');
        
        $parent_id = intval($_POST['parent_id']);
        
        if ($parent_id <= 0) {
            wp_send_json_error(array('message' => $this->msg('Geçersiz yorum ID.', 'Invalid comment ID.')));
        }
        
        $replies = get_comments(array(
            'parent' => $parent_id,
            'status' => 'approve',
            'orderby' => 'comment_date_gmt',
            'order' => 'ASC',
            'number' => 50 // Maksimum 50 yanıt
        ));
        
        $html = '';
        foreach($replies as $reply) {
            $html .= $this->generate_comment_html($reply);
        }
        
        wp_send_json_success(array(
            'html' => $html,
            'count' => count($replies)
        ));
    }

    // ILK VERILER
    public function get_initial_data_callback() {
        $this->verify_nonce();
        $this->check_rate_limit('get_comments');

        global $wpdb;
        $post_id = intval($_POST['post_id']);
        $reactions_table = $wpdb->prefix . 'ruh_reactions';
        
        // Tepki sayıları
        $counts = $wpdb->get_results($wpdb->prepare(
            "SELECT reaction, COUNT(id) as count FROM $reactions_table WHERE post_id = %d GROUP BY reaction",
            $post_id
        ), OBJECT_K);
        
        // Kullanıcı tepkisi
        $user_reaction = null;
        if (is_user_logged_in()) {
            $user_reaction = $wpdb->get_var($wpdb->prepare(
                "SELECT reaction FROM $reactions_table WHERE post_id = %d AND user_id = %d",
                $post_id, get_current_user_id()
            ));
        }
        
        wp_send_json_success(array(
            'counts' => $counts,
            'user_reaction' => $user_reaction
        ));
    }

    // SIKAYET
    public function flag_comment_callback() {
        $this->verify_nonce();
        $this->require_login();
        $this->check_rate_limit('flag_comment');
        
        $comment_id = intval($_POST['comment_id']);
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        $user_id = get_current_user_id();
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => 'Yorum bulunamadı.'));
        }
        
        // Kendi yorumunu şikayet edemez
        if ($comment->user_id == $user_id) {
            wp_send_json_error(array('message' => 'Kendi yorumunuzu şikayet edemezsiniz.'));
        }
        
        global $wpdb;
        $reports_table = $wpdb->prefix . 'ruh_reports';
        
        // Daha önce şikayet etmış mı?
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $reports_table WHERE comment_id = %d AND reporter_id = %d",
            $comment_id, $user_id
        ));
        
        if ($existing) {
            wp_send_json_error(array('message' => $this->msg('Bu yorumu zaten şikayet ettiniz.', 'You have already reported this comment.')));
        }
        
        // Şikayet kaydet
        $wpdb->insert($reports_table, array(
            'comment_id' => $comment_id,
            'reporter_id' => $user_id,
            'reason' => $reason
        ), array('%d', '%d', '%s'));
        
        // Şikayet sayısıni kontrol et - otomatik moderasyon
        $options = get_option('ruh_comment_options', array());
        $auto_moderate_limit = isset($options['auto_moderate_reports']) ? intval($options['auto_moderate_reports']) : 3;
        
        $report_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $reports_table WHERE comment_id = %d",
            $comment_id
        ));
        
        $hidden = false;
        if ($report_count >= $auto_moderate_limit) {
            // Yorumu moderasyona al
            wp_set_comment_status($comment_id, 'hold');
            $hidden = true;
        }
        
        wp_send_json_success(array(
            'message' => $this->msg('Şikayetiniz alındı.', 'Your report has been submitted.'),
            'hidden' => $hidden,
            'comment_id' => $comment_id
        ));
    }
    
    /**
     * Yorum sabitleme/sabitlemeyi kaldırma (sadece admin)
     */
    public function pin_comment_callback() {
        $this->verify_nonce();
        
        // Admin kontrolu
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => $this->msg('Bu işlemi yapmaya yetkiniz yok.', 'You do not have permission to perform this action.')));
        }
        
        $comment_id = intval($_POST['comment_id'] ?? 0);
        
        if (!$comment_id) {
            wp_send_json_error(array('message' => $this->msg('Geçersiz yorum.', 'Invalid comment.')));
        }
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => $this->msg('Yorum bulunamadı.', 'Comment not found.')));
        }
        
        // Mevcut pin durumunu kontrol et
        $is_pinned = get_comment_meta($comment_id, 'ruh_pinned', true);
        
        if ($is_pinned) {
            // Sabitlemeyi kaldır
            delete_comment_meta($comment_id, 'ruh_pinned');
            delete_comment_meta($comment_id, 'ruh_pinned_date');
            $new_status = false;
            $message = $this->msg('Yorum sabitlemesi kaldırıldı.', 'Comment unpinned.');
        } else {
            // Sabitle
            update_comment_meta($comment_id, 'ruh_pinned', '1');
            update_comment_meta($comment_id, 'ruh_pinned_date', current_time('mysql'));
            $new_status = true;
            $message = $this->msg('Yorum sabitlendi.', 'Comment pinned.');
        }
        
        wp_send_json_success(array(
            'message' => $message,
            'pinned' => $new_status,
            'comment_id' => $comment_id
        ));
    }

    /**
     * Profil sayfasında daha fazla yorum yükle
     */
    public function load_more_profile_comments_callback() {
        $this->verify_nonce();
        $this->check_rate_limit('load_more_profile_comments');
        
        $user_id = intval($_POST['user_id'] ?? 0);
        $page = intval($_POST['page'] ?? 1);
        
        if (!$user_id) {
            wp_send_json_error(array('message' => 'Geçersiz kullanıcı.'));
        }
        
        $args = array(
            'user_id' => $user_id,
            'status' => 'approve',
            'number' => 10,
            'offset' => ($page - 1) * 10,
            'orderby' => 'comment_date_gmt',
            'order' => 'DESC'
        );
        
        $comments = get_comments($args);
        
        if (empty($comments)) {
            wp_send_json_success(array(
                'html' => '',
                'has_more' => false
            ));
        }
        
        $html = '';
        foreach ($comments as $comment) {
            $post_title = ruh_get_comment_post_title($comment->comment_post_ID);
            $comment_link = ruh_get_comment_link($comment);
            $post_link = ruh_get_post_permalink($comment->comment_post_ID, $comment);
            $likes = get_comment_meta($comment->comment_ID, '_likes', true) ?: 0;
            
            // Timestamp fix
            $comment_time = intval(get_comment_time('U', true, $comment));
            if (!$comment_time) {
                $comment_time = strtotime($comment->comment_date);
            }
            
            $html .= '<div class="profile-comment-item">';
            $html .= '<div class="comment-header">';
            $html .= '<div class="comment-post-info">';
            $html .= '<a href="' . esc_url($post_link) . '" class="post-title" target="_blank">';
            $html .= esc_html($post_title);
            $html .= '</a>';
            $html .= '</div>';
            $html .= '<div class="comment-meta">';
            $html .= '<span class="comment-date">';
            $html .= '<a href="' . esc_url($comment_link) . '" target="_blank">';
            $html .= (function_exists('ruh_human_time_diff_tr') ? ruh_human_time_diff_tr($comment_time, current_time('timestamp')) : human_time_diff($comment_time, current_time('timestamp'))) . ' önce';
            $html .= '</a>';
            $html .= '</span>';
            
            if ($likes > 0) {
                $html .= '<span class="comment-likes">';
                $html .= '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">';
                $html .= '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>';
                $html .= '</svg> ';
                $html .= $likes;
                $html .= '</span>';
            }
            
            $html .= '</div></div>';
            
            $html .= '<div class="comment-excerpt">';
            $excerpt = wp_trim_words(strip_tags($comment->comment_content), 25, '...');
            $html .= esc_html($excerpt);
            $html .= '</div>';
            
            $html .= '<div class="comment-actions">';
            $html .= '<a href="' . esc_url($comment_link) . '" target="_blank" class="view-comment">Yorumu Görüntüle</a>';
            $html .= '<a href="' . esc_url($post_link) . '" target="_blank" class="view-post">Yazıya Git</a>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        // Check if there are more comments
        $next_args = $args;
        $next_args['offset'] = $page * 10;
        $next_args['number'] = 1;
        $has_more = !empty(get_comments($next_args));
        
        wp_send_json_success(array(
            'html' => $html,
            'has_more' => $has_more
        ));
    }
}

new Ruh_Comment_Ajax_Handlers();
