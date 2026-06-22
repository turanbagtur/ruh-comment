<?php
if (!defined('ABSPATH')) exit;

add_filter('preprocess_comment', 'ruh_comment_checks');
function ruh_comment_checks($commentdata) {
    // 1. Kullanıcı engelli mi veya zaman aşımı var mı kontrol et
    if (isset($commentdata['user_id']) && $commentdata['user_id']) {
        $user_id = intval($commentdata['user_id']);
        $ban_status = get_user_meta($user_id, 'ruh_ban_status', true);
        if ($ban_status === 'banned') {
            wp_die('Bu siteden kalıcı olarak engellendiniz.', 'Engellendi', array('response' => 403));
        }
        $timeout_until = get_user_meta($user_id, 'ruh_timeout_until', true);
        if ($timeout_until && current_time('timestamp') < intval($timeout_until)) {
            $remaining = (function_exists('ruh_human_time_diff_tr') ? ruh_human_time_diff_tr(intval($timeout_until), current_time('timestamp')) : human_time_diff(intval($timeout_until), current_time('timestamp')));
            wp_die(sprintf('Yorum gönderme yasağınızın bitmesine %s kaldı.', $remaining), 'Geçici Engel', array('response' => 403));
        }
    }

    // 2. Honeypot Spam Korumasi
    if (isset($_POST['ruh_honeypot']) && !empty($_POST['ruh_honeypot'])) {
        wp_die('Spam tespit edildi.', 'Spam', array('response' => 403));
    }
    
    // 2.5. IP Ban Kontrolü
    $user_ip = ruh_get_user_ip();
    $ip_bans = get_option('ruh_banned_ips', array());
    if (isset($ip_bans[$user_ip])) {
        wp_die('IP adresiniz engellenmiştir. Yorum gönderemezsiniz.', 'IP Engeli', array('response' => 403));
    }

    // 3. Link Sayisi Limiti - REGEX DUZELTILDI
    $options = get_option('ruh_comment_options', array());
    $link_limit = isset($options['spam_link_limit']) ? intval($options['spam_link_limit']) : 2;
    if ($link_limit > 0) {
        // Duzeltilmis regex - escape karakterleri duzgun
        $link_count = preg_match_all('/<a\s|https?:\/\//i', $commentdata['comment_content'], $matches);
        if ($link_count === false) {
            error_log('RUH Comment: preg_match_all hatasi - link kontrolu');
            $link_count = 0;
        }
        if ($link_count > $link_limit) {
            wp_die('Yorumunuzda çok fazla link var. Lütfen link sayısını azaltın.', 'Link Limiti', array('response' => 400));
        }
    }

    // 4. Rate Limiting - Sadece standart WordPress yorum formu için (AJAX istekler kendi handler'larında kontrol ediliyor)
    if (!defined('RUH_AJAX_RATE_CHECKED') && !defined('DOING_AJAX')) {
        if (isset($commentdata['user_id']) && $commentdata['user_id']) {
            $user_id = intval($commentdata['user_id']);
            $last_comment_time = get_user_meta($user_id, '_ruh_last_comment_time', true);
            $min_interval = 30; // 30 saniye
            
            if ($last_comment_time && (time() - intval($last_comment_time)) < $min_interval) {
                $wait_time = $min_interval - (time() - intval($last_comment_time));
                wp_die(sprintf('Çok hızlı yorum gönderiyorsunuz. %d saniye bekleyip tekrar deneyin.', $wait_time), 'Rate Limit', array('response' => 429));
            }
        }

        // IP tabanlı rate limiting
        $user_ip = ruh_get_user_ip();
        $ip_key = 'ruh_ip_' . md5($user_ip);
        $last_ip_time = get_transient($ip_key);
        $ip_min_interval = 15; // 15 saniye
        
        if ($last_ip_time && (time() - intval($last_ip_time)) < $ip_min_interval) {
            $wait_time = $ip_min_interval - (time() - intval($last_ip_time));
            wp_die(sprintf('Bu IP adresinden çok hızlı yorum gönderiliyor. %d saniye bekleyin.', $wait_time), 'Rate Limit', array('response' => 429));
        }
        
        set_transient($ip_key, time(), 300); // 5 dakika tutulacak
    }

    // 6. Kufur filtresi (regex destekli)
    $profanity_words = isset($options['profanity_filter_words']) ? $options['profanity_filter_words'] : '';
    if (!empty($profanity_words)) {
        $banned_words = array_map('trim', explode(',', $profanity_words));
        $banned_words = array_filter($banned_words);
        $comment_content = $commentdata['comment_content'];
        
        foreach ($banned_words as $word) {
            if (empty($word)) continue;
            
            // Regex pattern kontrolu (/ ile baslayip / ile bitiyorsa regex)
            if (preg_match('/^\/(.*)\/([gimsu]*)$/u', $word, $matches)) {
                // Regex pattern - flag yoksa iu ekle
                $pattern = '/' . $matches[1] . '/' . (empty($matches[2]) ? 'iu' : $matches[2]);
                if (@preg_match($pattern, $comment_content)) {
                    wp_die('Yorumunuzda uygunsuz içerik tespit edildi.', 'Uygunsuz İçerik', array('response' => 400));
                }
            } else {
                // Normal kelime - case insensitive arama
                if (mb_stripos($comment_content, $word) !== false) {
                    wp_die('Yorumunuzda uygunsuz içerik tespit edildi.', 'Uygunsuz İçerik', array('response' => 400));
                }
            }
        }
    }

    // 7. Cok kisa yorum kontrolu
    $min_length = 3;
    $clean_content = trim(strip_tags($commentdata['comment_content']));
    if (mb_strlen($clean_content) < $min_length) {
        wp_die(sprintf('Yorum en az %d karakter olmalıdır.', $min_length), 'Çok Kısa', array('response' => 400));
    }

    // 8. Cok uzun yorum kontrolu
    $max_length = isset($options['max_comment_length']) ? intval($options['max_comment_length']) : 1000;
    if (mb_strlen($commentdata['comment_content']) > $max_length) {
        wp_die(sprintf('Yorum maksimum %d karakter olabilir.', $max_length), 'Çok Uzun', array('response' => 400));
    }

    // 9. Ayni icerikli yorum kontrolu (duplicate check) - PERFORMANS IYILESTIRILDI
    if (isset($commentdata['user_id']) && $commentdata['user_id']) {
        $content_hash = md5($commentdata['comment_content']);
        $cache_key = 'ruh_dup_' . $commentdata['user_id'] . '_' . $commentdata['comment_post_ID'];
        $last_hash = get_transient($cache_key);
        
        if ($last_hash === $content_hash) {
            wp_die('Bu yorumu daha önce yapmışsınız.', 'Tekrar Yorum', array('response' => 400));
        }
        
        // 5 dakika icinde ayni yorumu engelle
        set_transient($cache_key, $content_hash, 300);
    }
    
    // 10. Yorum içeriğini temizle - GÜVENLİK İYİLEŞTİRİLMİŞ
    $allowed_html = array(
        'b' => array(), 
        'i' => array(), 
        'strong' => array(), 
        'em' => array(),
        'br' => array(), 
        'p' => array(),
        'a' => array(
            'href' => array(),
            'title' => array()
        ),
        'blockquote' => array('cite' => array()),
        'code' => array('class' => array()),
        'pre' => array('class' => array()),
        'span' => array('class' => array())
    );
    
    $commentdata['comment_content'] = wp_kses($commentdata['comment_content'], $allowed_html);
    
    // URL'leri guvenli hale getir - nofollow ve noopener ekle
    $commentdata['comment_content'] = preg_replace_callback(
        '/<a\s+([^>]*)href=["\']([^"\']+)["\']([^>]*)>/i',
        function($matches) {
            $url = esc_url($matches[2]);
            // Sadece http/https URL'lere izin ver
            if (!preg_match('/^https?:\/\//i', $url)) {
                return '';
            }
            return '<a href="' . $url . '" rel="nofollow noopener noreferrer" target="_blank">';
        },
        $commentdata['comment_content']
    );

    return $commentdata;
}

// Kullanıcı IP adresini güvenli şekilde al
function ruh_get_user_ip() {
    $ip_keys = array(
        'HTTP_CF_CONNECTING_IP',  // Cloudflare
        'HTTP_X_FORWARDED_FOR',   // Proxy
        'HTTP_X_REAL_IP',         // Nginx
        'REMOTE_ADDR'             // Standart
    );
    
    foreach ($ip_keys as $key) {
        if (isset($_SERVER[$key]) && !empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                // Gecerli IP mi ve private degilse
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
    }
    
    // Fallback - private IP'leri de kabul et (localhost icin)
    if (isset($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
        return $_SERVER['REMOTE_ADDR'];
    }
    
    return '0.0.0.0';
}

// Kufur filtresi (regex destekli)
add_filter('comment_text', 'ruh_profanity_filter', 1);
function ruh_profanity_filter($text) {
    $options = get_option('ruh_comment_options', array());
    $banned_words_str = isset($options['profanity_filter_words']) ? $options['profanity_filter_words'] : '';
    if (!empty($banned_words_str)) {
        $banned_words = array_map('trim', explode(',', $banned_words_str));
        $banned_words = array_filter($banned_words);
        
        foreach ($banned_words as $word) {
            if (empty($word)) continue;
            
            // Regex pattern kontrolu (/ ile baslayip / ile bitiyorsa regex)
            if (preg_match('/^\/(.*)\/([gimsu]*)$/u', $word, $matches)) {
                // Regex pattern - flag yoksa iu ekle
                $pattern = '/' . $matches[1] . '/' . (empty($matches[2]) ? 'iu' : $matches[2]);
                $text = @preg_replace($pattern, '***', $text);
            } else {
                // Normal kelime - case insensitive degistirme
                $text = str_ireplace($word, '***', $text);
            }
        }
    }
    return $text;
}

// Spoiler etiketlerini donustur
add_filter('comment_text', 'ruh_convert_spoiler_tags', 11);
function ruh_convert_spoiler_tags($text) {
    $text = preg_replace(
        '/\[spoiler\](.*?)\[\/spoiler\]/s',
        '<div class="ruh-spoiler"><div class="spoiler-header">Spoiler (Göstermek için tıkla)</div><div class="spoiler-content">$1</div></div>',
        $text
    );
    return $text;
}

// Yorum gönderildikten sonra işlemler
add_action('wp_insert_comment', 'ruh_handle_post_comment_actions', 10, 2);
function ruh_handle_post_comment_actions($comment_id, $comment) {
    // Son yorum zamanını güncelle
    if ($comment->user_id) {
        update_user_meta($comment->user_id, '_ruh_last_comment_time', time());
    }
    
    if ($comment->comment_approved == 1) {
        $user_id = intval($comment->user_id);
        if ($user_id > 0) {
            // XP güncelle
            if (function_exists('ruh_update_user_xp_and_level')) {
                ruh_update_user_xp_and_level($user_id);
            }
            // Rozetleri kontrol et
            if (function_exists('ruh_check_and_assign_auto_badges')) {
                ruh_check_and_assign_auto_badges($user_id);
            }
            // Cache temizle
            if (function_exists('ruh_clear_user_cache')) {
                ruh_clear_user_cache($user_id);
            }
        }
    }
}

// E-posta bildirimlerini devre disi birak (biz kendi sistemimizi kullanıyoruz)
add_filter('wp_new_comment_notify_moderators', '__return_false');
add_filter('wp_new_comment_notify_postauthor', '__return_false');

// Yanıt bildirimleri - sadece wp_insert_comment hook'u kullan.
// comment_approved_comment + wp_insert_comment birlikte kullanmak çifte bildirime yol açar.
add_action('wp_insert_comment', 'ruh_send_reply_notification', 10, 1);

function ruh_send_reply_notification($comment_id) {
    $comment = get_comment($comment_id);
    if (!$comment) return;
    
    // Yanıt değilse bildirim gönderme
    if (!$comment->comment_parent) return;
    
    // Onaylanmamışsa gönderme
    if ($comment->comment_approved != '1') return;
    
    $parent_comment = get_comment($comment->comment_parent);
    if (!$parent_comment) return;
    
    // Kendi kendine yanıt vermişse bildirim gönderme
    if ($parent_comment->user_id && $parent_comment->user_id == $comment->user_id) return;
    
    // Üst yorum sahibi kayıtlı kullanıcı değilse e-posta ile bildir
    $options = get_option('ruh_comment_options', array());
    $enable_notifications = $options['enable_reply_notifications'] ?? true;
    if (!$enable_notifications) return;
    
    // E-posta adresi var mı?
    $recipient_email = $parent_comment->comment_author_email;
    if (empty($recipient_email) || !is_email($recipient_email)) return;
    
    // Kullanıcı bildirim tercihini kontrol et
    if ($parent_comment->user_id) {
        $notify_pref = get_user_meta($parent_comment->user_id, 'ruh_notify_replies', true);
        // Varsayılan: bildirim açık (false veya '' ise açık, 'no' ise kapalı)
        if ($notify_pref === 'no') return;
    }
    
    $post = get_post($comment->comment_post_ID);
    $post_title = $post ? $post->post_title : 'bir gönderi';
    $author_name = $comment->comment_author ?: 'Biri';
    $comment_excerpt = wp_trim_words(wp_strip_all_tags($comment->comment_content), 20);
    $comment_url = get_comment_link($comment->comment_ID);
    $site_name = get_bloginfo('name');
    
    $lang = $options['language'] ?? 'tr_TR';
    if ($lang === 'en_US') {
        $subject = sprintf('[%s] Someone replied to your comment', $site_name);
        $message = sprintf(
            "Hello %s,\n\n%s replied to your comment on \"%s\":\n\n\"%s\"\n\nView the reply: %s\n\n---\nYou are receiving this email because you commented on %s.\nTo unsubscribe, go to your profile settings.",
            $parent_comment->comment_author,
            $author_name,
            $post_title,
            $comment_excerpt,
            $comment_url,
            $site_name
        );
    } else {
        $subject = sprintf('[%s] Yorumunuza yanıt geldi', $site_name);
        $message = sprintf(
            "Merhaba %s,\n\n%s, \"%s\" gönderisindeki yorumunuza yanıt verdi:\n\n\"%s\"\n\nYanıtı görüntüle: %s\n\n---\n%s sitesine yorum yaptığınız için bu e-postayı alıyorsunuz.\nBildirimlerden çıkmak için profil ayarlarınıza gidin.",
            $parent_comment->comment_author,
            $author_name,
            $post_title,
            $comment_excerpt,
            $comment_url,
            $site_name
        );
    }
    
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'From: ' . $site_name . ' <' . get_option('admin_email') . '>'
    );
    
    wp_mail($recipient_email, $subject, $message, $headers);
}

// Kullanıcı profil sayfalarına rozet ekleme bölümleri
add_action('edit_user_profile', 'ruh_add_badges_to_user_profile');
add_action('show_user_profile', 'ruh_add_badges_to_user_profile');
function ruh_add_badges_to_user_profile($user) {
    if (!current_user_can('edit_user', $user->ID)) return;
    
    global $wpdb;
    $manual_badges = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}ruh_badges WHERE is_automated = 0");
    $user_badges = array_map('intval', $wpdb->get_col($wpdb->prepare("SELECT badge_id FROM {$wpdb->prefix}ruh_user_badges WHERE user_id = %d", $user->ID)));
    ?>
    <h3>Ruh Comment Rozetleri</h3>
    <table class="form-table">
        <tr>
            <th><label for="ruh_user_badges">Manuel Rozetler</label></th>
            <td>
                <?php if (empty($manual_badges)) : ?>
                    <p>Henüz manuel olarak atanabilecek bir rozet oluşturulmamış.</p>
                <?php else: foreach ($manual_badges as $badge) : ?>
                    <label style="margin-right: 15px; display: inline-block; margin-bottom: 10px;">
                        <input type="checkbox" name="ruh_user_badges[]" value="<?php echo intval($badge->badge_id); ?>" <?php checked(in_array($badge->badge_id, $user_badges)); ?>>
                        <span style="display: inline-flex; align-items: center; gap: 5px;"><?php echo wp_kses_post($badge->badge_svg); ?> <?php echo esc_html($badge->badge_name); ?></span>
                    </label><br>
                <?php endforeach; endif; ?>
            </td>
        </tr>
        <tr>
            <th><label>Kullanıcı Durumu</label></th>
            <td>
                <?php 
                $ban_status = get_user_meta($user->ID, 'ruh_ban_status', true);
                $timeout_until = get_user_meta($user->ID, 'ruh_timeout_until', true);
                ?>
                <fieldset>
                    <legend class="screen-reader-text">Kullanıcı Durumu</legend>
                    <label>
                        <input type="radio" name="ruh_user_status" value="active" <?php checked($ban_status !== 'banned' && (!$timeout_until || $timeout_until < time())); ?>>
                        Aktif
                    </label><br>
                    <label>
                        <input type="radio" name="ruh_user_status" value="timeout" <?php checked($timeout_until && $timeout_until > time()); ?>>
                        24 Saat Susturulmuş
                    </label><br>
                    <label>
                        <input type="radio" name="ruh_user_status" value="banned" <?php checked($ban_status === 'banned'); ?>>
                        Kalıcı Engellenmiş
                    </label>
                </fieldset>
            </td>
        </tr>
    </table>
    <?php
}

// Kullanıcı rozetlerini ve durumunu kaydet
add_action('edit_user_profile_update', 'ruh_save_user_badges_and_status');
add_action('personal_options_update', 'ruh_save_user_badges_and_status');
function ruh_save_user_badges_and_status($user_id) {
    if (!current_user_can('edit_user', $user_id)) return;
    
    // Nonce kontrolu
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-user_' . $user_id)) {
        return;
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'ruh_user_badges';
    
    // Önce kullanıcının tüm manuel rozetlerini sil
    $manual_badge_ids = $wpdb->get_col("SELECT badge_id FROM {$wpdb->prefix}ruh_badges WHERE is_automated = 0");
    if (!empty($manual_badge_ids)) {
        $placeholders = implode(',', array_fill(0, count($manual_badge_ids), '%d'));
        $sql = $wpdb->prepare(
            "DELETE FROM $table WHERE user_id = %d AND badge_id IN ($placeholders)",
            array_merge(array($user_id), array_map('intval', $manual_badge_ids))
        );
        $wpdb->query($sql);
    }

    // Sonra secilenleri tekrar ekle
    if (!empty($_POST['ruh_user_badges']) && is_array($_POST['ruh_user_badges'])) {
        foreach ($_POST['ruh_user_badges'] as $badge_id) {
            $badge_id = intval($badge_id);
            if ($badge_id > 0 && in_array($badge_id, $manual_badge_ids)) {
                $wpdb->insert($table, array(
                    'user_id' => $user_id,
                    'badge_id' => $badge_id
                ), array('%d', '%d'));
            }
        }
    }
    
    // Kullanıcı durumunu kaydet
    if (isset($_POST['ruh_user_status'])) {
        $status = sanitize_key($_POST['ruh_user_status']);
        
        // Once eski durumlari temizle
        delete_user_meta($user_id, 'ruh_ban_status');
        delete_user_meta($user_id, 'ruh_timeout_until');
        
        switch ($status) {
            case 'banned':
                update_user_meta($user_id, 'ruh_ban_status', 'banned');
                break;
            case 'timeout':
                update_user_meta($user_id, 'ruh_timeout_until', time() + 86400); // 24 saat
                break;
            // 'active' icin herhangi bir meta eklemiyoruz
        }
    }
    
    // Cache temizle
    if (function_exists('ruh_clear_user_cache')) {
        ruh_clear_user_cache($user_id);
    }
}

// Admin panelinde spam korumasi ayarlari
add_action('admin_init', 'ruh_register_spam_protection_settings');
function ruh_register_spam_protection_settings() {
    add_settings_section(
        'ruh_spam_section',
        'Spam Korumasi',
        null,
        'ruh_comment_options'
    );
    
    add_settings_field(
        'profanity_filter_words',
        'Küfür Filtresi (virgülle ayırın)',
        'ruh_render_profanity_field',
        'ruh_comment_options',
        'ruh_spam_section'
    );
    
    add_settings_field(
        'spam_link_limit',
        'Maksimum Link Sayısı',
        'ruh_render_link_limit_field',
        'ruh_comment_options',
        'ruh_spam_section'
    );
    
    add_settings_field(
        'auto_moderate_reports',
        'Otomatik Moderasyon Şikayet Limiti',
        'ruh_render_auto_moderate_field',
        'ruh_comment_options',
        'ruh_spam_section'
    );
}

function ruh_render_profanity_field() {
    $options = get_option('ruh_comment_options', array());
    $value = isset($options['profanity_filter_words']) ? $options['profanity_filter_words'] : '';
    echo '<textarea name="ruh_comment_options[profanity_filter_words]" rows="5" cols="50" class="large-text">' . esc_textarea($value) . '</textarea>';
    echo '<p class="description">Yasaklı kelimeleri virgülle ayırarak yazın.<br>';
    echo '<strong>Normal:</strong> kelime1, kelime2, kelime3<br>';
    echo '<strong>Regex:</strong> /pattern/, /k[e3]l[i1]m[e3]/, /ba?d\s*word/<br>';
    echo '<em>Regex pattern\'leri / ile başlamalı ve / ile bitmeli.</em></p>';
}

function ruh_render_link_limit_field() {
    $options = get_option('ruh_comment_options', array());
    $value = isset($options['spam_link_limit']) ? intval($options['spam_link_limit']) : 2;
    echo '<input type="number" name="ruh_comment_options[spam_link_limit]" value="' . esc_attr($value) . '" class="small-text" min="0" max="10" />';
    echo '<p class="description">Bir yorumda izin verilen maksimum link sayısı (0 = sınırsız)</p>';
}

function ruh_render_auto_moderate_field() {
    $options = get_option('ruh_comment_options', array());
    $value = isset($options['auto_moderate_reports']) ? intval($options['auto_moderate_reports']) : 3;
    echo '<input type="number" name="ruh_comment_options[auto_moderate_reports]" value="' . esc_attr($value) . '" class="small-text" min="1" max="20" />';
    echo '<p class="description">Kaç şikayet aldığında yorum otomatik olarak moderasyona alınır</p>';
}

// Tüm yorumları otomatik onayla (sadece giriş yapmış kullanıcılar için)
add_filter('pre_comment_approved', 'ruh_auto_approve_comments', 10, 2);
function ruh_auto_approve_comments($approved, $commentdata) {
    // Sadece giriş yapmış kullanıcılar için otomatik onay
    if (isset($commentdata['user_id']) && $commentdata['user_id'] > 0) {
        return 1; // Onayla
    }
    
    // Diger durumlarda WordPress'in kendi kararini ver
    return $approved;
}

// Yorum durum degisikligini yakala
add_action('wp_set_comment_status', 'ruh_comment_status_changed', 10, 2);
function ruh_comment_status_changed($comment_id, $status) {
    if ($status === 'approve') {
        $comment = get_comment($comment_id);
        if ($comment && $comment->user_id) {
            $user_id = intval($comment->user_id);
            
            // XP ver
            if (function_exists('ruh_update_user_xp_and_level')) {
                ruh_update_user_xp_and_level($user_id);
            }
            
            // Rozetleri kontrol et
            if (function_exists('ruh_check_and_assign_auto_badges')) {
                ruh_check_and_assign_auto_badges($user_id);
            }
            
            // Cache temizle
            if (function_exists('ruh_clear_user_cache')) {
                ruh_clear_user_cache($user_id);
            }
        }
    }
}

// Yorum silindiginde cache temizle
add_action('delete_comment', 'ruh_on_comment_delete', 10, 2);
function ruh_on_comment_delete($comment_id, $comment) {
    if ($comment && $comment->user_id) {
        if (function_exists('ruh_clear_user_cache')) {
            ruh_clear_user_cache($comment->user_id);
        }
    }
}

/**
 * Global Avatar Filtresi
 * WordPress'in standart get_avatar fonksiyonunu filtreleyerek özel avatarları gösterir.
 */
add_filter('get_avatar', 'ruh_filter_get_avatar', 10, 6);
function ruh_filter_get_avatar($avatar, $id_or_email, $size, $default, $alt, $args) {
    // Döngüye girmeyi önlemek için kontrol
    if (isset($args['ruh_skip_filter']) && $args['ruh_skip_filter']) {
        return $avatar;
    }

    $user_id = 0;

    if (is_numeric($id_or_email)) {
        $user_id = (int)$id_or_email;
    } elseif (is_string($id_or_email) && is_email($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
        if ($user) {
            $user_id = $user->ID;
        }
    } elseif (is_object($id_or_email) && isset($id_or_email->comment_ID)) {
        $user_id = (int)$id_or_email->user_id;
    }

    if ($user_id > 0) {
        // Özel avatar fonksiyonumuzu çağır, ancak filter argümanını ekle ki sonsuz döngü olmasın
        if (function_exists('ruh_get_avatar')) {
            // ruh_get_avatar içinde tekrar get_avatar çağrılıyor, bu yüzden sonsuz döngüye dikkat
            // ruh_get_avatar mantığını burada tekrar uyguluyoruz veya ruh_get_avatar'ı güncelleyip args ile filter bypass eklememiz lazım.
            // En temizi: Doğrudan meta kontrolü yapmak.
            
            $custom_avatar_url = get_user_meta($user_id, 'ruh_custom_avatar_url', true);
            if ($custom_avatar_url) {
                $class = array('avatar', 'avatar-' . (int)$size, 'photo');
                if (isset($args['class'])) {
                    if (is_array($args['class'])) {
                        $class = array_merge($class, $args['class']);
                    } else {
                        $class[] = $args['class'];
                    }
                }
                return '<img src="' . esc_url($custom_avatar_url) . '" class="' . esc_attr(implode(' ', $class)) . '" width="' . (int)$size . '" height="' . (int)$size . '" alt="' . esc_attr($alt) . '" />';
            }
            
            // Legacy check
            $custom_avatar = get_user_meta($user_id, 'ruh_custom_avatar', true);
            if ($custom_avatar && file_exists($custom_avatar)) {
                $avatar_url = wp_upload_dir()['baseurl'] . '/' . basename($custom_avatar);
                $class = array('avatar', 'avatar-' . (int)$size, 'photo');
                if (isset($args['class'])) {
                    if (is_array($args['class'])) {
                        $class = array_merge($class, $args['class']);
                    } else {
                        $class[] = $args['class'];
                    }
                }
                return '<img src="' . esc_url($avatar_url) . '" class="' . esc_attr(implode(' ', $class)) . '" width="' . (int)$size . '" height="' . (int)$size . '" alt="' . esc_attr($alt) . '" />';
            }
        }
    }

    return $avatar;
}

/**
 * Global Avatar URL Filtresi
 */
add_filter('get_avatar_url', 'ruh_filter_get_avatar_url', 10, 3);
function ruh_filter_get_avatar_url($url, $id_or_email, $args) {
    $user_id = 0;

    if (is_numeric($id_or_email)) {
        $user_id = (int)$id_or_email;
    } elseif (is_string($id_or_email) && is_email($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
        if ($user) {
            $user_id = $user->ID;
        }
    } elseif (is_object($id_or_email) && isset($id_or_email->user_id)) {
        $user_id = (int)$id_or_email->user_id;
    }

    if ($user_id > 0) {
        $custom_avatar_url = get_user_meta($user_id, 'ruh_custom_avatar_url', true);
        if ($custom_avatar_url) {
            return $custom_avatar_url;
        }
        
        $custom_avatar = get_user_meta($user_id, 'ruh_custom_avatar', true);
        if ($custom_avatar && file_exists($custom_avatar)) {
            return wp_upload_dir()['baseurl'] . '/' . basename($custom_avatar);
        }
    }

    return $url;
}
