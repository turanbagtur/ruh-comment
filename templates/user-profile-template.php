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
$last_activity = !empty($last_comment) ? strtotime($last_comment[0]->comment_date) : strtotime($user_data['info']->user_registered);
?>

<div class="ruh-user-profile">
    <div class="profile-header">
        <div class="profile-avatar">
            <?php echo ruh_get_avatar($user_data['info']->ID, 120); ?>
            <?php if ($user_data['is_own_profile']) : ?>
                <button class="change-avatar-btn" type="button" onclick="document.getElementById('avatar-upload').click();">
                    📷
                </button>
                <input type="file" id="avatar-upload" accept="image/*" style="display: none;">
            <?php endif; ?>
        </div>
        
        <div class="profile-info">
            <div class="profile-name-section">
                <h2><?php echo esc_html($user_data['info']->display_name); ?></h2>
                <?php if ($user_data['is_own_profile']) : ?>
                    <button class="edit-profile-btn" type="button">✏️ <?php _e('Düzenle', 'ruh-comment'); ?></button>
                <?php endif; ?>
            </div>
            
            <?php if ($ban_status === 'banned') : ?>
                <div class="user-status banned">
                    🚫 <?php _e('Bu kullanıcı kalıcı olarak engellenmiştir.', 'ruh-comment'); ?>
                </div>
            <?php elseif ($timeout_until && current_time('timestamp') < $timeout_until) : ?>
                <div class="user-status timeout">
                    ⏰ <?php printf(__('Bu kullanıcı %s tarihine kadar askıya alınmıştır.', 'ruh-comment'), 
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
                <div class="level-badge" style="background-color: <?php echo ruh_get_level_color($user_data['level_info']->level); ?>">
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
                    <?php echo human_time_diff($last_activity, current_time('timestamp')) . ' ' . __('önce', 'ruh-comment'); ?>
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
                <?php echo $badge->badge_svg; ?>
                <span><?php echo esc_html($badge->badge_name); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="profile-section">
        <h3><?php _e('Son Yorumları', 'ruh-comment'); ?></h3>
        <div class="profile-comments-list">
            <?php if (!empty($user_data['comments'])) : ?>
                <?php foreach ($user_data['comments'] as $comment) : ?>
                <div class="profile-comment-item">
                    <div class="comment-excerpt">
                        <?php echo wp_trim_words($comment->comment_content, 30, '...'); ?>
                    </div>
                    <div class="comment-meta">
                        <span class="comment-post">
                            <?php printf(__('%s yazısına', 'ruh-comment'), 
                                '<a href="' . get_permalink($comment->comment_post_ID) . '">' . get_the_title($comment->comment_post_ID) . '</a>'); ?>
                        </span>
                        <span class="comment-date">
                            <a href="<?php echo get_comment_link($comment); ?>">
                                <?php echo human_time_diff(get_comment_time('U', true, $comment), current_time('timestamp')); ?> 
                                <?php _e('önce', 'ruh-comment'); ?>
                            </a>
                        </span>
                        <span class="comment-likes">
                            👍 <?php echo get_comment_meta($comment->comment_ID, '_likes', true) ?: 0; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-content"><?php _e('Henüz hiç yorum yapmamış.', 'ruh-comment'); ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($user_data['is_own_profile']) : ?>
    <!-- Profil Düzenleme Modalı -->
    <div id="profile-edit-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><?php _e('Profili Düzenle', 'ruh-comment'); ?></h3>
                <button class="modal-close" type="button">&times;</button>
            </div>
            
            <div class="modal-body">
                <div class="tab-navigation">
                    <button class="tab-btn active" data-tab="basic"><?php _e('Temel Bilgiler', 'ruh-comment'); ?></button>
                    <button class="tab-btn" data-tab="password"><?php _e('Şifre', 'ruh-comment'); ?></button>
                </div>
                
                <div class="tab-content">
                    <!-- Temel Bilgiler -->
                    <div class="tab-pane active" id="basic-tab">
                        <form id="profile-basic-form">
                            <?php wp_nonce_field('ruh_profile_nonce', 'nonce'); ?>
                            <input type="hidden" name="action" value="ruh_update_profile">
                            <input type="hidden" name="action_type" value="update_profile">
                            
                            <div class="form-group">
                                <label for="display_name"><?php _e('Görünen Ad', 'ruh-comment'); ?></label>
                                <input type="text" id="display_name" name="display_name" 
                                       value="<?php echo esc_attr($user_data['info']->display_name); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="user_email"><?php _e('E-posta Adresi', 'ruh-comment'); ?></label>
                                <input type="email" id="user_email" name="email" 
                                       value="<?php echo esc_attr($user_data['info']->user_email); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="description"><?php _e('Hakkında', 'ruh-comment'); ?></label>
                                <textarea id="description" name="description" rows="4" 
                                          placeholder="<?php _e('Kendinizden bahsedin...', 'ruh-comment'); ?>"><?php echo esc_textarea($user_data['info']->description); ?></textarea>
                            </div>
                            
                            <button type="submit" class="ruh-submit"><?php _e('Bilgileri Güncelle', 'ruh-comment'); ?></button>
                        </form>
                    </div>
                    
                    <!-- Şifre Değiştirme -->
                    <div class="tab-pane" id="password-tab">
                        <form id="profile-password-form">
                            <?php wp_nonce_field('ruh_profile_nonce', 'nonce'); ?>
                            <input type="hidden" name="action" value="ruh_update_profile">
                            <input type="hidden" name="action_type" value="change_password">
                            
                            <div class="form-group">
                                <label for="current_password"><?php _e('Mevcut Şifre', 'ruh-comment'); ?></label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password"><?php _e('Yeni Şifre', 'ruh-comment'); ?></label>
                                <input type="password" id="new_password" name="new_password" required minlength="6">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password"><?php _e('Yeni Şifre Tekrar', 'ruh-comment'); ?></label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="ruh-submit"><?php _e('Şifreyi Güncelle', 'ruh-comment'); ?></button>
                        </form>
                    </div>
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
.ruh-user-profile {
    max-width: 1000px;
    margin: 0 auto;
    padding: 2rem 1rem;
    background: var(--bg-primary, #0d1421);
    color: var(--text-primary, #ffffff);
}

.profile-header {
    display: flex;
    gap: 2rem;
    align-items: flex-start;
    margin-bottom: 3rem;
    background: var(--bg-secondary, #1a2332);
    padding: 2rem;
    border-radius: 12px;
    border: 1px solid var(--border-color, #334155);
}

.profile-avatar {
    position: relative;
}

.profile-avatar img {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid var(--primary-color, #005B43);
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
}

.change-avatar-btn {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: var(--primary-color, #005B43);
    border: none;
    color: white;
    cursor: pointer;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
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
    color: var(--text-primary, #ffffff);
}

.edit-profile-btn {
    background: var(--primary-color, #005B43);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.875rem;
    font-weight: 600;
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
    font-size: 1.5rem;
    color: var(--primary-color, #005B43);
    font-weight: 700;
}

.stat-item span {
    color: var(--text-secondary, #e2e8f0);
    font-size: 0.875rem;
}

.profile-level-info {
    margin-bottom: 1rem;
}

.level-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    color: white;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.xp-bar-container {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.xp-bar {
    flex: 1;
    height: 10px;
    background: var(--bg-card, #2d3e52);
    border-radius: 5px;
    overflow: hidden;
}

.xp-bar-progress {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color, #005B43), #00b894);
    transition: width 0.3s ease;
}

.xp-text {
    font-size: 0.875rem;
    color: var(--text-muted, #94a3b8);
    white-space: nowrap;
}

.profile-meta p {
    margin: 0.5rem 0;
    color: var(--text-secondary, #e2e8f0);
    font-size: 0.875rem;
}

.profile-section {
    margin-bottom: 3rem;
}

.profile-section h3 {
    margin: 0 0 1.5rem;
    font-size: 1.5rem;
    color: var(--text-primary, #ffffff);
    border-bottom: 2px solid var(--primary-color, #005B43);
    padding-bottom: 0.5rem;
}

.profile-badges {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 1rem;
}

.profile-badge-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: var(--bg-card, #2d3e52);
    padding: 1rem;
    border-radius: 8px;
    border: 1px solid var(--border-color, #334155);
    transition: all 0.2s ease;
}

.profile-badge-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    border-color: var(--primary-color, #005B43);
}

.profile-badge-item svg {
    width: 32px;
    height: 32px;
}

.profile-badge-item span {
    font-weight: 600;
    color: var(--text-primary, #ffffff);
}

.profile-comments-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.profile-comment-item {
    padding: 1.5rem;
    background: var(--bg-secondary, #1a2332);
    border-radius: 8px;
    border-left: 4px solid var(--primary-color, #005B43);
}

.comment-excerpt {
    color: var(--text-secondary, #e2e8f0);
    margin-bottom: 1rem;
    line-height: 1.6;
}

.comment-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--text-muted, #94a3b8);
}

.comment-meta a {
    color: var(--primary-color, #005B43);
    text-decoration: none;
}

.comment-meta a:hover {
    text-decoration: underline;
}

.no-content {
    text-align: center;
    color: var(--text-muted, #94a3b8);
    font-style: italic;
    padding: 2rem;
}

/* Modal Styles */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.8);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: var(--bg-secondary, #1a2332);
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color, #334155);
}

.modal-header h3 {
    margin: 0;
    color: var(--text-primary, #ffffff);
}

.modal-close {
    background: none;
    border: none;
    color: var(--text-muted, #94a3b8);
    font-size: 1.5rem;
    cursor: pointer;
}

.modal-body {
    padding: 1.5rem;
}

.tab-navigation {
    display: flex;
    margin-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color, #334155);
}

.tab-btn {
    background: none;
    border: none;
    padding: 0.75rem 1.5rem;
    color: var(--text-muted, #94a3b8);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s ease;
}

.tab-btn.active {
    color: var(--primary-color, #005B43);
    border-bottom-color: var(--primary-color, #005B43);
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
}

.form-group {
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-secondary, #e2e8f0);
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color, #334155);
    border-radius: 6px;
    background: var(--bg-card, #2d3e52);
    color: var(--text-primary, #ffffff);
}

.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color, #005B43);
    box-shadow: 0 0 0 3px rgba(0, 91, 67, 0.1);
}

.profile-actions {
    text-align: center;
    margin-top: 3rem;
    padding-top: 2rem;
    border-top: 1px solid var(--border-color, #334155);
}

.logout-btn {
    background: var(--error-color, #ef4444);
    color: white;
    padding: 0.75rem 2rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s ease;
}

.logout-btn:hover {
    background: #dc2626;
    transform: translateY(-1px);
    text-decoration: none;
    color: white;
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
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Modal işlemleri
    const editBtn = document.querySelector('.edit-profile-btn');
    const modal = document.getElementById('profile-edit-modal');
    const closeBtn = document.querySelector('.modal-close');
    
    if (editBtn && modal) {
        editBtn.addEventListener('click', () => modal.style.display = 'flex');
        closeBtn?.addEventListener('click', () => modal.style.display = 'none');
        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.style.display = 'none';
        });
    }
    
    // Tab navigation
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.dataset.tab;
            
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Update tab content
            document.querySelectorAll('.tab-pane').forEach(pane => pane.classList.remove('active'));
            document.getElementById(tabName + '-tab').classList.add('active');
        });
    });
    
    // Avatar upload
    const avatarUpload = document.getElementById('avatar-upload');
    if (avatarUpload) {
        avatarUpload.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const formData = new FormData();
                formData.append('action', 'ruh_update_profile');
                formData.append('action_type', 'upload_avatar');
                formData.append('nonce', document.querySelector('[name="nonce"]').value);
                formData.append('avatar', this.files[0]);
                
                fetch(ruh_comment_ajax.ajax_url, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.showNotification(data.data.message, 'success');
                        if (data.data.avatar_url) {
                            document.querySelector('.profile-avatar img').src = data.data.avatar_url;
                        }
                    } else {
                        window.showNotification(data.data.message, 'error');
                    }
                })
                .catch(error => {
                    window.showNotification('Avatar yükleme hatası.', 'error');
                });
            }
        });
    }
    
    // Form submissions
    const basicForm = document.getElementById('profile-basic-form');
    const passwordForm = document.getElementById('profile-password-form');
    
    if (basicForm) {
        basicForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.ruh-submit');
            const originalText = submitBtn.textContent;
            
            submitBtn.textContent = 'Güncelleniyor...';
            submitBtn.disabled = true;
            
            fetch(ruh_comment_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.showNotification(data.data.message, 'success');
                    // Sayfayı yenile
                    setTimeout(() => location.reload(), 1500);
                } else {
                    window.showNotification(data.data.message, 'error');
                }
            })
            .catch(error => {
                window.showNotification('Güncelleme hatası.', 'error');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
    }
    
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const newPassword = this.querySelector('[name="new_password"]').value;
            const confirmPassword = this.querySelector('[name="confirm_password"]').value;
            
            if (newPassword !== confirmPassword) {
                window.showNotification('Yeni şifreler eşleşmiyor.', 'error');
                return;
            }
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.ruh-submit');
            const originalText = submitBtn.textContent;
            
            submitBtn.textContent = 'Güncelleniyor...';
            submitBtn.disabled = true;
            
            fetch(ruh_comment_ajax.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.showNotification(data.data.message, 'success');
                    this.reset();
                } else {
                    window.showNotification(data.data.message, 'error');
                }
            })
            .catch(error => {
                window.showNotification('Şifre güncelleme hatası.', 'error');
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
        });
    }
});
</script>