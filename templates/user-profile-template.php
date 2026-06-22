<?php
if (!defined('ABSPATH')) exit;

$xp_for_next_level = ruh_calculate_xp_for_level($user_data['level_info']->level + 1);
$xp_progress_percent = ($xp_for_next_level > 0) ? ($user_data['level_info']->xp / $xp_for_next_level) * 100 : 0;
if ($xp_progress_percent > 100) $xp_progress_percent = 100;

// Kullanıcı cezaları
$ban_status = get_user_meta($user_data['info']->ID, 'ruh_ban_status', true);
$timeout_until = get_user_meta($user_data['info']->ID, 'ruh_timeout_until', true);

// Son aktivite
$last_comment = get_comments(['user_id' => $user_data['info']->ID, 'number' => 1, 'status' => 'approve']);
if (!empty($last_comment)) {
    $last_activity = strtotime($last_comment[0]->comment_date);
} else {
    $last_activity = strtotime($user_data['info']->user_registered);
}

// Ensure we have valid timestamps
if (!$last_activity) {
    $last_activity = current_time('timestamp');
}
?>

<div class="ruh-user-profile">
    <div class="profile-header">
        <div class="profile-avatar">
            <?php echo ruh_get_avatar($user_data['info']->ID, 120); ?>
            <?php if ($user_data['is_own_profile']) : ?>
                <button class="change-avatar-btn" type="button" onclick="document.getElementById('avatar-upload').click();">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                        <circle cx="12" cy="13" r="4"></circle>
                    </svg>
                </button>
                <input type="file" id="avatar-upload" accept="image/*" style="display: none;">
            <?php endif; ?>
        </div>
        
        <div class="profile-info">
            <div class="profile-name-section">
                <h2><?php echo esc_html($user_data['info']->display_name); ?></h2>
                <?php if ($user_data['is_own_profile']) : ?>
                    <button class="edit-profile-btn" type="button">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                            <path d="m18.5 2.5 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                        </svg>
                        <?php _e('Düzenle', 'ruh-comment'); ?>
                    </button>
                <?php endif; ?>
            </div>
            
            <?php if ($ban_status === 'banned') : ?>
                <div class="user-status banned">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="m4.9 4.9 14.2 14.2"></path>
                    </svg>
                    <?php _e('Bu kullanıcı kalıcı olarak engellenmiştir.', 'ruh-comment'); ?>
                </div>
            <?php elseif ($timeout_until && current_time('timestamp') < $timeout_until) : ?>
                <div class="user-status timeout">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12,6 12,12 16,14"></polyline>
                    </svg>
                    <?php printf(__('Bu kullanıcı %s tarihine kadar askıya alınmıştır.', 'ruh-comment'),
                        date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timeout_until)); ?>
                </div>
            <?php endif; ?>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <strong><?php echo $user_data['total_comments']; ?></strong>
                    <span><?php _e('Yorum', 'ruh-comment'); ?></span>
                </div>
                <div class="stat-item">
                    <strong><?php echo $user_data['total_likes']; ?></strong>
                    <span><?php _e('Beğeni Aldı', 'ruh-comment'); ?></span>
                </div>
                <div class="stat-item">
                    <strong><?php echo count($user_data['badges']); ?></strong>
                    <span><?php _e('Rozet', 'ruh-comment'); ?></span>
                </div>
            </div>
            
            <div class="profile-level-info">
                <div class="level-badge-oval" style="background: <?php echo ruh_get_level_color($user_data['level_info']->level); ?>">
                    <?php printf(__('Seviye %d', 'ruh-comment'), $user_data['level_info']->level); ?>
                </div>
                <div class="xp-bar-container">
                    <div class="xp-bar">
                        <div class="xp-bar-progress" style="width: <?php echo $xp_progress_percent; ?>%;"></div>
                    </div>
                    <span class="xp-text"><?php echo $user_data['level_info']->xp; ?> / <?php echo $xp_for_next_level; ?> XP</span>
                </div>
            </div>
            
            <div class="profile-meta">
                <p><strong><?php _e('Katılım:', 'ruh-comment'); ?></strong> 
                    <?php echo date_i18n(get_option('date_format'), strtotime($user_data['info']->user_registered)); ?>
                </p>
                <p><strong><?php _e('Son Aktivite:', 'ruh-comment'); ?></strong> 
                    <?php 
                    if ($last_activity && is_numeric($last_activity)) {
                        echo (function_exists('ruh_human_time_diff_tr') ? ruh_human_time_diff_tr($last_activity, current_time('timestamp')) : human_time_diff($last_activity, current_time('timestamp'))) . ' ' . __('önce', 'ruh-comment');
                    } else {
                        _e('Bilinmiyor', 'ruh-comment');
                    }
                    ?>
                </p>
            </div>
        </div>
    </div>

    <?php if (!empty($user_data['badges'])) : ?>
    <div class="profile-section">
        <h3><?php _e('Kazanılan Rozetler', 'ruh-comment'); ?></h3>
        <div class="profile-badges">
            <?php foreach ($user_data['badges'] as $badge) : ?>
            <div class="profile-badge-item" title="<?php echo esc_attr($badge->badge_name); ?>">
                <div class="badge-icon"><?php echo $badge->badge_svg; ?></div>
                <span class="badge-name"><?php echo esc_html($badge->badge_name); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="profile-section">
        <h3><?php _e('Son Yorumları', 'ruh-comment'); ?></h3>
        <div class="profile-comments-list">
            <?php if (!empty($user_data['comments'])) : ?>
                <?php foreach ($user_data['comments'] as $comment) :
                    $post_title = ruh_get_comment_post_title($comment->comment_post_ID);
                    $comment_link = ruh_get_comment_link($comment);
                    $post_link = ruh_get_post_permalink($comment->comment_post_ID, $comment);
                    $likes = get_comment_meta($comment->comment_ID, '_likes', true) ?: 0;
                    
                    // FIX: Convert to integer timestamp properly
                    $comment_time = intval(get_comment_time('U', true, $comment));
                    if (!$comment_time) {
                        // Fallback if get_comment_time fails
                        $comment_time = strtotime($comment->comment_date);
                    }
                ?>
                <div class="profile-comment-item">
                    <div class="comment-header">
                        <div class="comment-post-info">
                            <a href="<?php echo esc_url($post_link); ?>" class="post-title" target="_blank">
                                <?php echo esc_html($post_title); ?>
                            </a>
                        </div>
                        <div class="comment-meta">
                            <span class="comment-date">
                                <a href="<?php echo esc_url($comment_link); ?>" target="_blank">
                                    <?php echo (function_exists('ruh_human_time_diff_tr') ? ruh_human_time_diff_tr($comment_time, current_time('timestamp')) : human_time_diff($comment_time, current_time('timestamp'))); ?> 
                                    <?php _e('önce', 'ruh-comment'); ?>
                                </a>
                            </span>
                            <?php if ($likes > 0) : ?>
                            <span class="comment-likes">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                </svg>
                                <?php echo $likes; ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="comment-excerpt">
                        <?php 
                        $excerpt = wp_trim_words(strip_tags($comment->comment_content), 25, '...');
                        echo esc_html($excerpt); 
                        ?>
                    </div>
                    <div class="comment-actions">
                        <a href="<?php echo esc_url($comment_link); ?>" target="_blank" class="view-comment">
                            Yorumu Görüntüle
                        </a>
                        <a href="<?php echo esc_url($post_link); ?>" target="_blank" class="view-post">
                            Yazıya Git
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (count($user_data['comments']) >= 10) : ?>
                    <div class="load-more-comments-wrapper">
                        <button type="button" id="load-more-profile-comments" data-user-id="<?php echo $user_data['info']->ID; ?>" data-page="2">
                            Daha Fazla Yorum Göster
                        </button>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-content">
                    <div class="no-content-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                        </svg>
                    </div>
                    <p><?php _e('Henüz hiç yorum yapmamış.', 'ruh-comment'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($user_data['is_own_profile']) : ?>
    <!-- Profil Düzenleme Modalı - v2 Robust -->
    <div id="ruh-profile-edit-modal" class="ruh-modal-overlay" style="display: none;">
        <div class="ruh-modal-container">
            <div class="ruh-modal-header">
                <h3><?php _e('Profili Düzenle', 'ruh-comment'); ?></h3>
                <button type="button" class="ruh-modal-close-btn" onclick="document.getElementById('ruh-profile-edit-modal').style.display='none'">&times;</button>
            </div>
            
            <div class="ruh-modal-body">
                <div class="ruh-tabs-nav">
                    <button type="button" class="ruh-tab-link active" onclick="openRuhTab(event, 'ruh-tab-basic')"><?php _e('Temel Bilgiler', 'ruh-comment'); ?></button>
                    <button type="button" class="ruh-tab-link" onclick="openRuhTab(event, 'ruh-tab-account')"><?php _e('Hesap', 'ruh-comment'); ?></button>
                    <button type="button" class="ruh-tab-link" onclick="openRuhTab(event, 'ruh-tab-password')"><?php _e('Şifre', 'ruh-comment'); ?></button>
                </div>
                
                <div id="ruh-tab-basic" class="ruh-tab-content" style="display: block;">
                    <form id="profile-basic-form" class="ruh-profile-form">
                        <?php wp_nonce_field('ruh_update_profile_' . $user_data['info']->ID, 'nonce'); ?>
                        <input type="hidden" name="action" value="ruh_update_profile">
                        <input type="hidden" name="action_type" value="basic_info">
                        
                        <div class="ruh-form-group">
                            <label for="display_name"><?php _e('Görünen Ad', 'ruh-comment'); ?></label>
                            <input type="text" id="display_name" name="display_name" 
                                   value="<?php echo esc_attr($user_data['info']->display_name); ?>" required>
                        </div>
                        
                        <div class="ruh-form-group">
                            <label for="description"><?php _e('Hakkımda', 'ruh-comment'); ?></label>
                            <textarea id="description" name="description" rows="4" 
                                      placeholder="Kendiniz hakkında birkaç kelime..."><?php echo esc_textarea($user_data['info']->description); ?></textarea>
                        </div>
                        
                        <button type="submit" class="ruh-submit-btn"><?php _e('Bilgileri Güncelle', 'ruh-comment'); ?></button>
                    </form>
                </div>
                
                <div id="ruh-tab-account" class="ruh-tab-content" style="display: none;">
                    <form id="profile-account-form" class="ruh-profile-form">
                        <?php wp_nonce_field('ruh_update_profile_' . $user_data['info']->ID, 'nonce'); ?>
                        <input type="hidden" name="action" value="ruh_update_profile">
                        <input type="hidden" name="action_type" value="account_info">
                        
                        <div class="ruh-form-group">
                            <label for="user_email"><?php _e('E-posta Adresi', 'ruh-comment'); ?></label>
                            <input type="email" id="user_email" name="user_email" 
                                   value="<?php echo esc_attr($user_data['info']->user_email); ?>" required>
                        </div>
                        
                        <div class="ruh-form-group">
                            <label for="user_url"><?php _e('Web Sitesi', 'ruh-comment'); ?></label>
                            <input type="url" id="user_url" name="user_url" 
                                   value="<?php echo esc_attr($user_data['info']->user_url); ?>" 
                                   placeholder="https://example.com">
                        </div>
                        
                        <button type="submit" class="ruh-submit-btn"><?php _e('Hesap Bilgilerini Güncelle', 'ruh-comment'); ?></button>
                    </form>
                </div>
                
                <div id="ruh-tab-password" class="ruh-tab-content" style="display: none;">
                    <form id="profile-password-form" class="ruh-profile-form">
                        <?php wp_nonce_field('ruh_update_profile_' . $user_data['info']->ID, 'nonce'); ?>
                        <input type="hidden" name="action" value="ruh_update_profile">
                        <input type="hidden" name="action_type" value="change_password">
                        
                        <div class="ruh-form-group">
                            <label for="current_password"><?php _e('Mevcut Şifre', 'ruh-comment'); ?></label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="ruh-form-group">
                            <label for="new_password"><?php _e('Yeni Şifre', 'ruh-comment'); ?></label>
                            <input type="password" id="new_password" name="new_password" required minlength="6">
                        </div>
                        
                        <div class="ruh-form-group">
                            <label for="confirm_password"><?php _e('Yeni Şifre Tekrar', 'ruh-comment'); ?></label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" class="ruh-submit-btn"><?php _e('Şifreyi Güncelle', 'ruh-comment'); ?></button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Çıkış Butonu -->
    <div class="profile-actions">
        <a href="<?php echo ruh_logout_url(); ?>" class="logout-btn">
            <?php _e('Çıkış Yap', 'ruh-comment'); ?>
        </a>
    </div>
    <?php endif; ?>
</div>

<style>
/* Robust Modal Styles */
.ruh-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.85);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(5px);
}

.ruh-modal-container {
    background: #1e1e1e;
    width: 90%;
    max-width: 550px;
    max-height: 90vh;
    border-radius: 16px;
    border: 1px solid #333;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: ruhModalSlideIn 0.3s ease-out;
}

@keyframes ruhModalSlideIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.ruh-modal-header {
    padding: 20px 24px;
    border-bottom: 1px solid #333;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #252525;
}

.ruh-modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    color: #fff;
    font-weight: 600;
}

.ruh-modal-close-btn {
    background: transparent;
    border: none;
    color: #888;
    font-size: 28px;
    line-height: 1;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}

.ruh-modal-close-btn:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.1);
}

.ruh-modal-body {
    padding: 24px;
    overflow-y: auto;
}

.ruh-tabs-nav {
    display: flex;
    gap: 4px;
    border-bottom: 2px solid #333;
    margin-bottom: 24px;
    padding-bottom: 0;
}

.ruh-tab-link {
    background: transparent;
    border: none;
    color: #888;
    padding: 10px 16px;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    position: relative;
    transition: all 0.2s;
    border-radius: 6px 6px 0 0;
}

.ruh-tab-link:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.05);
}

.ruh-tab-link.active {
    color: #3b82f6;
    font-weight: 600;
}

.ruh-tab-link.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 100%;
    height: 2px;
    background: #3b82f6;
}

.ruh-tab-content {
    animation: ruhFadeIn 0.3s ease;
}

@keyframes ruhFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.ruh-form-group {
    margin-bottom: 20px;
}

.ruh-form-group label {
    display: block;
    margin-bottom: 8px;
    color: #ccc;
    font-size: 0.9rem;
    font-weight: 500;
}

.ruh-form-group input,
.ruh-form-group textarea {
    width: 100%;
    padding: 12px;
    background: #2a2a2a;
    border: 1px solid #404040;
    border-radius: 8px;
    color: #fff;
    font-size: 0.95rem;
    transition: all 0.2s;
    box-sizing: border-box;
}

.ruh-form-group input:focus,
.ruh-form-group textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    background: #333;
}

.ruh-submit-btn {
    width: 100%;
    padding: 14px;
    background: #3b82f6;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.ruh-submit-btn:hover {
    background: #2563eb;
    transform: translateY(-1px);
}

.ruh-submit-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none;
}

/* Original Styles Restored/Merged */
.ruh-user-profile {
    max-width: 1200px;
    margin: 0 auto;
    padding: 2rem 1rem;
    background: var(--bg-primary, #0f0f23);
    color: #ffffff;
}

.profile-header {
    display: flex;
    gap: 2rem;
    align-items: flex-start;
    margin-bottom: 3rem;
    background: #1a1a1a;
    padding: 2rem;
    border-radius: 12px;
    border: 1px solid #2a2a2a;
}

.profile-avatar {
    position: relative;
}

.profile-avatar img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 3px solid #3b82f6;
    transition: all 0.2s ease;
}

.profile-avatar img:hover {
    border-color: #60a5fa;
}

.change-avatar-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #3b82f6;
    border: 2px solid #1a1a1a;
    color: white;
    cursor: pointer;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.change-avatar-btn:hover {
    background: #2563eb;
}

.profile-info {
    flex: 1;
}

.profile-name-section {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
}

.profile-name-section h2 {
    margin: 0;
    font-size: 2rem;
    color: #ffffff;
}

.edit-profile-btn {
    background: #3b82f6;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.2s ease;
}

.edit-profile-btn:hover {
    background: #2563eb;
}

.user-status {
    padding: 0.5rem 1rem;
    border-radius: 6px;
    font-weight: 600;
    margin-bottom: 1rem;
}

.user-status.banned {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border: 1px solid #ef4444;
}

.user-status.timeout {
    background: rgba(245, 158, 11, 0.1);
    color: #f59e0b;
    border: 1px solid #f59e0b;
}

.profile-stats {
    display: flex;
    gap: 2rem;
    margin-bottom: 1.5rem;
}

.stat-item {
    text-align: center;
}

.stat-item strong {
    display: block;
    font-size: 2rem;
    color: #3b82f6;
    font-weight: 700;
}

.stat-item span {
    color: #e2e8f0;
    font-size: 0.875rem;
}

.profile-level-info {
    margin-bottom: 1rem;
}

.level-badge-oval, .user-level-oval {
    display: inline-block;
    padding: 8px 16px;
    border-radius: 20px;
    color: white;
    font-weight: 700;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    border: 2px solid rgba(255,255,255,0.2);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.xp-bar-container {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.xp-bar {
    flex: 1;
    height: 14px;
    background: rgba(15, 15, 35, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
}

.xp-bar-progress {
    height: 100%;
    background: #3b82f6;
    border-radius: 20px;
    transition: width 0.3s ease;
}

.xp-text {
    font-size: 0.875rem;
    color: #94a3b8;
    white-space: nowrap;
}

.profile-meta p {
    margin: 0.5rem 0;
    color: #e2e8f0;
    font-size: 0.875rem;
}

.profile-section {
    margin-bottom: 3rem;
}

.profile-section h3 {
    margin: 0 0 1.5rem;
    font-size: 1.5rem;
    color: #ffffff;
    border-bottom: 2px solid #005B43;
    padding-bottom: 0.5rem;
}

.profile-badges {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
}

.profile-badge-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    background: #1a1a1a;
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid #2a2a2a;
    transition: all 0.2s ease;
}

.profile-badge-item:hover {
    border-color: #3b82f6;
    background: #242424;
}

.profile-badge-item .badge-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.profile-badge-item .badge-icon svg {
    width: 28px;
    height: 28px;
}

.profile-badge-item .badge-name {
    font-weight: 600;
    color: #ffffff;
    font-size: 0.9rem;
}

.profile-comments-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    overflow: hidden;
    max-width: 100%;
}

.profile-comment-item {
    padding: 1.5rem;
    background: #1a1a1a;
    border-radius: 8px;
    border-left: 4px solid #3b82f6;
    transition: all 0.2s ease;
    overflow: hidden;
    max-width: 100%;
    box-sizing: border-box;
}

.profile-comment-item:hover {
    background: #242424;
    border-left-color: #60a5fa;
}

.comment-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.comment-post-info {
    flex: 1;
}

.post-title {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    line-height: 1.4;
    display: block;
    transition: all 0.2s ease;
}

.post-title:hover {
    color: #60a5fa;
}

.comment-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.875rem;
    color: #94a3b8;
}

.comment-date a {
    color: #94a3b8;
    text-decoration: none;
}

.comment-date a:hover {
    color: #e2e8f0;
}

.comment-likes {
    color: #10b981;
    font-weight: 600;
}

.comment-excerpt {
    color: #e2e8f0;
    line-height: 1.6;
    margin-bottom: 1rem;
    font-size: 0.95rem;
    word-break: break-word !important;
    overflow-wrap: anywhere !important;
    white-space: normal !important;
    overflow: hidden !important;
    max-width: 100% !important;
}

.comment-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.comment-actions a {
    color: #005B43;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.comment-actions a:hover {
    background: rgba(0, 91, 67, 0.1);
    border-color: #005B43;
    text-decoration: none;
}

.load-more-comments-wrapper {
    text-align: center;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #404040;
}

#load-more-profile-comments {
    background: #2a2a2a;
    color: #ffffff;
    border: 2px solid #404040;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.2s ease;
}

#load-more-profile-comments:hover {
    border-color: #005B43;
    background: rgba(0, 91, 67, 0.1);
}

.no-content {
    text-align: center;
    padding: 3rem;
    color: #94a3b8;
    background: #2a2a2a;
    border-radius: 12px;
    border: 2px dashed #404040;
}

.no-content-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.profile-actions {
    text-align: center;
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid #404040;
}

.logout-btn {
    background: #ef4444;
    color: white;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
    display: inline-block;
}

.logout-btn:hover {
    background: #dc2626;
    text-decoration: none;
    color: white;
}

.avatar-loading {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-loading .spinner {
    width: 24px;
    height: 24px;
    border: 2px solid #ffffff;
    border-top: 2px solid #005B43;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .profile-stats {
        justify-content: center;
    }
    
    .xp-bar-container {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }
    
    .profile-badges {
        grid-template-columns: 1fr;
    }
    
    .comment-header {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>

<script>
// Tab switching function in global scope for onclick handlers
function openRuhTab(evt, tabName) {
    var i, tabcontent, tablinks;
    
    // Hide all tab content
    tabcontent = document.getElementsByClassName("ruh-tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    
    // Remove active class from all tab links
    tablinks = document.getElementsByClassName("ruh-tab-link");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    
    // Show current tab and add active class
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

document.addEventListener('DOMContentLoaded', function() {
    // Edit Profile Modal
    const editBtn = document.querySelector('.edit-profile-btn');
    const modal = document.getElementById('ruh-profile-edit-modal');
    
    if (editBtn && modal) {
        editBtn.addEventListener('click', (e) => {
            e.preventDefault();
            modal.style.display = 'flex';
        });
        
        // Close on outside click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
        
        // Close on ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                modal.style.display = 'none';
            }
        });
    }
    
    // Form Submissions
    const forms = document.querySelectorAll('.ruh-profile-form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('.ruh-submit-btn');
            const originalText = submitBtn.textContent;
            
            // Password validation
            if (this.id === 'profile-password-form') {
                const newPass = this.querySelector('input[name="new_password"]').value;
                const confirmPass = this.querySelector('input[name="confirm_password"]').value;
                
                if (newPass !== confirmPass) {
                    showNotification('Şifreler eşleşmiyor.', 'error');
                    return;
                }
            }
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'İşleniyor...';
            
            const formData = new FormData(this);
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.data.message || 'Başarılı!', 'success');
                    if (this.id === 'profile-password-form') this.reset();
                    
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification(data.data.message || 'Hata oluştu.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            })
            .catch(err => {
                console.error(err);
                showNotification('Bir bağlantı hatası oluştu.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    });
    
    // Avatar Upload
    const avatarUpload = document.getElementById('avatar-upload');
    if (avatarUpload) {
        avatarUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            // Basic validation
            if (file.size > 5 * 1024 * 1024) {
                showNotification('Dosya 5MB\'dan küçük olmalı.', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'ruh_upload_image');
            formData.append('nonce', '<?php echo wp_create_nonce('ruh-comment-nonce'); ?>');
            formData.append('image', file);
            
            // Show loading
            const avatarImg = document.querySelector('.profile-avatar img');
            if (avatarImg) avatarImg.style.opacity = '0.5';
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update user meta
                    const updateData = new FormData();
                    updateData.append('action', 'ruh_update_profile');
                    updateData.append('nonce', '<?php echo wp_create_nonce('ruh-comment-nonce'); ?>');
                    updateData.append('action_type', 'update_avatar');
                    updateData.append('avatar_url', data.data.url);
                    
                    return fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        body: updateData
                    }).then(r => r.json().then(d => ({...d, url: data.data.url})));
                } else {
                    throw new Error(data.data.message);
                }
            })
            .then(data => {
                if (data.success) {
                    if (avatarImg) {
                        avatarImg.src = data.url;
                        avatarImg.style.opacity = '1';
                    }
                    showNotification('Profil resmi güncellendi!', 'success');
                } else {
                    throw new Error(data.data.message);
                }
            })
            .catch(err => {
                showNotification(err.message || 'Yükleme hatası.', 'error');
                if (avatarImg) avatarImg.style.opacity = '1';
            });
        });
    }
    
    // Notification Helper
    function showNotification(msg, type) {
        const div = document.createElement('div');
        div.textContent = msg;
        div.style.cssText = `
            position: fixed; top: 20px; right: 20px; padding: 15px 25px; border-radius: 8px; color: white;
            background: ${type === 'success' ? '#10b981' : '#ef4444'}; z-index: 1000000;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3); animation: slideIn 0.3s ease;
        `;
        document.body.appendChild(div);
        setTimeout(() => {
            div.style.opacity = '0';
            setTimeout(() => div.remove(), 300);
        }, 3000);
    }
    
    // Load More Comments Logic
    const loadMoreBtn = document.getElementById('load-more-profile-comments');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const page = parseInt(this.dataset.page);
            this.innerText = 'Yükleniyor...';
            
            const fd = new FormData();
            fd.append('action', 'ruh_load_more_profile_comments');
            fd.append('nonce', '<?php echo wp_create_nonce('ruh-comment-nonce'); ?>');
            fd.append('user_id', userId);
            fd.append('page', page);
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {method:'POST', body:fd})
            .then(r=>r.json())
            .then(d=>{
                if(d.success && d.data.html) {
                    const temp = document.createElement('div');
                    temp.innerHTML = d.data.html;
                    while(temp.firstChild) document.querySelector('.profile-comments-list').insertBefore(temp.firstChild, this.parentElement);
                    if(d.data.has_more) {
                        this.dataset.page = page+1;
                        this.innerText = 'Daha Fazla Yorum Göster';
                    } else {
                        this.parentElement.remove();
                    }
                } else {
                    this.parentElement.remove();
                }
            })
            .catch(() => {
                this.innerText = 'Hata!';
            });
        });
    }
});
</script>
