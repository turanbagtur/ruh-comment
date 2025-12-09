<?php
/**
 * WordPress REST API Entegrasyonu - Guvenlik ve Performans Iyilestirilmis
 * 
 * @package RuhComment
 * @version 5.1.1
 */

if (!defined('ABSPATH')) exit;

class Ruh_REST_API {
    
    private $namespace = 'ruh-comment/v1';
    
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * REST route'lari kaydet
     */
    public function register_routes() {
        // Yorumlari getir
        register_rest_route($this->namespace, '/comments/(?P<post_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_comments'),
            'permission_callback' => '__return_true',
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint'
                ),
                'page' => array(
                    'default' => 1,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return $param > 0 && $param < 1000;
                    }
                ),
                'per_page' => array(
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return $param > 0 && $param <= 50;
                    }
                )
            )
        ));
        
        // Tek yorum getir
        register_rest_route($this->namespace, '/comment/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_comment'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        // Yorum gonder
        register_rest_route($this->namespace, '/comment', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_comment'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'sanitize_callback' => 'absint'
                ),
                'content' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'validate_callback' => function($param) {
                        $len = strlen(trim($param));
                        return $len >= 3 && $len <= 5000;
                    }
                ),
                'parent' => array(
                    'default' => 0,
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        // Kullanici istatistikleri
        register_rest_route($this->namespace, '/user/(?P<id>\d+)/stats', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_user_stats'),
            'permission_callback' => '__return_true',
            'args' => array(
                'id' => array(
                    'required' => true,
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        // Tepkiler
        register_rest_route($this->namespace, '/reactions/(?P<post_id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_reactions'),
            'permission_callback' => '__return_true',
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        // Leaderboard (en aktif kullanicilar)
        register_rest_route($this->namespace, '/leaderboard', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_leaderboard'),
            'permission_callback' => '__return_true',
            'args' => array(
                'limit' => array(
                    'default' => 10,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return $param > 0 && $param <= 100;
                    }
                )
            )
        ));
    }
    
    /**
     * Yorumlari getir - Cache ile
     */
    public function get_comments($request) {
        $post_id = $request['post_id'];
        $page = $request['page'];
        $per_page = min(50, max(1, $request['per_page']));
        
        // Cache kontrolu
        $cache_key = 'ruh_api_comments_' . $post_id . '_' . $page . '_' . $per_page;
        $cached = wp_cache_get($cache_key, 'ruh_comment');
        
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }
        
        $comments = get_comments(array(
            'post_id' => $post_id,
            'status' => 'approve',
            'number' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'orderby' => 'comment_date_gmt',
            'order' => 'DESC'
        ));
        
        $data = array();
        foreach ($comments as $comment) {
            $data[] = $this->format_comment($comment);
        }
        
        $total = get_comments(array(
            'post_id' => $post_id,
            'status' => 'approve',
            'count' => true
        ));
        
        $response = array(
            'comments' => $data,
            'total' => $total,
            'pages' => ceil($total / $per_page)
        );
        
        // 5 dakika cache
        wp_cache_set($cache_key, $response, 'ruh_comment', 300);
        
        return new WP_REST_Response($response, 200);
    }
    
    /**
     * Tek yorum getir
     */
    public function get_comment($request) {
        $comment = get_comment($request['id']);
        
        if (!$comment || $comment->comment_approved != 1) {
            return new WP_Error('not_found', 'Yorum bulunamadi.', array('status' => 404));
        }
        
        return new WP_REST_Response($this->format_comment($comment), 200);
    }
    
    /**
     * Yorum olustur - Rate limiting ile
     */
    public function create_comment($request) {
        $user_id = get_current_user_id();
        
        // Rate limiting
        $rate_key = 'ruh_api_rate_' . $user_id;
        $rate_count = get_transient($rate_key) ?: 0;
        
        if ($rate_count >= 10) { // 10 yorum/dakika
            return new WP_Error('rate_limit', 'Cok hizli islem yapiyorsunuz.', array('status' => 429));
        }
        
        set_transient($rate_key, $rate_count + 1, 60);
        
        // Ban kontrolu
        $ban_status = get_user_meta($user_id, 'ruh_ban_status', true);
        if ($ban_status === 'banned') {
            return new WP_Error('forbidden', 'Yorum yapma yetkiniz yok.', array('status' => 403));
        }
        
        $post_id = $request['post_id'];
        $content = trim($request['content']);
        $parent = $request['parent'];
        
        // Post var mi kontrol
        if (!get_post($post_id)) {
            return new WP_Error('not_found', 'Yazi bulunamadi.', array('status' => 404));
        }
        
        // Parent yorum varsa kontrol et
        if ($parent > 0) {
            $parent_comment = get_comment($parent);
            if (!$parent_comment || $parent_comment->comment_approved != 1) {
                return new WP_Error('not_found', 'Yanit verilen yorum bulunamadi.', array('status' => 404));
            }
        }
        
        $user = wp_get_current_user();
        
        $comment_data = array(
            'comment_post_ID' => $post_id,
            'comment_content' => wp_kses_post($content),
            'comment_parent' => $parent,
            'user_id' => $user_id,
            'comment_author' => $user->display_name,
            'comment_author_email' => $user->user_email,
            'comment_approved' => 1
        );
        
        $comment_id = wp_insert_comment($comment_data);
        
        if (is_wp_error($comment_id) || !$comment_id) {
            return new WP_Error('create_failed', 'Yorum olusturulamadi.', array('status' => 500));
        }
        
        // Cache temizle
        wp_cache_delete('ruh_api_comments_' . $post_id . '_1_10', 'ruh_comment');
        
        $comment = get_comment($comment_id);
        
        return new WP_REST_Response($this->format_comment($comment), 201);
    }
    
    /**
     * Kullanici istatistikleri - Cache ile
     */
    public function get_user_stats($request) {
        $user_id = $request['id'];
        
        if (!get_userdata($user_id)) {
            return new WP_Error('not_found', 'Kullanici bulunamadi.', array('status' => 404));
        }
        
        // Cache kontrolu
        $cache_key = 'ruh_api_user_stats_' . $user_id;
        $cached = wp_cache_get($cache_key, 'ruh_comment');
        
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }
        
        $stats = function_exists('ruh_get_user_stats') ? ruh_get_user_stats($user_id) : array();
        $level_info = function_exists('ruh_get_user_level_info') ? ruh_get_user_level_info($user_id) : (object)array('level' => 1, 'xp' => 0);
        $badges = function_exists('ruh_get_user_badges') ? ruh_get_user_badges($user_id) : array();
        
        $response = array(
            'user_id' => $user_id,
            'level' => $level_info->level,
            'xp' => $level_info->xp,
            'stats' => $stats,
            'badges' => array_map(function($badge) {
                return array(
                    'id' => $badge->badge_id,
                    'name' => $badge->badge_name
                );
            }, $badges)
        );
        
        // 10 dakika cache
        wp_cache_set($cache_key, $response, 'ruh_comment', 600);
        
        return new WP_REST_Response($response, 200);
    }
    
    /**
     * Tepkileri getir - Cache ile
     */
    public function get_reactions($request) {
        global $wpdb;
        $post_id = $request['post_id'];
        
        // Cache kontrolu
        $cache_key = 'ruh_api_reactions_' . $post_id;
        $cached = wp_cache_get($cache_key, 'ruh_comment');
        
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }
        
        $reactions_table = $wpdb->prefix . 'ruh_reactions';
        
        $counts = $wpdb->get_results($wpdb->prepare(
            "SELECT reaction, COUNT(id) as count FROM $reactions_table WHERE post_id = %d GROUP BY reaction",
            $post_id
        ), OBJECT_K);
        
        $total = 0;
        foreach ($counts as $r) {
            $total += intval($r->count);
        }
        
        $response = array(
            'reactions' => $counts,
            'total' => $total
        );
        
        // 5 dakika cache
        wp_cache_set($cache_key, $response, 'ruh_comment', 300);
        
        return new WP_REST_Response($response, 200);
    }
    
    /**
     * Leaderboard (en aktif kullanicilar) - Cache ile
     */
    public function get_leaderboard($request) {
        $limit = min(100, max(1, $request['limit']));
        
        // Cache kontrolu
        $cache_key = 'ruh_api_leaderboard_' . $limit;
        $cached = wp_cache_get($cache_key, 'ruh_comment');
        
        if ($cached !== false) {
            return new WP_REST_Response($cached, 200);
        }
        
        $users = function_exists('ruh_get_top_users') ? ruh_get_top_users($limit) : array();
        
        $leaderboard = array();
        foreach ($users as $user) {
            $leaderboard[] = array(
                'user_id' => $user->ID,
                'display_name' => $user->display_name,
                'level' => isset($user->level) ? $user->level : 1,
                'xp' => isset($user->xp) ? $user->xp : 0,
                'comment_count' => isset($user->comment_count) ? $user->comment_count : 0,
                'total_likes' => isset($user->total_likes) ? $user->total_likes : 0,
                'avatar' => get_avatar_url($user->ID, array('size' => 64))
            );
        }
        
        $response = array('leaderboard' => $leaderboard);
        
        // 30 dakika cache
        wp_cache_set($cache_key, $response, 'ruh_comment', 1800);
        
        return new WP_REST_Response($response, 200);
    }
    
    /**
     * Yorum formatla - XSS koruması ile
     */
    private function format_comment($comment) {
        $likes = intval(get_comment_meta($comment->comment_ID, '_likes', true) ?: 0);
        $dislikes = intval(get_comment_meta($comment->comment_ID, '_dislikes', true) ?: 0);
        
        return array(
            'id' => intval($comment->comment_ID),
            'post_id' => intval($comment->comment_post_ID),
            'parent_id' => intval($comment->comment_parent),
            'author' => array(
                'id' => intval($comment->user_id),
                'name' => esc_html($comment->comment_author),
                'avatar' => get_avatar_url($comment->comment_author_email, array('size' => 64))
            ),
            'content' => wp_kses_post($comment->comment_content),
            'date' => $comment->comment_date,
            'date_gmt' => $comment->comment_date_gmt,
            'likes' => $likes,
            'dislikes' => $dislikes,
            'score' => $likes - $dislikes
        );
    }
    
    /**
     * Izin kontrolu
     */
    public function check_permission() {
        return is_user_logged_in();
    }
}

new Ruh_REST_API();
