<?php
/**
 * Analytics Dashboard & Advanced Moderation
 * 
 * @package RuhComment
 * @version 5.1
 */

if (!defined('ABSPATH')) exit;

class Ruh_Analytics_Dashboard {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Analytics sayfası ekle
     */
    public function add_menu_page() {
        add_submenu_page(
            'ruh-comment',
            'Analytics & İstatistikler',
            'Analytics',
            'manage_options',
            'ruh-analytics',
            array($this, 'render_page')
        );
    }
    
    /**
     * Chart.js yükle
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'ruh-comment_page_ruh-analytics') return;
        
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', array(), '4.4.0', true);
    }
    
    /**
     * Analytics sayfasını render et
     */
    public function render_page() {
        $stats = $this->get_stats();
        ?>
        <div class="wrap ruh-admin-wrap ruh-analytics-dashboard">
            <div class="ruh-admin-header" style="background:linear-gradient(135deg,#667eea,#764ba2);padding:24px 28px;border-radius:16px;color:#fff;margin-bottom:20px;">
                <h1 style="margin:0;color:#fff;">Analytics & İstatistikler</h1>
                <p style="margin:8px 0 0;opacity:.9;">Yorum, tepki ve kullanıcı aktivitesinin özeti.</p>
            </div>
            
            <!-- Overview Cards -->
            <div class="analytics-overview">
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        💬
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_comments']); ?></h3>
                        <p>Toplam Yorum</p>
                        <span class="stat-change positive">
                            +<?php echo $stats['comments_this_week']; ?> bu hafta
                        </span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb, #f5576c);">
                        👥
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_users']); ?></h3>
                        <p>Aktif Kullanıcı</p>
                        <span class="stat-change positive">
                            +<?php echo $stats['new_users_this_week']; ?> bu hafta
                        </span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe, #00f2fe);">
                        ❤️
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_reactions']); ?></h3>
                        <p>Toplam Tepki</p>
                        <span class="stat-change positive">
                            +<?php echo $stats['reactions_this_week']; ?> bu hafta
                        </span>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a, #fee140);">
                        🏆
                    </div>
                    <div class="stat-info">
                        <h3><?php echo number_format($stats['total_badges_earned']); ?></h3>
                        <p>Kazanılan Rozet</p>
                        <span class="stat-change">
                            <?php echo $stats['badges_this_week']; ?> bu hafta
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="analytics-charts">
                <div class="chart-card">
                    <h2>📈 Yorum Trendi (Son 30 Gün)</h2>
                    <canvas id="commentsChart" height="80"></canvas>
                </div>
                
                <div class="chart-card">
                    <h2>😍 En Popüler Tepkiler</h2>
                    <canvas id="reactionsChart" height="80"></canvas>
                </div>
            </div>
            
            <!-- Top Users -->
            <div class="analytics-tables">
                <div class="table-card">
                    <h2>🏅 En Aktif Kullanıcılar</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Sıra</th>
                                <th>Kullanıcı</th>
                                <th>Seviye</th>
                                <th>XP</th>
                                <th>Yorum</th>
                                <th>Beğeni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $top_users = $this->get_top_users(10);
                            $rank = 1;
                            foreach ($top_users as $user) : 
                            ?>
                            <tr>
                                <td><strong><?php echo $rank++; ?></strong></td>
                                <td>
                                    <a href="<?php echo get_edit_user_link($user->ID); ?>">
                                        <?php echo esc_html($user->display_name); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="level-badge" style="background: <?php echo ruh_get_level_color_safe($user->level); ?>">
                                        Lv. <?php echo $user->level; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($user->xp); ?></td>
                                <td><?php echo number_format($user->comment_count); ?></td>
                                <td><?php echo number_format($user->total_likes); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="table-card">
                    <h2>🔥 En Popüler Yorumlar</h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Yorum</th>
                                <th>Yazar</th>
                                <th>Beğeni</th>
                                <th>Tarih</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $popular_comments = $this->get_popular_comments(10);
                            foreach ($popular_comments as $comment) : 
                            ?>
                            <tr>
                                <td>
                                    <a href="<?php echo get_comment_link($comment->comment_ID); ?>" target="_blank">
                                        <?php echo wp_trim_words(strip_tags($comment->comment_content), 10); ?>
                                    </a>
                                </td>
                                <td><?php echo esc_html($comment->comment_author); ?></td>
                                <td>
                                    <span class="like-badge">
                                        ❤️ <?php echo $comment->likes; ?>
                                    </span>
                                </td>
                                <td><?php echo human_time_diff(strtotime($comment->comment_date), current_time('timestamp')); ?> önce</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            if (typeof Chart === 'undefined') return;
            if (!document.getElementById('commentsChart') || !document.getElementById('reactionsChart')) return;
            const commentsData = <?php echo json_encode($this->get_comments_trend()); ?>;
            
            new Chart(document.getElementById('commentsChart'), {
                type: 'line',
                data: {
                    labels: commentsData.labels,
                    datasets: [{
                        label: 'Yorumlar',
                        data: commentsData.data,
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Reactions chart
            const reactionsData = <?php echo json_encode($this->get_reactions_stats()); ?>;
            
            new Chart(document.getElementById('reactionsChart'), {
                type: 'doughnut',
                data: {
                    labels: reactionsData.labels,
                    datasets: [{
                        data: reactionsData.data,
                        backgroundColor: [
                            '#667eea', '#764ba2', '#f093fb', '#4facfe', 
                            '#00f2fe', '#43e97b'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true
                }
            });
        });
        </script>
        
        <style>
        .ruh-analytics-dashboard {
            max-width: 1400px;
        }
        
        .analytics-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            gap: 1.5rem;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #ffffff;
        }
        
        .stat-info h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
        }
        
        .stat-info p {
            margin: 0.25rem 0;
            color: #718096;
            font-size: 0.9rem;
        }
        
        .stat-change {
            display: inline-block;
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            background: #e2e8f0;
            color: #64748b;
        }
        
        .stat-change.positive {
            background: #d1fae5;
            color: #059669;
        }
        
        .analytics-charts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .chart-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .chart-card h2 {
            margin-top: 0;
            font-size: 1.25rem;
            color: #1a202c;
        }
        
        .analytics-tables {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .table-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .table-card h2 {
            margin-top: 0;
            font-size: 1.25rem;
            color: #1a202c;
        }
        
        .level-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-block;
        }
        
        .like-badge {
            background: linear-gradient(135deg, #ec4899, #be185d);
            color: #ffffff;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        @media (max-width: 782px) {
            .analytics-overview,
            .analytics-charts,
            .analytics-tables {
                grid-template-columns: 1fr !important;
            }
            .stat-card { padding: 1rem; }
            .chart-card, .table-card { padding: 1rem; overflow-x: auto; }
        }
        </style>
        <?php
    }
    
    /**
     * Genel istatistikleri getir
     */
    private function get_stats() {
        global $wpdb;
        
        $week_ago = gmdate('Y-m-d H:i:s', strtotime('-1 week'));
        
        return array(
            'total_comments' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = '1'"),
            'comments_this_week' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_date >= %s AND comment_approved = '1'",
                $week_ago
            )),
            'total_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->comments} WHERE user_id > 0"),
            'new_users_this_week' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered >= %s",
                $week_ago
            )),
            'total_reactions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ruh_reactions") ?: 0,
            'reactions_this_week' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ruh_reactions WHERE created_at >= %s",
                $week_ago
            )) ?: 0,
            'total_badges_earned' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ruh_user_badges") ?: 0,
            'badges_this_week' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}ruh_user_badges WHERE assigned_at >= %s",
                $week_ago
            )) ?: 0
        );
    }
    
    /**
     * Son 30 günün yorum trendini getir
     */
    private function get_comments_trend() {
        global $wpdb;
        
        $days = 30;
        $labels = array();
        $data = array();
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $labels[] = date('d M', strtotime($date));
            
            $count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->comments} 
                WHERE DATE(comment_date) = %s AND comment_approved = '1'",
                $date
            ));
            
            $data[] = intval($count);
        }
        
        return array(
            'labels' => $labels,
            'data' => $data
        );
    }
    
    /**
     * Tepki istatistikleri
     */
    private function get_reactions_stats() {
        global $wpdb;
        
        $results = $wpdb->get_results(
            "SELECT reaction, COUNT(*) as count 
            FROM {$wpdb->prefix}ruh_reactions 
            GROUP BY reaction 
            ORDER BY count DESC"
        );
        
        $labels = array();
        $data = array();
        
        $opts = get_option('ruh_comment_options', array());
        $reaction_names = array(
            'begendim' => $opts['emoji_label_begendim'] ?? 'Beğendim',
            'sinir_bozucu' => $opts['emoji_label_sinir_bozucu'] ?? 'Sinir Bozucu',
            'mukemmel' => $opts['emoji_label_mukemmel'] ?? 'Mükemmel',
            'sasirtici' => $opts['emoji_label_sasirtici'] ?? 'Şaşırtıcı',
            'sakin' => $opts['emoji_label_sakin'] ?? 'Üzücü',
            'bitti' => $opts['emoji_label_bitti'] ?? 'Bitti',
            'sakin_olmalivim' => $opts['emoji_label_sakin'] ?? 'Üzücü',
            'bolum_bitti' => $opts['emoji_label_bitti'] ?? 'Bitti'
        );
        
        foreach ($results as $result) {
            $labels[] = $reaction_names[$result->reaction] ?? $result->reaction;
            $data[] = intval($result->count);
        }
        
        return array(
            'labels' => $labels,
            'data' => $data
        );
    }
    
    /**
     * En aktif kullanıcılar
     */
    private function get_top_users($limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                u.ID,
                u.display_name,
                COALESCE(ul.level, 1) as level,
                COALESCE(ul.xp, 0) as xp,
                COUNT(DISTINCT c.comment_ID) as comment_count,
                COALESCE(SUM(CAST(cm.meta_value AS UNSIGNED)), 0) as total_likes
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->prefix}ruh_user_levels ul ON u.ID = ul.user_id
            LEFT JOIN {$wpdb->comments} c ON u.ID = c.user_id AND c.comment_approved = '1'
            LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = '_likes'
            GROUP BY u.ID
            HAVING comment_count > 0
            ORDER BY (COALESCE(ul.level, 1) * 10 + comment_count) DESC
            LIMIT %d
        ", $limit));
    }
    
    /**
     * En popüler yorumlar
     */
    private function get_popular_comments($limit = 10) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare("
            SELECT 
                c.*,
                CAST(COALESCE(cm.meta_value, 0) AS UNSIGNED) as likes
            FROM {$wpdb->comments} c
            LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = '_likes'
            WHERE c.comment_approved = '1'
            ORDER BY likes DESC, c.comment_date DESC
            LIMIT %d
        ", $limit));
    }
}

new Ruh_Analytics_Dashboard();

/**
 * Gelişmiş Moderasyon Araçları
 */
class Ruh_Advanced_Moderation {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_post_ruh_bulk_action', array($this, 'handle_bulk_action'));
    }
    
    public function add_menu_page() {
        add_submenu_page(
            'ruh-comment',
            'Gelişmiş Moderasyon',
            'Moderasyon',
            'moderate_comments',
            'ruh-moderation',
            array($this, 'render_page')
        );
    }
    
    public function render_page() {
        global $wpdb;
        
        // Filtreler
        $status = isset($_GET['status']) ? sanitize_key($_GET['status']) : 'all';
        $orderby = isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'date';
        
        // Şikayetli yorumlar
        $reported_comments = $wpdb->get_results("
            SELECT 
                c.*,
                COUNT(r.id) as report_count,
                GROUP_CONCAT(DISTINCT u.display_name SEPARATOR ', ') as reporters
            FROM {$wpdb->comments} c
            INNER JOIN {$wpdb->prefix}ruh_reports r ON c.comment_ID = r.comment_id
            LEFT JOIN {$wpdb->users} u ON r.reporter_id = u.ID
            GROUP BY c.comment_ID
            HAVING report_count > 0
            ORDER BY report_count DESC
            LIMIT 20
        ");
        ?>
        <div class="wrap ruh-admin-wrap ruh-moderation-page">
            <div class="ruh-admin-header" style="background:linear-gradient(135deg,#f59e0b,#d97706);padding:24px 28px;border-radius:16px;color:#fff;margin-bottom:20px;">
                <h1 style="margin:0;color:#fff;">Gelişmiş Moderasyon</h1>
                <p style="margin:8px 0 0;opacity:.9;">Şikayetli yorumları inceleyin ve toplu işlem uygulayın.</p>
            </div>
            <?php if (isset($_GET['processed'])) : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html(intval($_GET['processed'])); ?> yorum işlendi.</p></div>
            <?php endif; ?>
            
            <?php if (!empty($reported_comments)) : ?>
            <div class="moderation-card">
                <h2>Şikayetli Yorumlar (<?php echo count($reported_comments); ?>)</h2>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="ruh-moderation-bulk-form">
                    <input type="hidden" name="action" value="ruh_bulk_action">
                    <?php wp_nonce_field('ruh_bulk_action'); ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="select-all">
                            </th>
                            <th>Yorum</th>
                            <th>Yazar</th>
                            <th>Şikayet</th>
                            <th>Şikayetçiler</th>
                            <th>İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reported_comments as $comment) : ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="comment_ids[]" value="<?php echo $comment->comment_ID; ?>">
                            </td>
                            <td>
                                <a href="<?php echo get_comment_link($comment->comment_ID); ?>" target="_blank">
                                    <?php echo wp_trim_words(strip_tags($comment->comment_content), 15); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($comment->comment_author); ?></td>
                            <td>
                                <span class="report-badge" style="background: <?php echo $comment->report_count > 5 ? '#ef4444' : '#f59e0b'; ?>">
                                    <?php echo $comment->report_count; ?> şikayet
                                </span>
                            </td>
                            <td><?php echo esc_html($comment->reporters); ?></td>
                            <td>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('comment.php?action=approvecomment&c=' . $comment->comment_ID), 'approve-comment_' . $comment->comment_ID)); ?>" class="button button-small">Onayla</a>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('comment.php?action=trashcomment&c=' . $comment->comment_ID), 'delete-comment_' . $comment->comment_ID)); ?>" class="button button-small">Sil</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                    <div class="bulk-actions-bar">
                        <select name="bulk_action" required>
                            <option value="">Toplu İşlem Seç</option>
                            <option value="approve">Onayla</option>
                            <option value="trash">Çöpe Taşı</option>
                            <option value="spam">Spam Olarak İşaretle</option>
                            <option value="delete">Kalıcı Sil</option>
                        </select>
                        <button type="submit" class="button button-primary">Uygula</button>
                    </div>
                </form>
                <script>
                jQuery(function($){
                    $('#select-all').on('change', function(){
                        $('input[name="comment_ids[]"]').prop('checked', this.checked);
                    });
                });
                </script>
            </div>
            <?php else : ?>
            <div class="notice notice-success">
                <p>✅ Şu anda şikayetli yorum bulunmuyor!</p>
            </div>
            <?php endif; ?>
        </div>
        
        <style>
        .moderation-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin: 2rem 0;
        }
        
        .moderation-card h2 {
            margin-top: 0;
            color: #667eea;
        }
        
        .report-badge {
            color: #ffffff;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }
        
        .bulk-actions-bar {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .bulk-actions-bar form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .bulk-actions-bar select {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: 1px solid #d1d5db;
        }
        </style>
        <?php
    }
    
    /**
     * Toplu işlemleri yönet
     */
    public function handle_bulk_action() {
        check_admin_referer('ruh_bulk_action');
        
        if (!current_user_can('moderate_comments')) {
            wp_die('Yetkisiz erişim.');
        }
        
        $action = isset($_POST['bulk_action']) ? sanitize_key($_POST['bulk_action']) : '';
        $comment_ids = isset($_POST['comment_ids']) ? array_map('intval', $_POST['comment_ids']) : array();
        
        if (empty($action) || empty($comment_ids)) {
            wp_redirect(admin_url('admin.php?page=ruh-moderation'));
            exit;
        }
        
        $processed = 0;
        
        foreach ($comment_ids as $comment_id) {
            switch ($action) {
                case 'approve':
                    wp_set_comment_status($comment_id, 'approve');
                    $processed++;
                    break;
                case 'trash':
                    wp_trash_comment($comment_id);
                    $processed++;
                    break;
                case 'spam':
                    wp_spam_comment($comment_id);
                    $processed++;
                    break;
                case 'delete':
                    wp_delete_comment($comment_id, true);
                    $processed++;
                    break;
            }
        }
        
        wp_redirect(add_query_arg(
            array(
                'page' => 'ruh-moderation',
                'processed' => $processed,
                'action' => $action
            ),
            admin_url('admin.php')
        ));
        exit;
    }
}

new Ruh_Advanced_Moderation();

