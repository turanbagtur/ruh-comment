<?php
if (!defined('ABSPATH')) exit;

class Ruh_Comment_Ajax_Handlers {
    
    public function __construct() {
        $actions = array(
            'get_initial_data', 'handle_reaction', 'get_comments', 
            'handle_like', 'flag_comment', 'submit_comment', 
            'admin_edit_comment', 'load_more_replies'
        );
        
        foreach ($actions as $action) {
            $is_nopriv = !in_array($action, array('flag_comment', 'submit_comment', 'admin_edit_comment'));
            add_action('wp_ajax_ruh_' . $action, array($this, $action . '_callback'));
            if ($is_nopriv) {
                add_action('wp_ajax_nopriv_ruh_' . $action, array($this, $action . '_callback'));
            }
        }
    }

    /**
     * Güvenli nonce kontrolü
     */
    private function verify_nonce($action = 'ruh-comment-nonce') {
        if (!check_ajax_referer($action, 'nonce', false)) {
            wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız.'));
        }
    }

    /**
     * Kullanıcı oturum kontrolü
     */
    private function require_login() {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Bu işlem için giriş yapmalısınız.'));
        }
    }

    /**
     * Yorum HTML'ini oluşturan yardımcı fonksiyon
     */
    private function generate_comment_html($comment) {
        if (is_numeric($comment)) {
            $comment = get_comment($comment);
        }
        
        if (!$comment) {
            return '';
        }
        
        // Global comment değişkenini ayarla
        $GLOBALS['comment'] = $comment;
        
        // HTML çıktısını yakala
        ob_start();
        if (function_exists('ruh_comment_format')) {
            ruh_comment_format($comment, array(
                'max_depth' => get_option('thread_comments_depth', 5)
            ), 1);
        } else {
            echo '<li class="ruh-comment-item">Yorum: ' . esc_html($comment->comment_content) . '</li>';
        }
        $html = ob_get_clean();
        
        return $html;
    }

    /**
     * Yorumları getir - geliştirilmiş sıralama ve sayfalama ile
     */
    public function get_comments_callback() {
        $this->verify_nonce();
        
        $post_id = intval($_POST['post_id']);
        $page = intval($_POST['page']);
        $sort = sanitize_key($_POST['sort']);
        $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;
        $comments_per_page = get_option('comments_per_page', 10);

        // Temel sorgu parametreleri
        $args = array(
            'post_id' => $post_id,
            'status' => 'approve',
            'number' => $comments_per_page,
            'offset' => ($page - 1) * $comments_per_page,
            'parent' => $parent_id,
            'hierarchical' => false
        );
        
        // Sıralama seçenekleri
        switch($sort) {
            case 'oldest':
                $args['orderby'] = 'comment_date_gmt';
                $args['order'] = 'ASC';
                break;
                
            case 'best':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_likes';
                $args['order'] = 'DESC';
                break;
                
            case 'most_replied':
                // En çok yanıtlanan yorumlar
                $args['orderby'] = 'comment_date_gmt';
                $args['order'] = 'DESC';
                break;
                
            case 'newest':
            default:
                $args['orderby'] = 'comment_date_gmt';
                $args['order'] = 'DESC';
                break;
        }

        $comments = get_comments($args);
        
        if (empty($comments)) {
            wp_send_json_success(array(
                'html' => '',
                'has_more' => false,
                'total' => 0
            ));
        }

        $html = '';
        foreach($comments as $comment) {
            $html .= $this->generate_comment_html($comment);
        }
        
        // Daha fazla yorum var mı kontrolü
        $next_page_args = $args;
        $next_page_args['offset'] = $page * $comments_per_page;
        $next_page_args['number'] = 1;
        $has_more = !empty(get_comments($next_page_args));
        
        wp_send_json_success(array(
            'html' => $html,
            'has_more' => $has_more,
            'total' => count($comments)
        ));
    }
    
    /**
     * Alt yorumları yükle
     */
    public function load_more_replies_callback() {
        $this->verify_nonce();
        
        $parent_id = intval($_POST['parent_id']);
        $page = intval($_POST['page']);
        $replies_per_page = 5; // Alt yorumlar için daha az

        $args = array(
            'parent' => $parent_id,
            'status' => 'approve',
            'number' => $replies_per_page,
            'offset' => ($page - 1) * $replies_per_page,
            'orderby' => 'comment_date_gmt',
            'order' => 'ASC'
        );

        $replies = get_comments($args);
        
        $html = '';
        foreach($replies as $reply) {
            $html .= $this->generate_comment_html($reply);
        }
        
        // Daha fazla alt yorum var mı?
        $next_args = $args;
        $next_args['offset'] = $page * $replies_per_page;
        $next_args['number'] = 1;
        $has_more = !empty(get_comments($next_args));
        
        wp_send_json_success(array(
            'html' => $html,
            'has_more' => $has_more
        ));
    }
    
    /**
     * İlk veri yükleme - tepkiler ve istatistikler
     */
    public function get_initial_data_callback() {
        $this->verify_nonce();
        
        global $wpdb;
        $post_id = intval($_POST['post_id']);
        $reactions_table = $wpdb->prefix . 'ruh_reactions';
        
        // Tepki sayılarını al
        $counts = $wpdb->get_results($wpdb->prepare(
            "SELECT reaction, COUNT(id) as count FROM $reactions_table WHERE post_id = %d GROUP BY reaction",
            $post_id
        ), OBJECT_K);
        
        // Kullanıcının tepkisini al
        $user_reaction = null;
        if (is_user_logged_in()) {
            $user_reaction = $wpdb->get_var($wpdb->prepare(
                "SELECT reaction FROM $reactions_table WHERE post_id = %d AND user_id = %d",
                $post_id,
                get_current_user_id()
            ));
        }
        
        // Yorum istatistikleri
        $total_comments = wp_count_comments($post_id);
        
        wp_send_json_success(array(
            'counts' => $counts,
            'user_reaction' => $user_reaction,
            'comment_stats' => array(
                'approved' => $total_comments->approved,
                'moderated' => $total_comments->moderated,
                'total' => $total_comments->total_comments
            )
        ));
    }

    /**
     * Tepki işleme - geliştirilmiş
     */
    public function handle_reaction_callback() {
        $this->verify_nonce();
        $this->require_login();
        
        global $wpdb;
        $post_id = intval($_POST['post_id']);
        $reaction = sanitize_key($_POST['reaction']);
        $user_id = get_current_user_id();
        $reactions_table = $wpdb->prefix . 'ruh_reactions';
        
        // Geçerli tepki tipleri
        $valid_reactions = array('guzel', 'sevdim', 'asik_oldum', 'sasirtici', 'gaza_geldim', 'uzucu');
        if (!in_array($reaction, $valid_reactions)) {
            wp_send_json_error(array('message' => 'Geçersiz tepki türü.'));
        }
        
        // Mevcut tepkiyi kontrol et
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, reaction FROM $reactions_table WHERE post_id = %d AND user_id = %d",
            $post_id,
            $user_id
        ));

        if ($existing) {
            if ($existing->reaction == $reaction) {
                // Aynı tepki - kaldır
                $result = $wpdb->delete($reactions_table, array('id' => $existing->id));
            } else {
                // Farklı tepki - güncelle
                $result = $wpdb->update(
                    $reactions_table,
                    array('reaction' => $reaction),
                    array('id' => $existing->id)
                );
            }
        } else {
            // Yeni tepki ekle
            $result = $wpdb->insert($reactions_table, array(
                'post_id' => $post_id,
                'user_id' => $user_id,
                'reaction' => $reaction
            ));
        }
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Tepki kaydedilemedi.'));
        }

        // Güncel sayıları al ve döndür
        $counts = $wpdb->get_results($wpdb->prepare(
            "SELECT reaction, COUNT(id) as count FROM $reactions_table WHERE post_id = %d GROUP BY reaction",
            $post_id
        ), OBJECT_K);
        
        wp_send_json_success(array('counts' => $counts));
    }

    /**
     * Beğeni/beğenmeme işleme - geliştirilmiş
     */
    public function handle_like_callback() {
        $this->verify_nonce();
        $this->require_login();
        
        $comment_id = intval($_POST['comment_id']);
        $type = sanitize_key($_POST['type']);
        $user_id = get_current_user_id();
        
        if (!in_array($type, array('like', 'dislike'))) {
            wp_send_json_error(array('message' => 'Geçersiz işlem türü.'));
        }
        
        // Yorumun var olduğunu kontrol et
        $comment = get_comment($comment_id);
        if (!$comment || $comment->comment_approved !== '1') {
            wp_send_json_error(array('message' => 'Yorum bulunamadı.'));
        }
        
        // Kullanıcı kendi yorumunu beğenmeye çalışıyor mu?
        if ($comment->user_id == $user_id) {
            wp_send_json_error(array('message' => 'Kendi yorumunuzu beğenemezsiniz.'));
        }
        
        // Mevcut oyları al
        $likes = intval(get_comment_meta($comment_id, '_likes', true));
        $dislikes = intval(get_comment_meta($comment_id, '_dislikes', true));
        $user_vote = get_comment_meta($comment_id, '_user_vote_' . $user_id, true);

        if ($type == 'like') {
            if ($user_vote == 'liked') {
                // Beğeniyi kaldır
                update_comment_meta($comment_id, '_likes', max(0, $likes - 1));
                delete_comment_meta($comment_id, '_user_vote_' . $user_id);
                $new_user_vote = '';
            } else {
                // Beğeni ekle
                update_comment_meta($comment_id, '_likes', $likes + 1);
                if ($user_vote == 'disliked') {
                    update_comment_meta($comment_id, '_dislikes', max(0, $dislikes - 1));
                }
                update_comment_meta($comment_id, '_user_vote_' . $user_id, 'liked');
                $new_user_vote = 'liked';
            }
        } elseif ($type == 'dislike') {
            if ($user_vote == 'disliked') {
                // Beğenmemeyi kaldır
                update_comment_meta($comment_id, '_dislikes', max(0, $dislikes - 1));
                delete_comment_meta($comment_id, '_user_vote_' . $user_id);
                $new_user_vote = '';
            } else {
                // Beğenmeme ekle
                update_comment_meta($comment_id, '_dislikes', $dislikes + 1);
                if ($user_vote == 'liked') {
                    update_comment_meta($comment_id, '_likes', max(0, $likes - 1));
                }
                update_comment_meta($comment_id, '_user_vote_' . $user_id, 'disliked');
                $new_user_vote = 'disliked';
            }
        }
        
        // Güncel değerleri döndür
        $new_likes = intval(get_comment_meta($comment_id, '_likes', true));
        $new_dislikes = intval(get_comment_meta($comment_id, '_dislikes', true));
        
        wp_send_json_success(array(
            'likes' => $new_likes,
            'dislikes' => $new_dislikes,
            'user_vote' => $new_user_vote
        ));
    }

    /**
     * Şikayet etme - geliştirilmiş
     */
    public function flag_comment_callback() {
        $this->verify_nonce();
        $this->require_login();
        
        global $wpdb;
        $comment_id = intval($_POST['comment_id']);
        $reporter_id = get_current_user_id();
        $reports_table = $wpdb->prefix . 'ruh_reports';
        
        // Yorumun var olduğunu kontrol et
        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(array('message' => 'Yorum bulunamadı.'));
        }
        
        // Kendi yorumunu şikayet etmeyi engelle
        if ($comment->user_id == $reporter_id) {
            wp_send_json_error(array('message' => 'Kendi yorumunuzu şikayet edemezsiniz.'));
        }
        
        // Daha önce şikayet edilmiş mi kontrol et
        $existing_report = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $reports_table WHERE comment_id = %d AND reporter_id = %d",
            $comment_id,
            $reporter_id
        ));
        
        if ($existing_report > 0) {
            wp_send_json_error(array('message' => 'Bu yorumu zaten şikayet ettiniz.'));
        }
        
        // Şikayeti kaydet
        $result = $wpdb->insert($reports_table, array(
            'comment_id' => $comment_id,
            'reporter_id' => $reporter_id,
            'report_time' => current_time('mysql', 1)
        ));
        
        if ($result === false) {
            wp_send_json_error(array('message' => 'Şikayet kaydedilemedi.'));
        }
        
        // Toplam şikayet sayısı
        $total_reports = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $reports_table WHERE comment_id = %d",
            $comment_id
        ));
        
        // Eğer şikayet sayısı belirli bir eşiği geçerse otomatik moderasyona al
        $options = get_option('ruh_comment_options', array());
        $report_threshold = isset($options['auto_moderate_reports']) ? $options['auto_moderate_reports'] : 3;
        
        if ($total_reports >= $report_threshold) {
            wp_set_comment_status($comment_id, 'hold');
        }
        
        wp_send_json_success(array(
            'message' => 'Şikayetiniz alındı. Teşekkür ederiz.',
            'total_reports' => $total_reports
        ));
    }
    
    /**
     * Yorum gönderme - geliştirilmiş güvenlik ve doğrulama
     */
    public function submit_comment_callback() {
        $this->verify_nonce();
        $this->require_login();
        
        // Kullanıcı engelli mi kontrol et
        $user_id = get_current_user_id();
        if (function_exists('ruh_is_user_banned') && ruh_is_user_banned($user_id)) {
            wp_send_json_error(array('message' => 'Yorum gönderme yetkiniz bulunmuyor.'));
        }
        
        // Form verilerini temizle ve doğrula
        $comment_data = array(
            'comment_post_ID' => intval($_POST['comment_post_ID']),
            'comment_content' => trim($_POST['comment']),
            'comment_parent' => intval($_POST['comment_parent']),
            'user_id' => $user_id
        );
        
        // Post ID kontrolü
        $post = get_post($comment_data['comment_post_ID']);
        if (!$post || !comments_open($post->ID)) {
            wp_send_json_error(array('message' => 'Bu yazı için yorumlar kapalı.'));
        }
        
        // İçerik kontrolü
        if (empty($comment_data['comment_content'])) {
            wp_send_json_error(array('message' => 'Yorum içeriği boş olamaz.'));
        }
        
        if (strlen($comment_data['comment_content']) > 5000) {
            wp_send_json_error(array('message' => 'Yorum çok uzun. Maksimum 5000 karakter.'));
        }
        
        // Kullanıcı bilgilerini ekle
        $user = wp_get_current_user();
        $comment_data = array_merge($comment_data, array(
            'comment_author' => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_author_url' => $user->user_url,
            'comment_type' => '',
            'comment_meta' => array()
        ));
        
        // Parent comment kontrolü
        if ($comment_data['comment_parent'] > 0) {
            $parent_comment = get_comment($comment_data['comment_parent']);
            if (!$parent_comment || $parent_comment->comment_post_ID != $comment_data['comment_post_ID']) {
                wp_send_json_error(array('message' => 'Geçersiz üst yorum.'));
            }
        }
        
        // Spam ve güvenlik kontrollerini uygula
        $comment_data = apply_filters('preprocess_comment', $comment_data);
        
        // Yorumu al ve onay durumunu kontrol et
        $comment = get_comment($comment_id);
        
        // Otomatik onay (eğer ayarlar izin veriyorsa)
        if ($comment->comment_approved == '0' && !get_option('comment_moderation')) {
            wp_set_comment_status($comment_id, 'approve');
            $comment = get_comment($comment_id); // Güncel hali
        }
        
        // HTML çıktısını oluştur
        $html = '';
        if ($comment->comment_approved == '1') {
            $html = $this->generate_comment_html($comment);
        }
        
        wp_send_json_success(array(
            'html' => $html,
            'comment_id' => $comment_id,
            'parent_id' => $comment->comment_parent,
            'approved' => $comment->comment_approved == '1',
            'message' => $comment->comment_approved == '1' 
                ? 'Yorumunuz başarıyla gönderildi.'
                : 'Yorumunuz onay bekliyor.'
        ));
    }
    
    /**
     * Admin yorum düzenleme
     */
    public function admin_edit_comment_callback() {
        // Admin yetkisi kontrolü
        if (!current_user_can('moderate_comments')) {
            wp_send_json_error(array('message' => 'Yetkiniz bulunmuyor.'));
        }
        
        // Özel nonce kontrolü
        if (!check_ajax_referer('ruh_admin_edit_comment', '_ajax_nonce', false)) {
            wp_send_json_error(array('message' => 'Güvenlik kontrolü başarısız.'));
        }
        
        $comment_id = intval($_POST['comment_id']);
        $content = trim($_POST['content']);
        
        if (empty($content)) {
            wp_send_json_error(array('message' => 'Yorum içeriği boş olamaz.'));
        }
        
        // Yorumu güncelle
        $result = wp_update_comment(array(
            'comment_ID' => $comment_id,
            'comment_content' => wp_kses_post($content)
        ));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Başarı
        wp_send_json_success(array(
            'content' => wp_trim_words(esc_html($content), 50),
            'message' => 'Yorum başarıyla güncellendi.'
        ));
    }
}

// Sınıfı başlat
new Ruh_Comment_Ajax_Handlers();