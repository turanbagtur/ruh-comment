<?php
if (!defined('ABSPATH')) exit;

/**
 * Seviye Yönetimi Sayfası
 */
function render_level_manager_page_content() {
    global $wpdb;
    
    // AJAX işlemleri
    if (isset($_POST['action'])) {
        check_admin_referer('ruh_level_manager', '_wpnonce');
        
        switch($_POST['action']) {
            case 'update_user_level':
                $user_id = intval($_POST['user_id']);
                $new_level = intval($_POST['new_level']);
                $new_xp = intval($_POST['new_xp']);
                
                if ($user_id && $new_level >= 1 && $new_xp >= 0) {
                    $table = $wpdb->prefix . 'ruh_user_levels';
                    
                    // Mevcut kayıt var mı kontrol et
                    $exists = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(*) FROM $table WHERE user_id = %d", 
                        $user_id
                    ));
                    
                    if ($exists) {
                        $wpdb->update($table, 
                            array('level' => $new_level, 'xp' => $new_xp),
                            array('user_id' => $user_id)
                        );
                    } else {
                        $wpdb->insert($table, array(
                            'user_id' => $user_id,
                            'level' => $new_level,
                            'xp' => $new_xp
                        ));
                    }
                    
                    // Cache temizle
                    wp_cache_delete('ruh_user_level_' . $user_id, 'ruh_comment');
                    
                    echo '<div class="notice notice-success"><p>Kullanıcı seviyesi başarıyla güncellendi!</p></div>';
                }
                break;
                
            case 'reset_all_levels':
                $wpdb->query("TRUNCATE TABLE {$wpdb->prefix}ruh_user_levels");
                echo '<div class="notice notice-success"><p>Tüm kullanıcı seviyeleri sıfırlandı!</p></div>';
                break;
        }
    }
    
    // Sayfalama
    $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $per_page = 20;
    $offset = ($page - 1) * $per_page;
    
    // Arama
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $search_sql = '';
    if ($search) {
        $search_sql = $wpdb->prepare(" AND (u.display_name LIKE %s OR u.user_login LIKE %s)", 
            '%' . $search . '%', '%' . $search . '%');
    }
    
    // Kullanıcı seviye verilerini çek
    $sql = "
        SELECT u.ID, u.display_name, u.user_login, u.user_email,
               COALESCE(ul.level, 1) as level,
               COALESCE(ul.xp, 0) as xp,
               COUNT(c.comment_ID) as comment_count,
               COALESCE(SUM(CAST(cm.meta_value AS UNSIGNED)), 0) as total_likes
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->prefix}ruh_user_levels ul ON u.ID = ul.user_id
        LEFT JOIN {$wpdb->comments} c ON u.ID = c.user_id AND c.comment_approved = '1'
        LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = '_likes'
        WHERE 1=1 $search_sql
        GROUP BY u.ID
        ORDER BY COALESCE(ul.level, 1) DESC, COALESCE(ul.xp, 0) DESC
        LIMIT $offset, $per_page
    ";
    
    $users = $wpdb->get_results($sql);
    
    // Toplam kullanıcı sayısı
    $total_users = $wpdb->get_var("
        SELECT COUNT(DISTINCT u.ID)
        FROM {$wpdb->users} u
        WHERE 1=1 $search_sql
    ");
    
    $total_pages = ceil($total_users / $per_page);
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-chart-line" style="color: #2271b1; margin-right: 8px;"></span>
            Seviye Yönetimi
        </h1>
        
        <div class="ruh-admin-header">
            <div class="ruh-stats-cards">
                <?php
                $total_levels = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ruh_user_levels");
                $avg_level = $wpdb->get_var("SELECT AVG(level) FROM {$wpdb->prefix}ruh_user_levels");
                $highest_level = $wpdb->get_var("SELECT MAX(level) FROM {$wpdb->prefix}ruh_user_levels");
                ?>
                <div class="ruh-stat-card">
                    <div class="stat-number"><?php echo number_format($total_levels); ?></div>
                    <div class="stat-label">Seviyeli Kullanıcı</div>
                </div>
                <div class="ruh-stat-card">
                    <div class="stat-number"><?php echo number_format($avg_level, 1); ?></div>
                    <div class="stat-label">Ortalama Seviye</div>
                </div>
                <div class="ruh-stat-card">
                    <div class="stat-number"><?php echo number_format($highest_level); ?></div>
                    <div class="stat-label">En Yüksek Seviye</div>
                </div>
            </div>
        </div>

        <!-- Arama ve İşlemler -->
        <div class="ruh-admin-toolbar">
            <form method="get" class="search-form">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Kullanıcı ara...">
                <input type="submit" class="button" value="Ara">
                <?php if ($search): ?>
                    <a href="<?php echo admin_url('admin.php?page=' . $_GET['page']); ?>" class="button">Temizle</a>
                <?php endif; ?>
            </form>
            
            <div class="bulk-actions">
                <button type="button" class="button button-secondary" onclick="showResetAllModal()">
                    <span class="dashicons dashicons-update-alt"></span> Tüm Seviyeleri Sıfırla
                </button>
            </div>
        </div>

        <!-- Kullanıcı Tablosu -->
        <form method="post">
            <?php wp_nonce_field('ruh_level_manager', '_wpnonce'); ?>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Kullanıcı</th>
                        <th>Mevcut Seviye</th>
                        <th>XP</th>
                        <th>İstatistikler</th>
                        <th>Eylemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px;">
                                <?php echo $search ? 'Arama kriterinize uygun kullanıcı bulunamadı.' : 'Henüz kullanıcı bulunmamaktadır.'; ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <strong><?php echo esc_html($user->display_name); ?></strong><br>
                                    <small style="color: #666;">@<?php echo esc_html($user->user_login); ?></small>
                                </td>
                                <td>
                                    <span class="level-badge level-<?php echo $user->level; ?>" style="background: <?php echo ruh_get_level_color_safe($user->level); ?>;">
                                        Seviye <?php echo $user->level; ?>
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo number_format($user->xp); ?></strong> XP<br>
                                    <small style="color: #666;">
                                        Sonraki: <?php echo number_format(ruh_calculate_xp_for_level($user->level + 1) - $user->xp); ?> XP
                                    </small>
                                </td>
                                <td>
                                    <div class="user-stats">
                                        📝 <?php echo number_format($user->comment_count); ?> yorum<br>
                                        👍 <?php echo number_format($user->total_likes); ?> beğeni
                                    </div>
                                </td>
                                <td>
                                    <button type="button" class="button button-small" 
                                            onclick="editUserLevel(<?php echo $user->ID; ?>, <?php echo $user->level; ?>, <?php echo $user->xp; ?>, '<?php echo esc_js($user->display_name); ?>')">
                                        <span class="dashicons dashicons-edit"></span> Düzenle
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>

        <!-- Sayfalama -->
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    $pagination_args = array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo; Önceki',
                        'next_text' => 'Sonraki &raquo;',
                        'total' => $total_pages,
                        'current' => $page
                    );
                    echo paginate_links($pagination_args);
                    ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Seviye Düzenleme Modal -->
    <div id="editLevelModal" class="ruh-modal" style="display: none;">
        <div class="ruh-modal-content">
            <div class="ruh-modal-header">
                <h3>Kullanıcı Seviyesi Düzenle</h3>
                <span class="ruh-modal-close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="post" id="editLevelForm">
                <?php wp_nonce_field('ruh_level_manager', '_wpnonce'); ?>
                <input type="hidden" name="action" value="update_user_level">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div class="ruh-modal-body">
                    <p><strong>Kullanıcı:</strong> <span id="edit_user_name"></span></p>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="new_level">Yeni Seviye:</label></th>
                            <td>
                                <input type="number" name="new_level" id="new_level" min="1" max="999" class="small-text">
                                <p class="description">Minimum: 1, Maksimum: 999</p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="new_xp">Yeni XP:</label></th>
                            <td>
                                <input type="number" name="new_xp" id="new_xp" min="0" class="regular-text">
                                <p class="description">Bu seviye için gereken minimum XP otomatik hesaplanacak.</p>
                            </td>
                        </tr>
                    </table>
                    
                    <div id="level_preview" class="level-preview"></div>
                </div>
                
                <div class="ruh-modal-footer">
                    <button type="button" class="button" onclick="closeEditModal()">İptal</button>
                    <button type="submit" class="button button-primary">Güncelle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sıfırlama Onay Modal -->
    <div id="resetAllModal" class="ruh-modal" style="display: none;">
        <div class="ruh-modal-content">
            <div class="ruh-modal-header">
                <h3>⚠️ Tüm Seviyeleri Sıfırla</h3>
                <span class="ruh-modal-close" onclick="closeResetModal()">&times;</span>
            </div>
            <form method="post">
                <?php wp_nonce_field('ruh_level_manager', '_wpnonce'); ?>
                <input type="hidden" name="action" value="reset_all_levels">
                
                <div class="ruh-modal-body">
                    <p style="color: #d63638; font-weight: 600;">Bu işlem geri alınamaz!</p>
                    <p>Tüm kullanıcıların seviye ve XP bilgileri silinecek. Bu işlemi yapmak istediğinizden emin misiniz?</p>
                    
                    <label>
                        <input type="checkbox" id="confirm_reset" required> 
                        Evet, tüm seviyeleri sıfırlamak istiyorum
                    </label>
                </div>
                
                <div class="ruh-modal-footer">
                    <button type="button" class="button" onclick="closeResetModal()">İptal</button>
                    <button type="submit" class="button button-primary button-danger" id="confirm_reset_btn" disabled>
                        Sıfırla
                    </button>
                </div>
            </form>
        </div>
    </div>

    <style>
    .ruh-admin-header {
        margin: 20px 0;
    }
    .ruh-stats-cards {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }
    .ruh-stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        text-align: center;
        min-width: 120px;
    }
    .stat-number {
        font-size: 24px;
        font-weight: 600;
        color: #2271b1;
        margin-bottom: 5px;
    }
    .stat-label {
        color: #666;
        font-size: 13px;
    }
    .ruh-admin-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 20px 0;
        padding: 15px;
        background: #f9f9f9;
        border-radius: 6px;
    }
    .search-form input[type="search"] {
        width: 200px;
    }
    .level-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        color: white;
        font-size: 12px;
        font-weight: 600;
        text-shadow: 0 1px 1px rgba(0,0,0,0.3);
    }
    .user-stats {
        font-size: 12px;
        color: #666;
        line-height: 1.4;
    }
    .ruh-modal {
        position: fixed;
        z-index: 100000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
    }
    .ruh-modal-content {
        background-color: #fff;
        margin: 5% auto;
        border-radius: 8px;
        width: 500px;
        max-width: 90%;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    }
    .ruh-modal-header {
        padding: 20px;
        border-bottom: 1px solid #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .ruh-modal-header h3 {
        margin: 0;
    }
    .ruh-modal-close {
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        color: #666;
    }
    .ruh-modal-close:hover {
        color: #000;
    }
    .ruh-modal-body {
        padding: 20px;
    }
    .ruh-modal-footer {
        padding: 20px;
        border-top: 1px solid #ddd;
        text-align: right;
    }
    .ruh-modal-footer .button {
        margin-left: 10px;
    }
    .button-danger {
        background: #d63638 !important;
        border-color: #d63638 !important;
        color: white !important;
    }
    .level-preview {
        margin-top: 15px;
        padding: 10px;
        background: #f0f6fc;
        border-radius: 4px;
        border-left: 3px solid #2271b1;
    }
    </style>

    <script>
    function editUserLevel(userId, currentLevel, currentXp, userName) {
        document.getElementById('edit_user_id').value = userId;
        document.getElementById('edit_user_name').textContent = userName;
        document.getElementById('new_level').value = currentLevel;
        document.getElementById('new_xp').value = currentXp;
        document.getElementById('editLevelModal').style.display = 'block';
        updateLevelPreview();
    }

    function closeEditModal() {
        document.getElementById('editLevelModal').style.display = 'none';
    }

    function showResetAllModal() {
        document.getElementById('resetAllModal').style.display = 'block';
    }

    function closeResetModal() {
        document.getElementById('resetAllModal').style.display = 'none';
    }

    function updateLevelPreview() {
        const level = parseInt(document.getElementById('new_level').value) || 1;
        const xp = parseInt(document.getElementById('new_xp').value) || 0;
        
        // Basit XP hesaplama (PHP fonksiyonunu taklit et)
        const requiredXp = Math.floor(Math.pow(level, 1.8) * 100);
        const nextLevelXp = Math.floor(Math.pow(level + 1, 1.8) * 100);
        
        let preview = `
            <strong>Önizleme:</strong><br>
            Seviye ${level} için gereken minimum XP: ${requiredXp.toLocaleString()}<br>
            Sonraki seviye için gereken XP: ${nextLevelXp.toLocaleString()}<br>
        `;
        
        if (xp < requiredXp) {
            preview += `<span style="color: #d63638;">⚠️ XP bu seviye için yetersiz!</span>`;
        } else {
            preview += `<span style="color: #00a32a;">✅ XP bu seviye için uygun</span>`;
        }
        
        document.getElementById('level_preview').innerHTML = preview;
    }

    // Event listeners
    document.getElementById('new_level').addEventListener('input', updateLevelPreview);
    document.getElementById('new_xp').addEventListener('input', updateLevelPreview);

    document.getElementById('confirm_reset').addEventListener('change', function() {
        document.getElementById('confirm_reset_btn').disabled = !this.checked;
    });

    // Modal dışına tıklayınca kapat
    window.onclick = function(event) {
        const editModal = document.getElementById('editLevelModal');
        const resetModal = document.getElementById('resetAllModal');
        
        if (event.target == editModal) {
            editModal.style.display = 'none';
        }
        if (event.target == resetModal) {
            resetModal.style.display = 'none';
        }
    }
    </script>
    <?php
}