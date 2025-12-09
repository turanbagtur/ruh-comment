<?php
if (!defined('ABSPATH')) exit;

class Ruh_Comment_Ajax_Handlers {
    
    public function __construct() {
        $actions = array(
            'get_initial_data', 'handle_reaction', 'get_comments', 
            'handle_like', 'flag_comment', 'submit_comment', 
            'edit_comment', 'delete_comment', 'load_replies'
        );
        
        foreach ($actions as $action) {
            add_action('wp_ajax_ruh_' . $action, array($this, $action . '_callback'));
            
            // Public actions
            $public_actions = array('get_initial_data', 'get_comments');
            if (in_array($action, $public_actions)) {
                add_action('wp_ajax_nopriv_ruh_' . $action, array($this, $action . '_callback'));
            }
        }
    }

    private function verify_nonce($action = 'ruh-comment-nonce') {
        if (!check_ajax_referer($action, 'nonce', false)) {
            wp_send_json_error(array('message' => 'Guvenlik kontrolu basarisiz.'));
        }
    }

    private function require_login() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Bu islem icin giris yapmalisiniz.'));
        }
    }
    
    /**
     * Rate limiting kontrolu - Performans ve guvenlik
     */
    private function check_rate_limit($action_type = 'comment') {
        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        
        $limits = array(
            'comment' => array('count' => 5, 'window' => 60),   // 5 yorum/dakika
            'like' => array('count' => 30, 'window' => 60),     // 30 begeni/dakika
            'reaction' => array('count' => 20, 'window' => 60)  // 20 tepki/dakika
        );
        
        $limit = isset($limits[$action_type]) ? $limits[$action_type] : $limits['comment'];
        $cache_key = 'ruh_rate_' . $action_type . '_' . ($user_id ?: md5($ip));
        
        $current = get_transient($cache_key) ?: 0;
        
        if ($current >= $limit['count']) {
            wp_send_json_error(array('message' => 'Cok hizli islem yapiyorsunuz. Lutfen bekleyin.'));
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
        
        // Veri alma ve sanitization
        $post_id = intval($_POST['post_id'] ?? $_POST['comment_post_ID'] ?? 0);
        $comment_content = trim(sanitize_textarea_field($_POST['comment'] ?? ''));
        $comment_parent = intval($_POST['comment_parent'] ?? 0);
        
        // Temel validasyon
        if (empty($comment_content)) {
            wp_send_json_error(array('message' => 'Yorum icerigi bos olamaz.'));
        }
        
        if (strlen($comment_content) > 5000) {
            wp_send_json_error(array('message' => 'Yorum cok uzun. Maksimum 5000 karakter.'));
        }
        
        if (strlen($comment_content) < 3) {
            wp_send_json_error(array('message' => 'Yorum en az 3 karakter olmalidir.'));
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
                // URL guvenlik kontrolu - sadece ayni domain
                $home_host = parse_url(home_url(), PHP_URL_HOST);
                $url_host = parse_url($current_url, PHP_URL_HOST);
                
                if ($url_host === $home_host) {
                    // Manga chapter/seri URL'leri icin dinamik ID
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
                // URL'den benzersiz ID olustur
                $post_id = abs(crc32($current_url)) % 2000000000 + 100;
            } else {
                $post_id = 999999; // Fallback
            }
        }
        
        // Parent comment varsa, gercekten var mi kontrol et
        if ($comment_parent > 0) {
            $parent_comment = get_comment($comment_parent);
            if (!$parent_comment || $parent_comment->comment_approved != 1) {
                wp_send_json_error(array('message' => 'Yanit verilen yorum bulunamadi.'));
            }
        }
        
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        // Kullanici banlı mi kontrol et
        $ban_status = get_user_meta($user_id, 'ruh_ban_status', true);
        if ($ban_status === 'banned') {
            wp_send_json_error(array('message' => 'Yorum yapma yetkiniz bulunmuyor.'));
        }
        
        // Timeout kontrolu
        $timeout_until = get_user_meta($user_id, 'ruh_timeout_until', true);
        if ($timeout_until && current_time('timestamp') < $timeout_until) {
            wp_send_json_error(array('message' => 'Gecici olarak yorum yapamazsiniz.'));
        }
        
        // Izin verilen HTML tagleri
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
            wp_send_json_error(array('message' => 'Yorum kaydedilemedi. Lutfen tekrar deneyin.'));
        }
        
        // XP ve seviye guncelle
        if (function_exists('ruh_update_user_xp_and_level')) {
            ruh_update_user_xp_and_level($user_id);
        }
        
        // Otomatik rozetleri kontrol et
        if (function_exists('ruh_check_and_assign_auto_badges')) {
            ruh_check_and_assign_auto_badges($user_id);
        }
        
        // Yorumu al ve HTML olustur
        $comment = get_comment($comment_id);
        $html = $this->generate_comment_html($comment);
        
        wp_send_json_success(array(
            'html' => $html,
            'comment_id' => $comment_id,
            'parent_id' => $comment->comment_parent,
            'message' => 'Yorum basariyla gonderildi.'
        ));
    }

    // YORUM HTML OLUSTURMA - YENI TASARIM
    private function generate_comment_html($comment) {
        if (!$comment) return '';
        
        global $wpdb;
        $user = get_userdata($comment->user_id);
        
        // Avatar - user_id veya email ile al
        if ($comment->user_id) {
            $avatar = get_avatar($comment->user_id, 40);
        } else {
            $avatar = get_avatar($comment->comment_author_email, 40);
        }
        
        $time_ago = human_time_diff(strtotime($comment->comment_date), current_time('timestamp')) . ' once';
        $author_name = $user ? $user->display_name : ($comment->comment_author ?: 'Anonim');
        $current_user_id = get_current_user_id();
        
        // Begeni bilgisi
        $likes = intval(get_comment_meta($comment->comment_ID, '_likes', true));
        $user_liked = get_comment_meta($comment->comment_ID, '_user_vote_' . $current_user_id, true) === 'liked';
        $liked_class = $user_liked ? 'liked' : '';
        
        // Kullanici seviyesi
        $user_level = 1;
        if ($comment->user_id) {
            $level_table = $wpdb->prefix . 'ruh_user_levels';
            $level_data = $wpdb->get_row($wpdb->prepare("SELECT level FROM $level_table WHERE user_id = %d", $comment->user_id));
            if ($level_data) {
                $user_level = $level_data->level;
            }
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
                    $badges_html .= '<span class="comment-badge-item">';
                    $badges_html .= '<span class="comment-badge">' . $svg . '</span>';
                    $badges_html .= '<span class="comment-badge-name">' . esc_html($badge->badge_name) . '</span>';
                    $badges_html .= '</span>';
                }
                $badges_html .= '</span>';
            }
        }
        
        // Spoiler ve format islemleri
        $content = $comment->comment_content;
        $content = $this->process_comment_formatting($content);
        
        $html = '<li class="comment" id="comment-' . esc_attr($comment->comment_ID) . '" data-comment-id="' . esc_attr($comment->comment_ID) . '">';
        $html .= '<div class="comment-body">';
        $html .= '<div class="comment-avatar">' . $avatar . '</div>';
        $html .= '<div class="comment-main">';
        
        // Header
        $html .= '<div class="comment-header">';
        $html .= '<span class="comment-author">' . esc_html($author_name) . '</span>';
        $html .= '<span class="comment-level">Lv.' . $user_level . '</span>';
        $html .= $badges_html;
        $html .= '<span class="comment-date">' . esc_html($time_ago) . '</span>';
        $html .= '</div>';
        
        // Content
        $html .= '<div class="comment-text">' . $content . '</div>';
        
        // Actions
        $html .= '<div class="comment-actions">';
        
        // Begeni butonu - her zaman goster
        $html .= '<button class="action-btn like-btn ' . $liked_class . '" data-comment-id="' . esc_attr($comment->comment_ID) . '">';
        $html .= '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M12,21.35L10.55,20.03C5.4,15.36 2,12.27 2,8.5C2,5.41 4.42,3 7.5,3C9.24,3 10.91,3.81 12,5.08C13.09,3.81 14.76,3 16.5,3C19.58,3 22,5.41 22,8.5C22,12.27 18.6,15.36 13.45,20.03L12,21.35Z"/></svg>';
        $html .= '<span class="like-count">' . $likes . '</span>';
        $html .= '</button>';
        
        // Yanitla butonu
        if (is_user_logged_in()) {
            $html .= '<button class="action-btn reply-btn" data-comment-id="' . esc_attr($comment->comment_ID) . '" data-author="' . esc_attr($author_name) . '">';
            $html .= '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M10,9V5L3,12L10,19V14.9C15,14.9 18.5,16.5 21,20C20,15 17,10 10,9Z"/></svg>';
            $html .= 'Yanit';
            $html .= '</button>';
        }
        
        // 3 Nokta Menu - sadece giris yapmis kullanicilar icin goster
        if (is_user_logged_in()) {
            $html .= '<div class="comment-more-menu">';
            $html .= '<button class="more-btn" data-comment-id="' . esc_attr($comment->comment_ID) . '">';
            $html .= '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12,16A2,2 0 0,1 14,18A2,2 0 0,1 12,20A2,2 0 0,1 10,18A2,2 0 0,1 12,16M12,10A2,2 0 0,1 14,12A2,2 0 0,1 12,14A2,2 0 0,1 10,12A2,2 0 0,1 12,10M12,4A2,2 0 0,1 14,6A2,2 0 0,1 12,8A2,2 0 0,1 10,6A2,2 0 0,1 12,4Z"/></svg>';
            $html .= '</button>';
            $html .= '<div class="more-dropdown">';
            
            // Duzenle/Sil - sadece yorum sahibi veya admin
            if ($comment->user_id == $current_user_id || current_user_can('moderate_comments')) {
                $html .= '<button class="edit-btn" data-comment-id="' . esc_attr($comment->comment_ID) . '">';
                $html .= '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M20.71,7.04C21.1,6.65 21.1,6 20.71,5.63L18.37,3.29C18,2.9 17.35,2.9 16.96,3.29L15.12,5.12L18.87,8.87M3,17.25V21H6.75L17.81,9.93L14.06,6.18L3,17.25Z"/></svg>';
                $html .= 'Duzenle';
                $html .= '</button>';
                
                $html .= '<button class="delete-btn" data-comment-id="' . esc_attr($comment->comment_ID) . '">';
                $html .= '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/></svg>';
                $html .= 'Sil';
                $html .= '</button>';
            }
            
            // Sikayet - kendi yorumu degilse
            if ($comment->user_id != $current_user_id) {
                $html .= '<button class="report-btn" data-comment-id="' . esc_attr($comment->comment_ID) . '">';
                $html .= '<svg viewBox="0 0 24 24"><path fill="currentColor" d="M14.4,6L14,4H5V21H7V14H12.6L13,16H20V6H14.4Z"/></svg>';
                $html .= 'Sikayet Et';
                $html .= '</button>';
            }
            
            $html .= '</div>'; // more-dropdown
            $html .= '</div>'; // comment-more-menu
        }
        
        $html .= '</div>';
        
        // Yanitlar icin container
        $html .= '<div class="comment-replies" id="replies-' . esc_attr($comment->comment_ID) . '"></div>';
        
        $html .= '</div>'; // comment-main
        $html .= '</div>'; // comment-body
        $html .= '</li>';
        
        return $html;
    }
    
    // BASIT FORMAT ISLEME - DISCORD TARZI
    private function process_comment_formatting($content) {
        // HTML entities decode et
        $content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
        
        // Discord tarzi kalin text: **text**
        $content = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $content);
        
        // Discord tarzi italik text: *text* (** olmadan)
        $content = preg_replace('/(?<!\*)\*([^\*]+)\*(?!\*)/s', '<em>$1</em>', $content);
        
        // Discord tarzi spoiler: ||text||
        $content = preg_replace('/\|\|(.+?)\|\|/s', '<span class="spoiler">$1</span>', $content);
        
        // Eski format destegi
        $content = preg_replace('/\[spoiler\](.*?)\[\/spoiler\]/is', '<span class="spoiler">$1</span>', $content);
        
        // GIF
        $content = preg_replace_callback(
            '/!\[GIF\]\((https?:\/\/[^\)]+)\)/',
            function($matches) {
                $url = esc_url($matches[1]);
                // Sadece guvenilir GIF kaynaklarini kabul et
                $allowed_hosts = array('giphy.com', 'media.giphy.com', 'i.giphy.com', 'tenor.com', 'media.tenor.com');
                $host = parse_url($url, PHP_URL_HOST);
                
                $is_allowed = false;
                foreach ($allowed_hosts as $allowed) {
                    if (strpos($host, $allowed) !== false) {
                        $is_allowed = true;
                        break;
                    }
                }
                
                if ($is_allowed) {
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
        $comments_per_page = min(50, max(5, intval(get_option('ruh_comment_options')['comments_per_page'] ?? 10)));
        
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
        
        $html = '';
        foreach ($comments as $comment) {
            $html .= $this->generate_comment_html($comment);
        }
        
        // Daha fazla yorum var mi?
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

    // TEPKILER
    public function handle_reaction_callback() {
        $this->verify_nonce();
        $this->require_login();
        $this->check_rate_limit('reaction');
        
        global $wpdb;
        $post_id = intval($_POST['post_id']);
        $reaction = sanitize_key($_POST['reaction']);
        $user_id = get_current_user_id();
        $reactions_table = $wpdb->prefix . 'ruh_reactions';
        
        $valid_reactions = array('begendim', 'sinir_bozucu', 'mukemmel', 'sasirtici', 'sakin', 'bitti', 'uzucu', 'kalp');
        if (!in_array($reaction, $valid_reactions)) {
            wp_send_json_error(array('message' => 'Gecersiz tepki turu: ' . $reaction));
        }
        
        // Mevcut tepki
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, reaction FROM $reactions_table WHERE post_id = %d AND user_id = %d",
            $post_id, $user_id
        ));
        
        if ($existing) {
            if ($existing->reaction == $reaction) {
                // Ayni tepki - kaldir
                $wpdb->delete($reactions_table, array('id' => $existing->id), array('%d'));
            } else {
                // Farkli tepki - guncelle
                $wpdb->update($reactions_table, array('reaction' => $reaction), array('id' => $existing->id), array('%s'), array('%d'));
            }
        } else {
            // Yeni tepki ekle
            $wpdb->insert($reactions_table, array(
                'post_id' => $post_id,
                'user_id' => $user_id,
                'reaction' => $reaction
            ), array('%d', '%d', '%s'));
        }
        
        // Guncel sayilari al
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
            wp_send_json_error(array('message' => 'Yorum bulunamadi.'));
        }
        
        // Kendi yorumunu begenemez
        if ($comment->user_id == $user_id) {
            wp_send_json_error(array('message' => 'Kendi yorumunuzu begenemezsiniz.'));
        }
        
        $likes = intval(get_comment_meta($comment_id, '_likes', true));
        $user_vote = get_comment_meta($comment_id, '_user_vote_' . $user_id, true);
        
        if ($user_vote == 'liked') {
            // Begeniyi kaldir
            update_comment_meta($comment_id, '_likes', max(0, $likes - 1));
            delete_comment_meta($comment_id, '_user_vote_' . $user_id);
            $new_user_vote = '';
        } else {
            // Begeni ekle
            update_comment_meta($comment_id, '_likes', $likes + 1);
            update_comment_meta($comment_id, '_user_vote_' . $user_id, 'liked');
            $new_user_vote = 'liked';
        }
        
        $new_likes = intval(get_comment_meta($comment_id, '_likes', true));
        
        wp_send_json_success(array(
            'likes' => $new_likes,
            'dislikes' => 0,
            'user_vote' => $new_user_vote
        ));
    }

    // YORUM DUZENLEME
    public function edit_comment_callback() {
        $this->verify_nonce();
        $this->require_login();
        
        $comment_id = intval($_POST['comment_id']);
        $content = trim(sanitize_textarea_field($_POST['content']));
        $user_id = get_current_user_id();
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => 'Yorum bulunamadi.'));
        }
        
        // Sadece kendi yorumunu duzenleyebilir (veya admin)
        if ($comment->user_id != $user_id && !current_user_can('moderate_comments')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok.'));
        }
        
        if (empty($content)) {
            wp_send_json_error(array('message' => 'Icerik bos olamaz.'));
        }
        
        if (strlen($content) > 5000) {
            wp_send_json_error(array('message' => 'Yorum cok uzun.'));
        }
        
        $result = wp_update_comment(array(
            'comment_ID' => $comment_id,
            'comment_content' => wp_kses_post($content)
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => 'Guncelleme basarisiz.'));
        }
        
        wp_send_json_success(array(
            'content' => $this->process_comment_formatting(wp_kses_post($content)),
            'message' => 'Yorum guncellendi.'
        ));
    }

    // YORUM SILME
    public function delete_comment_callback() {
        $this->verify_nonce();
        $this->require_login();
        
        $comment_id = intval($_POST['comment_id']);
        $user_id = get_current_user_id();
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => 'Yorum bulunamadi.'));
        }
        
        // Sadece kendi yorumunu silebilir (veya admin)
        if ($comment->user_id != $user_id && !current_user_can('moderate_comments')) {
            wp_send_json_error(array('message' => 'Yetkiniz yok.'));
        }
        
        $result = wp_trash_comment($comment_id);
        
        if (!$result) {
            wp_send_json_error(array('message' => 'Silme basarisiz.'));
        }
        
        wp_send_json_success(array('message' => 'Yorum silindi.'));
    }

    // YANITLARI YUKLE
    public function load_replies_callback() {
        $this->verify_nonce();
        
        $parent_id = intval($_POST['parent_id']);
        
        if ($parent_id <= 0) {
            wp_send_json_error(array('message' => 'Gecersiz yorum ID.'));
        }
        
        $replies = get_comments(array(
            'parent' => $parent_id,
            'status' => 'approve',
            'orderby' => 'comment_date_gmt',
            'order' => 'ASC',
            'number' => 50 // Maksimum 50 yanit
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
        global $wpdb;
        $post_id = intval($_POST['post_id']);
        $reactions_table = $wpdb->prefix . 'ruh_reactions';
        
        // Tepki sayilari
        $counts = $wpdb->get_results($wpdb->prepare(
            "SELECT reaction, COUNT(id) as count FROM $reactions_table WHERE post_id = %d GROUP BY reaction",
            $post_id
        ), OBJECT_K);
        
        // Kullanici tepkisi
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
        
        $comment_id = intval($_POST['comment_id']);
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        $user_id = get_current_user_id();
        
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => 'Yorum bulunamadi.'));
        }
        
        // Kendi yorumunu sikayat edemez
        if ($comment->user_id == $user_id) {
            wp_send_json_error(array('message' => 'Kendi yorumunuzu sikayet edemezsiniz.'));
        }
        
        global $wpdb;
        $reports_table = $wpdb->prefix . 'ruh_reports';
        
        // Daha once sikayet etmis mi?
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $reports_table WHERE comment_id = %d AND reporter_id = %d",
            $comment_id, $user_id
        ));
        
        if ($existing) {
            wp_send_json_error(array('message' => 'Bu yorumu zaten sikayet ettiniz.'));
        }
        
        // Sikayet kaydet
        $wpdb->insert($reports_table, array(
            'comment_id' => $comment_id,
            'reporter_id' => $user_id,
            'reason' => $reason
        ), array('%d', '%d', '%s'));
        
        // Sikayet sayisini kontrol et - otomatik moderasyon
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
            'message' => 'Sikayetiniz alindi.',
            'hidden' => $hidden,
            'comment_id' => $comment_id
        ));
    }
}

new Ruh_Comment_Ajax_Handlers();
