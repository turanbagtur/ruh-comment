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

// Dinamik post ID - ONCE URL'den al, sonra WordPress ID kullan
$post_id = 0;

// Manga sayfalari icin URL'den dinamik ID al
if (function_exists('ruh_get_dynamic_post_id')) {
    $post_id = ruh_get_dynamic_post_id();
}

// Dinamik ID bulunamazsa normal WordPress post ID kullan
if (!$post_id) {
    $post_id = get_the_ID();
}

if (!$post_id) {
    $post_id = 1;
}

// Yorum sayisini dogrudan veritabanindan al
global $wpdb;
$comment_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_post_ID = %d AND comment_approved = '1'",
    $post_id
));
if (!$comment_count) $comment_count = 0;
$current_user_id = get_current_user_id();

// Kullanici seviye ve rozet bilgisi
$user_level = 1;
$user_badges = array();
if ($current_user_id) {
    global $wpdb;
    
    // Seviye bilgisi
    $level_table = $wpdb->prefix . 'ruh_user_levels';
    $user_level_data = $wpdb->get_row($wpdb->prepare("SELECT level FROM $level_table WHERE user_id = %d", $current_user_id));
    if ($user_level_data) {
        $user_level = $user_level_data->level;
    }
    
    // Rozet bilgisi - dogrudan veritabanindan cek
    $badges_table = $wpdb->prefix . 'ruh_badges';
    $user_badges_table = $wpdb->prefix . 'ruh_user_badges';
    
    // Tablolarin varligini kontrol et
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
?>
<div id="ruh-comments" class="comments-area">
    <?php if (isset($options['enable_reactions']) && $options['enable_reactions']) : ?>
    <div class="ruh-reactions-section">
        <div class="reactions-header">
            <h3><?php echo $t['react_title']; ?></h3>
            <span class="total-reactions"><span id="total-reaction-count">0</span> <?php echo $t['reactions']; ?></span>
        </div>
        <div class="content-reactions">
            <div class="reaction-item">
                <button class="content-reaction-btn" data-reaction="begendim">
                    <span class="reaction-emoji">👍</span>
                </button>
                <span class="reaction-label"><?php echo $t['like']; ?></span>
                <span class="reaction-count">0</span>
            </div>
            <div class="reaction-item">
                <button class="content-reaction-btn" data-reaction="sinir_bozucu">
                    <span class="reaction-emoji">😡</span>
                </button>
                <span class="reaction-label"><?php echo $t['angry']; ?></span>
                <span class="reaction-count">0</span>
            </div>
            <div class="reaction-item">
                <button class="content-reaction-btn" data-reaction="mukemmel">
                    <span class="reaction-emoji">🥰</span>
                </button>
                <span class="reaction-label"><?php echo $t['love']; ?></span>
                <span class="reaction-count">0</span>
            </div>
            <div class="reaction-item">
                <button class="content-reaction-btn" data-reaction="sasirtici">
                    <span class="reaction-emoji">😳</span>
                </button>
                <span class="reaction-label"><?php echo $t['wow']; ?></span>
                <span class="reaction-count">0</span>
            </div>
            <div class="reaction-item">
                <button class="content-reaction-btn" data-reaction="sakin">
                    <span class="reaction-emoji">🥺</span>
                </button>
                <span class="reaction-label"><?php echo $t['sad']; ?></span>
                <span class="reaction-count">0</span>
            </div>
            <div class="reaction-item">
                <button class="content-reaction-btn" data-reaction="bitti">
                    <span class="reaction-emoji">😔</span>
                </button>
                <span class="reaction-label"><?php echo $t['end']; ?></span>
                <span class="reaction-count">0</span>
            </div>
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
            </div>
            <?php endif; ?>
        </div>

        <?php if (comments_open()) : ?>
            <?php if (is_user_logged_in()) : ?>
                <div id="ruh-comment-form-wrapper">
                    <div class="comment-user-avatar">
                        <?php 
                        $avatar = get_avatar($current_user_id, 40, '', '', array('class' => 'ruh-avatar'));
                        if ($avatar) {
                            echo $avatar;
                        } else {
                            // Varsayilan avatar
                            $name_initial = strtoupper(substr(wp_get_current_user()->display_name, 0, 1));
                            echo '<div class="default-avatar">' . esc_html($name_initial) . '</div>';
                        }
                        ?>
                    </div>
                    <div class="comment-form-content">
                        <div class="user-info-bar">
                            <span class="user-name"><?php echo esc_html(wp_get_current_user()->display_name); ?></span>
                            <span class="user-level">Lv.<?php echo $user_level; ?></span>
                            <?php if (!empty($user_badges)) : ?>
                                <span class="user-badges">
                                    <?php foreach (array_slice($user_badges, 0, 3) as $badge) : ?>
                                        <span class="badge-item">
                                            <span class="badge-icon">
                                                <?php 
                                                if (!empty($badge->badge_svg)) {
                                                    echo $badge->badge_svg;
                                                } else {
                                                    echo '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="#667eea" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/></svg>';
                                                }
                                                ?>
                                            </span>
                                            <span class="badge-name"><?php echo esc_html($badge->badge_name); ?></span>
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
                            <textarea id="comment" name="comment" placeholder="<?php echo $t['write_comment']; ?>" required maxlength="5000"></textarea>
                            <div class="form-footer">
                                <span class="char-counter"><span id="char-count">0</span>/5000</span>
                                <span id="reply-indicator" style="display:none;">
                                    <span id="reply-to-name"></span>
                                    <button type="button" id="cancel-reply">✕</button>
                                </span>
                                <input type="hidden" name="comment_post_ID" value="<?php echo $post_id; ?>">
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
                <div class="auth-required">
                    <svg viewBox="0 0 24 24" width="48" height="48"><path fill="#667eea" d="M12,4A4,4 0 0,1 16,8A4,4 0 0,1 12,12A4,4 0 0,1 8,8A4,4 0 0,1 12,4M12,14C16.42,14 20,15.79 20,18V20H4V18C4,15.79 7.58,14 12,14Z"/></svg>
                    <h4><?php echo $t['login_required']; ?></h4>
                    <p><?php echo $t['login_desc']; ?></p>
                    <div class="auth-buttons">
                        <a href="<?php echo wp_login_url(get_permalink()); ?>" class="auth-btn login-btn"><?php echo $t['login']; ?></a>
                        <a href="<?php echo wp_registration_url(); ?>" class="auth-btn register-btn"><?php echo $t['register']; ?></a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div id="comment-list-wrapper">
            <ol class="comment-list" id="comment-list"></ol>
            <div id="comment-loader" style="display:none;">
                <div class="loader"></div>
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
    <div id="gif-modal" class="modal" style="display:none;">
        <div class="modal-content">
            <div class="modal-header">
                <h4><?php echo $t['search_gif']; ?></h4>
                <button class="modal-close">&times;</button>
            </div>
            <input type="text" id="gif-search" placeholder="<?php echo $t['search_gif_placeholder']; ?>">
            <div id="gif-results"></div>
        </div>
    </div>

    <!-- Sikayet Modal -->
    <div id="report-modal" class="modal" style="display:none;">
        <div class="modal-content report-modal-content">
            <div class="modal-header">
                <h4><?php echo $t['report_comment']; ?></h4>
                <button class="modal-close">&times;</button>
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
</div>

<style>
/* Ruh Comment - Kompakt Tasarim */
#ruh-comments {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 100%;
    margin: 20px 0;
    color: #e0e0e0;
}

/* Tepkiler - Ornek Gibi */
.ruh-reactions-section {
    background: #1F1F1F;
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
    background: transparent;
    border: none;
    padding: 8px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s ease;
}

.content-reaction-btn:hover {
    transform: scale(1.1);
}

.content-reaction-btn.active {
    outline: 3px solid #667EEA;
    outline-offset: 2px;
}

.reaction-emoji {
    font-size: 32px;
    line-height: 1;
    display: block;
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

/* Mobil - 3x2 Grid */
@media (max-width: 768px) {
    .ruh-reactions-section {
        padding: 16px;
    }
    .content-reactions {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px 10px;
        justify-items: center;
    }
    .reaction-emoji {
        font-size: 30px;
    }
    .reaction-label {
        font-size: 10px;
    }
    .reaction-count {
        font-size: 13px;
    }
}

/* Ana Bolum */
.ruh-comments-main {
    background: #1a1a1a;
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
    background: #222;
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
    background: linear-gradient(135deg, #667eea, #764ba2);
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
    background: rgba(102, 126, 234, 0.15);
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
    background: rgba(102, 126, 234, 0.12);
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
    color: #667eea;
    font-weight: 500;
}

/* Avatar goruntuleme */
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
    background: #333;
    border: 1px solid #444;
    border-radius: 6px;
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
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 8px;
    color: #e0e0e0;
    font-size: 14px;
    resize: vertical;
    box-sizing: border-box;
}

#comment:focus {
    outline: none;
    border-color: #667eea;
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

.auth-required {
    text-align: center;
    padding: 30px 20px;
    background: #222;
    border-radius: 12px;
    margin-bottom: 16px;
}

.auth-required svg {
    margin-bottom: 12px;
    opacity: 0.8;
}

.auth-required h4 {
    margin: 0 0 8px;
    color: #fff;
    font-size: 16px;
    font-weight: 600;
}

.auth-required p {
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
    padding: 10px 24px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}

.auth-btn.login-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: #fff;
}

.auth-btn.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.auth-btn.register-btn {
    background: transparent;
    border: 1px solid #444;
    color: #ccc;
}

.auth-btn.register-btn:hover {
    background: #333;
    border-color: #555;
}

/* Yorum Listesi */
.comment-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.comment {
    margin-bottom: 8px;
}

.comment-body {
    display: flex;
    gap: 10px;
    padding: 12px;
    background: #1F1F1F;
    border-radius: 8px;
}

.comment-avatar {
    flex-shrink: 0;
}

.comment-avatar img,
.comment-avatar .avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.comment-main {
    flex: 1;
    min-width: 0;
}

.comment-header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
    flex-wrap: wrap;
}

.comment-author {
    font-weight: 600;
    color: #fff;
    font-size: 13px;
}

.comment-level {
    padding: 1px 6px;
    background: #667eea;
    border-radius: 8px;
    font-size: 10px;
    color: #fff;
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

.comment-date {
    font-size: 11px;
    color: #666;
}

.comment-text {
    font-size: 14px;
    line-height: 1.5;
    color: #ccc;
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

/* Yorum Aksiyonlari */
.comment-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 10px;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 6px 12px;
    background: transparent;
    border: 1px solid #333;
    border-radius: 6px;
    font-size: 12px;
    color: #888;
    cursor: pointer;
    transition: all 0.2s;
}

.action-btn:hover {
    background: #333;
    color: #fff;
    border-color: #444;
}

.action-btn.like-btn:hover,
.action-btn.like-btn.liked {
    border-color: #ef4444;
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
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

/* Yanitlar */
.comment-replies {
    margin-top: 8px;
    margin-left: 20px;
    padding-left: 12px;
    border-left: 2px solid #333;
}

.comment-replies .comment-body {
    background: #252525;
}

/* Inline Yanit Formu */
.inline-reply-form {
    margin: 12px 0 12px 48px;
    padding: 12px;
    background: #1f1f1f;
    border: 1px solid #333;
    border-radius: 8px;
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

/* Yanit Formu Eski */
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

/* Duzenleme Formu */
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

.loader {
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
.modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.8);
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: #1a1a1a;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 80vh;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #333;
}

.modal-header h4 {
    margin: 0;
    color: #fff;
}

.modal-close {
    background: none;
    border: none;
    color: #888;
    font-size: 24px;
    cursor: pointer;
}

#gif-search {
    margin: 12px;
    padding: 10px;
    background: #2a2a2a;
    border: 1px solid #333;
    border-radius: 6px;
    color: #fff;
    font-size: 14px;
}

#gif-results {
    padding: 12px;
    overflow-y: auto;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

#gif-results img {
    width: 100%;
    border-radius: 4px;
    cursor: pointer;
}

/* 3 Nokta Menu */
.comment-more-menu {
    position: relative;
    margin-left: auto;
    flex-shrink: 0;
}

.more-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: transparent;
    border: 1px solid #333;
    border-radius: 6px;
    color: #666;
    cursor: pointer;
    transition: all 0.2s;
}

.more-btn:hover {
    background: #333;
    color: #fff;
    border-color: #444;
}

.more-dropdown {
    position: absolute;
    right: 0;
    top: 100%;
    background: #2a2a2a;
    border: 1px solid #333;
    border-radius: 8px;
    min-width: 140px;
    z-index: 100;
    display: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

.more-dropdown.show {
    display: block;
}

.more-dropdown button {
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    padding: 10px 14px;
    background: none;
    border: none;
    color: #ccc;
    font-size: 13px;
    cursor: pointer;
    text-align: left;
}

.more-dropdown button:hover {
    background: #333;
}

.more-dropdown button.delete-btn {
    color: #ef4444;
}

.more-dropdown button svg {
    width: 16px;
    height: 16px;
}

/* Sikayet Modal - Guncel Tasarim */
.report-modal-content {
    max-width: 420px;
    background: #1F1F1F;
    border: 1px solid #333;
}

.report-modal-content .modal-header {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    padding: 16px 20px;
    border-radius: 10px 10px 0 0;
}

.report-modal-content .modal-header h4 {
    display: flex;
    align-items: center;
    gap: 8px;
}

.report-modal-content .modal-header h4::before {
    content: "⚠️";
}

#report-form {
    padding: 20px;
}

#report-form .form-group {
    margin-bottom: 18px;
}

#report-form label {
    display: block;
    margin-bottom: 8px;
    font-size: 13px;
    font-weight: 500;
    color: #e0e0e0;
}

#report-form select,
#report-form textarea {
    width: 100%;
    padding: 12px 14px;
    background: #141414;
    border: 2px solid #333;
    border-radius: 8px;
    color: #fff;
    font-size: 14px;
    transition: border-color 0.2s;
}

#report-form select:focus,
#report-form textarea:focus {
    outline: none;
    border-color: #ef4444;
}

#report-form textarea {
    min-height: 90px;
    resize: vertical;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
    padding-top: 16px;
    border-top: 1px solid #333;
}

.btn-cancel {
    padding: 12px 24px;
    background: #2a2a2a;
    border: 1px solid #444;
    border-radius: 8px;
    color: #ccc;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-cancel:hover {
    background: #333;
    color: #fff;
}

.btn-submit {
    padding: 12px 24px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    border: none;
    border-radius: 8px;
    color: #fff;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-submit:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

/* Mobil Uyumluluk */
@media (max-width: 768px) {
    .ruh-reactions-section {
        padding: 16px 12px;
    }
    
    .reactions {
        gap: 12px;
    }
    
    .reaction {
        padding: 8px 10px;
        min-width: 60px;
    }
    
    .reaction-emoji {
        font-size: 28px;
    }
    
    .reaction-name {
        font-size: 9px;
    }
    
    .reaction .count {
        font-size: 12px;
    }
    
    .comments-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .sort-buttons {
        width: 100%;
        justify-content: flex-start;
    }
    
    #ruh-comment-form-wrapper {
        padding: 10px;
    }
    
    .comment-form-content {
        width: 100%;
    }
    
    #comment {
        min-height: 70px;
        font-size: 14px;
    }
    
    .comment-body {
        padding: 10px;
    }
    
    .comment-avatar img {
        width: 32px;
        height: 32px;
    }
    
    .comment-replies {
        margin-left: 8px;
        padding-left: 8px;
    }
    
    .form-footer {
        flex-direction: column;
        align-items: stretch;
        gap: 8px;
    }
    
    .submit-btn {
        margin-left: 0;
        justify-content: center;
    }
    
    #gif-results {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .comment-actions {
        flex-wrap: wrap;
    }
    
    .action-btn {
        padding: 4px 8px;
        font-size: 11px;
    }
    
    .user-info-bar {
        font-size: 12px;
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
        display: none;
    }
    
    .comment-main {
        width: 100%;
    }
    
    .auth-required {
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
</style>
