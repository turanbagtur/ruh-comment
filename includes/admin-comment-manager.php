<?php
if (!defined('ABSPATH')) exit;
if (!class_exists('WP_List_Table')) { require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php'); }

class Ruh_Comments_List_Table extends WP_List_Table {
    function __construct() { 
        parent::__construct(['singular' => 'Yorum', 'plural' => 'Yorumlar', 'ajax' => false]); 
    }

    function get_columns() {
        return [
            'cb' => '<input type="checkbox" />', 
            'author' => 'Yazar', 
            'comment' => 'Yorum', 
            'response' => 'Yanıtlanan Yazı', 
            'likes' => 'Beğeniler', 
            'reports' => 'Şikayetler', 
            'date' => 'Tarih'
        ];
    }

    function get_bulk_actions() {
        return [
            'approve' => __('Onayla', 'ruh-comment'),
            'unapprove' => __('Onayı Kaldır', 'ruh-comment'),
            'spam' => __('Spam İşaretle', 'ruh-comment'),
            'unspam' => __('Spam İşaretini Kaldır', 'ruh-comment'),
            'trash' => __('Çöpe At', 'ruh-comment'),
            'delete' => __('Kalıcı Sil', 'ruh-comment')
        ];
    }

    function process_bulk_action() {
        if (empty($_GET['comment']) || !is_array($_GET['comment'])) return;
        
        $comment_ids = array_map('intval', $_GET['comment']);
        $action = $this->current_action();
        
        if (!$action || !wp_verify_nonce($_GET['_wpnonce'], 'bulk-' . $this->_args['plural'])) {
            return;
        }

        $message = '';
        switch ($action) {
            case 'approve':
                foreach ($comment_ids as $comment_id) {
                    wp_set_comment_status($comment_id, 'approve');
                }
                $message = sprintf(__('%d yorum onaylandı.', 'ruh-comment'), count($comment_ids));
                break;
                
            case 'unapprove':
                foreach ($comment_ids as $comment_id) {
                    wp_set_comment_status($comment_id, 'hold');
                }
                $message = sprintf(__('%d yorumun onayı kaldırıldı.', 'ruh-comment'), count($comment_ids));
                break;
                
            case 'spam':
                foreach ($comment_ids as $comment_id) {
                    wp_spam_comment($comment_id);
                }
                $message = sprintf(__('%d yorum spam olarak işaretlendi.', 'ruh-comment'), count($comment_ids));
                break;
                
            case 'unspam':
                foreach ($comment_ids as $comment_id) {
                    wp_unspam_comment($comment_id);
                }
                $message = sprintf(__('%d yorumun spam işareti kaldırıldı.', 'ruh-comment'), count($comment_ids));
                break;
                
            case 'trash':
                foreach ($comment_ids as $comment_id) {
                    wp_trash_comment($comment_id);
                }
                $message = sprintf(__('%d yorum çöpe atıldı.', 'ruh-comment'), count($comment_ids));
                break;
                
            case 'delete':
                foreach ($comment_ids as $comment_id) {
                    wp_delete_comment($comment_id, true);
                }
                $message = sprintf(__('%d yorum kalıcı olarak silindi.', 'ruh-comment'), count($comment_ids));
                break;
        }
        
        if (!empty($message)) {
            add_action('admin_notices', function() use ($message) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            });
        }
    }

    function prepare_items() {
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns()];
        $this->process_bulk_action();
        
        $paged = $this->get_pagenum();
        $per_page = 20;

        $args = ['number' => $per_page, 'offset' => ($paged - 1) * $per_page, 'orderby' => 'comment_date', 'order' => 'DESC'];
        if (isset($_GET['comment_status'])) { 
            $status = sanitize_key($_GET['comment_status']);
            if ($status !== 'all') {
                $args['status'] = $status === 'moderated' ? 'hold' : $status;
            }
        }
        
        $this->items = get_comments($args);
        $total_items = get_comments(array_merge($args, ['count' => true, 'offset' => 0, 'number' => 0]));
        $this->set_pagination_args(['total_items' => $total_items, 'per_page' => $per_page]);
    }

    function column_cb($item) { 
        return sprintf('<input type="checkbox" name="comment[]" value="%s" />', $item->comment_ID); 
    }
    
    function column_author($item) { 
        $user_info = '';
        if ($item->user_id) {
            $user = get_userdata($item->user_id);
            $user_info = $user ? ' (ID: ' . $item->user_id . ')' : '';
        }
        return '<strong>' . esc_html($item->comment_author) . '</strong>' . $user_info . '<br>' . 
               '<span style="color:#666;">' . esc_html($item->comment_author_email) . '</span>';
    }
    
    function column_response($item) { 
        $post_title = get_the_title($item->comment_post_ID);
        return '<a href="' . esc_url(get_permalink($item->comment_post_ID)) . '" target="_blank">' . 
               esc_html($post_title ?: 'Bilinmeyen Yazı') . '</a>';
    }
    
    function column_likes($item) { 
        $likes = get_comment_meta($item->comment_ID, '_likes', true) ?: 0; 
        $dislikes = get_comment_meta($item->comment_ID, '_dislikes', true) ?: 0; 
        return "<div class='comment-stats'><span class='likes'>👍 {$likes}</span> / <span class='dislikes'>👎 {$dislikes}</span></div>";
    }
    
    function column_reports($item) { 
        global $wpdb; 
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}ruh_reports WHERE comment_id = %d", $item->comment_ID)); 
        $class = $count > 0 ? "color:red; font-weight:bold;" : "";
        return "<span style='{$class}'>{$count}</span>";
    }
    
    function column_date($item) { 
        return mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $item->comment_date); 
    }
    
    function column_comment($item) {
        $actions = [];
        $approve_nonce = wp_create_nonce("approve-comment_{$item->comment_ID}");
        
        // Onay durumu butonları
        if ($item->comment_approved == '0') { 
            $actions['approve'] = "<a href='?page=ruh-comment-manager&action=approve&c={$item->comment_ID}&_wpnonce=$approve_nonce' class='button button-small'>Onayla</a>"; 
        } else { 
            $actions['unapprove'] = "<a href='?page=ruh-comment-manager&action=unapprove&c={$item->comment_ID}&_wpnonce=$approve_nonce' class='button button-small'>Onayı Kaldır</a>"; 
        }
        
        // Diğer eylemler
        $actions['view'] = "<a href='" . esc_url(get_comment_link($item)) . "' target='_blank' class='button button-small'>Görüntüle</a>";
        $actions['edit'] = "<a href='" . admin_url('comment.php?action=editcomment&c=' . $item->comment_ID) . "' class='button button-small'>Düzenle</a>";
        $actions['quick-edit'] = "<a href='#' class='quick-edit-comment button button-small' data-comment-id='{$item->comment_ID}'>Hızlı Düzenle</a>";
        
        if ($item->comment_approved !== 'spam') {
            $actions['spam'] = "<a href='" . wp_nonce_url("?page=ruh-comment-manager&action=spam&c={$item->comment_ID}", "spam-comment_{$item->comment_ID}") . "' class='button button-small' style='color:#d63638;'>Spam</a>";
        } else {
            $actions['unspam'] = "<a href='" . wp_nonce_url("?page=ruh-comment-manager&action=unspam&c={$item->comment_ID}", "unspam-comment_{$item->comment_ID}") . "' class='button button-small'>Spam Değil</a>";
        }
        
        $actions['trash'] = "<a href='" . wp_nonce_url("?page=ruh-comment-manager&action=trash&c={$item->comment_ID}", "trash-comment_{$item->comment_ID}") . "' class='button button-small' style='color:#d63638;'>Çöpe At</a>";

        // Kullanıcı yönetimi eylemleri
        $ban_nonce = wp_create_nonce("ban-user_{$item->user_id}");
        if ($item->user_id && $item->user_id != get_current_user_id()) {
            $user = get_userdata($item->user_id);
            if ($user) {
                $ban_status = get_user_meta($item->user_id, 'ruh_ban_status', true);
                $timeout_until = get_user_meta($item->user_id, 'ruh_timeout_until', true);
                
                if ($ban_status !== 'banned') {
                    $actions['ban'] = "<a href='?page=ruh-comment-manager&action=ban&user_id={$item->user_id}&_wpnonce=$ban_nonce' onclick='return confirm(\"Bu kullanıcıyı kalıcı olarak engellemek istediğinizden emin misiniz?\")' class='button button-small' style='background:#d63638;color:white;'>Engelle</a>";
                }
                
                if (!$timeout_until || $timeout_until < time()) {
                    $actions['timeout'] = "<a href='?page=ruh-comment-manager&action=timeout&user_id={$item->user_id}&_wpnonce=$ban_nonce' onclick='return confirm(\"Bu kullanıcıya 24 saat zaman aşımı uygulamak istediğinizden emin misiniz?\")' class='button button-small' style='background:#e67e22;color:white;'>24 Saat Sustur</a>";
                }
            }
        }
        
        // Yorum metni ve durum göstergesi
        $status_indicator = '';
        switch ($item->comment_approved) {
            case '1':
                $status_indicator = '<span class="status-indicator approved" title="Onaylı"></span>';
                break;
            case '0':
                $status_indicator = '<span class="status-indicator pending" title="Onay Bekliyor"></span>';
                break;
            case 'spam':
                $status_indicator = '<span class="status-indicator spam" title="Spam"></span>';
                break;
            case 'trash':
                $status_indicator = '<span class="status-indicator trash" title="Çöp"></span>';
                break;
        }
        
        $comment_text = '<div class="comment-content-wrapper">';
        $comment_text .= $status_indicator;
        $comment_text .= '<div id="comment-text-' . $item->comment_ID . '">' . wp_trim_words(esc_html($item->comment_content), 50) . '</div>';
        $comment_text .= '<div id="edit-comment-' . $item->comment_ID . '" style="display:none;">';
        $comment_text .= '<textarea style="width:100%; min-height:80px;" rows="4">' . esc_textarea($item->comment_content) . '</textarea>';
        $comment_text .= '<div style="margin-top:8px;">';
        $comment_text .= '<button class="button button-primary save-edit-comment" data-comment-id="' . $item->comment_ID . '">Kaydet</button> ';
        $comment_text .= '<button class="button cancel-edit-comment">İptal</button>';
        $comment_text .= '</div></div></div>';

        // Eylem butonları
        $comment_text .= '<div class="comment-actions-wrapper" style="margin-top:12px;">';
        foreach ($actions as $action_key => $action_link) {
            $comment_text .= $action_link . ' ';
        }
        $comment_text .= '</div>';

        return $comment_text;
    }
    
    function get_views() {
        $status_links = [];
        $num_comments = wp_count_comments();
        
        $stati = [
            'all' => ['label' => 'Tümü', 'count' => $num_comments->total_comments],
            'moderated' => ['label' => 'Onay Bekleyen', 'count' => $num_comments->moderated],
            'approved' => ['label' => 'Onaylı', 'count' => $num_comments->approved],
            'spam' => ['label' => 'Spam', 'count' => $num_comments->spam],
            'trash' => ['label' => 'Çöp', 'count' => $num_comments->trash]
        ];

        $current_status = isset($_GET['comment_status']) ? $_GET['comment_status'] : 'all';

        foreach ($stati as $status => $data) {
            $class = ($current_status === $status) ? ' class="current"' : '';
            $link = add_query_arg('comment_status', $status, admin_url('admin.php?page=ruh-comment-manager'));
            $status_links[$status] = "<a href='$link'$class>" . $data['label'] . " <span class='count'>(" . number_format_i18n($data['count']) . ")</span></a>";
        }
        
        return $status_links;
    }
}

function render_comment_manager_page_content() {
    // Tekli aksiyonları işle (URL'den gelen)
    if (isset($_GET['action']) && isset($_GET['c'])) {
        $action = sanitize_key($_GET['action']);
        $comment_id = intval($_GET['c']);
        
        switch ($action) {
            case 'approve':
            case 'unapprove':
                check_admin_referer("approve-comment_$comment_id");
                wp_set_comment_status($comment_id, $action === 'approve' ? 'approve' : 'hold');
                $message = $action === 'approve' ? 'Yorum onaylandı.' : 'Yorumun onayı kaldırıldı.';
                break;
                
            case 'trash':
                check_admin_referer("trash-comment_$comment_id");
                wp_trash_comment($comment_id);
                $message = 'Yorum çöpe atıldı.';
                break;
                
            case 'spam':
                check_admin_referer("spam-comment_$comment_id");
                wp_spam_comment($comment_id);
                $message = 'Yorum spam olarak işaretlendi.';
                break;
                
            case 'unspam':
                check_admin_referer("unspam-comment_$comment_id");
                wp_unspam_comment($comment_id);
                $message = 'Yorumun spam işareti kaldırıldı.';
                break;
        }
        
        if (isset($message)) {
            add_action('admin_notices', function() use ($message) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            });
        }
    }
    
    // Kullanıcı durumu aksiyonları
    if (isset($_GET['action']) && isset($_GET['user_id']) && isset($_GET['_wpnonce'])) {
        $action = sanitize_key($_GET['action']);
        $user_id = intval($_GET['user_id']);
        
        if (wp_verify_nonce($_GET['_wpnonce'], "ban-user_$user_id")) {
            switch ($action) {
                case 'ban':
                    update_user_meta($user_id, 'ruh_ban_status', 'banned');
                    $message = 'Kullanıcı kalıcı olarak engellendi.';
                    break;
                    
                case 'timeout':
                    update_user_meta($user_id, 'ruh_timeout_until', time() + 86400); // 24 saat
                    $message = 'Kullanıcı 24 saat süreyle susturuldu.';
                    break;
            }
            
            if (isset($message)) {
                add_action('admin_notices', function() use ($message) {
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
                });
            }
        }
    }

    // Yönlendirme yap
    if (isset($_GET['action'])) {
        wp_redirect(admin_url('admin.php?page=ruh-comment-manager'));
        exit;
    }

    $list_table = new Ruh_Comments_List_Table();
    $list_table->prepare_items();
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e('Yorum Yönetimi', 'ruh-comment'); ?></h1>
        
        <div class="ruh-comment-manager-stats" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border-radius: 6px;">
            <?php
            $total_comments = wp_count_comments();
            global $wpdb;
            $total_reports = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ruh_reports");
            $banned_users = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key = 'ruh_ban_status' AND meta_value = 'banned'");
            ?>
            <div style="display: flex; gap: 30px; flex-wrap: wrap;">
                <div><strong>Toplam Yorum:</strong> <?php echo number_format_i18n($total_comments->total_comments); ?></div>
                <div><strong>Onay Bekleyen:</strong> <span style="color:#e67e22;"><?php echo number_format_i18n($total_comments->moderated); ?></span></div>
                <div><strong>Toplam Şikayet:</strong> <span style="color:#d63638;"><?php echo number_format_i18n($total_reports); ?></span></div>
                <div><strong>Engellenmiş Kullanıcı:</strong> <span style="color:#d63638;"><?php echo number_format_i18n($banned_users); ?></span></div>
            </div>
        </div>
        
        <?php $list_table->views(); ?>
        <form id="comments-filter" method="get">
            <input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
            <?php if (isset($_GET['comment_status'])) : ?>
                <input type="hidden" name="comment_status" value="<?php echo esc_attr($_GET['comment_status']); ?>" />
            <?php endif; ?>
            <?php $list_table->display(); ?>
        </form>
        
        <style>
        .status-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 6px;
        }
        .status-indicator.approved { background: #00a32a; }
        .status-indicator.pending { background: #dba617; }
        .status-indicator.spam { background: #d63638; }
        .status-indicator.trash { background: #646970; }
        
        .comment-actions-wrapper .button {
            margin-right: 4px;
            margin-bottom: 4px;
            font-size: 11px;
            padding: 2px 8px;
            height: auto;
            line-height: 1.4;
        }
        
        .comment-content-wrapper {
            max-width: 500px;
        }
        
        #edit-comment textarea {
            font-family: Consolas, Monaco, monospace;
            font-size: 12px;
        }
        
        .ruh-comment-manager-stats {
            border: 1px solid #ddd;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Hızlı düzenle
            $('#comments-filter').on('click', '.quick-edit-comment', function(e) {
                e.preventDefault();
                var commentId = $(this).data('comment-id');
                $('#comment-text-' + commentId).hide();
                $('#edit-comment-' + commentId).show();
            });

            $('#comments-filter').on('click', '.cancel-edit-comment', function() {
                var wrapper = $(this).closest('[id^="edit-comment-"]');
                var commentId = wrapper.attr('id').replace('edit-comment-', '');
                wrapper.hide();
                $('#comment-text-' + commentId).show();
            });

            $('#comments-filter').on('click', '.save-edit-comment', function() {
                var button = $(this);
                var commentId = button.data('comment-id');
                var newContent = button.siblings('textarea').val();
                
                button.prop('disabled', true).text('Kaydediliyor...');

                $.post(ajaxurl, {
                    action: 'ruh_admin_edit_comment',
                    _ajax_nonce: '<?php echo wp_create_nonce("ruh_admin_edit_comment"); ?>',
                    comment_id: commentId,
                    content: newContent
                }).done(function(response) {
                    if (response.success) {
                        $('#comment-text-' + commentId).html(response.data.content).show();
                        $('#edit-comment-' + commentId).hide();
                        
                        // Başarı mesajı göster
                        $('<div class="notice notice-success is-dismissible"><p>Yorum başarıyla güncellendi.</p></div>')
                            .insertAfter('.wp-header-end')
                            .delay(3000)
                            .fadeOut();
                    } else {
                        alert('Hata: ' + (response.data.message || 'Bilinmeyen hata'));
                    }
                }).fail(function() {
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                }).always(function() {
                    button.prop('disabled', false).text('Kaydet');
                });
            });
        });
        </script>
    </div>
    <?php
}