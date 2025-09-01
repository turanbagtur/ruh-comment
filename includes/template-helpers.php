<?php
if (!defined('ABSPATH')) exit;

/**
 * Yorum HTML'ini oluşturan ana callback fonksiyonu - TÜM ÖZELLİKLER İLE
 */
function ruh_comment_format($comment, $args, $depth) {
    if (!$comment) return '';
    
    $GLOBALS['comment'] = $comment;
    $options = get_option('ruh_comment_options', array());
    $profile_url = ruh_get_user_profile_url($comment->user_id);
    $max_depth = isset($args['max_depth']) ? $args['max_depth'] : 5;
    $user_vote = '';
    
    if (is_user_logged_in()) {
        $user_vote = get_comment_meta($comment->comment_ID, '_user_vote_' . get_current_user_id(), true);
    }
    ?>
    <li <?php comment_class('ruh-comment-item') ?> id="comment-<?php comment_ID() ?>" data-comment-id="<?php comment_ID() ?>">
        <div class="comment-body" id="div-comment-<?php comment_ID() ?>">
            <div class="comment-author vcard">
                <a href="<?php echo esc_url($profile_url); ?>" class="avatar-link">
                    <?php echo ruh_get_avatar($comment->user_id ?: $comment->comment_author_email, 55); ?>
                </a>
            </div>
            <div class="comment-content">
                <div class="comment-author-meta">
                    <a class="fn author-name" href="<?php echo esc_url($profile_url); ?>">
                        <?php echo get_comment_author(); ?>
                    </a>
                    <?php 
                    // Kullanıcı seviye ve rozetlerini göster
                    if ($comment->user_id) {
                        echo ruh_get_user_level_badge($comment->user_id);
                        echo ruh_get_user_custom_badges($comment->user_id);
                        
                        // Kullanıcı durumu kontrolü
                        $ban_status = get_user_meta($comment->user_id, 'ruh_ban_status', true);
                        $timeout_until = get_user_meta($comment->user_id, 'ruh_timeout_until', true);
                        
                        if ($ban_status === 'banned') {
                            echo '<span class="user-status banned" title="Engellenmiş kullanıcı">🚫</span>';
                        } elseif ($timeout_until && current_time('timestamp') < $timeout_until) {
                            echo '<span class="user-status timeout" title="Susturulmuş kullanıcı">⏰</span>';
                        }
                    }
                    ?>
                    <span class="comment-metadata">
                        <a href="<?php echo htmlspecialchars(get_comment_link($comment->comment_ID)); ?>" class="comment-time">
                            <time datetime="<?php comment_time('c'); ?>" title="<?php comment_time(); ?>">
                                <?php printf('%s önce', human_time_diff(get_comment_time('U'), current_time('timestamp'))); ?>
                            </time>
                        </a>
                        <?php if ($comment->comment_approved == '0') : ?>
                            <span class="comment-awaiting-moderation">(Onay bekliyor)</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="comment-text" data-comment-id="<?php comment_ID(); ?>">
                    <?php comment_text(); ?>
                </div>

                <div class="comment-actions">
                    <?php if (isset($options['enable_likes']) && $options['enable_likes']) : ?>
                        <div class="comment-like-buttons" data-comment-id="<?php comment_ID(); ?>">
                            <button class="like-btn <?php echo ($user_vote === 'liked') ? 'active' : ''; ?>" 
                                    type="button" 
                                    title="Beğen">
                                👍 <span class="count"><?php echo get_comment_meta($comment->comment_ID, '_likes', true) ?: 0; ?></span>
                            </button>
                            <button class="dislike-btn <?php echo ($user_vote === 'disliked') ? 'active' : ''; ?>" 
                                    type="button" 
                                    title="Beğenme">
                                👎 <span class="count"><?php echo get_comment_meta($comment->comment_ID, '_dislikes', true) ?: 0; ?></span>
                            </button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($depth < $max_depth && comments_open($comment->comment_post_ID)) : ?>
                        <button type="button" class="comment-reply-link" data-comment-id="<?php comment_ID(); ?>">
                            Yanıtla
                        </button>
                    <?php endif; ?>
                    
                    <?php if (isset($options['enable_reporting']) && $options['enable_reporting'] && is_user_logged_in() && $comment->user_id != get_current_user_id()) : ?>
                        <button type="button" class="report-btn" data-comment-id="<?php comment_ID(); ?>">
                            Şikayet Et
                        </button>
                    <?php endif; ?>
                    
                    <?php if (current_user_can('moderate_comments')) : ?>
                        <span class="admin-actions">
                            <a href="<?php echo admin_url('comment.php?action=editcomment&c=' . $comment->comment_ID); ?>" class="edit-link">
                                Düzenle
                            </a>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php
        // Alt yorumları göster
        $children = get_comments(array(
            'parent' => $comment->comment_ID,
            'status' => 'approve',
            'number' => 3,
            'orderby' => 'comment_date',
            'order' => 'ASC'
        ));
        
        if (!empty($children)) {
            echo '<ol class="children">';
            foreach ($children as $child) {
                ruh_comment_format($child, $args, $depth + 1);
            }
            
            // Daha fazla alt yorum varsa "daha fazla" butonu
            $total_children = get_comments(array(
                'parent' => $comment->comment_ID,
                'status' => 'approve',
                'count' => true
            ));
            
            if ($total_children > 3) {
                echo '<li class="load-more-replies">';
                echo '<button type="button" class="load-more-replies-btn" data-parent-id="' . $comment->comment_ID . '">';
                printf('%d yanıt daha göster', $total_children - 3);
                echo '</button>';
                echo '</li>';
            }
            
            echo '</ol>';
        }
        ?>
    <?php
}

/**
 * Özel avatar fonksiyonu - custom avatar desteği ile
 */
function ruh_get_avatar($user_id_or_email, $size = 50, $default = '', $alt = '') {
    if (is_numeric($user_id_or_email)) {
        $custom_avatar = get_user_meta($user_id_or_email, 'ruh_custom_avatar', true);
        if ($custom_avatar && file_exists($custom_avatar)) {
            $avatar_url = wp_upload_dir()['baseurl'] . '/' . basename($custom_avatar);
            return '<img src="' . esc_url($avatar_url) . '" class="avatar avatar-' . $size . '" width="' . $size . '" height="' . $size . '" alt="' . esc_attr($alt) . '">';
        }
        $user = get_userdata($user_id_or_email);
        $email = $user ? $user->user_email : '';
    } else {
        $email = $user_id_or_email;
    }
    
    return get_avatar($email, $size, $default, $alt);
}

/**
 * Bir yorum yapıldığında kullanıcının XP'sini ve seviyesini günceller.
 */
function ruh_update_user_xp_and_level($user_id) {
    if (!$user_id) return;
    
    global $wpdb;
    $table = $wpdb->prefix . 'ruh_user_levels';
    $options = get_option('ruh_comment_options', array());
    $xp_per_comment = isset($options['xp_per_comment']) ? intval($options['xp_per_comment']) : 15;
    
    $user_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));

    if ($user_data) {
        $new_xp = $user_data->xp + $xp_per_comment;
        
        // Seviye hesaplama - daha dengeli formül
        $current_level = $user_data->level;
        $xp_for_next_level = ruh_calculate_xp_for_level($current_level + 1);
        $new_level = $current_level;
        
        // Seviye atlama kontrolü
        while ($new_xp >= $xp_for_next_level) {
            $new_level++;
            $xp_for_next_level = ruh_calculate_xp_for_level($new_level + 1);
        }
        
        $result = $wpdb->update($table, 
            array('xp' => $new_xp, 'level' => $new_level), 
            array('user_id' => $user_id)
        );
        
        // Seviye atladıysa otomatik rozetleri kontrol et
        if ($new_level > $current_level) {
            ruh_check_and_assign_auto_badges($user_id);
        }
    } else {
        $wpdb->insert($table, array(
            'user_id' => $user_id,
            'xp' => $xp_per_comment,
            'level' => 1
        ));
    }
}

/**
 * Belirli bir seviye için gereken XP miktarını hesaplar
 */
function ruh_calculate_xp_for_level($level) {
    return (int)(pow($level, 1.8) * 100);
}

/**
 * Kullanıcının seviye ve XP bilgisini veritabanından çeker.
 */
function ruh_get_user_level_info($user_id) {
    if (!$user_id) return (object)array('level' => 1, 'xp' => 0);
    
    global $wpdb;
    $table = $wpdb->prefix . 'ruh_user_levels';
    $info = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));
    
    if (!$info) {
        return (object)array('level' => 1, 'xp' => 0);
    }
    
    return $info;
}

/**
 * Kullanıcının sahip olduğu tüm rozetleri veritabanından çeker.
 */
function ruh_get_user_badges($user_id) {
    if (!$user_id) return array();
    
    global $wpdb;
    $badges_table = $wpdb->prefix . 'ruh_badges';
    $user_badges_table = $wpdb->prefix . 'ruh_user_badges';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT b.* FROM $badges_table b 
         JOIN $user_badges_table ub ON b.badge_id = ub.badge_id 
         WHERE ub.user_id = %d 
         ORDER BY b.badge_id DESC", 
        $user_id
    ));
}

/**
 * Otomatik rozet koşullarını kontrol eder ve gerekirse kullanıcıya atar.
 */
function ruh_check_and_assign_auto_badges($user_id) {
    if (!$user_id) return;
    
    global $wpdb;
    $badges_table = $wpdb->prefix . 'ruh_badges';
    $user_badges_table = $wpdb->prefix . 'ruh_user_badges';
    
    // Otomatik rozetleri al
    $auto_badges = $wpdb->get_results("SELECT * FROM $badges_table WHERE is_automated = 1");
    if (empty($auto_badges)) return;

    // Kullanıcının yorum sayısını al
    $user_comment_count = get_comments(array(
        'user_id' => $user_id, 
        'count' => true, 
        'status' => 'approve'
    ));
    
    // Kullanıcının toplam beğeni sayısını al
    $user_total_likes = ruh_get_user_total_likes($user_id);
    
    // Kullanıcının seviyesini al
    $level_info = ruh_get_user_level_info($user_id);

    foreach ($auto_badges as $badge) {
        $should_assign = false;
        
        switch ($badge->auto_condition_type) {
            case 'comment_count':
                $should_assign = ($user_comment_count >= $badge->auto_condition_value);
                break;
            case 'like_count':
                $should_assign = ($user_total_likes >= $badge->auto_condition_value);
                break;
            case 'level':
                $should_assign = ($level_info->level >= $badge->auto_condition_value);
                break;
        }

        if ($should_assign) {
            // Rozet zaten var mı kontrol et
            $badge_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $user_badges_table WHERE user_id = %d AND badge_id = %d",
                $user_id,
                $badge->badge_id
            ));
            
            if ($badge_exists == 0) {
                $wpdb->insert($user_badges_table, array(
                    'user_id' => $user_id,
                    'badge_id' => $badge->badge_id
                ));
            }
        }
    }
}

/**
 * Bir kullanıcının tüm yorumlarından aldığı toplam beğeni sayısını hesaplar.
 */
function ruh_get_user_total_likes($user_id) {
    if (!$user_id) return 0;
    
    global $wpdb;
    
    $sql = "SELECT SUM(CAST(meta_value AS UNSIGNED)) 
            FROM {$wpdb->commentmeta} cm 
            JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID 
            WHERE c.user_id = %d 
            AND cm.meta_key = '_likes' 
            AND c.comment_approved = '1'";
            
    $total = $wpdb->get_var($wpdb->prepare($sql, $user_id));
    
    return (int)$total;
}

/**
 * Admin panelinde kullanılacak genişletilmiş SVG rozet şablonlarını döndürür.
 */
function ruh_get_badge_templates() {
    return array(
        'shield' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1Z"/></svg>',
        'star' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/></svg>',
        'heart' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,21.35L10.55,20.03C5.4,15.36 2,12.27 2,8.5C2,5.41 4.42,3 7.5,3C9.24,3 10.91,3.81 12,5.08C13.09,3.81 14.76,3 16.5,3C19.58,3 22,5.41 22,8.5C22,12.27 18.6,15.36 13.45,20.03L12,21.35Z"/></svg>',
        'trophy' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M7,4V2A1,1 0 0,1 8,1H16A1,1 0 0,1 17,2V4H20A1,1 0 0,1 21,5V8A3,3 0 0,1 18,11L17.83,11C17.42,12.43 16.31,13.5 14.83,13.91V16.5A2.5,2.5 0 0,1 12.5,19H11.5A2.5,2.5 0 0,1 9,16.5V13.91C7.69,13.5 6.58,12.43 6.17,11L6,11A3,3 0 0,1 3,8V5A1,1 0 0,1 4,4H7M5,6V8A1,1 0 0,0 6,9H7.17C7.59,7.5 9.15,6.27 11,6.08V6H5M19,6H13V6.08C14.85,6.27 16.41,7.5 16.83,9H18A1,1 0 0,0 19,8V6Z"/></svg>',
        'diamond' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M6,2L2,8L12,22L22,8L18,2H6M6.5,4H8.5L7,7L6.5,4M9.5,4H14.5L15,7L12,10L9,7L9.5,4M16.5,4H17.5L17,7L15,4H16.5Z"/></svg>',
        'flame' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M17.66,11.2C17.43,10.9 17.15,10.64 16.89,10.38C16.22,9.78 15.46,9.35 14.82,8.72C13.33,7.26 13,4.85 13.95,3C13.74,3.09 13.54,3.22 13.34,3.36C11.44,4.72 10.5,7.15 10.5,9.26C9.74,8.21 9.77,6.79 10.75,5.81C10.75,5.81 10.75,5.81 10.75,5.81C10.1,6.27 9.66,6.99 9.66,7.83C9.66,9.47 10.83,10.86 12.41,11.14C12.03,11.72 12.4,12.54 13.09,12.54C13.92,12.54 14.59,11.87 14.59,11.04C15.73,11.04 16.66,10.08 16.66,8.91C16.66,8.91 16.66,8.91 16.66,8.91C17.66,9.74 18.25,10.88 18.25,12.11C18.25,14.57 16.28,16.54 13.82,16.54C11.36,16.54 9.39,14.57 9.39,12.11C9.39,11.64 9.5,11.2 9.66,10.78C8.61,11.75 8,13.13 8,14.61C8,18.08 10.92,21 14.39,21C17.86,21 20.78,18.08 20.78,14.61C20.78,13.38 20.31,12.11 17.66,11.2Z"/></svg>',
        'crown' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M5,16L3,5L5.5,7.5L8.5,4L12,7L15.5,4L18.5,7.5L21,5L19,16H5M12,2A1,1 0 0,1 13,3A1,1 0 0,1 12,4A1,1 0 0,1 11,3A1,1 0 0,1 12,2M19,19H5V21H19V19Z"/></svg>',
        'medal' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,4A6,6 0 0,0 6,10C6,13.31 8.69,16 12.03,16C15.31,16 18,13.31 18,10A6,6 0 0,0 12,4M12,14A4,4 0 0,1 8,10A4,4 0 0,1 12,6A4,4 0 0,1 16,10A4,4 0 0,1 12,14M7,18A1,1 0 0,0 6,19A1,1 0 0,0 7,20H17A1,1 0 0,0 18,19A1,1 0 0,0 17,18H7Z"/></svg>',
        'rocket' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M2.81,14.12L5.64,11.29L8.17,10.79C11.39,6.41 17.55,5.54 19.07,7.07C20.59,8.59 19.72,14.75 15.34,18L14.84,20.53L12,17.69L2.81,14.12M15.92,8.08C15.38,8.62 15.38,9.5 15.92,10.04C16.46,10.58 17.34,10.58 17.88,10.04C18.42,9.5 18.42,8.62 17.88,8.08C17.34,7.54 16.46,7.54 15.92,8.08M5.93,16.5L7.65,17.35L8.5,19.07L9.64,17.93L8.22,16.5L5.93,16.5Z"/></svg>',
        'lightning' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M11,4L6,10.5H9L8,18L13,11.5H10L11,4Z"/></svg>',
        'gem' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M16,9H19L14,16M10,9H14L12,16M5,9H8L10,16M15,4L14,9M10,4L12,9M9,4L10,9M2,9L7,4H17L22,9L12,20L2,9Z"/></svg>',
        'bell' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M21,19V20H3V19L5,17V11C5,7.9 7.03,5.17 10,4.29C10,4.19 10,4.1 10,4A2,2 0 0,1 12,2A2,2 0 0,1 14,4C14,4.1 14,4.19 14,4.29C16.97,5.17 19,7.9 19,11V17L21,19M14,21A2,2 0 0,1 12,23A2,2 0 0,1 10,21"/></svg>',
        'magic' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M7.5,5.6L10,7L8.6,4.5L10,2L7.5,3.4L5,2L6.4,4.5L5,7L7.5,5.6M19.5,15.4L22,14L20.6,16.5L22,19L19.5,17.6L17,19L18.4,16.5L17,14L19.5,15.4M22,2L20.6,4.5L22,7L19.5,5.6L17,7L18.4,4.5L17,2L19.5,3.4L22,2M13.34,12.78L15.78,10.34L13.66,8.22L11.22,10.66L13.34,12.78M14.37,7.29L16.71,9.63C17.1,10 17.1,10.65 16.71,11.04L5.04,22.71C4.65,23.1 4,23.1 3.63,22.71L1.29,20.37C0.9,20 0.9,19.35 1.29,18.96L12.96,7.29C13.35,6.9 14,6.9 14.37,7.29Z"/></svg>',
        'target' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8M12,10A2,2 0 0,0 10,12A2,2 0 0,0 12,14A2,2 0 0,0 14,12A2,2 0 0,0 12,10Z"/></svg>',
        'clock' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M16.2,16.2L11,13V7H12.5V12.2L17,14.7L16.2,16.2Z"/></svg>',
        'eye' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z"/></svg>',
        'book' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M18,22A2,2 0 0,0 20,20V4A2,2 0 0,0 18,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18M6,4H13V12L9.5,10.5L6,12V4Z"/></svg>',
        'puzzle' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M20.5,11H19V7C19,5.89 18.1,5 17,5H13V3.5A2.5,2.5 0 0,0 10.5,1A2.5,2.5 0 0,0 8,3.5V5H4A2,2 0 0,0 2,7V10.8H3.5C5,10.8 6.2,12 6.2,13.5C6.2,15 5,16.2 3.5,16.2H2V20A2,2 0 0,0 4,22H7.8V20.5C7.8,19 9,17.8 10.5,17.8C12,17.8 13.2,19 13.2,20.5V22H17A2,2 0 0,0 19,20V16H20.5A2.5,2.5 0 0,0 23,13.5A2.5,2.5 0 0,0 20.5,11Z"/></svg>',
        'music' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,3V13.55C11.41,13.21 10.73,13 10,13A4,4 0 0,0 6,17A4,4 0 0,0 10,21A4,4 0 0,0 14,17V7H18V3H12Z"/></svg>',
        'paintbrush' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M20.71,4.63L19.37,3.29C19,2.9 18.35,2.9 17.96,3.29L9,12.25L11.75,15L20.71,6.04C21.1,5.65 21.1,5 20.71,4.63M7,14A3,3 0 0,0 4,17C4,18.31 2.84,19 2,19C2.92,20.22 4.5,21 6,21A4,4 0 0,0 10,17A3,3 0 0,0 7,14Z"/></svg>',
        'camera' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M4,4H7L9,2H15L17,4H20A2,2 0 0,1 22,6V18A2,2 0 0,1 20,20H4A2,2 0 0,1 2,18V6A2,2 0 0,1 4,4M12,7A5,5 0 0,0 7,12A5,5 0 0,0 12,17A5,5 0 0,0 17,12A5,5 0 0,0 12,7M12,9A3,3 0 0,1 15,12A3,3 0 0,1 12,15A3,3 0 0,1 9,12A3,3 0 0,1 12,9Z"/></svg>',
        'code' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M14.6,16.6L19.2,12L14.6,7.4L16,6L22,12L16,18L14.6,16.6M9.4,16.6L4.8,12L9.4,7.4L8,6L2,12L8,18L9.4,16.6Z"/></svg>',
        'thumbs_up' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M23,10C23,8.89 22.1,8 21,8H14.68L15.64,3.43C15.66,3.33 15.67,3.22 15.67,3.11C15.67,2.7 15.5,2.32 15.23,2.05L14.17,1L7.59,7.58C7.22,7.95 7,8.45 7,9V19A2,2 0 0,0 9,21H18C18.83,21 19.54,20.5 19.84,19.78L22.86,12.73C22.95,12.5 23,12.26 23,12V10.08L23,10M1,21H5V9H1V21Z"/></svg>',
        'sparkles' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M9.5,2L8.5,7L3.5,8L8.5,9L9.5,14L10.5,9L15.5,8L10.5,7L9.5,2M19,6L18.5,7.5L17,8L18.5,8.5L19,10L19.5,8.5L21,8L19.5,7.5L19,6M17,2L16,4L14,5L16,6L17,8L18,6L20,5L18,4L17,2Z"/></svg>',
        'award' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,15.5A3.5,3.5 0 0,1 8.5,12A3.5,3.5 0 0,1 12,8.5A3.5,3.5 0 0,1 15.5,12A3.5,3.5 0 0,1 12,15.5M19.43,12.97C19.47,12.65 19.5,12.33 19.5,12C19.5,11.67 19.47,11.34 19.43,11L21.54,9.37C21.73,9.22 21.78,8.95 21.66,8.73L19.66,5.27C19.54,5.05 19.27,4.96 19.05,5.05L16.56,6.05C16.04,5.66 15.5,5.32 14.87,5.07L14.5,2.42C14.46,2.18 14.25,2 14,2H10C9.75,2 9.54,2.18 9.5,2.42L9.13,5.07C8.5,5.32 7.96,5.66 7.44,6.05L4.95,5.05C4.73,4.96 4.46,5.05 4.34,5.27L2.34,8.73C2.22,8.95 2.27,9.22 2.46,9.37L4.57,11C4.53,11.34 4.5,11.67 4.5,12C4.5,12.33 4.53,12.65 4.57,12.97L2.46,14.63C2.27,14.78 2.22,15.05 2.34,15.27L4.34,18.73C4.46,18.95 4.73,19.03 4.95,18.95L7.44,17.94C7.96,18.34 8.5,18.68 9.13,18.93L9.5,21.58C9.54,21.82 9.75,22 10,22H14C14.25,22 14.46,21.82 14.5,21.58L14.87,18.93C15.5,18.68 16.04,18.34 16.56,17.94L19.05,18.95C19.27,19.03 19.54,18.95 19.66,18.73L21.66,15.27C21.78,15.05 21.73,14.78 21.54,14.63L19.43,12.97Z"/></svg>',
        'brain' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M21.33,12.91C21.42,14.46 20.71,15.95 19.44,16.86L20.21,18.35C20.44,18.8 20.47,19.33 20.27,19.8C20.08,20.27 19.69,20.64 19.21,20.8L18.42,21.05C18.25,21.11 18.06,21.14 17.88,21.14C17.37,21.14 16.89,20.91 16.56,20.5L14.44,18C13.55,17.85 12.8,17.7 12,17.7C11.2,17.7 10.45,17.85 9.56,18L7.44,20.5C7.11,20.91 6.63,21.14 6.12,21.14C5.94,21.14 5.75,21.11 5.58,21.05L4.79,20.8C4.31,20.64 3.92,20.27 3.73,19.8C3.53,19.33 3.56,18.8 3.79,18.35L4.56,16.86C3.29,15.95 2.58,14.46 2.67,12.91C2.4,12.1 2.4,11.21 2.76,10.41C3.1,9.63 3.75,9 4.56,8.65C4.84,7.1 5.8,5.73 7.17,4.89C8.54,4.05 10.21,3.8 11.78,4.2C12.93,3.5 14.26,3.15 15.61,3.19C16.96,3.22 18.27,3.64 19.39,4.4C20.5,5.16 21.35,6.22 21.84,7.46C22.33,8.69 22.44,10.03 22.15,11.32C21.86,12.61 21.18,13.78 20.21,14.66L21.33,12.91Z"/></svg>',
        'graduation' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,3L1,9L12,15L21,10.09V17H23V9M5,13.18V17.18L12,21L19,17.18V13.18L12,17L5,13.18Z"/></svg>',
        'fingerprint' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M17.81,4.47C17.73,4.47 17.65,4.45 17.58,4.41C15.66,3.42 14,3.87 12.5,5C11,3.87 9.34,3.42 7.42,4.41C7.35,4.45 7.27,4.47 7.19,4.47C5.03,4.47 3.29,6.21 3.29,8.37V21H4.79V8.37C4.79,7.04 5.86,5.97 7.19,5.97C7.27,5.97 7.35,5.99 7.42,6.03C9.34,7.02 11,6.57 12.5,5.5C14,6.57 15.66,7.02 17.58,6.03C17.65,5.99 17.73,5.97 17.81,5.97C19.14,5.97 20.21,7.04 20.21,8.37V21H21.71V8.37C21.71,6.21 19.97,4.47 17.81,4.47Z"/></svg>',
        'compass' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M14.5,8L11,14.5L9.5,16L13,9.5L14.5,8Z"/></svg>',
        'sun' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,8A4,4 0 0,0 8,12A4,4 0 0,0 12,16A4,4 0 0,0 16,12A4,4 0 0,0 12,8M12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6A6,6 0 0,1 18,12A6,6 0 0,1 12,18M20,8.69V4H15.31L12,0.69L8.69,4H4V8.69L0.69,12L4,15.31V20H8.69L12,23.31L15.31,20H20V15.31L23.31,12L20,8.69Z"/></svg>',
        'mountain' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M20,21H3L12,4L20,21M7.5,19H16.5L12,10.5L7.5,19Z"/></svg>',
        'verified' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,1L9,9L1,12L9,15L12,23L15,15L23,12L15,9L12,1Z"/></svg>',
        'infinity' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M18.6,6.62C21.58,9.59 21.58,14.41 18.61,17.38C15.64,20.35 10.82,20.35 7.85,17.38C4.88,14.41 4.88,9.59 7.85,6.62C10.82,3.65 15.64,3.65 18.6,6.62M12,10.93L16.07,6.86C17.78,8.57 17.78,11.43 16.07,13.14L12,9.07L7.93,13.14C6.22,11.43 6.22,8.57 7.93,6.86L12,10.93Z"/></svg>',
        'spiral' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,4C7.58,4 4,7.58 4,12S7.58,20 12,20C16.42,20 20,16.42 20,12H18C18,15.31 15.31,18 12,18S6,15.31 6,12S8.69,6 12,6C13.66,6 15.14,6.69 16.22,7.78L14.5,9.5C13.81,8.81 12.81,8.5 12,8.5C10.62,8.5 9.5,9.62 9.5,11C9.5,12.38 10.62,13.5 12,13.5C13.38,13.5 14.5,12.38 14.5,11H16.5C16.5,13.49 14.49,15.5 12,15.5C9.51,15.5 7.5,13.49 7.5,11C7.5,8.51 9.51,6.5 12,6.5C14.49,6.5 16.5,8.51 16.5,11H18.5C18.5,7.36 15.64,4.5 12,4.5C8.36,4.5 5.5,7.36 5.5,11C5.5,14.64 8.36,17.5 12,17.5C15.64,17.5 18.5,14.64 18.5,11H20.5C20.5,15.14 17.14,18.5 13,18.5L12,4Z"/></svg>',
        'leaf' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z"/></svg>',
        'feather' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12.69,3.09L7.82,14.09L14.5,7.41L12.69,3.09M13.79,2.22L16.2,8.83L22,3.59L13.79,2.22M5.71,16.2L3.79,14.28L2.22,16.84L9.72,19.81L5.71,16.2M12.05,18.32L8.69,19.97L14.22,21.11L16.91,14.22L12.05,18.32Z"/></svg>',
        'crystal' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,3L2,9L12,15L22,9L12,3M12,7.5L17.6,10.5L12,13.5L6.4,10.5L12,7.5M2,13.5L12,19.5L22,13.5L18,11.5L12,15L6,11.5L2,13.5Z"/></svg>',
        'wings' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,3C8.13,3 5,6.13 5,10V11C3.34,11 2,12.34 2,14C2,15.66 3.34,17 5,17H7V19C7,20.1 7.9,21 9,21H15C16.1,21 17,20.1 17,19V17H19C20.66,17 22,15.66 22,14C22,12.34 20.66,11 19,11V10C19,6.13 15.87,3 12,3M12,5C14.76,5 17,7.24 17,10V11H16V10C16,7.79 14.21,6 12,6S8,7.79 8,10V11H7V10C7,7.24 9.24,5 12,5Z"/></svg>'
    );
}

/**
 * Kullanıcının profil sayfasına giden URL'i oluşturur.
 */
function ruh_get_user_profile_url($user_id) {
    if (!$user_id) return '#';
    
    $options = get_option('ruh_comment_options', array());
    $page_id = isset($options['profile_page_id']) ? $options['profile_page_id'] : 0;
    
    if (!$page_id || !get_post($page_id)) {
        return get_author_posts_url($user_id);
    }
    
    return add_query_arg('user_id', $user_id, get_permalink($page_id));
}

/**
 * Kullanıcının seviye rozetini HTML olarak döndürür.
 */
function ruh_get_user_level_badge($user_id) {
    if (!$user_id) return '';
    
    $level_info = ruh_get_user_level_info($user_id);
    $level_color = ruh_get_level_color($level_info->level);
    $level_title = ruh_get_level_title($level_info->level);
    
    return sprintf(
        '<span class="user-level" style="background: linear-gradient(135deg, %s, %s);" title="%s - %d XP">Lv.%d</span>',
        $level_color,
        ruh_darken_color($level_color, 20),
        $level_title,
        $level_info->xp,
        $level_info->level
    );
}

/**
 * Seviye rengini belirler - ana renk #005B43 korunarak
 */
function ruh_get_level_color($level) {
    if ($level >= 100) return '#e74c3c';     // Kırmızı - Efsanevi
    if ($level >= 75) return '#9b59b6';      // Mor - Mitik  
    if ($level >= 50) return '#3498db';      // Mavi - Epik
    if ($level >= 30) return '#1abc9c';      // Turkuaz - Nadir
    if ($level >= 20) return '#f39c12';      // Turuncu - Sıradışı
    if ($level >= 10) return '#27ae60';      // Yeşil - Deneyimli
    if ($level >= 5) return '#005B43';       // Ana renk - Aktif
    return '#95a5a6';                        // Gri - Yeni başlayan
}

/**
 * Seviye unvanını belirler
 */
function ruh_get_level_title($level) {
    if ($level >= 100) return 'Efsanevi Yorumcu';
    if ($level >= 75) return 'Mitik Katılımcı';
    if ($level >= 50) return 'Epik Topluluk Üyesi';
    if ($level >= 30) return 'Nadir Katkıda Bulunan';
    if ($level >= 20) return 'Sıradışı Yorum Yazarı';
    if ($level >= 10) return 'Deneyimli Üye';
    if ($level >= 5) return 'Aktif Katılımcı';
    return 'Yeni Başlayan';
}

/**
 * Rengi koyulaştırma yardımcı fonksiyonu
 */
function ruh_darken_color($hex, $percent) {
    $hex = str_replace('#', '', $hex);
    $r = max(0, hexdec(substr($hex, 0, 2)) - ($percent * 2.55));
    $g = max(0, hexdec(substr($hex, 2, 2)) - ($percent * 2.55));
    $b = max(0, hexdec(substr($hex, 4, 2)) - ($percent * 2.55));
    return sprintf("#%02x%02x%02x", $r, $g, $b);
}

/**
 * Kullanıcının özel rozetlerini HTML olarak döndürür.
 */
function ruh_get_user_custom_badges($user_id) {
    if (!$user_id) return '';
    
    $badges = ruh_get_user_badges($user_id);
    if (empty($badges)) return '';

    $output = '<span class="user-badges">';
    foreach (array_slice($badges, 0, 5) as $badge) {
        $output .= sprintf(
            '<span class="badge-item" title="%s">%s</span>',
            esc_attr($badge->badge_name),
            $badge->badge_svg
        );
    }
    
    if (count($badges) > 5) {
        $remaining = count($badges) - 5;
        $output .= sprintf(
            '<span class="badge-more" title="Ve %d rozet daha">+%d</span>',
            $remaining,
            $remaining
        );
    }
    
    $output .= '</span>';
    return $output;
}

/**
 * Kullanıcı istatistiklerini döndürür
 */
function ruh_get_user_stats($user_id) {
    if (!$user_id) return array();
    
    $comment_count = get_comments(array('user_id' => $user_id, 'count' => true, 'status' => 'approve'));
    $total_likes = ruh_get_user_total_likes($user_id);
    $level_info = ruh_get_user_level_info($user_id);
    $badges = ruh_get_user_badges($user_id);
    $user = get_userdata($user_id);
    
    $last_comment = get_comments(['user_id' => $user_id, 'number' => 1, 'status' => 'approve']);
    $last_activity = !empty($last_comment) ? strtotime($last_comment[0]->comment_date) : strtotime($user->user_registered);
    
    $avg_likes = $comment_count > 0 ? round($total_likes / $comment_count, 1) : 0;
    
    return array(
        'comment_count' => (int)$comment_count,
        'total_likes' => (int)$total_likes,
        'avg_likes' => $avg_likes,
        'level' => (int)$level_info->level,
        'xp' => (int)$level_info->xp,
        'badge_count' => count($badges),
        'join_date' => $user->user_registered,
        'last_activity' => $last_activity,
        'days_active' => floor((time() - strtotime($user->user_registered)) / DAY_IN_SECONDS)
    );
}

/**
 * Yorum için meta bilgileri döndürür
 */
function ruh_get_comment_meta_info($comment_id) {
    $likes = get_comment_meta($comment_id, '_likes', true) ?: 0;
    $dislikes = get_comment_meta($comment_id, '_dislikes', true) ?: 0;
    $reports = ruh_get_comment_report_count($comment_id);
    
    return array(
        'likes' => (int)$likes,
        'dislikes' => (int)$dislikes,
        'reports' => (int)$reports,
        'score' => (int)$likes - (int)$dislikes
    );
}

/**
 * Yorumun şikayet sayısını döndürür
 */
function ruh_get_comment_report_count($comment_id) {
    global $wpdb;
    $reports_table = $wpdb->prefix . 'ruh_reports';
    
    return (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $reports_table WHERE comment_id = %d",
        $comment_id
    ));
}

/**
 * Kullanıcı durumu kontrolü
 */
function ruh_is_user_banned($user_id) {
    if (!$user_id) return false;
    
    $ban_status = get_user_meta($user_id, 'ruh_ban_status', true);
    $timeout_until = get_user_meta($user_id, 'ruh_timeout_until', true);
    
    return $ban_status === 'banned' || ($timeout_until && current_time('timestamp') < $timeout_until);
}

/**
 * En aktif kullanıcıları getir
 */
function ruh_get_top_users($limit = 10) {
    global $wpdb;
    
    $sql = "
        SELECT u.ID, u.display_name, 
               COALESCE(ul.level, 1) as level,
               COALESCE(ul.xp, 0) as xp,
               COUNT(c.comment_ID) as comment_count,
               COALESCE(SUM(CAST(cm.meta_value AS UNSIGNED)), 0) as total_likes
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->prefix}ruh_user_levels ul ON u.ID = ul.user_id
        LEFT JOIN {$wpdb->comments} c ON u.ID = c.user_id AND c.comment_approved = '1'
        LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = '_likes'
        GROUP BY u.ID
        ORDER BY (COALESCE(ul.level, 1) * 10 + COUNT(c.comment_ID) + COALESCE(SUM(CAST(cm.meta_value AS UNSIGNED)), 0)) DESC
        LIMIT %d
    ";
    
    return $wpdb->get_results($wpdb->prepare($sql, $limit));
}

/**
 * Haftalık istatistikler
 */
function ruh_get_weekly_stats() {
    global $wpdb;
    
    $week_ago = date('Y-m-d H:i:s', strtotime('-1 week'));
    
    $stats = array(
        'comments' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_date >= %s AND comment_approved = '1'",
            $week_ago
        )),
        'reactions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ruh_reactions"),
        'new_users' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered >= %s",
            $week_ago
        )),
        'badges_earned' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ruh_user_badges")
    );
    
    return $stats;
}

/**
 * Widget sınıfı
 */
class Ruh_Comment_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'ruh_comment_widget',
            'Ruh Comment İstatistikleri',
            array('description' => 'Topluluk istatistiklerini gösterir')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $stats = ruh_get_weekly_stats();
        $top_users = ruh_get_top_users(5);
        
        echo '<div class="ruh-widget-content">';
        echo '<div class="stats-section">';
        echo '<h4>Bu Hafta</h4>';
        echo '<ul>';
        echo '<li><strong>' . $stats['comments'] . '</strong> yeni yorum</li>';
        echo '<li><strong>' . $stats['new_users'] . '</strong> yeni üye</li>';
        echo '<li><strong>' . $stats['reactions'] . '</strong> tepki</li>';
        echo '</ul>';
        echo '</div>';
        
        if (!empty($top_users)) {
            echo '<div class="top-users-section">';
            echo '<h4>En Aktif Üyeler</h4>';
            echo '<ol>';
            foreach ($top_users as $user) {
                $profile_url = ruh_get_user_profile_url($user->ID);
                echo '<li><a href="' . esc_url($profile_url) . '">' . esc_html($user->display_name) . '</a> ';
                echo '<span class="level">(Lv.' . $user->level . ')</span></li>';
            }
            echo '</ol>';
            echo '</div>';
        }
        echo '</div>';
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Topluluk İstatistikleri';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Başlık:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

function ruh_register_widgets() {
    register_widget('Ruh_Comment_Widget');
}
add_action('widgets_init', 'ruh_register_widgets');