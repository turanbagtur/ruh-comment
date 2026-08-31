<?php
if (!defined('ABSPATH')) exit;

function ruh_flush_comment_list_cache($post_id) {
    $post_id = intval($post_id);
    if (!$post_id) return;
    $bump = intval(wp_cache_get('ruh_clist_bump_' . $post_id, 'ruh_comment'));
    wp_cache_set('ruh_clist_bump_' . $post_id, $bump + 1, 'ruh_comment', DAY_IN_SECONDS);
    wp_cache_delete('ruh_highlights_' . $post_id, 'ruh_comment');
}

function ruh_comment_list_cache_key($post_id, $page, $sort, $parent, $search, $author) {
    $bump = intval(wp_cache_get('ruh_clist_bump_' . intval($post_id), 'ruh_comment'));
    return 'ruh_clist_' . md5(implode('|', array($post_id, $page, $sort, $parent, $search, $author, $bump, get_current_user_id())));
}

function ruh_add_notification($user_id, $type, $args = array()) {
    $user_id = intval($user_id);
    if ($user_id <= 0) return false;
    $options = get_option('ruh_comment_options', array());
    if (empty($options['enable_notifications'])) return false;
    if (!empty($args['actor_id']) && intval($args['actor_id']) === $user_id) return false;

    global $wpdb;
    return (bool) $wpdb->insert(
        $wpdb->prefix . 'ruh_notifications',
        array(
            'user_id' => $user_id,
            'type' => sanitize_key($type),
            'actor_id' => intval($args['actor_id'] ?? 0),
            'comment_id' => intval($args['comment_id'] ?? 0),
            'post_id' => intval($args['post_id'] ?? 0),
            'message' => sanitize_text_field($args['message'] ?? ''),
            'is_read' => 0,
        ),
        array('%d', '%s', '%d', '%d', '%d', '%s', '%d')
    );
}

function ruh_notify_reply($comment_id, $comment) {
    if (!$comment || empty($comment->comment_parent) || $comment->comment_approved != 1) return;
    $parent = get_comment($comment->comment_parent);
    if (!$parent || !$parent->user_id) return;
    $actor = $comment->user_id ? get_userdata($comment->user_id) : null;
    $name = $actor ? $actor->display_name : ($comment->comment_author ?: 'Birisi');
    ruh_add_notification($parent->user_id, 'reply', array(
        'actor_id' => intval($comment->user_id),
        'comment_id' => intval($comment_id),
        'post_id' => intval($comment->comment_post_ID),
        'message' => sprintf('%s yorumunuza yanıt verdi', $name),
    ));
}

function ruh_send_webhooks($event, $payload) {
    $options = get_option('ruh_comment_options', array());
    $title = isset($payload['title']) ? $payload['title'] : '';
    $author = isset($payload['author']) ? $payload['author'] : '';
    $excerpt = isset($payload['excerpt']) ? $payload['excerpt'] : '';
    $link = isset($payload['link']) ? $payload['link'] : '';
    $text = sprintf('[%s] %s: %s %s', $event, $author, $excerpt, $link);

    $discord = isset($options['discord_webhook_url']) ? esc_url_raw($options['discord_webhook_url']) : '';
    if ($discord && strpos($discord, 'https://') === 0) {
        wp_remote_post($discord, array(
            'timeout' => 4,
            'blocking' => false,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode(array(
                'content' => $text,
                'username' => 'Ruh Comment',
            )),
        ));
    }

    $token = isset($options['telegram_bot_token']) ? sanitize_text_field($options['telegram_bot_token']) : '';
    $chat = isset($options['telegram_chat_id']) ? sanitize_text_field($options['telegram_chat_id']) : '';
    if ($token && $chat) {
        wp_remote_post('https://api.telegram.org/bot' . rawurlencode($token) . '/sendMessage', array(
            'timeout' => 4,
            'blocking' => false,
            'body' => array(
                'chat_id' => $chat,
                'text' => $text,
                'disable_web_page_preview' => true,
            ),
        ));
    }
}

function ruh_community_on_comment($comment_id, $comment = null) {
    if (!$comment) $comment = get_comment($comment_id);
    if (!$comment) return;
    ruh_flush_comment_list_cache($comment->comment_post_ID);
    if ($comment->comment_approved != 1) return;
    ruh_notify_reply($comment_id, $comment);
    ruh_send_webhooks('comment', array(
        'title' => get_the_title($comment->comment_post_ID),
        'author' => $comment->comment_author,
        'excerpt' => wp_trim_words(wp_strip_all_tags($comment->comment_content), 18),
        'link' => get_comment_link($comment_id),
    ));
}
add_action('wp_insert_comment', 'ruh_community_on_comment', 20, 2);
add_action('deleted_comment', function ($comment_id, $comment) {
    if ($comment) ruh_flush_comment_list_cache($comment->comment_post_ID);
}, 10, 2);
add_action('wp_set_comment_status', function ($comment_id) {
    $comment = get_comment($comment_id);
    if ($comment) ruh_flush_comment_list_cache($comment->comment_post_ID);
});

function ruh_get_highlight_comments($post_id, $limit = 3) {
    $post_id = intval($post_id);
    $cache_key = 'ruh_highlights_' . $post_id;
    $cached = wp_cache_get($cache_key, 'ruh_comment');
    if ($cached !== false) return $cached;

    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT c.comment_ID, c.comment_author, c.comment_content, c.user_id,
                CAST(COALESCE(cm.meta_value, 0) AS UNSIGNED) as likes
         FROM {$wpdb->comments} c
         LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = '_likes'
         WHERE c.comment_post_ID = %d AND c.comment_approved = '1' AND c.comment_parent = 0
         ORDER BY likes DESC, c.comment_date_gmt DESC
         LIMIT %d",
        $post_id,
        $limit
    ));
    $out = array();
    foreach ((array) $rows as $row) {
        if (intval($row->likes) < 1) continue;
        $out[] = $row;
    }
    wp_cache_set($cache_key, $out, 'ruh_comment', 300);
    return $out;
}

function ruh_notifications_ajax() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'Giriş gerekli.'));
    }
    check_ajax_referer('ruh-comment-nonce', 'nonce');
    global $wpdb;
    $table = $wpdb->prefix . 'ruh_notifications';
    $user_id = get_current_user_id();
    $mark = isset($_POST['mark_read']) ? intval($_POST['mark_read']) : 0;
    if ($mark) {
        $wpdb->update($table, array('is_read' => 1), array('user_id' => $user_id), array('%d'), array('%d'));
        wp_send_json_success(array('ok' => true));
    }
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table WHERE user_id = %d ORDER BY id DESC LIMIT 20",
        $user_id
    ));
    $unread = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_id = %d AND is_read = 0", $user_id));
    $items = array();
    foreach ((array) $rows as $row) {
        $link = $row->comment_id ? get_comment_link($row->comment_id) : '';
        $items[] = array(
            'id' => intval($row->id),
            'type' => $row->type,
            'message' => $row->message,
            'is_read' => intval($row->is_read),
            'link' => $link,
            'time' => human_time_diff(strtotime($row->created_at), current_time('timestamp')) . ' önce',
        );
    }
    wp_send_json_success(array('items' => $items, 'unread' => $unread));
}
add_action('wp_ajax_ruh_get_notifications', 'ruh_notifications_ajax');
