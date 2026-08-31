<?php
/**
 * Import/Export System
 * Disqus import, CSV export, JSON backup
 * 
 * @package RuhComment
 * @version 5.1
 */

if (!defined('ABSPATH')) exit;

class Ruh_Import_Export {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_post_ruh_export_comments', array($this, 'export_comments'));
        add_action('admin_post_ruh_export_users', array($this, 'export_users'));
        add_action('admin_post_ruh_import_disqus', array($this, 'import_disqus'));
        add_action('admin_post_ruh_backup_data', array($this, 'backup_data'));
    }
    
    /**
     * Admin menüye Import/Export sayfası ekle
     */
    public function add_menu_page() {
        add_submenu_page(
            'ruh-comment',
            'İçe/Dışa Aktar',
            'İçe/Dışa Aktar',
            'manage_options',
            'ruh-import-export',
            array($this, 'render_page')
        );
    }
    
    /**
     * Sayfa render
     */
    public function render_page() {
        ?>
        <div class="wrap ruh-admin-wrap ruh-import-export-page">
            <div class="ruh-admin-header" style="background:linear-gradient(135deg,#0ea5e9,#2563eb);padding:24px 28px;border-radius:16px;color:#fff;margin-bottom:20px;">
                <h1 style="margin:0;color:#fff;">İçe / Dışa Aktar</h1>
                <p style="margin:8px 0 0;opacity:.9;">Yorumları yedekleyin, CSV alın veya Disqus XML içe aktarın.</p>
            </div>
            <?php if (isset($_GET['imported'])) : ?>
                <div class="notice notice-success is-dismissible"><p><?php echo esc_html(intval($_GET['imported'])); ?> yorum içe aktarıldı<?php echo isset($_GET['skipped']) ? ', ' . intval($_GET['skipped']) . ' atlandı' : ''; ?>.</p></div>
            <?php endif; ?>
            
            <div class="ruh-admin-grid">
                <!-- Export Section -->
                <div class="ruh-admin-card">
                    <h2>📤 Dışa Aktar</h2>
                    
                    <div class="export-section">
                        <h3>Yorumları Dışa Aktar</h3>
                        <p>Tüm yorumları CSV formatında indirin.</p>
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                            <input type="hidden" name="action" value="ruh_export_comments">
                            <?php wp_nonce_field('ruh_export_comments'); ?>
                            <button type="submit" class="button button-primary">CSV İndir</button>
                        </form>
                    </div>
                    
                    <hr>
                    
                    <div class="export-section">
                        <h3>Kullanıcı İstatistiklerini Dışa Aktar</h3>
                        <p>Kullanıcı seviye ve rozet verilerini CSV formatında indirin.</p>
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                            <input type="hidden" name="action" value="ruh_export_users">
                            <?php wp_nonce_field('ruh_export_users'); ?>
                            <button type="submit" class="button button-primary">CSV İndir</button>
                        </form>
                    </div>
                    
                    <hr>
                    
                    <div class="export-section">
                        <h3>Tam Yedek (JSON)</h3>
                        <p>Tüm Ruh Comment verilerini JSON formatında yedekleyin.</p>
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                            <input type="hidden" name="action" value="ruh_backup_data">
                            <?php wp_nonce_field('ruh_backup_data'); ?>
                            <button type="submit" class="button button-primary">JSON Yedek İndir</button>
                        </form>
                    </div>
                </div>
                
                <!-- Import Section -->
                <div class="ruh-admin-card">
                    <h2>📥 İçe Aktar</h2>
                    
                    <div class="import-section">
                        <h3>Disqus'tan İçe Aktar</h3>
                        <p>Disqus XML export dosyanızı yükleyin.</p>
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
                            <input type="hidden" name="action" value="ruh_import_disqus">
                            <?php wp_nonce_field('ruh_import_disqus'); ?>
                            <input type="file" name="disqus_file" accept=".xml" required>
                            <br><br>
                            <button type="submit" class="button button-primary">Disqus İçe Aktar</button>
                        </form>
                    </div>
                    
                    <div class="import-info">
                        <h4>⚠️ Önemli Notlar</h4>
                        <ul>
                            <li>İçe aktarma işlemi geri alınamaz</li>
                            <li>Büyük dosyalarda işlem uzun sürebilir</li>
                            <li>İşlem sırasında sayfadan ayrılmayın</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .ruh-import-export-page {
            max-width: 1200px;
        }
        
        .ruh-admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .ruh-admin-card {
            background: #fff;
            border: 1px solid #eef0f6;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 8px 24px rgba(15,23,42,0.06);
        }
        @media (max-width: 782px) {
            .ruh-admin-grid { grid-template-columns: 1fr; }
        }
        
        .ruh-admin-card h2 {
            margin-top: 0;
            color: #667eea;
        }
        
        .export-section,
        .import-section {
            margin: 1.5rem 0;
        }
        
        .export-section h3,
        .import-section h3 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .import-info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 1rem;
            margin-top: 2rem;
        }
        
        .import-info h4 {
            margin-top: 0;
            color: #856404;
        }
        
        .import-info ul {
            margin-bottom: 0;
        }
        </style>
        <?php
    }
    
    /**
     * Yorumları CSV olarak dışa aktar
     */
    public function export_comments() {
        check_admin_referer('ruh_export_comments');
        
        if (!current_user_can('manage_options')) {
            wp_die('Yetkisiz erişim.');
        }
        
        global $wpdb;
        
        $comments = $wpdb->get_results("
            SELECT 
                c.comment_ID,
                c.comment_post_ID,
                p.post_title,
                c.comment_author,
                c.comment_author_email,
                c.comment_content,
                c.comment_date,
                c.comment_approved,
                c.comment_parent,
                COALESCE(cm_likes.meta_value, 0) as likes,
                COALESCE(cm_dislikes.meta_value, 0) as dislikes
            FROM {$wpdb->comments} c
            LEFT JOIN {$wpdb->posts} p ON c.comment_post_ID = p.ID
            LEFT JOIN {$wpdb->commentmeta} cm_likes ON c.comment_ID = cm_likes.comment_id AND cm_likes.meta_key = '_likes'
            LEFT JOIN {$wpdb->commentmeta} cm_dislikes ON c.comment_ID = cm_dislikes.comment_id AND cm_dislikes.meta_key = '_dislikes'
            ORDER BY c.comment_date DESC
        ");
        
        // CSV header
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=ruh-comments-' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // UTF-8 BOM for Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers
        fputcsv($output, array('ID', 'Post ID', 'Post Başlık', 'Yazar', 'Email', 'İçerik', 'Tarih', 'Durum', 'Üst Yorum', 'Beğeni', 'Beğenmeme'));
        
        // Data
        foreach ($comments as $comment) {
            fputcsv($output, array(
                $comment->comment_ID,
                $comment->comment_post_ID,
                $comment->post_title,
                $comment->comment_author,
                $comment->comment_author_email,
                strip_tags($comment->comment_content),
                $comment->comment_date,
                $comment->comment_approved == 1 ? 'Onaylı' : 'Beklemede',
                $comment->comment_parent,
                $comment->likes,
                $comment->dislikes
            ));
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Kullanıcı istatistiklerini CSV olarak dışa aktar
     */
    public function export_users() {
        check_admin_referer('ruh_export_users');
        
        if (!current_user_can('manage_options')) {
            wp_die('Yetkisiz erişim.');
        }
        
        global $wpdb;
        
        $users = $wpdb->get_results("
            SELECT 
                u.ID,
                u.user_login,
                u.display_name,
                u.user_email,
                u.user_registered,
                COALESCE(ul.level, 1) as level,
                COALESCE(ul.xp, 0) as xp,
                COUNT(DISTINCT c.comment_ID) as comment_count,
                COUNT(DISTINCT ub.badge_id) as badge_count
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->prefix}ruh_user_levels ul ON u.ID = ul.user_id
            LEFT JOIN {$wpdb->comments} c ON u.ID = c.user_id AND c.comment_approved = '1'
            LEFT JOIN {$wpdb->prefix}ruh_user_badges ub ON u.ID = ub.user_id
            GROUP BY u.ID
            ORDER BY ul.level DESC, ul.xp DESC
        ");
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=ruh-users-' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($output, array('ID', 'Kullanıcı Adı', 'Görünen Ad', 'Email', 'Kayıt Tarihi', 'Seviye', 'XP', 'Yorum Sayısı', 'Rozet Sayısı'));
        
        foreach ($users as $user) {
            fputcsv($output, (array)$user);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Tam yedek JSON
     */
    public function backup_data() {
        check_admin_referer('ruh_backup_data');
        
        if (!current_user_can('manage_options')) {
            wp_die('Yetkisiz erişim.');
        }
        
        global $wpdb;
        
        $backup = array(
            'version' => RUH_COMMENT_VERSION,
            'date' => current_time('mysql'),
            'site_url' => get_site_url(),
            'reactions' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ruh_reactions", ARRAY_A),
            'user_levels' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ruh_user_levels", ARRAY_A),
            'badges' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ruh_badges", ARRAY_A),
            'user_badges' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ruh_user_badges", ARRAY_A),
            'reports' => $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ruh_reports", ARRAY_A),
            'options' => get_option('ruh_comment_options', array())
        );
        
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=ruh-comment-backup-' . date('Y-m-d-His') . '.json');
        
        echo json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Disqus XML import
     */
    public function import_disqus() {
        check_admin_referer('ruh_import_disqus');
        
        if (!current_user_can('manage_options')) {
            wp_die('Yetkisiz erişim.');
        }
        
        global $wpdb;
        
        if (!isset($_FILES['disqus_file'])) {
            wp_die('Dosya yüklenmedi.');
        }
        
        $file = $_FILES['disqus_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_die('Dosya yüklenirken hata oluştu.');
        }
        
        // XML dosyasını oku
        $xml_content = file_get_contents($file['tmp_name']);
        
        if (!$xml_content) {
            wp_die('Dosya okunamadı.');
        }
        
        // XML parse
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xml_content);
        
        if (!$xml) {
            wp_die('Geçersiz XML dosyası.');
        }
        
        $imported = 0;
        $skipped = 0;
        $xml->registerXPathNamespace('dsq', 'http://disqus.com/disqus-internals');
        $posts = $xml->xpath('//post') ?: $xml->xpath('//dsq:post') ?: array();
        if (empty($posts) && isset($xml->post)) {
            $posts = $xml->post;
        }
        
        foreach ($posts as $comment_node) {
            $author = isset($comment_node->author) ? $comment_node->author : null;
            $author_name = 'Anonim';
            $author_email = '';
            if ($author) {
                if (isset($author->name)) $author_name = (string) $author->name;
                elseif (isset($author->username)) $author_name = (string) $author->username;
                if (isset($author->email)) $author_email = (string) $author->email;
            }
            $message = isset($comment_node->message) ? (string) $comment_node->message : (isset($comment_node->content) ? (string) $comment_node->content : '');
            $created = isset($comment_node->createdAt) ? (string) $comment_node->createdAt : current_time('mysql');
            $is_spam = strtolower(isset($comment_node->isSpam) ? (string) $comment_node->isSpam : 'false');
            $is_deleted = strtolower(isset($comment_node->isDeleted) ? (string) $comment_node->isDeleted : 'false');
            $thread = isset($comment_node->thread) ? (string) $comment_node->thread : (isset($comment_node['thread']) ? (string) $comment_node['thread'] : '');
            
            $post_id = 0;
            if ($thread) {
                $matched = $wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE guid LIKE %s OR post_name = %s LIMIT 1", '%' . $wpdb->esc_like($thread) . '%', sanitize_title($thread)));
                $post_id = intval($matched);
            }
            if (!$post_id) {
                $post_id = (int) get_option('page_on_front');
            }
            if (!$post_id || !$message) {
                $skipped++;
                continue;
            }
            
            $comment_data = array(
                'comment_post_ID' => $post_id,
                'comment_author' => sanitize_text_field($author_name),
                'comment_author_email' => sanitize_email($author_email),
                'comment_content' => wp_kses_post($message),
                'comment_date' => date('Y-m-d H:i:s', strtotime($created) ?: time()),
                'comment_approved' => ($is_spam === 'false' && $is_deleted === 'false') ? 1 : 'spam',
                'comment_type' => 'comment'
            );
            
            $result = wp_insert_comment($comment_data);
            if ($result) {
                $imported++;
            } else {
                $skipped++;
            }
        }
        
        wp_redirect(add_query_arg(
            array(
                'page' => 'ruh-import-export',
                'imported' => $imported,
                'skipped' => $skipped
            ),
            admin_url('admin.php')
        ));
        exit;
    }
}

new Ruh_Import_Export();

