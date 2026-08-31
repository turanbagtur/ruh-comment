<?php
if (post_password_required()) return;

$options = get_option('ruh_comment_options', array());

// Dil sistemi
$ruh_lang = $options['language'] ?? 'tr_TR';
$ruh_texts = array(
    'tr_TR' => array(
        'react_title' => 'Bu içeriğe tepki ver',
        'reactions' => 'tepki',
        'like' => 'Beğendim',
        'angry' => 'Sinir Bozucu',
        'love' => 'Mükemmel',
        'wow' => 'Şaşırtıcı',
        'sad' => 'Üzücü',
        'end' => 'Bölüm Bitti',
        'comments' => 'Yorum',
        'newest' => 'En Yeni',
        'oldest' => 'En Eski',
        'best' => 'En İyi',
        'discussed' => 'En çok tartışılan',
        'search_comments' => 'Yorumlarda ara...',
        'filter_user' => 'Kullanıcı',
        'highlights' => 'Öne çıkan yorumlar',
        'notifications' => 'Bildirimler',
        'write_comment' => 'Yorumunuzu yazın...',
        'submit' => 'Gönder',
        'login_required' => 'Yorum yapmak için giriş yapmalısınız.',
        'login' => 'Giriş Yap',
        'register' => 'Kayıt Ol',
        'reply' => 'Yanıtla',
        'like_btn' => 'Beğen',
        'report' => 'Şikayet Et',
        'edit' => 'Düzenle',
        'delete' => 'Sil',
        'level' => 'Seviye',
        'no_comments' => 'Henüz yorum yok. İlk yorumu sen yap!',
        'load_more' => 'Daha Fazla Yükle',
        'login_desc' => 'Yorumlara katılmak ve tepki vermek için hesabınıza giriş yapın veya yeni bir hesap oluşturun.',
        'bold' => 'Kalın',
        'italic' => 'İtalik',
        'add_gif' => 'GIF Ekle',
        'search_gif' => 'GIF Ara',
        'search_gif_placeholder' => 'GIF ara...',
        'report_comment' => 'Yorumu Şikayet Et',
        'report_type' => 'Şikayet Türü',
        'select' => 'Seç...',
        'spam' => 'Spam / Reklam',
        'insult' => 'Hakaret / Küfür',
        'hate' => 'Nefret Söylemi',
        'spoiler_unmarked' => 'Spoiler (Etiketlenmemiş)',
        'false_info' => 'Yanlış Bilgi',
        'other' => 'Diğer',
        'description' => 'Açıklama (Opsiyonel)',
        'description_placeholder' => 'Detaylı açıklama yazabilirsiniz...',
        'cancel' => 'İptal',
        'send_report' => 'Şikayet Gönder',
    ),
    'en_US' => array(
        'react_title' => 'React to this content',
        'reactions' => 'reactions',
        'like' => 'Like',
        'angry' => 'Angry',
        'love' => 'Love',
        'wow' => 'Wow',
        'sad' => 'Sad',
        'end' => 'Episode End',
        'comments' => 'Comments',
        'newest' => 'Newest',
        'oldest' => 'Oldest',
        'best' => 'Best',
        'discussed' => 'Most discussed',
        'search_comments' => 'Search comments...',
        'filter_user' => 'User',
        'highlights' => 'Top comments',
        'notifications' => 'Notifications',
        'write_comment' => 'Write your comment...',
        'submit' => 'Submit',
        'login_required' => 'You must login to comment.',
        'login' => 'Login',
        'register' => 'Register',
        'reply' => 'Reply',
        'like_btn' => 'Like',
        'report' => 'Report',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'level' => 'Level',
        'no_comments' => 'No comments yet. Be the first to comment!',
        'load_more' => 'Load More',
        'login_desc' => 'Please login or create an account to join the comments and reactions.',
        'bold' => 'Bold',
        'italic' => 'Italic',
        'add_gif' => 'Add GIF',
        'search_gif' => 'Search GIF',
        'search_gif_placeholder' => 'Search GIF...',
        'report_comment' => 'Report Comment',
        'report_type' => 'Report Type',
        'select' => 'Select...',
        'spam' => 'Spam / Advertisement',
        'insult' => 'Insult / Profanity',
        'hate' => 'Hate Speech',
        'spoiler_unmarked' => 'Spoiler (Unmarked)',
        'false_info' => 'False Information',
        'other' => 'Other',
        'description' => 'Description (Optional)',
        'description_placeholder' => 'You can write a detailed description...',
        'cancel' => 'Cancel',
        'send_report' => 'Send Report',
    ),
);
$t = $ruh_texts[$ruh_lang] ?? $ruh_texts['tr_TR'];

// Dinamık post ID - ONCE URL'den al, sonra WordPress ID kullan
$post_id = 0;

// Manga sayfalari için URL'den dinamık ID al
if (function_exists('ruh_get_dynamic_post_id')) {
    $post_id = ruh_get_dynamic_post_id();
}

// Dinamık ID bulunamazsa normal WordPress post ID kullan
if (!$post_id) {
    $post_id = get_the_ID();
}

// Post ID yoksa yorum sistemini gösterme
if (!$post_id || $post_id == 0) {
    return; // Yorum sistemı devre disi
}

// Yorum sayısıni doğrudan veritabanından al
global $wpdb;
$comment_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_post_ID = %d AND comment_approved = '1'",
    $post_id
));
if (!$comment_count) $comment_count = 0;
$current_user_id = get_current_user_id();

// Kullanıcı seviye ve rozet bilgisi
$user_level = 1;
$user_badges = array();
if ($current_user_id) {
    // $wpdb zaten yukarıda global olarak tanımlandı
    
    // Seviye bilgisi
    $level_table = $wpdb->prefix . 'ruh_user_levels';
    $user_level_data = $wpdb->get_row($wpdb->prepare("SELECT level FROM $level_table WHERE user_id = %d", $current_user_id));
    if ($user_level_data) {
        $user_level = $user_level_data->level;
    }
    
    // Rozet bilgisi - doğrudan veritabanından çek
    $badges_table = $wpdb->prefix . 'ruh_badges';
    $user_badges_table = $wpdb->prefix . 'ruh_user_badges';
    
    // Tablolarin varlığını kontrol et
    $badges_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$badges_table'") === $badges_table;
    $user_badges_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$user_badges_table'") === $user_badges_table;
    
    if ($badges_table_exists && $user_badges_table_exists) {
        $user_badges = $wpdb->get_results($wpdb->prepare(
            "SELECT b.* FROM $badges_table b 
             JOIN $user_badges_table ub ON b.badge_id = ub.badge_id 
             WHERE ub.user_id = %d 
             ORDER BY b.badge_id DESC
             LIMIT 5", 
            $current_user_id
        ));
        
        if (!$user_badges) {
            $user_badges = array();
        }
    }
}

// Tema seçimi
$comment_theme = isset($options['comment_theme']) ? $options['comment_theme'] : 'modern';
$theme_class = ($comment_theme === 'disqus') ? 'theme-disqus' : 'theme-modern';
$color_mode = isset($options['color_mode']) ? $options['color_mode'] : 'auto';
?>
<div id="ruh-comments" class="comments-area ruh-comments-section <?php echo esc_attr($theme_class); ?>" data-color-mode="<?php echo esc_attr($color_mode); ?>">
    <?php if (isset($options['enable_reactions']) && $options['enable_reactions']) : 
        // Emoji ve label ayarlarıni al
        $reaction_settings = array(
            'begendim' => array(
                'emoji' => $options['emoji_begendim'] ?? '👍',
                'label' => $options['emoji_label_begendim'] ?? $t['like']
            ),
            'sinir_bozucu' => array(
                'emoji' => $options['emoji_sinir_bozucu'] ?? '😡',
                'label' => $options['emoji_label_sinir_bozucu'] ?? $t['angry']
            ),
            'mukemmel' => array(
                'emoji' => $options['emoji_mukemmel'] ?? '🥰',
                'label' => $options['emoji_label_mukemmel'] ?? $t['love']
            ),
            'sasirtici' => array(
                'emoji' => $options['emoji_sasirtici'] ?? '😳',
                'label' => $options['emoji_label_sasirtici'] ?? $t['wow']
            ),
            'sakin' => array(
                'emoji' => $options['emoji_sakin'] ?? '🥺',
                'label' => $options['emoji_label_sakin'] ?? $t['sad']
            ),
            'bitti' => array(
                'emoji' => $options['emoji_bitti'] ?? '😔',
                'label' => $options['emoji_label_bitti'] ?? $t['end']
            )
        );
    ?>
    <div class="ruh-reactions-section">
        <div class="reactions-header">
            <h3><?php echo $t['react_title']; ?></h3>
            <span class="total-reactions"><span id="total-reaction-count">0</span> <?php echo $t['reactions']; ?></span>
        </div>
        <div class="content-reactions">
            <?php foreach ($reaction_settings as $key => $reaction) : ?>
            <div class="reaction-item" data-reaction="<?php echo esc_attr($key); ?>">
                <button class="content-reaction-btn" data-reaction="<?php echo esc_attr($key); ?>" type="button" aria-label="<?php echo esc_attr($reaction['label']); ?>">
                    <span class="reaction-emoji"><?php echo esc_html($reaction['emoji']); ?></span>
                    <span class="reaction-ring"></span>
                </button>
                <span class="reaction-label"><?php echo esc_html($reaction['label']); ?></span>
                <span class="reaction-count">0</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="ruh-comments-main">
        <div class="comments-header">
            <h3 class="comments-title">
                <svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M9,22A1,1 0 0,1 8,21V18H4A2,2 0 0,1 2,16V4C2,2.89 2.9,2 4,2H20A2,2 0 0,1 22,4V16A2,2 0 0,1 20,18H13.9L10.2,21.71C10,21.9 9.75,22 9.5,22V22H9Z"/></svg>
                <span class="comment-count"><?php echo $comment_count; ?></span> <?php echo $t['comments']; ?>
            </h3>
            
            <?php if (isset($options['enable_sorting']) && $options['enable_sorting']) : ?>
            <div class="sort-buttons">
                <button class="sort-btn active" data-sort="newest"><?php echo $t['newest']; ?></button>
                <button class="sort-btn" data-sort="oldest"><?php echo $t['oldest']; ?></button>
                <button class="sort-btn" data-sort="best"><?php echo $t['best']; ?></button>
                <button class="sort-btn" data-sort="discussed"><?php echo $t['discussed']; ?></button>
            </div>
            <?php endif; ?>
            <?php if (is_user_logged_in() && (!isset($options['enable_notifications']) || !empty($options['enable_notifications']))) : ?>
            <div class="ruh-notify-wrap">
                <button type="button" id="ruh-notify-btn" class="ruh-notify-btn" aria-label="<?php echo esc_attr($t['notifications']); ?>">
                    <svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M12,22A2,2 0 0,0 14,20H10A2,2 0 0,0 12,22M18,16V11C18,7.93 16.36,5.36 13.5,4.68V4A1.5,1.5 0 0,0 12,2.5A1.5,1.5 0 0,0 10.5,4V4.68C7.63,5.36 6,7.92 6,11V16L4,18V19H20V18L18,16Z"/></svg>
                    <span class="ruh-notify-count" id="ruh-notify-count" hidden>0</span>
                </button>
                <div id="ruh-notify-panel" class="ruh-notify-panel" hidden></div>
            </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($options['enable_comment_search'])) : ?>
        <div class="ruh-comment-tools">
            <input type="search" id="ruh-comment-search" placeholder="<?php echo esc_attr($t['search_comments']); ?>" autocomplete="off">
            <input type="text" id="ruh-comment-author" placeholder="<?php echo esc_attr($t['filter_user']); ?>" autocomplete="off">
        </div>
        <?php endif; ?>

        <?php 
        // Yorum Kurallari
        $enable_rules = isset($options['enable_comment_rules']) && $options['enable_comment_rules'];
        $rules_text = $options['comment_rules_text'] ?? '';
        if ($enable_rules && !empty($rules_text)) : 
            $rules_array = array_filter(array_map('trim', explode("\n", $rules_text)));
        ?>
        <div class="ruh-comment-rules">
            <button type="button" class="ruh-rules-toggle" id="ruh-rules-toggle">
                <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12,2A10,10 0 0,1 22,12A10,10 0 0,1 12,22A10,10 0 0,1 2,12A10,10 0 0,1 12,2M12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20A8,8 0 0,0 20,12A8,8 0 0,0 12,4M11,16.5L6.5,12L7.91,10.59L11,13.67L16.59,8.09L18,9.5L11,16.5Z"/></svg>
                <span><?php echo $ruh_lang === 'en_US' ? 'Comment Rules' : 'Yorum Kuralları'; ?></span>
                <svg class="toggle-arrow" viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z"/></svg>
            </button>
            <div class="ruh-rules-content" id="ruh-rules-content" style="display:none;">
                <ul class="ruh-rules-list">
                    <?php foreach ($rules_array as $rule) : ?>
                        <li><?php echo esc_html($rule); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <?php if (comments_open()) : ?>
            <?php if (is_user_logged_in()) : ?>
                <div id="ruh-comment-form-wrapper">
                    <div class="comment-user-avatar">
                        <?php 
                        $avatar = ruh_get_avatar($current_user_id, 40, '', '', array('class' => 'ruh-avatar'));
                        if ($avatar) {
                            echo $avatar;
                        } else {
                            // Varsayılan avatar
                            $name_initial = strtoupper(substr(wp_get_current_user()->display_name, 0, 1));
                            echo '<div class="default-avatar">' . esc_html($name_initial) . '</div>';
                        }
                        ?>
                    </div>
                    <div class="comment-form-content">
                        <div class="user-info-bar">
                            <span class="user-name"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
                            <span class="user-level comment-level level-tier-<?php echo esc_attr(function_exists('ruh_get_level_tier') ? ruh_get_level_tier($user_level) : 'novice'); ?>" data-level="<?php echo intval($user_level); ?>">Lv.<?php echo intval($user_level); ?></span>
                            <?php if (!empty($user_badges)) : ?>
                                <span class="user-badges">
                                    <?php foreach (array_slice($user_badges, 0, 3) as $badge) : ?>
                                        <span class="badge-item comment-badge-item" data-rarity="<?php echo esc_attr(function_exists('ruh_get_badge_rarity') ? ruh_get_badge_rarity($badge) : 'common'); ?>">
                                            <span class="badge-icon comment-badge">
                                                <?php 
                                                if (!empty($badge->badge_svg)) {
                                                    echo $badge->badge_svg;
                                                } else {
                                                    echo '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="#667eea" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/></svg>';
                                                }
                                                ?>
                                            </span>
                                            <span class="badge-name comment-badge-name"><?php echo esc_html($badge->badge_name); ?></span>
                                        </span>
                                    <?php endforeach; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <form id="commentform" class="comment-form">
                            <div id="ruh-editor-toolbar">
                                <button type="button" class="toolbar-btn" data-action="bold" title="<?php echo $t['bold']; ?> (Ctrl+B)">
                                    <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M13.5,15.5H10V12.5H13.5A1.5,1.5 0 0,1 15,14A1.5,1.5 0 0,1 13.5,15.5M10,6.5H13A1.5,1.5 0 0,1 14.5,8A1.5,1.5 0 0,1 13,9.5H10M15.6,10.79C16.57,10.11 17.25,9 17.25,8C17.25,5.74 15.5,4 13.25,4H7V18H14.04C16.14,18 17.75,16.3 17.75,14.21C17.75,12.69 16.89,11.39 15.6,10.79Z"/></svg>
                                </button>
                                <button type="button" class="toolbar-btn" data-action="italic" title="<?php echo $t['italic']; ?> (Ctrl+I)">
                                    <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M10,4V7H12.21L8.79,15H6V18H14V15H11.79L15.21,7H18V4H10Z"/></svg>
                                </button>
                                <button type="button" class="toolbar-btn" data-action="spoiler" title="Spoiler">
                                    <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z"/></svg>
                                </button>
                                <button type="button" class="toolbar-btn gif-btn" data-action="gif" title="<?php echo $t['add_gif']; ?>">
                                    <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M11.5,9H13V15H11.5V9M9,9V15H6A1.5,1.5 0 0,1 4.5,13.5V10.5A1.5,1.5 0 0,1 6,9H9M7.5,10.5H6V13.5H7.5V10.5M19,10.5V9H14.5V15H16V13H18V11.5H16V10.5H19Z"/></svg>
                                </button>
                            </div>
                            <?php $max_comment_length = isset($options['max_comment_length']) ? intval($options['max_comment_length']) : 1000; ?>
                            <textarea id="comment" name="comment" placeholder="<?php echo $t['write_comment']; ?>" required maxlength="<?php echo $max_comment_length; ?>"></textarea>
                            <span class="form-shortcut"><?php echo $ruh_lang === 'en_US' ? 'Ctrl+Enter to send' : 'Göndermek için Ctrl+Enter'; ?></span>
                            <div class="form-footer">
                                <span class="char-counter" id="char-counter"><span id="char-count">0</span>/<?php echo $max_comment_length; ?></span>
                                <span class="form-hint"><?php echo $ruh_lang === 'en_US' ? '**bold**  *italic*  ||spoiler||  @mention' : '**kalın**  *italik*  ||spoiler||  @etiket'; ?></span>
                                <span id="reply-indicator" style="display:none;">
                                    <span id="reply-to-name"></span>
                                    <button type="button" id="cancel-reply">✕</button>
                                </span>
                                <!-- Honeypot spam koruması - botlar doldurur, insanlar görmez -->
                                <input type="text" name="ruh_honeypot" value="" style="display:none !important; visibility:hidden; position:absolute; left:-9999px;" tabindex="-1" autocomplete="off">
                                <input type="hidden" name="comment_post_ID" value="<?php echo esc_attr($post_id); ?>">
                                <input type="hidden" name="comment_parent" id="comment_parent" value="0">
                                <button type="submit" id="submit" class="submit-btn">
                                    <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M2,21L23,12L2,3V10L17,12L2,14V21Z"/></svg>
                                    <?php echo $t['submit']; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else : ?>
                <div class="ruh-auth-required">
                    <svg viewBox="0 0 24 24" width="48" height="48"><path fill="#667eea" d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/></svg>
                    <h4><?php echo $t['login_required']; ?></h4>
                    <p><?php echo $t['login_desc']; ?></p>
                    <div class="auth-buttons">
                        <button type="button" class="auth-btn login-btn" id="ruh-open-login"><?php echo $t['login']; ?></button>
                        <button type="button" class="auth-btn register-btn" id="ruh-open-register"><?php echo $t['register']; ?></button>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div id="comment-list-wrapper">
            <ol class="comment-list" id="comment-list"></ol>
            <div id="comment-loader" class="skeleton-container">
                <div class="skeleton-comment">
                    <div class="skeleton-avatar"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-header">
                            <div class="skeleton-name"></div>
                            <div class="skeleton-date"></div>
                        </div>
                        <div class="skeleton-text"></div>
                        <div class="skeleton-text short"></div>
                    </div>
                </div>
                <div class="skeleton-comment">
                    <div class="skeleton-avatar"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-header">
                            <div class="skeleton-name"></div>
                            <div class="skeleton-date"></div>
                        </div>
                        <div class="skeleton-text"></div>
                        <div class="skeleton-text short"></div>
                    </div>
                </div>
                <div class="skeleton-comment">
                    <div class="skeleton-avatar"></div>
                    <div class="skeleton-content">
                        <div class="skeleton-header">
                            <div class="skeleton-name"></div>
                            <div class="skeleton-date"></div>
                        </div>
                        <div class="skeleton-text"></div>
                        <div class="skeleton-text short"></div>
                    </div>
                </div>
            </div>
            <button id="load-more-comments" class="load-more-btn" style="display:none;"><?php echo $t['load_more']; ?></button>
            <?php if ($comment_count == 0) : ?>
                <div class="no-comments" id="no-comments">
                    <p><?php echo $t['no_comments']; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- GIF Modal -->
    <div id="gif-modal" class="ruh-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="gif-modal-title">
        <div class="ruh-modal-content">
            <div class="ruh-modal-header">
                <h4 id="gif-modal-title"><?php echo $t['search_gif']; ?></h4>
                <button class="ruh-modal-close" aria-label="<?php echo esc_attr($t['close'] ?? 'Kapat'); ?>">&times;</button>
            </div>
            <input type="text" id="gif-search" placeholder="<?php echo $t['search_gif_placeholder']; ?>">
            <div id="gif-results"></div>
        </div>
    </div>

    <!-- Şikayet Modal -->
    <div id="report-modal" class="ruh-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="report-modal-title">
        <div class="ruh-modal-content report-modal-content">
            <div class="ruh-modal-header">
                <h4 id="report-modal-title"><?php echo $t['report_comment']; ?></h4>
                <button class="ruh-modal-close" aria-label="<?php echo esc_attr($t['close'] ?? 'Kapat'); ?>">&times;</button>
            </div>
            <form id="report-form">
                <input type="hidden" id="report-comment-id" value="">
                <div class="form-group">
                    <label><?php echo $t['report_type']; ?></label>
                    <select id="report-type" required>
                        <option value=""><?php echo $t['select']; ?></option>
                        <option value="spam"><?php echo $t['spam']; ?></option>
                        <option value="hakaret"><?php echo $t['insult']; ?></option>
                        <option value="nefret"><?php echo $t['hate']; ?></option>
                        <option value="spoiler"><?php echo $t['spoiler_unmarked']; ?></option>
                        <option value="yanlis_bilgi"><?php echo $t['false_info']; ?></option>
                        <option value="diger"><?php echo $t['other']; ?></option>
                    </select>
                </div>
                <div class="form-group">
                    <label><?php echo $t['description']; ?></label>
                    <textarea id="report-reason" placeholder="<?php echo $t['description_placeholder']; ?>" maxlength="500"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-cancel" onclick="document.getElementById('report-modal').style.display='none'"><?php echo $t['cancel']; ?></button>
                    <button type="submit" class="btn-submit"><?php echo $t['send_report']; ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Silme Onay Modal -->
    <div id="delete-confirm-modal" class="ruh-modal" style="display:none;" role="dialog" aria-modal="true" aria-labelledby="delete-modal-title">
        <div class="ruh-modal-content delete-modal-content">
            <div class="ruh-modal-header">
                <h4 id="delete-modal-title"><svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M19,4H15.5L14.5,3H9.5L8.5,4H5V6H19M6,19A2,2 0 0,0 8,21H16A2,2 0 0,0 18,19V7H6V19Z"/></svg> <?php echo $ruh_lang === 'en_US' ? 'Delete Comment' : 'Yorumu Sil'; ?></h4>
                <button class="ruh-modal-close" aria-label="<?php echo esc_attr($t['close'] ?? 'Kapat'); ?>">&times;</button>
            </div>
            <div class="delete-modal-body">
                <div class="delete-icon">
                    <svg viewBox="0 0 24 24" width="48" height="48"><path fill="#ef4444" d="M12,2C17.53,2 22,6.47 22,12C22,17.53 17.53,22 12,22C6.47,22 2,17.53 2,12C2,6.47 6.47,2 12,2M15.59,7L12,10.59L8.41,7L7,8.41L10.59,12L7,15.59L8.41,17L12,13.41L15.59,17L17,15.59L13.41,12L17,8.41L15.59,7Z"/></svg>
                </div>
                <p><?php echo $ruh_lang === 'en_US' ? 'Are you sure you want to delete this comment?' : 'Bu yorumu silmek istediğinizden emin misiniz?'; ?></p>
                <p class="delete-warning"><svg viewBox="0 0 24 24" width="14" height="14" style="vertical-align: middle;"><path fill="#ef4444" d="M13,13H11V7H13M13,17H11V15H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z"/></svg> <?php echo $ruh_lang === 'en_US' ? 'This action cannot be undone!' : 'Bu işlem geri alınamaz!'; ?></p>
            </div>
            <div class="delete-modal-actions">
                <button type="button" class="btn-cancel" id="delete-cancel-btn"><?php echo $t['cancel']; ?></button>
                <button type="button" class="btn-delete" id="delete-confirm-btn"><?php echo $t['delete']; ?></button>
            </div>
            <input type="hidden" id="delete-comment-id" value="">
        </div>
    </div>

    <!-- Auth Modal (Login/Register Popup) -->
    <div id="ruh-auth-modal" class="ruh-modal" style="display:none;" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr($t['login']); ?>">
        <div class="ruh-modal-content ruh-auth-modal-content">
            <div class="ruh-modal-header">
                <div class="ruh-auth-tabs">
                    <button type="button" class="ruh-auth-tab active" data-tab="login"><?php echo $t['login']; ?></button>
                    <button type="button" class="ruh-auth-tab" data-tab="register"><?php echo $t['register']; ?></button>
                </div>
                <button class="ruh-modal-close" aria-label="<?php echo esc_attr($t['close'] ?? 'Kapat'); ?>">&times;</button>
            </div>
            
            <!-- Login Form -->
            <div class="ruh-auth-form-container" id="ruh-login-form" style="display:block;">
                <form id="ruh-login-form-el">
                    <?php wp_nonce_field('ruh_auth_nonce', 'ruh_auth_nonce_field'); ?>
                    <div class="ruh-form-group">
                        <label for="ruh-login-user">
                            <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/></svg>
                            <?php echo $ruh_lang === 'en_US' ? 'Username or Email' : 'Kullanıcı Adı veya E-posta'; ?>
                        </label>
                        <input type="text" id="ruh-login-user" name="log" required autocomplete="username">
                    </div>
                    <div class="ruh-form-group">
                        <label for="ruh-login-pass">
                            <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12,17A2,2 0 0,0 14,15C14,13.89 13.1,13 12,13A2,2 0 0,0 10,15A2,2 0 0,0 12,17M18,8A2,2 0 0,1 20,10V20A2,2 0 0,1 18,22H6A2,2 0 0,1 4,20V10C4,8.89 4.9,8 6,8H7V6A5,5 0 0,1 12,1A5,5 0 0,1 17,6V8H18M12,3A3,3 0 0,0 9,6V8H15V6A3,3 0 0,0 12,3Z"/></svg>
                            <?php echo $ruh_lang === 'en_US' ? 'Password' : 'Şifre'; ?>
                        </label>
                        <input type="password" id="ruh-login-pass" name="pwd" required autocomplete="current-password">
                    </div>
                    <div class="ruh-form-options">
                        <label class="ruh-remember-me">
                            <input type="checkbox" name="rememberme" value="forever">
                            <span><?php echo $ruh_lang === 'en_US' ? 'Remember me' : 'Beni hatırla'; ?></span>
                        </label>
                        <a href="<?php echo wp_lostpassword_url(); ?>" class="ruh-forgot-pass" target="_blank">
                            <?php echo $ruh_lang === 'en_US' ? 'Forgot password?' : 'Şifremı unuttum'; ?>
                        </a>
                    </div>
                    <div class="ruh-form-message" id="ruh-login-message"></div>
                    <button type="submit" class="ruh-auth-submit">
                        <span class="btn-text"><?php echo $t['login']; ?></span>
                        <span class="btn-loader" style="display:none;"></span>
                    </button>
                </form>
            </div>
            
            <!-- Register Form -->
            <div class="ruh-auth-form-container" id="ruh-register-form" style="display:none;">
                <form id="ruh-register-form-el">
                    <?php wp_nonce_field('ruh_auth_nonce', 'ruh_register_nonce_field'); ?>
                    <div class="ruh-form-group">
                        <label for="ruh-reg-user">
                            <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/></svg>
                            <?php echo $ruh_lang === 'en_US' ? 'Username' : 'Kullanıcı Adı'; ?>
                        </label>
                        <input type="text" id="ruh-reg-user" name="user_login" required autocomplete="username" minlength="3">
                    </div>
                    <div class="ruh-form-group">
                        <label for="ruh-reg-email">
                            <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M20,8L12,13L4,8V6L12,11L20,6M20,4H4C2.89,4 2,4.89 2,6V18A2,2 0 0,0 4,20H20A2,2 0 0,0 22,18V6C22,4.89 21.1,4 20,4Z"/></svg>
                            <?php echo $ruh_lang === 'en_US' ? 'Email' : 'E-posta'; ?>
                        </label>
                        <input type="email" id="ruh-reg-email" name="user_email" required autocomplete="email">
                    </div>
                    <div class="ruh-form-group">
                        <label for="ruh-reg-pass">
                            <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12,17A2,2 0 0,0 14,15C14,13.89 13.1,13 12,13A2,2 0 0,0 10,15A2,2 0 0,0 12,17M18,8A2,2 0 0,1 20,10V20A2,2 0 0,1 18,22H6A2,2 0 0,1 4,20V10C4,8.89 4.9,8 6,8H7V6A5,5 0 0,1 12,1A5,5 0 0,1 17,6V8H18M12,3A3,3 0 0,0 9,6V8H15V6A3,3 0 0,0 12,3Z"/></svg>
                            <?php echo $ruh_lang === 'en_US' ? 'Password' : 'Şifre'; ?>
                        </label>
                        <input type="password" id="ruh-reg-pass" name="user_pass" required autocomplete="new-password" minlength="8">
                    </div>
                    <div class="ruh-form-group">
                        <label for="ruh-reg-pass-confirm">
                            <svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M12,17A2,2 0 0,0 14,15C14,13.89 13.1,13 12,13A2,2 0 0,0 10,15A2,2 0 0,0 12,17M18,8A2,2 0 0,1 20,10V20A2,2 0 0,1 18,22H6A2,2 0 0,1 4,20V10C4,8.89 4.9,8 6,8H7V6A5,5 0 0,1 12,1A5,5 0 0,1 17,6V8H18M12,3A3,3 0 0,0 9,6V8H15V6A3,3 0 0,0 12,3Z"/></svg>
                            <?php echo $ruh_lang === 'en_US' ? 'Confirm Password' : 'Şifre Tekrar'; ?>
                        </label>
                        <input type="password" id="ruh-reg-pass-confirm" name="user_pass_confirm" required autocomplete="new-password" minlength="8">
                    </div>
                    <div class="ruh-form-message" id="ruh-register-message"></div>
                    <button type="submit" class="ruh-auth-submit">
                        <span class="btn-text"><?php echo $t['register']; ?></span>
                        <span class="btn-loader" style="display:none;"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
/* Ruh Comment - Kompakt Tasarım */
#ruh-comments {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 100%;
    margin: 20px 0;
    color: #e0e0e0;
    background: transparent !important;
}

/* Tepkiler */
.ruh-reactions-section {
    background: transparent;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    text-align: center;
}

.reactions-header {
    margin-bottom: 16px;
}

.reactions-header h3 {
    margin: 0 0 4px 0;
    font-size: 16px;
    color: #fff;
    font-weight: 500;
}

.total-reactions {
    font-size: 13px;
    color: #888;
}

.content-reactions {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 20px;
}

.reaction-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
}

.content-reaction-btn {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    padding: 8px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.content-reaction-btn:hover {
    transform: scale(1.1);
}

.content-reaction-btn:active {
    transform: scale(0.95);
}

.content-reaction-btn.active {
    outline: none;
    background: rgba(168, 85, 247, 0.18);
}

.reaction-emoji {
    font-size: 32px;
    line-height: 1;
    display: block;
    transition: transform 0.2s ease;
}

/* Tepki Pop Animasyonu */
.reaction-emoji.reaction-pop {
    animation: reactionPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes reactionPop {
    0% { transform: scale(1); }
    50% { transform: scale(1.4); }
    100% { transform: scale(1); }
}

/* Parçacık Efekti */
.reaction-particle {
    position: absolute;
    font-size: 16px;
    pointer-events: none;
    animation: particleFly 0.6s ease-out forwards;
    z-index: 10;
}

@keyframes particleFly {
    0% {
        opacity: 1;
        transform: translate(0, 0) scale(1);
    }
    100% {
        opacity: 0;
        transform: translate(var(--x), var(--y)) scale(0.5);
    }
}

.reaction-label {
    font-size: 11px;
    color: #888;
    white-space: nowrap;
}

.reaction-count {
    font-size: 14px;
    color: #fff;
    font-weight: 500;
}

.content-reaction-btn.active + .reaction-label {
    color: #a855f7;
}

@media (max-width: 768px) {
    .ruh-reactions-section { padding: 12px 4px 16px; }
    .content-reactions {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 12px 8px;
        justify-items: center;
    }
    .content-reaction-btn { width: 62px; height: 62px; }
    .reaction-emoji { font-size: 32px; }
    .reaction-label { font-size: 12px; }
    .reaction-count { font-size: 13px; }
}

/* Ana Bölüm */
.ruh-comments-main {
    background: transparent;
    border-radius: 12px;
    padding: 16px;
}

.comments-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid #333;
    flex-wrap: wrap;
    gap: 10px;
}

.comments-title {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #fff;
}

.comments-title svg {
    color: #667eea;
}

.comment-count {
    color: #667eea;
}

.sort-buttons {
    display: flex;
    gap: 6px;
}

.sort-btn {
    padding: 6px 12px;
    background: #2a2a2a;
    border: 1px solid #333;
    border-radius: 6px;
    font-size: 12px;
    color: #888;
    cursor: pointer;
    transition: all 0.2s;
}

.sort-btn:hover {
    background: #333;
}

.sort-btn.active {
    background: #667eea;
    border-color: #667eea;
    color: #fff;
}

/* Yorum Formu */
#ruh-comment-form-wrapper {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    padding: 12px;
    background: transparent;
    border-radius: 10px;
}

.comment-user-avatar {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
}

.comment-user-avatar img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.comment-form-content {
    flex: 1;
    min-width: 0;
}

.user-info-bar {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.user-name {
    font-weight: 600;
    color: #fff;
    font-size: 14px;
}

.user-level {
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    color: #fff;
}

.user-badges {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-left: 10px;
}

.badge-item {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px 3px 4px;
    border-radius: 12px;
}

.badge-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
}

.badge-icon svg {
    width: 16px !important;
    height: 16px !important;
    display: block;
}

.badge-name {
    font-size: 11px;
    color: #667eea;
    font-weight: 500;
}

/* Yorum icerisindeki rozetler */
.comment-badges {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-left: 8px;
}

.comment-badge-item {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 2px 6px 2px 3px;
    border-radius: 10px;
}

.comment-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 14px;
    height: 14px;
}

.comment-badge svg {
    width: 14px !important;
    height: 14px !important;
    display: block;
}

.comment-badge-name {
    font-size: 10px;
    font-weight: 500;
}

/* Avatar görüntüleme */
.comment-user-avatar img,
.comment-avatar img,
.comment-user-avatar .ruh-avatar,
.comment-avatar .ruh-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.default-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-weight: 600;
    font-size: 16px;
}

.comment-avatar {
    width: 40px;
    height: 40px;
    flex-shrink: 0;
}

.comment-avatar img.avatar {
    width: 100%;
    height: 100%;
    border-radius: 50%;
}

.comment-form {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

#ruh-editor-toolbar {
    display: flex;
    gap: 4px;
}

.toolbar-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    color: #aaa;
    cursor: pointer;
    transition: all 0.2s;
}

.toolbar-btn:hover {
    background: #667eea;
    border-color: #667eea;
    color: #fff;
}

#comment {
    width: 100%;
    min-height: 80px;
    padding: 10px;
    background: transparent;
    border: 1px solid rgba(255,255,255,0.12);
    border-radius: 10px;
    color: #e0e0e0;
    font-size: 14px;
    resize: vertical;
    box-sizing: border-box;
}

#comment:focus {
    outline: none;
    border-color: #a855f7;
}

.form-footer {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

.char-counter {
    font-size: 11px;
    color: #666;
}

#reply-indicator {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: #333;
    border-radius: 4px;
    font-size: 12px;
    color: #aaa;
}

#cancel-reply {
    background: none;
    border: none;
    color: #ef4444;
    cursor: pointer;
    font-size: 14px;
    padding: 0;
}

.submit-btn {
    margin-left: auto;
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
    border-radius: 8px;
    color: #fff;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.submit-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.ruh-auth-required {
    text-align: center;
    padding: 30px 20px;
    background: transparent;
    border-radius: 12px;
    margin-bottom: 16px;
    position: relative;
    z-index: 1;
}

.ruh-auth-required * {
    pointer-events: auto;
}

.ruh-auth-required svg {
    margin-bottom: 12px;
    opacity: 0.8;
}

.ruh-auth-required h4 {
    margin: 0 0 8px;
    color: #fff;
    font-size: 16px;
    font-weight: 600;
}

.ruh-auth-required p {
    margin: 0 0 16px;
    color: #888;
    font-size: 13px;
    line-height: 1.5;
}

.auth-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.auth-btn {
    display: inline-block;
    padding: 10px 24px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none !important;
    transition: all 0.2s;
    cursor: pointer;
}

.auth-btn.login-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff !important;
    border: none;
}

.auth-btn.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    color: #fff !important;
}

.auth-btn.register-btn {
    background: transparent;
    border: 1px solid #444;
    color: #ccc !important;
}

.auth-btn.register-btn:hover {
    background: #333;
    border-color: #555;
    color: #fff !important;
}

/* Yorum Listesi — kart stilleri ruh-comment-style.css'te */
.comment-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.comment-avatar {
    flex-shrink: 0;
}

.comment-main {
    flex: 1;
    min-width: 0;
}

.comment-author-link,
.comment-avatar-link {
    text-decoration: none;
    transition: opacity 0.2s;
}

.comment-avatar-link:hover {
    opacity: 0.8;
}

.comment-badges {
    display: flex;
    gap: 3px;
}

.comment-badge {
    width: 16px;
    height: 16px;
}

.comment-badge svg {
    width: 100%;
    height: 100%;
}

.comment-text {
    word-wrap: break-word;
}

.comment-text strong,
.comment-text b {
    font-weight: 700;
    color: #fff;
}

.comment-text em,
.comment-text i {
    font-style: italic;
    color: #e0e0e0;
}

.comment-text img {
    max-width: 100%;
    border-radius: 6px;
    margin: 8px 0;
}

/* Spoiler */
.spoiler {
    background: #333;
    color: transparent;
    padding: 2px 6px;
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.2s;
}

.spoiler.revealed {
    background: #444;
    color: #fff;
}

/* Like/Dislike — boyutlar ruh-comment-style.css'te */
.action-btn.like-btn,
.action-btn.dislike-btn {
    background: transparent;
    border: none;
}

.action-btn.like-btn:hover,
.action-btn.like-btn.liked {
    color: #22c55e;
    background: transparent;
}

.action-btn.like-btn.liked svg path {
    fill: #22c55e;
}

.action-btn.like-btn.liked .like-count {
    color: #22c55e;
}

.action-btn.dislike-btn:hover,
.action-btn.dislike-btn.disliked {
    color: #ef4444;
    background: transparent;
}

.action-btn.dislike-btn.disliked svg path {
    fill: #ef4444;
}

/* Like/Dislike okları */
.action-btn.like-btn svg,
.action-btn.dislike-btn svg {
    width: 16px;
    height: 16px;
}

.action-btn.reply-btn:hover {
    border-color: #3b82f6;
    color: #3b82f6;
    background: rgba(59, 130, 246, 0.1);
}

.action-btn svg {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
}

/* Yanıtlar */
.comment-replies {
    margin-top: 8px;
    margin-left: 20px;
    padding-left: 12px;
    border-left: 2px solid #333;
}

.comment-replies .comment-body {
    background: transparent;
}

/* Inline Yanıt Formu */
.inline-reply-form {
    margin: 6px 0 6px 0;
    padding: 8px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    box-sizing: border-box;
    width: 100%;
}

.reply-form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
    font-size: 12px;
    color: #888;
}

.reply-form-header span {
    color: #667eea;
}

.cancel-inline-reply {
    background: none;
    border: none;
    color: #666;
    font-size: 18px;
    cursor: pointer;
    padding: 0 4px;
}

.cancel-inline-reply:hover {
    color: #fff;
}

.inline-reply-textarea {
    width: 100%;
    min-height: 70px;
    padding: 10px;
    background: #141414;
    border: 1px solid #333;
    border-radius: 6px;
    color: #e0e0e0;
    font-size: 13px;
    resize: vertical;
    box-sizing: border-box;
}

.inline-reply-textarea:focus {
    outline: none;
    border-color: #667eea;
}

/* Inline Toolbar */
.inline-toolbar {
    display: flex;
    gap: 4px;
    margin-bottom: 8px;
}

.inline-toolbar-btn {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #252525;
    border: 1px solid #333;
    border-radius: 4px;
    color: #888;
    cursor: pointer;
    transition: all 0.2s;
}

.inline-toolbar-btn:hover {
    background: #667eea;
    border-color: #667eea;
    color: #fff;
}

.reply-form-actions {
    display: flex;
    justify-content: flex-end;
    margin-top: 10px;
}

.submit-inline-reply {
    padding: 8px 16px;
    background: #667eea;
    border: none;
    border-radius: 6px;
    color: #fff;
    font-size: 13px;
    cursor: pointer;
}

.submit-inline-reply:hover {
    background: #5a6fd6;
}

.submit-inline-reply:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

/* Yanıt Formu Eski */
.reply-form {
    margin-top: 10px;
    padding: 10px;
    background: #252525;
    border-radius: 6px;
}

.reply-form textarea {
    width: 100%;
    min-height: 60px;
    padding: 8px;
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 6px;
    color: #e0e0e0;
    font-size: 13px;
    resize: vertical;
    box-sizing: border-box;
}

.reply-form textarea:focus {
    outline: none;
    border-color: #667eea;
}

.old-reply-form-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
    justify-content: flex-end;
}

.reply-cancel-btn,
.reply-submit-btn {
    padding: 6px 14px;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.2s;
}

.reply-cancel-btn {
    background: #333;
    color: #aaa;
}

.reply-submit-btn {
    background: #667eea;
    color: #fff;
}

/* Düzenleme Formu */
.edit-form {
    margin-top: 8px;
}

.edit-form textarea {
    width: 100%;
    min-height: 60px;
    padding: 8px;
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 6px;
    color: #e0e0e0;
    font-size: 13px;
    resize: vertical;
    box-sizing: border-box;
}

.edit-form-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
    justify-content: flex-end;
}

.edit-cancel-btn,
.edit-save-btn {
    padding: 6px 14px;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
}

.edit-cancel-btn {
    background: #333;
    color: #aaa;
}

.edit-save-btn {
    background: #10b981;
    color: #fff;
}

/* Loader */
#comment-loader {
    text-align: center;
    padding: 20px;
}

.ruh-loader {
    width: 30px;
    height: 30px;
    border: 3px solid #333;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.load-more-btn {
    width: 100%;
    padding: 10px;
    background: #2a2a2a;
    border: 1px dashed #444;
    border-radius: 6px;
    color: #888;
    font-size: 13px;
    cursor: pointer;
    margin-top: 10px;
}

.load-more-btn:hover {
    background: #333;
    color: #fff;
}

.no-comments {
    text-align: center;
    padding: 30px;
    color: #666;
}

/* GIF Modal */
.ruh-modal {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.72);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(2px);
}
.ruh-modal[style*="block"] { display: flex !important; }

.ruh-modal-content {
    background: #1C1C1C;
    border: 1px solid #2d2d2d;
    border-radius: 10px;
    width: 92%;
    max-width: 420px;
    max-height: 82vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    box-shadow: 0 24px 48px rgba(0,0,0,0.6);
    animation: ruh-modal-in 0.18s ease;
}
@keyframes ruh-modal-in {
    from { opacity: 0; transform: scale(0.94) translateY(8px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

.ruh-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 14px;
    border-bottom: 1px solid #2d2d2d;
    flex-shrink: 0;
}
.ruh-modal-header h4 {
    margin: 0;
    color: #f0f0f0;
    font-size: 0.82rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}
.ruh-modal-header h4 svg { width: 15px; height: 15px; flex-shrink: 0; }

.ruh-modal-close {
    background: none;
    border: none;
    color: #777;
    font-size: 20px;
    cursor: pointer;
    line-height: 1;
    padding: 2px 5px;
    border-radius: 4px;
    transition: color 0.12s, background 0.12s;
}
.ruh-modal-close:hover { color: #fff; background: rgba(255,255,255,0.08); }

#gif-search {
    margin: 8px 10px;
    padding: 6px 8px;
    background: #242424;
    border: 1px solid #2d2d2d;
    border-radius: 6px;
    color: #f0f0f0;
    font-size: 0.75rem;
    outline: none;
    transition: border-color 0.15s;
}
#gif-search:focus { border-color: #dc3545; }

#gif-results {
    padding: 8px 10px;
    overflow-y: auto;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 5px;
}
#gif-results img {
    width: 100%;
    border-radius: 4px;
    cursor: pointer;
    transition: opacity 0.12s;
}
#gif-results img:hover { opacity: 0.82; }

/* 3 Nokta menü stilleri ruh-comment-style.css (glassmorphism + body portal) */

/* Silme Onay Modal */
.delete-modal-content {
    max-width: 340px;
    background: #1C1C1C;
    border: 1px solid #2d2d2d;
}
.delete-modal-content .ruh-modal-header {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    padding: 10px 14px;
    border-radius: 9px 9px 0 0;
}
.delete-modal-content .ruh-modal-header h4 { font-size: 0.8rem; }
.delete-modal-body { padding: 14px 16px; text-align: center; }
.delete-modal-body .delete-icon { margin-bottom: 8px; }
.delete-modal-body p { margin: 0 0 5px; color: #ccc; font-size: 0.76rem; }
.delete-modal-body .delete-warning { color: #ef4444; font-size: 0.68rem; font-weight: 500; }
.delete-modal-actions { display: flex; gap: 8px; padding: 0 16px 14px; justify-content: center; }
.delete-modal-actions .btn-cancel {
    padding: 6px 16px;
    background: #2a2a2a;
    border: 1px solid #3a3a3a;
    border-radius: 6px;
    color: #ccc;
    cursor: pointer;
    font-size: 0.72rem;
    transition: background 0.12s;
}
.delete-modal-actions .btn-cancel:hover { background: #333; color: #fff; }
.delete-modal-actions .btn-delete {
    padding: 6px 16px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border: none;
    border-radius: 6px;
    color: #fff;
    cursor: pointer;
    font-size: 0.72rem;
    font-weight: 600;
    transition: transform 0.12s, box-shadow 0.12s;
}
.delete-modal-actions .btn-delete:hover {
    transform: translateY(-1px);
    box-shadow: 0 3px 10px rgba(239,68,68,0.4);
}

/* Şikayet Modal */
.report-modal-content {
    max-width: 380px;
    background: #1C1C1C;
    border: 1px solid #2d2d2d;
}
.report-modal-content .ruh-modal-header {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    padding: 10px 14px;
    border-radius: 9px 9px 0 0;
}
.report-modal-content .ruh-modal-header h4 { display: flex; align-items: center; gap: 6px; font-size: 0.8rem; }
.report-modal-content .ruh-modal-header h4::before { content: "⚠️"; font-size: 0.85rem; }

#report-form { padding: 12px 14px; }

#report-form .form-group { margin-bottom: 10px; }
#report-form label {
    display: block;
    margin-bottom: 4px;
    font-size: 0.72rem;
    font-weight: 500;
    color: #c8c8c8;
}
#report-form select,
#report-form textarea {
    width: 100%;
    padding: 6px 8px;
    background: #141414;
    border: 1px solid #2d2d2d;
    border-radius: 6px;
    color: #f0f0f0;
    font-size: 0.72rem;
    transition: border-color 0.15s;
    outline: none;
    font-family: inherit;
}
#report-form select:focus,
#report-form textarea:focus { border-color: #ef4444; }
#report-form textarea { min-height: 62px; resize: vertical; }

.form-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #2d2d2d;
}
.btn-cancel {
    padding: 5px 14px;
    background: #2a2a2a;
    border: 1px solid #3a3a3a;
    border-radius: 6px;
    color: #aaa;
    font-size: 0.7rem;
    cursor: pointer;
    transition: background 0.12s, color 0.12s;
    font-family: inherit;
}
.btn-cancel:hover { background: #333; color: #fff; }
.btn-submit {
    padding: 5px 14px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border: none;
    border-radius: 6px;
    color: #fff;
    font-size: 0.7rem;
    font-weight: 600;
    cursor: pointer;
    transition: transform 0.12s, box-shadow 0.12s;
    font-family: inherit;
}
.btn-submit:hover { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(239,68,68,0.35); }

/* Mobil Uyumluluk */
@media (max-width: 768px) {
    .ruh-reactions-section { padding: 10px 8px; }
    .reactions { gap: 4px; }
    .reaction { padding: 4px 6px; min-width: 50px; }
    .reaction-emoji { font-size: 1rem; }
    .reaction-name { font-size: 0.55rem; }
    .reaction .count { font-size: 0.55rem; }
    .comments-header { flex-direction: column; align-items: flex-start; }
    .sort-buttons { width: 100%; justify-content: flex-start; }
    #ruh-comment-form-wrapper { padding: 8px; }
    .comment-form-content { width: 100%; }
    #comment { min-height: 56px; font-size: 0.78rem; }
    .comment-body { padding: 6px 8px; }
    .comment-avatar img { width: 26px; height: 26px; }
    .comment-replies { margin-left: 6px; padding-left: 6px; }
    .form-footer { flex-direction: column; align-items: stretch; gap: 5px; }
    .submit-btn { margin-left: 0; justify-content: center; }
    #gif-results { grid-template-columns: repeat(2, 1fr); }
    .comment-actions { flex-wrap: wrap; }
    .action-btn { padding: 2px 6px; font-size: 0.62rem; }
    .user-info-bar {
        font-size: 0.65rem;
    }
    
    .user-level {
        font-size: 10px;
        padding: 2px 6px;
    }
    
    #ruh-editor-toolbar {
        flex-wrap: wrap;
    }
    
    .toolbar-btn {
        width: 28px;
        height: 28px;
    }
}

@media (max-width: 480px) {
    .reactions {
        gap: 2px;
    }
    
    .reaction {
        padding: 3px 5px;
        gap: 2px;
    }
    
    .reaction-emoji {
        font-size: 11px;
    }
    
    .reaction-name {
        display: none;
    }
    
    .reaction .count {
        font-size: 9px;
    }
    
    .comment-user-avatar,
    .comment-avatar {
        width: 32px;
        height: 32px;
        flex-shrink: 0;
    }
    
    .comment-avatar img,
    .comment-user-avatar img {
        width: 32px !important;
        height: 32px !important;
    }
    
    .comment-main {
        width: calc(100% - 40px);
    }
    
    .ruh-auth-required {
        padding: 16px 12px;
    }
    
    .auth-btn {
        padding: 6px 14px;
        font-size: 11px;
    }
    
    .comment-actions {
        gap: 3px;
        flex-wrap: wrap;
    }
    
    .action-btn {
        padding: 3px 6px;
        font-size: 10px;
    }
    
    .action-btn svg {
        width: 11px;
        height: 11px;
    }
    
    .more-btn {
        width: 26px;
        height: 26px;
    }
}

/* ========== AUTH MODAL STYLES ========== */
.ruh-auth-modal-content {
    width: 100%;
    max-width: 420px;
    background: #1a1a1a;
    border-radius: 16px;
    border: 1px solid #333;
    overflow: hidden;
    animation: ruh-modal-slide-up 0.3s ease;
}

@keyframes ruh-modal-slide-up {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.ruh-auth-modal-content .ruh-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0;
    background: #222;
    border-bottom: 1px solid #333;
}

.ruh-auth-tabs {
    display: flex;
    flex: 1;
}

.ruh-auth-tab {
    flex: 1;
    padding: 16px 20px;
    background: transparent;
    border: none;
    color: #888;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.ruh-auth-tab:hover {
    color: #ccc;
    background: rgba(255,255,255,0.03);
}

.ruh-auth-tab.active {
    color: #fff;
    background: rgba(102, 126, 234, 0.1);
}

.ruh-auth-tab.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: linear-gradient(135deg, #667eea, #764ba2);
}

.ruh-auth-modal-content .ruh-modal-close {
    background: transparent;
    border: none;
    color: #666;
    font-size: 24px;
    cursor: pointer;
    padding: 16px 20px;
    transition: color 0.2s;
}

.ruh-auth-modal-content .ruh-modal-close:hover {
    color: #fff;
}

.ruh-auth-form-container {
    padding: 24px;
}

.ruh-form-group {
    margin-bottom: 18px;
}

.ruh-form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #aaa;
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 8px;
}

.ruh-form-group label svg {
    color: #667eea;
}

.ruh-form-group input {
    width: 100%;
    padding: 12px 14px;
    background: #111;
    border: 2px solid #333;
    border-radius: 10px;
    color: #fff;
    font-size: 14px;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.ruh-form-group input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.15);
}

.ruh-form-group input::placeholder {
    color: #555;
}

.ruh-form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
    flex-wrap: wrap;
    gap: 10px;
}

.ruh-remember-me {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    color: #888;
    font-size: 13px;
}

.ruh-remember-me input[type="checkbox"] {
    width: 16px;
    height: 16px;
    accent-color: #667eea;
    cursor: pointer;
}

.ruh-forgot-pass {
    color: #667eea;
    font-size: 13px;
    text-decoration: none;
    transition: color 0.2s;
}

.ruh-forgot-pass:hover {
    color: #8b9ff5;
    text-decoration: underline;
}

.ruh-form-message {
    padding: 10px 14px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 16px;
    display: none;
}

.ruh-form-message.error {
    display: block;
    background: rgba(239, 68, 68, 0.15);
    border: 1px solid rgba(239, 68, 68, 0.3);
    color: #f87171;
}

.ruh-form-message.success {
    display: block;
    background: rgba(34, 197, 94, 0.15);
    border: 1px solid rgba(34, 197, 94, 0.3);
    color: #4ade80;
}

.ruh-auth-submit {
    width: 100%;
    padding: 14px 20px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border: none;
    border-radius: 10px;
    color: #fff;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.ruh-auth-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.ruh-auth-submit:active {
    transform: translateY(0);
}

.ruh-auth-submit:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

.ruh-auth-submit .btn-loader {
    width: 18px;
    height: 18px;
    border: 2px solid rgba(255,255,255,0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: ruh-spin 0.8s linear infinite;
}

@keyframes ruh-spin {
    to { transform: rotate(360deg); }
}

/* Auth Modal Responsive */
@media (max-width: 480px) {
    .ruh-auth-modal-content {
        max-width: 100%;
        margin: 10px;
        border-radius: 12px;
    }
    
    .ruh-auth-tab {
        padding: 14px 12px;
        font-size: 13px;
    }
    
    .ruh-auth-form-container {
        padding: 18px;
    }
    
    .ruh-form-group input {
        padding: 11px 12px;
        font-size: 14px;
    }
    
    .ruh-auth-submit {
        padding: 12px 18px;
        font-size: 14px;
    }
}

/* ========== YORUM KURALLARI DROPDOWN ========== */
.ruh-comment-rules {
    margin-bottom: 16px;
}

.ruh-rules-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(102, 126, 234, 0.1);
    border: 1px solid rgba(102, 126, 234, 0.3);
    color: #a0aec0;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    transition: all 0.2s ease;
    width: 100%;
    justify-content: flex-start;
}

.ruh-rules-toggle:hover {
    background: rgba(102, 126, 234, 0.2);
    color: #667eea;
}

.ruh-rules-toggle svg {
    flex-shrink: 0;
}

.ruh-rules-toggle .toggle-arrow {
    margin-left: auto;
    transition: transform 0.3s ease;
}

.ruh-rules-toggle.active .toggle-arrow {
    transform: rotate(180deg);
}

.ruh-rules-content {
    background: rgba(30, 32, 44, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-top: none;
    border-radius: 0 0 8px 8px;
    padding: 16px;
    margin-top: -1px;
}

.ruh-rules-list {
    list-style: none;
    margin: 0;
    padding: 0;
}

.ruh-rules-list li {
    position: relative;
    padding: 8px 0 8px 24px;
    color: #a0aec0;
    font-size: 13px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.ruh-rules-list li:last-child {
    border-bottom: none;
}

.ruh-rules-list li::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 6px;
    height: 6px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 50%;
}

/* ========== SABITLENMIS YORUM STILI ========== */
.comment-item.pinned {
    border-left: 3px solid #dc3545 !important;
}

.pinned-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    font-size: 10px;
    font-weight: 600;
    padding: 3px 10px;
    border-radius: 12px;
    margin-left: 8px;
    letter-spacing: 0.3px;
}

.pinned-badge svg {
    width: 10px;
    height: 10px;
}

/* Admin Pin Button */
.pin-btn {
    background: none;
    border: none;
    color: #a0aec0;
    cursor: pointer;
    padding: 4px 8px;
    font-size: 12px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.pin-btn:hover {
    background: rgba(102, 126, 234, 0.2);
    color: #667eea;
}

.pin-btn.pinned {
    color: #667eea;
}

.pin-btn svg {
    width: 14px;
    height: 14px;
}

/* ========== SKELETON LOADING ========== */
.skeleton-container {
    padding: 16px;
}

.skeleton-comment {
    display: flex;
    gap: 12px;
    padding: 16px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.skeleton-comment:last-child {
    border-bottom: none;
}

.skeleton-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(90deg, #2a2a2a 25%, #333 50%, #2a2a2a 75%);
    background-size: 200% 100%;
    animation: skeletonShimmer 1.5s infinite;
}

.skeleton-content {
    flex: 1;
}

.skeleton-header {
    display: flex;
    gap: 12px;
    margin-bottom: 12px;
}

.skeleton-name {
    width: 120px;
    height: 14px;
    border-radius: 4px;
    background: linear-gradient(90deg, #2a2a2a 25%, #333 50%, #2a2a2a 75%);
    background-size: 200% 100%;
    animation: skeletonShimmer 1.5s infinite;
}

.skeleton-date {
    width: 80px;
    height: 14px;
    border-radius: 4px;
    background: linear-gradient(90deg, #2a2a2a 25%, #333 50%, #2a2a2a 75%);
    background-size: 200% 100%;
    animation: skeletonShimmer 1.5s infinite;
    animation-delay: 0.1s;
}

.skeleton-text {
    width: 100%;
    height: 12px;
    border-radius: 4px;
    margin-bottom: 8px;
    background: linear-gradient(90deg, #2a2a2a 25%, #333 50%, #2a2a2a 75%);
    background-size: 200% 100%;
    animation: skeletonShimmer 1.5s infinite;
    animation-delay: 0.2s;
}

.skeleton-text.short {
    width: 60%;
}

@keyframes skeletonShimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* ========== GELISMIS HOVER EFEKTLERI ========== */
.comment-item {
    transition: background 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease;
}

.action-btn {
    transition: all 0.2s ease;
}

.action-btn:hover {
    transform: translateY(-1px);
}

.action-btn:active {
    transform: translateY(0);
}

.submit-btn {
    transition: all 0.3s ease;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.submit-btn:active {
    transform: translateY(0);
}

.load-more-btn {
    transition: all 0.3s ease;
}

.load-more-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}
</style>
