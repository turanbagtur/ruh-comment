<?php
if (post_password_required()) return;

$options = get_option('ruh_comment_options', array());
$post_id = get_the_ID();
$comment_count = get_comments_number($post_id);
?>
<div id="ruh-comments" class="comments-area">
    <?php if (isset($options['enable_reactions']) && $options['enable_reactions']) : ?>
    <div class="ruh-reactions-section">
        <div class="reactions-header">
            <h3 class="section-title">Bölüm Nasıldı?</h3>
            <div class="total-reactions">
                <span id="total-reaction-count">0</span> Tepki
            </div>
        </div>
        <div class="reactions">
            <button class="reaction" data-reaction="guzel" title="Güzel">
                <span class="emoji">👍</span>
                <span>Güzel</span>
                <span class="count">0</span>
            </button>
            <button class="reaction" data-reaction="sevdim" title="Sevdim">
                <span class="emoji">😂</span>
                <span>Sevdim</span>
                <span class="count">0</span>
            </button>
            <button class="reaction" data-reaction="asik_oldum" title="Aşık Oldum">
                <span class="emoji">😍</span>
                <span>Aşık Oldum</span>
                <span class="count">0</span>
            </button>
            <button class="reaction" data-reaction="sasirtici" title="Şaşırtıcı">
                <span class="emoji">😮</span>
                <span>Şaşırtıcı</span>
                <span class="count">0</span>
            </button>
            <button class="reaction" data-reaction="gaza_geldim" title="Gaza Geldim">
                <span class="emoji">🔥</span>
                <span>Gaza Geldim</span>
                <span class="count">0</span>
            </button>
            <button class="reaction" data-reaction="uzucu" title="Üzücü">
                <span class="emoji">😢</span>
                <span>Üzücü</span>
                <span class="count">0</span>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <div class="ruh-comments-main">
        <div class="comments-header">
            <h3 class="comments-title">
                <span class="comment-count"><?php echo $comment_count; ?></span> 
                <?php printf(_n('Yorum', '%s Yorum', $comment_count), $comment_count); ?>
            </h3>
            <?php if (isset($options['enable_sorting']) && $options['enable_sorting']) : ?>
            <div class="comment-sorting">
                <label>Sırala:</label>
                <div class="sort-buttons">
                    <button class="sort-button active" data-sort="newest" type="button">
                        En Yeniler
                    </button>
                    <button class="sort-button" data-sort="best" type="button">
                        En Beğenilenler
                    </button>
                    <button class="sort-button" data-sort="oldest" type="button">
                        En Eskiler
                    </button>
                    <button class="sort-button" data-sort="most_replied" type="button">
                        En Çok Yanıtlanan
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if (comments_open()) : ?>
            <?php if (is_user_logged_in()) : ?>
                <div id="ruh-comment-form-wrapper">
                    <div class="comment-user-info">
                        <?php echo get_avatar(get_current_user_id(), 50); ?>
                    </div>
                    <form id="commentform" class="comment-form" novalidate>
                        <div id="ruh-editor-container">
                            <div id="ruh-editor-toolbar">
                                <button type="button" class="ruh-toolbar-button" data-tag="b" title="Kalın">
                                    <strong>B</strong>
                                </button>
                                <button type="button" class="ruh-toolbar-button" data-tag="i" title="İtalik">
                                    <em>I</em>
                                </button>
                                <button type="button" class="ruh-toolbar-button" data-tag="spoiler" title="Spoiler">
                                    [S]
                                </button>
                                <button type="button" class="ruh-toolbar-button" data-tag="link" id="ruh-add-link" title="Link Ekle">
                                    🔗
                                </button>
                            </div>
                            <textarea 
                                id="comment" 
                                name="comment" 
                                placeholder="Tartışma başlat... Düşüncelerinizi paylaşın." 
                                required
                                maxlength="5000"
                            ></textarea>
                        </div>
                        <div class="form-submit">
                            <div class="ruh-form-hidden-fields" style="position:absolute;left:-9999px;">
                                <input type="text" name="ruh_honeypot" value="" tabindex="-1" autocomplete="off">
                            </div>
                            <input type="hidden" name="comment_post_ID" value="<?php echo $post_id; ?>">
                            <input type="hidden" name="comment_parent" value="0">
                            
                            <div class="form-submit-info">
                                <small class="char-counter">
                                    <span id="char-count">0</span>/5000 karakter
                                </small>
                            </div>
                            
                            <button type="submit" id="submit" class="submit ruh-submit">
                                Yorum Yap
                            </button>
                        </div>
                    </form>
                </div>
            <?php else : ?>
                <div class="auth-required-message">
                    <h3>Toplulukla Etkileşime Geç!</h3>
                    <p>Yorumlar yapmak, tepki vermek ve diğer kullanıcılarla etkileşime geçmek için hesabınıza giriş yapın.</p>
                    <div class="auth-buttons">
                        <?php 
                        $login_page = isset($options['login_page_id']) ? $options['login_page_id'] : 0;
                        $register_page = isset($options['register_page_id']) ? $options['register_page_id'] : 0;
                        
                        if ($login_page && get_post($login_page)) : ?>
                            <a href="<?php echo get_permalink($login_page); ?>" class="auth-button primary">
                                Giriş Yap
                            </a>
                        <?php else : ?>
                            <a href="<?php echo wp_login_url(get_permalink()); ?>" class="auth-button primary">
                                Giriş Yap
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($register_page && get_post($register_page)) : ?>
                            <a href="<?php echo get_permalink($register_page); ?>" class="auth-button">
                                Kayıt Ol
                            </a>
                        <?php else : ?>
                            <a href="<?php echo wp_registration_url(); ?>" class="auth-button">
                                Kayıt Ol
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else : ?>
            <div class="comments-closed">
                <p>Bu yazı için yorumlar kapalı.</p>
            </div>
        <?php endif; ?>

        <div id="comment-list-wrapper">
            <ol class="comment-list" role="list">
                <!-- Yorumlar AJAX ile yüklenecek -->
            </ol>
            
            <div id="comment-loader" style="display: none;">
                <p>Yorumlar yükleniyor...</p>
            </div>
            
            <button id="load-more-comments" style="display: none;" type="button">
                Daha Fazla Yorum Yükle
            </button>
            
            <?php if ($comment_count == 0) : ?>
                <div class="no-comments">
                    <p>İlk yorumu sen yap! 💬</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bildirim alanı -->
    <div id="ruh-notification" class="ruh-notification" style="display: none;">
        <span class="notification-text"></span>
        <button class="notification-close" type="button">&times;</button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Bildirim sistemi
    window.showNotification = function(message, type) {
        const notification = document.getElementById('ruh-notification');
        if (!notification) return;
        
        const textElement = notification.querySelector('.notification-text');
        notification.className = 'ruh-notification ' + (type || 'info');
        textElement.textContent = message;
        notification.style.display = 'flex';
        
        setTimeout(() => {
            notification.style.display = 'none';
        }, 5000);
    };
    
    const closeBtn = document.querySelector('.notification-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            document.getElementById('ruh-notification').style.display = 'none';
        });
    }
    
    // Karakter sayacı
    const textarea = document.getElementById('comment');
    const charCount = document.getElementById('char-count');
    
    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = count;
            
            if (count > 4500) {
                charCount.style.color = '#ef4444';
            } else if (count > 4000) {
                charCount.style.color = '#f59e0b';
            } else {
                charCount.style.color = '';
            }
        });
        
        // Otomatik yeniden boyutlandırma
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = this.scrollHeight + 'px';
        });
    }
});
</script>