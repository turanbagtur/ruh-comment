<?php
if (!defined('ABSPATH')) exit;

/**
 * Yorum içeriğini render eder - GIF markdown'ını HTML'ye çevirir
 * 
 * @since 5.0
 * @param string $content Yorum içeriği
 * @return string Render edilmiş HTML içerik
 */
function ruh_render_comment_content($content) {
    if (empty($content)) return '';
    
    // GIF markdown'ını HTML'ye çevir: ![GIF](url) -> <div class="gif-container"><img src="url" alt="GIF" loading="lazy"></div>
    $content = preg_replace_callback(
        '/!\[GIF\]\((https?:\/\/[^\)]+)\)/',
        function($matches) {
            $gif_url = esc_url($matches[1]);
            return '<div class="gif-container"><img src="' . $gif_url . '" alt="GIF" loading="lazy" class="comment-gif"></div>';
        },
        $content
    );
    
    // Spoiler desteği: [spoiler]content[/spoiler] - Siyah kutu stili
    $content = preg_replace_callback(
        '/\[spoiler\](.*?)\[\/spoiler\]/s',
        function($matches) {
            $spoiler_content = trim($matches[1]);
            return '<span class="ruh-spoiler"><span class="spoiler-content" onclick="this.classList.toggle(\'revealed\')">' . $spoiler_content . '</span></span>';
        },
        $content
    );
    
    // WordPress'in kendi text formatlamasını uygula (br, p tagları vs.)
    $content = wpautop($content);
    
    return $content;
}

/**
 * Türkçe zaman farkı hesaplama - human_time_diff'in Türkçe versiyonu
 *
 * @since 5.0
 * @param int $from Unix timestamp
 * @param int $to Unix timestamp
 * @return string Türkçe zaman farkı
 */
function ruh_human_time_diff_tr($from, $to = null) {
    if (empty($to)) {
        $to = time();
    }
    
    $diff = (int) abs($to - $from);
    
    if ($diff < 60) {
        return $diff == 1 ? '1 saniye' : $diff . ' saniye';
    }
    
    $mins = round($diff / 60);
    if ($mins < 60) {
        return $mins == 1 ? '1 dakika' : $mins . ' dakika';
    }
    
    $hours = round($diff / 3600);
    if ($hours < 24) {
        return $hours == 1 ? '1 saat' : $hours . ' saat';
    }
    
    $days = round($diff / 86400);
    if ($days < 30) {
        return $days == 1 ? '1 gün' : $days . ' gün';
    }
    
    $months = round($diff / 2635200); // 30.5 gün ortalama
    if ($months < 12) {
        return $months == 1 ? '1 ay' : $months . ' ay';
    }
    
    $years = round($diff / 31536000);
    return $years == 1 ? '1 yıl' : $years . ' yıl';
}

/**
 * Dinamik post ID belirleme - Manga sistemi uyumlu
 * 
 * @since 5.0
 * @return int Post ID veya dinamik hash ID
 */
function ruh_get_dynamic_post_id() {
    try {
        global $wp_query, $post;
        
        // Önce normal WordPress post ID'yi al - güvenli kontrol
        $normal_post_id = 0;
        if (function_exists('get_the_ID')) {
            $normal_post_id = get_the_ID();
        }
        
        if (!$normal_post_id && isset($post) && is_object($post) && property_exists($post, 'ID')) {
            $normal_post_id = intval($post->ID);
        }
        
        // URL tabanli sistem - guvenli hata yakalama ile
        $current_url = ruh_get_current_page_url();
        if (!empty($current_url)) {
            $dynamic_id = ruh_get_dynamic_post_id_from_url($current_url);
            if ($dynamic_id > 0) {
                return $dynamic_id;
            }
        }
        
        // Normal WordPress post ID varsa ve geçerliyse kullan
        if ($normal_post_id > 0) {
            // Post'un var olduğunu kontrol et
            $post_exists = get_post($normal_post_id);
            if ($post_exists && $post_exists->post_status === 'publish') {
                return $normal_post_id;
            }
        }
        
        // Son çare: 0 döndür
        return 0;
        
    } catch (Exception $e) {
        error_log('[Ruh Comments] Dynamic post ID error: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Mevcut sayfa URL'sini al
 */
function ruh_get_current_page_url() {
    try {
        // Güvenlik kontrolleri
        if (!isset($_SERVER['HTTP_HOST']) || !isset($_SERVER['REQUEST_URI'])) {
            return '';
        }
        
        $host = sanitize_text_field($_SERVER['HTTP_HOST']);
        $uri = sanitize_text_field($_SERVER['REQUEST_URI']);
        
        // Host validation
        if (empty($host) || !preg_match('/^[a-zA-Z0-9.-]+$/', $host)) {
            return '';
        }
        
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $url = $protocol . $host . $uri;
        
        // Final URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }
        
        return $url;
        
    } catch (Exception $e) {
        error_log('[Ruh Comments] Current page URL error: ' . $e->getMessage());
        return '';
    }
}

/**
 * URL'den dinamik post ID belirle - AJAX icin
 */
function ruh_get_dynamic_post_id_from_url($url) {
    try {
        if (empty($url) || !is_string($url)) {
            return 0;
        }
        
        // URL gecerlilik kontrolu
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return 0;
        }
        
        $url_path = parse_url($url, PHP_URL_PATH);
        if (!$url_path) {
            return 0;
        }
        
        // URL'yi normalize et - trailing slash kaldir
        $url_path = rtrim($url_path, '/');
        
        // Manga chapter pattern - alt bolumler dahil (chapter-29, chapter-29-5, bolum-10 vb.)
        if (preg_match('/\/manga\/([a-zA-Z0-9\-_]+)\/(?:chapter|bolum|ch)[_-]?([0-9]+(?:[._-][0-9]+)*)/i', $url_path, $matches)) {
            $manga_slug = sanitize_title($matches[1]);
            $chapter_full = preg_replace('/[^0-9.-]/', '', $matches[2]); // Sadece rakam ve nokta/tire
            
            // Benzersiz ID olustur - manga slug + chapter numarasi
            if (!empty($manga_slug) && !empty($chapter_full)) {
                $unique_key = $manga_slug . '_chapter_' . $chapter_full;
                return abs(crc32($unique_key)) % 2000000000 + 1000;
            }
        }
        
        // Manga ana sayfa pattern - sadece /manga/slug/ formati
        if (preg_match('/\/manga\/([a-zA-Z0-9\-_]+)$/i', $url_path, $matches)) {
            $manga_slug = sanitize_title($matches[1]);
            
            if (!empty($manga_slug)) {
                $unique_key = $manga_slug . '_main_series';
                return abs(crc32($unique_key)) % 2000000000 + 2000;
            }
        }
        
        // Diger dinamik sayfalar icin URL hash'i kullan
        return 0;
        
    } catch (Exception $e) {
        error_log('[Ruh Comments] Dynamic post ID from URL error: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Yorum HTML'ini oluşturan ana callback fonksiyonu - TÜM ÖZELLİKLER İLE
 */
function ruh_comment_format($comment, $args, $depth) {
    if (!$comment) return '';
    
    $GLOBALS['comment'] = $comment;
    $options = get_option('ruh_comment_options', array());
    $profile_url = ruh_get_user_profile_url($comment->user_id);
    $max_depth = isset($args['max_depth']) ? $args['max_depth'] : 5;
    $user_vote = '';
    
    if (is_user_logged_in()) {
        $user_vote = get_comment_meta($comment->comment_ID, '_user_vote_' . get_current_user_id(), true);
    }
    ?>
    <li <?php comment_class('ruh-comment-item') ?> id="comment-<?php comment_ID() ?>" data-comment-id="<?php comment_ID() ?>">
        <div class="comment-body" id="div-comment-<?php comment_ID() ?>">
            <div class="comment-author vcard">
                <a href="<?php echo esc_url($profile_url); ?>" class="avatar-link" target="_blank">
                    <?php echo ruh_get_avatar($comment->user_id ?: $comment->comment_author_email, 55); ?>
                </a>
            </div>
            <div class="comment-content">
                <div class="comment-author-meta">
                    <a class="fn author-name" href="<?php echo esc_url($profile_url); ?>" target="_blank">
                        <?php echo get_comment_author(); ?>
                    </a>
                    <?php 
                    // Kullanıcı seviye, rozet ve tag'lerini göster
                    if ($comment->user_id) {
                        echo ruh_get_user_tag($comment->user_id); // Kullanıcı tag (Editör, Çevirmen vb.)
                        echo ruh_get_user_level_badge($comment->user_id);
                        echo ruh_get_user_custom_badges($comment->user_id);
                        
                        // Kullanıcı durumu kontrolü
                        $ban_status = get_user_meta($comment->user_id, 'ruh_ban_status', true);
                        $timeout_until = get_user_meta($comment->user_id, 'ruh_timeout_until', true);
                        
                        if ($ban_status === 'banned') {
                            echo '<span class="user-status banned" title="Engellenmiş kullanıcı">🚫</span>';
                        } elseif ($timeout_until && current_time('timestamp') < $timeout_until) {
                            echo '<span class="user-status timeout" title="Susturulmuş kullanıcı">⏰</span>';
                        }
                    }
                    ?>
                    <span class="comment-metadata">
                        <a href="<?php echo esc_url(ruh_get_comment_link($comment)); ?>" class="comment-time">
                            <time datetime="<?php comment_time('c'); ?>" title="<?php comment_time(); ?>">
                                <?php printf('%s önce', ruh_human_time_diff_tr(get_comment_time('U'), current_time('timestamp'))); ?>
                            </time>
                        </a>
                        <?php if ($comment->comment_approved == '0') : ?>
                            <span class="comment-awaiting-moderation">(Onay bekliyor)</span>
                        <?php endif; ?>
                    </span>
                </div>
                
                <div class="comment-text" data-comment-id="<?php comment_ID(); ?>">
                    <?php echo ruh_render_comment_content(get_comment_text()); ?>
                </div>

                <div class="comment-actions-modern">
                    <div class="comment-interaction-buttons">
                        <!-- Modern Icon'lu Beğeni Butonu -->
                        <button class="heart-like-btn <?php echo ($user_vote === 'liked') ? 'active' : ''; ?>"
                                type="button"
                                data-comment-id="<?php comment_ID(); ?>"
                                title="Beğen">
                            <svg class="heart-icon" viewBox="0 0 24 24" width="18" height="18">
                                <path fill="currentColor" d="M12,21.35L10.55,20.03C5.4,15.36 2,12.27 2,8.5 2,5.41 4.42,3 7.5,3C9.24,3 10.91,3.81 12,5.08C13.09,3.81 14.76,3 16.5,3C19.58,3 22,5.41 22,8.5C22,12.27 18.6,15.36 13.45,20.03L12,21.35Z"/>
                            </svg>
                            <span class="like-count"><?php
                                $likes = get_comment_meta($comment->comment_ID, '_likes', true) ?: 0;
                                $dislikes = get_comment_meta($comment->comment_ID, '_dislikes', true) ?: 0;
                                $total = $likes - $dislikes;
                                echo $total > 0 ? $total : 0;
                            ?></span>
                        </button>
                        
                        <?php if ($depth < $max_depth) : ?>
                            <!-- Modern Icon'lu Yanıt Butonu -->
                            <button type="button" class="reply-btn-modern" data-comment-id="<?php comment_ID(); ?>" title="Yanıtla">
                                <svg class="reply-icon" viewBox="0 0 24 24" width="18" height="18">
                                    <path fill="currentColor" d="M10,9V5L3,12L10,19V14.9C15,14.9 18.5,16.5 21,20C20,15 17,10 10,9Z"/>
                                </svg>
                                <span class="reply-text">Yanıtla</span>
                            </button>
                        <?php endif; ?>
                        
                        <?php 
                        // Yanıt sayısını al - buton için
                        $reply_count = get_comments(array(
                            'parent' => $comment->comment_ID,
                            'status' => 'approve',
                            'count' => true
                        ));
                        if ($reply_count > 0) : ?>
                            <!-- Yanıtları Göster Butonu - Yanıtla butonunun yanında -->
                            <button type="button" class="replies-toggle-btn" 
                                    data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>"
                                    data-replies-count="<?php echo esc_attr($reply_count); ?>"
                                    data-parent-id="<?php echo esc_attr($comment->comment_ID); ?>"
                                    title="Yanıtları göster">
                                <svg class="toggle-icon" viewBox="0 0 24 24" width="16" height="16">
                                    <path fill="currentColor" d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z"/>
                                </svg>
                                <span class="toggle-text"><?php echo $reply_count; ?> yanıt</span>
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 3 nokta dropdown menü -->
                    <?php if (is_user_logged_in()) : ?>
                        <div class="comment-menu-dropdown">
                            <button type="button" class="menu-trigger" data-comment-id="<?php comment_ID(); ?>" title="Seçenekler">
                                <span class="dots">…</span>
                            </button>
                            <div class="dropdown-menu" id="menu-<?php comment_ID(); ?>">
                                <!-- Yorumu Görüntüle - Her zaman göster -->
                                <a href="<?php echo esc_url(ruh_get_comment_link($comment)); ?>" class="dropdown-item" target="_blank">
                                    <span class="icon">🔗</span> Yorumu Görüntüle
                                </a>
                                
                                <?php if ($comment->user_id == get_current_user_id()) : ?>
                                    <div class="dropdown-divider"></div>
                                    <button type="button" class="dropdown-item edit-comment-btn" data-comment-id="<?php comment_ID(); ?>">
                                        <span class="icon">✎</span> Düzenle
                                    </button>
                                    <button type="button" class="dropdown-item delete-comment-btn" data-comment-id="<?php comment_ID(); ?>">
                                        <span class="icon">🗑️</span> Sil
                                    </button>
                                <?php else : ?>
                                    <div class="dropdown-divider"></div>
                                    <button type="button" class="dropdown-item report-comment-btn" data-comment-id="<?php comment_ID(); ?>">
                                        <span class="icon">⚠</span> Şikayet Et
                                    </button>
                                <?php endif; ?>
                                
                                <?php if (current_user_can('moderate_comments')) : ?>
                                    <div class="dropdown-divider"></div>
                                    <a href="<?php echo admin_url('comment.php?action=editcomment&c=' . $comment->comment_ID); ?>"
                                       class="dropdown-item" target="_blank">
                                        <span class="icon">🔧</span> Admin Düzenle
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Yanıt formu konteyneri -->
        <div class="reply-form-container" id="reply-form-<?php comment_ID(); ?>" style="display: none;">
            <!-- AJAX ile yüklenecek -->
        </div>
        
        <!-- Yanıtlar Konteyneri - HER ZAMAN OLUŞTUR -->
        <ol class="children replies-container"
            id="replies-<?php echo esc_attr($comment->comment_ID); ?>"
            style="display: none;"
            data-parent-id="<?php echo esc_attr($comment->comment_ID); ?>"
            data-loaded="false">
            <!-- AJAX ile yüklenecek -->
        </ol>
    <?php
}

/**
 * Özel avatar fonksiyonu - custom avatar desteği ile
 */
function ruh_get_avatar($user_id_or_email, $size = 50, $default = '', $alt = '', $args = null) {
    // Class argumanini işle
    $class = 'avatar avatar-' . $size . ' photo';
    if (isset($args['class'])) {
        if (is_array($args['class'])) {
            $class .= ' ' . implode(' ', $args['class']);
        } else {
            $class .= ' ' . $args['class'];
        }
    }

    $user_id = 0;
    $email = '';

    if (is_numeric($user_id_or_email)) {
        $user_id = (int)$user_id_or_email;
        $user = get_userdata($user_id);
        $email = $user ? $user->user_email : '';
    } elseif (is_string($user_id_or_email)) {
        if (is_email($user_id_or_email)) {
            $email = $user_id_or_email;
            $user = get_user_by('email', $email);
            if ($user) {
                $user_id = $user->ID;
            }
        }
    } elseif (is_object($user_id_or_email) && isset($user_id_or_email->user_id)) {
        $user_id = (int)$user_id_or_email->user_id;
    }

    // Custom avatar kontrolü
    if ($user_id > 0) {
        // Önce yeni meta key'i kontrol et
        $custom_avatar_url = get_user_meta($user_id, 'ruh_custom_avatar_url', true);
        if ($custom_avatar_url) {
            return '<img src="' . esc_url($custom_avatar_url) . '" class="' . esc_attr($class) . '" width="' . $size . '" height="' . $size . '" alt="' . esc_attr($alt) . '">';
        }
        
        // Eski meta key desteği (geriye dönük uyumluluk)
        $custom_avatar = get_user_meta($user_id, 'ruh_custom_avatar', true);
        if ($custom_avatar && file_exists($custom_avatar)) {
            $avatar_url = wp_upload_dir()['baseurl'] . '/' . basename($custom_avatar);
            return '<img src="' . esc_url($avatar_url) . '" class="' . esc_attr($class) . '" width="' . $size . '" height="' . $size . '" alt="' . esc_attr($alt) . '">';
        }
    }
    
    return get_avatar($user_id_or_email, $size, $default, $alt, $args);
}

/**
 * Özel avatar URL fonksiyonu
 * 
 * @since 6.2
 * @param mixed $user_id_or_email
 * @param array $args
 * @return string Avatar URL
 */
function ruh_get_avatar_url($user_id_or_email, $args = null) {
    $user_id = 0;

    if (is_numeric($user_id_or_email)) {
        $user_id = (int)$user_id_or_email;
    } elseif (is_string($user_id_or_email) && is_email($user_id_or_email)) {
        $user = get_user_by('email', $user_id_or_email);
        if ($user) {
            $user_id = $user->ID;
        }
    }

    if ($user_id > 0) {
        // Önce yeni meta key'i kontrol et
        $custom_avatar_url = get_user_meta($user_id, 'ruh_custom_avatar_url', true);
        if ($custom_avatar_url) {
            return $custom_avatar_url;
        }
        
        // Eski meta key desteği
        $custom_avatar = get_user_meta($user_id, 'ruh_custom_avatar', true);
        if ($custom_avatar && file_exists($custom_avatar)) {
            return wp_upload_dir()['baseurl'] . '/' . basename($custom_avatar);
        }
    }
    
    return get_avatar_url($user_id_or_email, $args);
}

/**
 * Bir yorum yapıldığında kullanıcının XP'sini ve seviyesini günceller.
 * 
 * @since 5.0
 * @param int $user_id Kullanıcı ID
 * @return void
 */
function ruh_update_user_xp_and_level($user_id) {
    if (!$user_id || !is_numeric($user_id)) return;
    
    global $wpdb;
    $table = $wpdb->prefix . 'ruh_user_levels';
    $options = get_option('ruh_comment_options', array());
    $xp_per_comment = isset($options['xp_per_comment']) ? intval($options['xp_per_comment']) : 15;
    
    // Güvenli kullanıcı var mı kontrolü
    if (!get_userdata($user_id)) {
        error_log('RUH Comment: Geçersiz user_id - ' . $user_id);
        return;
    }
    
    $user_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));

    if ($user_data) {
        $new_xp = intval($user_data->xp) + $xp_per_comment;
        
        // Seviye hesaplama - overflow koruması
        $current_level = intval($user_data->level);
        $new_level = $current_level;
        
        // Seviye atlama kontrolü - maksimum 1000 seviye
        $max_iterations = 50; // Sonsuz döngü koruması
        $iteration = 0;
        
        while ($iteration < $max_iterations && $new_level < 1000) {
            $xp_for_next_level = ruh_calculate_xp_for_level($new_level + 1);
            if ($new_xp >= $xp_for_next_level) {
                $new_level++;
                $iteration++;
            } else {
                break;
            }
        }
        
        $result = $wpdb->update($table,
            array('xp' => $new_xp, 'level' => $new_level),
            array('user_id' => $user_id),
            array('%d', '%d'),
            array('%d')
        );
        
        if ($result === false) {
            error_log('RUH Comment: XP update hatası - User: ' . $user_id . ', Error: ' . $wpdb->last_error);
        }
        
        // Cache'i temizle
        ruh_clear_user_cache($user_id);
        
        // Seviye atladıysa otomatik rozetleri kontrol et
        if ($new_level > $current_level && function_exists('ruh_check_and_assign_auto_badges')) {
            ruh_check_and_assign_auto_badges($user_id);
        }
    } else {
        // ON DUPLICATE KEY UPDATE ile duplicate hatasını önle
        $result = $wpdb->query($wpdb->prepare(
            "INSERT INTO $table (user_id, xp, level) VALUES (%d, %d, %d) 
             ON DUPLICATE KEY UPDATE xp = xp + %d",
            $user_id, $xp_per_comment, 1, $xp_per_comment
        ));
        
        if ($result === false) {
            error_log('RUH Comment: XP insert hatası - User: ' . $user_id . ', Error: ' . $wpdb->last_error);
        }
        
        // Cache'i temizle
        ruh_clear_user_cache($user_id);
    }
}

/**
 * Belirli bir seviye için gereken XP miktarını hesaplar
 * 
 * @since 5.0
 * @param int $level Seviye numarası
 * @return int Gereken XP miktarı
 */
function ruh_calculate_xp_for_level($level) {
    return (int)(pow($level, 1.8) * 100);
}

/**
 * Kullanıcının seviye ve XP bilgisini veritabanından çeker - CACHE İLE
 */
function ruh_get_user_level_info($user_id) {
    if (!$user_id) return (object)array('level' => 1, 'xp' => 0);
    
    // Cache kontrolü
    $cache_key = 'ruh_user_level_' . $user_id;
    $info = wp_cache_get($cache_key, 'ruh_comment');
    
    if (false === $info) {
        global $wpdb;
        $table = $wpdb->prefix . 'ruh_user_levels';
        $info = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE user_id = %d", $user_id));
        
        if (!$info) {
            $info = (object)array('level' => 1, 'xp' => 0, 'user_id' => $user_id);
        }
        
        // 1 saat cache
        wp_cache_set($cache_key, $info, 'ruh_comment', 3600);
    }
    
    return $info;
}

/**
 * Kullanıcının sahip olduğu tüm rozetleri veritabanından çeker - CACHE İLE
 */
function ruh_get_user_badges($user_id) {
    if (!$user_id) return array();
    
    // Cache kontrolü
    $cache_key = 'ruh_user_badges_' . $user_id;
    $badges = wp_cache_get($cache_key, 'ruh_comment');
    
    if (false === $badges) {
        global $wpdb;
        $badges_table = $wpdb->prefix . 'ruh_badges';
        $user_badges_table = $wpdb->prefix . 'ruh_user_badges';
        
        $badges = $wpdb->get_results($wpdb->prepare(
            "SELECT b.* FROM $badges_table b 
             JOIN $user_badges_table ub ON b.badge_id = ub.badge_id 
             WHERE ub.user_id = %d 
             ORDER BY b.badge_id DESC", 
            $user_id
        ));
        
        if (!$badges) {
            $badges = array();
        }
        
        // 30 dakika cache
        wp_cache_set($cache_key, $badges, 'ruh_comment', 1800);
    }
    
    return $badges;
}

/**
 * Kullanıcı cache'ini temizle
 * 
 * @since 5.1
 * @param int $user_id Kullanıcı ID
 * @return void
 */
function ruh_clear_user_cache($user_id) {
    wp_cache_delete('ruh_user_level_' . $user_id, 'ruh_comment');
    wp_cache_delete('ruh_user_badges_' . $user_id, 'ruh_comment');
    wp_cache_delete('ruh_user_stats_' . $user_id, 'ruh_comment');
}

/**
 * Otomatik rozet koşullarını kontrol eder ve gerekirse kullanıcıya atar.
 */
function ruh_check_and_assign_auto_badges($user_id) {
    if (!$user_id) return;

    global $wpdb;
    $badges_table = $wpdb->prefix . 'ruh_badges';
    $user_badges_table = $wpdb->prefix . 'ruh_user_badges';
    
    // Otomatik rozetleri al
    $auto_badges = $wpdb->get_results("SELECT * FROM $badges_table WHERE is_automated = 1");
    if (empty($auto_badges)) return;

    // Kullanıcının yorum sayısını al
    $user_comment_count = get_comments(array(
        'user_id' => $user_id, 
        'count' => true, 
        'status' => 'approve'
    ));
    
    // Kullanıcının toplam beğeni sayısını al
    $user_total_likes = ruh_get_user_total_likes($user_id);
    
    // Kullanıcının seviyesini al
    $level_info = ruh_get_user_level_info($user_id);

    foreach ($auto_badges as $badge) {
        $should_assign = false;
        
        switch ($badge->auto_condition_type) {
            case 'comment_count':
                $should_assign = ($user_comment_count >= $badge->auto_condition_value);
                break;
            case 'like_count':
                $should_assign = ($user_total_likes >= $badge->auto_condition_value);
                break;
            case 'level':
                $should_assign = ($level_info->level >= $badge->auto_condition_value);
                break;
        }

        if ($should_assign) {
            // Rozet zaten var mı kontrol et
            $badge_exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $user_badges_table WHERE user_id = %d AND badge_id = %d",
                $user_id,
                $badge->badge_id
            ));
            
            if ($badge_exists == 0) {
                $wpdb->insert($user_badges_table, array(
                    'user_id' => $user_id,
                    'badge_id' => $badge->badge_id
                ));
            }
        }
    }
}

/**
 * Bir kullanıcının tüm yorumlarından aldığı toplam beğeni sayısını hesaplar.
 */
function ruh_get_user_total_likes($user_id) {
    if (!$user_id) return 0;
    
    global $wpdb;
    
    $sql = "SELECT SUM(CAST(meta_value AS UNSIGNED)) 
            FROM {$wpdb->commentmeta} cm 
            JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID 
            WHERE c.user_id = %d 
            AND cm.meta_key = '_likes' 
            AND c.comment_approved = '1'";
            
    $total = $wpdb->get_var($wpdb->prepare($sql, $user_id));
    
    return (int)$total;
}

/**
 * Admin panelinde kullanılacak genişletilmiş SVG rozet şablonlarını döndürür.
 */
function ruh_get_badge_templates() {
    return array(
        // === KORUMA VE GÜVENLİK ===
        'shield' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M11,7H13V9H11V7M11,11H13V17H11V11Z"/></svg>',
        'crown' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M5,16L3,5L5.5,7.5L8.5,4L12,7L15.5,4L18.5,7.5L21,5L19,16H5M12,2L13,3L12,4L11,3L12,2M19,19H5V21H19V19Z"/></svg>',
        
        // === BAŞARI VE YILDIZ ===
        'star' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,17.27L18.18,21L16.54,13.97L22,9.24L14.81,8.62L12,2L9.19,8.62L2,9.24L7.45,13.97L5.82,21L12,17.27Z"/></svg>',
        'trophy' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M7,2V4H6.5A2.5,2.5 0 0,0 4,6.5V11A2,2 0 0,0 6,13H6.64L8.77,19.74A2,2 0 0,0 10.69,21H13.31A2,2 0 0,0 15.23,19.74L17.36,13H18A2,2 0 0,0 20,11V6.5A2.5,2.5 0 0,0 17.5,4H17V2H7M9,4H15V11.64L13.31,19H10.69L9,11.64V4M6,6.5A.5,.5 0 0,1 6.5,6H7V11H6V6.5M17,6H17.5A.5,.5 0 0,1 18,6.5V11H17V6Z"/></svg>',
        'medal' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,4A6,6 0 0,1 18,10C18,13.31 15.31,16 12,16C8.69,16 6,13.31 6,10A6,6 0 0,1 12,4M12,6A4,4 0 0,0 8,10A4,4 0 0,0 12,14A4,4 0 0,0 16,10A4,4 0 0,0 12,6M7,18A1,1 0 0,1 6,19A1,1 0 0,1 5,18H7M17,18A1,1 0 0,0 18,19A1,1 0 0,0 19,18H17M10,18H14V20H10V18Z"/></svg>',
        
        // === SEVGİ VE DOST ===
        'heart' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,21.35L10.55,20.03C5.4,15.36 2,12.27 2,8.5C2,5.41 4.42,3 7.5,3C9.24,3 10.91,3.81 12,5.08C13.09,3.81 14.76,3 16.5,3C19.58,3 22,5.41 22,8.5C22,12.27 18.6,15.36 13.45,20.03L12,21.35Z"/></svg>',
        'diamond' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M6,2L2,8L12,22L22,8L18,2H6M6.5,4H8.5L7,7L6.5,4M9.5,4H14.5L15,7L12,10L9,7L9.5,4M16.5,4H17.5L17,7L15,4H16.5Z"/></svg>',
        'gem' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M16,9H19L14,16M10,9H14L12,16M5,9H8L10,16M15,4L14,9M10,4L12,9M9,4L10,9M2,9L7,4H17L22,9L12,20L2,9Z"/></svg>',
        
        // === ENERJİ VE HAREKET ===
        'flame' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,23A1,1 0 0,1 11,22V19H7A2,2 0 0,1 5,17V7A2,2 0 0,1 7,5H10V2A1,1 0 0,1 12,2A1,1 0 0,1 12,2V5H17A2,2 0 0,1 19,7V17A2,2 0 0,1 17,19H14V22A1,1 0 0,1 13,23H12M12,6C8.69,6 6,8.69 6,12S8.69,18 12,18S18,15.31 18,12S15.31,6 12,6M12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16Z"/></svg>',
        'lightning' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M11,4L6,10.5H9L8,18L13,11.5H10L11,4Z"/></svg>',
        'rocket' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,2L13,3L12,4L11,3L12,2M6.5,6L7.91,4.59C9.66,2.84 12.34,2.84 14.09,4.59L15.5,6C15.85,6.35 15.85,6.85 15.5,7.2L14.09,8.61C13.74,8.96 13.24,8.96 12.89,8.61L11.11,6.83C10.76,6.48 10.24,6.48 9.89,6.83L8.11,8.61C7.76,8.96 7.26,8.96 6.91,8.61L5.5,7.2C5.15,6.85 5.15,6.35 5.5,6L6.5,6Z"/></svg>',
        
        // === BİLGİ VE ÖĞRENİM ===
        'book' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M18,22A2,2 0 0,0 20,20V4A2,2 0 0,0 18,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18M6,4H13V12L9.5,10.5L6,12V4Z"/></svg>',
        'graduation' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,3L1,9L12,15L21,10.09V17H23V9M5,13.18V17.18L12,21L19,17.18V13.18L12,17L5,13.18Z"/></svg>',
        'lightbulb' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,2A7,7 0 0,0 5,9C5,11.38 6.19,13.47 8,14.74V17A1,1 0 0,0 9,18H15A1,1 0 0,0 16,17V14.74C17.81,13.47 19,11.38 19,9A7,7 0 0,0 12,2M9,21A1,1 0 0,0 10,22H14A1,1 0 0,0 15,21V20H9V21Z"/></svg>',
        
        // === AKTİVİTE VE ZAMIR ===
        'clock' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M16.2,16.2L11,13V7H12.5V12.2L17,14.7L16.2,16.2Z"/></svg>',
        'eye' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z"/></svg>',
        'bell' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M21,19V20H3V19L5,17V11C5,7.9 7.03,5.17 10,4.29C10,4.19 10,4.1 10,4A2,2 0 0,1 12,2A2,2 0 0,1 14,4C14,4.19 14,4.29 14,4.29C16.97,5.17 19,7.9 19,11V17L21,19M14,21A2,2 0 0,1 12,23A2,2 0 0,1 10,21"/></svg>',
        
        // === ÖZEL VE BÜYÜLÜ ===
        'magic' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M7.5,5.6L10,7L8.6,4.5L10,2L7.5,3.4L5,2L6.4,4.5L5,7L7.5,5.6M19.5,15.4L22,14L20.6,16.5L22,19L19.5,17.6L17,19L18.4,16.5L17,14L19.5,15.4M22,2L20.6,4.5L22,7L19.5,5.6L17,7L18.4,4.5L17,2L19.5,3.4L22,2M13.34,12.78L15.78,10.34L13.66,8.22L11.22,10.66L13.34,12.78M14.37,7.29L16.71,9.63C17.1,10 17.1,10.65 16.71,11.04L5.04,22.71C4.65,23.1 4,23.1 3.63,22.71L1.29,20.37C0.9,20 0.9,19.35 1.29,18.96L12.96,7.29C13.35,6.9 14,6.9 14.37,7.29Z"/></svg>',
        'target' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4M12,6A6,6 0 0,0 6,12A6,6 0 0,0 12,18A6,6 0 0,0 18,12A6,6 0 0,0 12,6M12,8A4,4 0 0,1 16,12A4,4 0 0,1 12,16A4,4 0 0,1 8,12A4,4 0 0,1 12,8M12,10A2,2 0 0,0 10,12A2,2 0 0,0 12,14A2,2 0 0,0 14,12A2,2 0 0,0 12,10Z"/></svg>',
        'puzzle' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M20.5,11H19V7C19,5.89 18.1,5 17,5H13V3.5A2.5,2.5 0 0,0 10.5,1A2.5,2.5 0 0,0 8,3.5V5H4A2,2 0 0,0 2,7V10.8H3.5C5,10.8 6.2,12 6.2,13.5C6.2,15 5,16.2 3.5,16.2H2V20A2,2 0 0,0 4,22H7.8V20.5C7.8,19 9,17.8 10.5,17.8C12,17.8 13.2,19 13.2,20.5V22H17A2,2 0 0,0 19,20V16H20.5A2.5,2.5 0 0,0 23,13.5A2.5,2.5 0 0,0 20.5,11Z"/></svg>',
        
        // === YENİ MODERN İCONLAR ===
        'thumbsup' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M23,10C23,8.89 22.1,8 21,8H14.68L15.64,3.43C15.66,3.33 15.67,3.22 15.67,3.11C15.67,2.7 15.5,2.32 15.23,2.05L14.17,1L7.59,7.58C7.22,7.95 7,8.45 7,9V19A2,2 0 0,0 9,21H18C18.83,21 19.54,20.5 19.84,19.78L22.86,12.73C22.95,12.5 23,12.26 23,12V10.08L23,10M1,21H5V9H1V21Z"/></svg>',
        'handshake' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M11,1.07C7.05,1.56 4,4.92 4,9H7L12,4L17,9H20C20,4.92 16.95,1.56 13,1.07V4A1,1 0 0,1 12,5A1,1 0 0,1 11,4V1.07M4,11V16L6,18H9L11,16V11H4M13,11V16L15,18H18L20,16V11H13M2,20C2,21.11 2.9,22 4,22H20A2,2 0 0,0 22,20H2Z"/></svg>',
        'chat' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M12,3C6.5,3 2,6.58 2,11A7.18,7.18 0 0,0 2.64,14.34L1.17,18.83L5.66,17.36C7.38,18.39 9.61,19 12,19C17.5,19 22,15.42 22,11S17.5,3 12,3M8,9H16V11H8V9M8,12H13V14H8V12Z"/></svg>',
        'trending' => '<svg viewBox="0 0 24 24" width="24" height="24"><path fill="{color}" d="M16,6L18.29,8.29L13.41,13.17L9.41,9.17L2,16.59L3.41,18L9.41,12L13.41,16L19.71,9.71L22,12V6H16Z"/></svg>'
    );
}

/**
 * Kullanıcının profil sayfasına giden URL'i oluşturur - DÜZELTİLMİŞ
 */
function ruh_get_user_profile_url($user_id) {
    if (!$user_id || !is_numeric($user_id)) return '#';
    
    // Kullanıcı var mı kontrol et
    if (!get_userdata($user_id)) {
        return '#';
    }
    
    $options = get_option('ruh_comment_options', array());
    $page_id = isset($options['profile_page_id']) ? intval($options['profile_page_id']) : 0;
    
    // Önce kayıtlı profile page ID'yi kontrol et
    if ($page_id > 0 && get_post_status($page_id) === 'publish') {
        return add_query_arg('user_id', $user_id, get_permalink($page_id));
    }
    
    // Cache kontrolü
    $cache_key = 'ruh_profile_page_id';
    $cached_page_id = wp_cache_get($cache_key, 'ruh_comment');
    
    if ($cached_page_id !== false && get_post_status($cached_page_id) === 'publish') {
        return add_query_arg('user_id', $user_id, get_permalink($cached_page_id));
    }
    
    // Shortcode'a sahip sayfa ara - Optimize edilmiş
    $pages = get_posts([
        'post_type' => 'page',
        'post_status' => 'publish',
        'numberposts' => 50, // Limit koy
        'fields' => 'ids', // Sadece ID'ler
        'meta_query' => [
            [
                'key' => '_wp_page_template',
                'value' => 'page-profile.php',
                'compare' => 'LIKE'
            ]
        ]
    ]);
    
    // Template bulunamazsa content'te shortcode ara
    if (empty($pages)) {
        $all_pages = get_posts([
            'post_type' => 'page',
            'post_status' => 'publish',
            'numberposts' => 50, // Limit koy
            'fields' => 'ids' // Sadece ID'ler
        ]);
        
        foreach ($all_pages as $page_id) {
            $page = get_post($page_id);
            if ($page && has_shortcode($page->post_content, 'ruh_user_profile')) {
                $pages = [$page_id];
                break;
            }
        }
    }
    
    // Sayfa bulunduysa
    if (!empty($pages)) {
        $profile_page_id = is_array($pages) ? $pages[0] : $pages;
        
        // Cache'e kaydet
        wp_cache_set($cache_key, $profile_page_id, 'ruh_comment', 3600);
        
        // Seçenekleri güncelle
        $options['profile_page_id'] = $profile_page_id;
        update_option('ruh_comment_options', $options);
        
        return add_query_arg('user_id', $user_id, get_permalink($profile_page_id));
    }
    
    // Fallback olarak author posts URL kullan
    $author_url = get_author_posts_url($user_id);
    return $author_url ? $author_url : '#';
}

/**
 * Kullanıcının seviye rozetini HTML olarak döndürür - YENİ OVAL TASARIM
 * NOT: Kullanıcı tag'ı bu fonksiyonda dahil değil; ruh_comment_format() içinde ayrıca ekleniyor.
 */
function ruh_get_user_level_badge($user_id) {
    if (!$user_id) return '';
    
    $level_info = ruh_get_user_level_info($user_id);
    $level_color = ruh_get_level_color($level_info->level);
    $level_title = ruh_get_level_title($level_info->level);
    
    $html = sprintf(
        '<span class="user-level-oval" style="background: %s;" title="%s - %d XP" data-level="%d">Seviye %d</span>',
        esc_attr($level_color),
        esc_attr($level_title),
        intval($level_info->xp),
        intval($level_info->level),
        intval($level_info->level)
    );
    
    return $html;
}

/**
 * Kullanıcının tag'ını döndürür (Editör, Çevirmen vb.)
 */
function ruh_get_user_tag($user_id) {
    if (!$user_id) return '';
    
    $tag = get_user_meta($user_id, 'ruh_user_tag', true);
    $tag_color = get_user_meta($user_id, 'ruh_user_tag_color', true);
    
    if (empty($tag)) return '';
    
    $color = !empty($tag_color) ? $tag_color : '#6366f1';
    
    return sprintf(
        '<span class="user-tag-badge" style="background: %s;">%s</span>',
        esc_attr($color),
        esc_html($tag)
    );
}

/**
 * Seviye rengini belirler - DARK MODE GRADIENT
 */
function ruh_get_level_color($level) {
    if ($level >= 100) return '#ef4444';     // Kırmızı - Efsanevi
    if ($level >= 75) return '#a855f7';      // Mor - Mitik  
    if ($level >= 50) return '#ec4899';      // Pembe - Epik
    if ($level >= 30) return '#8b5cf6';      // Mor-Mavi - Nadir
    if ($level >= 20) return '#f59e0b';      // Turuncu - Sıradışı
    if ($level >= 10) return '#10b981';      // Yeşil - Deneyimli
    if ($level >= 5) return '#3b82f6';       // Mavi - Aktif
    return '#6b7280';                        // Gri - Yeni başlayan
}

/**
 * Seviye unvanını belirler
 */
function ruh_get_level_title($level) {
    if ($level >= 100) return 'Efsanevi Yorumcu';
    if ($level >= 75) return 'Mitik Katılımcı';
    if ($level >= 50) return 'Epik Topluluk Üyesi';
    if ($level >= 30) return 'Nadir Katkıda Bulunan';
    if ($level >= 20) return 'Sıradışı Yorum Yazarı';
    if ($level >= 10) return 'Deneyimli Üye';
    if ($level >= 5) return 'Aktif Katılımcı';
    return 'Yeni Başlayan';
}

/**
 * Kullanıcının özel rozetlerini HTML olarak döndürür - YAZILI VERSİYON
 */
function ruh_get_user_custom_badges($user_id) {
    if (!$user_id) return '';
    
    $badges = ruh_get_user_badges($user_id);
    if (empty($badges)) return '';

    $output = '<span class="user-badges">';
    foreach (array_slice($badges, 0, 3) as $badge) {
        $output .= sprintf(
            '<span class="badge-item-with-text" title="%s">%s <span class="badge-text">%s</span></span>',
            esc_attr($badge->badge_name),
            $badge->badge_svg,
            esc_html($badge->badge_name)
        );
    }
    
    if (count($badges) > 3) {
        $remaining = count($badges) - 3;
        $output .= sprintf(
            '<span class="badge-more" title="Ve %d rozet daha">+%d</span>',
            $remaining,
            $remaining
        );
    }
    
    $output .= '</span>';
    return $output;
}

/**
 * Kullanıcı istatistiklerini döndürür
 */
function ruh_get_user_stats($user_id) {
    if (!$user_id) return array();
    
    $comment_count = get_comments(array('user_id' => $user_id, 'count' => true, 'status' => 'approve'));
    $total_likes = ruh_get_user_total_likes($user_id);
    $level_info = ruh_get_user_level_info($user_id);
    $badges = ruh_get_user_badges($user_id);
    $user = get_userdata($user_id);
    
    $last_comment = get_comments(['user_id' => $user_id, 'number' => 1, 'status' => 'approve']);
    $last_activity = !empty($last_comment) ? strtotime($last_comment[0]->comment_date) : strtotime($user->user_registered);
    
    $avg_likes = $comment_count > 0 ? round($total_likes / $comment_count, 1) : 0;
    
    return array(
        'comment_count' => (int)$comment_count,
        'total_likes' => (int)$total_likes,
        'avg_likes' => $avg_likes,
        'level' => (int)$level_info->level,
        'xp' => (int)$level_info->xp,
        'badge_count' => count($badges),
        'join_date' => $user->user_registered,
        'last_activity' => $last_activity,
        'days_active' => floor((time() - strtotime($user->user_registered)) / DAY_IN_SECONDS)
    );
}

/**
 * Yorum için meta bilgileri döndürür
 */
function ruh_get_comment_meta_info($comment_id) {
    $likes = get_comment_meta($comment_id, '_likes', true) ?: 0;
    $dislikes = get_comment_meta($comment_id, '_dislikes', true) ?: 0;
    $reports = ruh_get_comment_report_count($comment_id);
    
    return array(
        'likes' => (int)$likes,
        'dislikes' => (int)$dislikes,
        'reports' => (int)$reports,
        'score' => (int)$likes - (int)$dislikes
    );
}

/**
 * Yorumun şikayet sayısını döndürür
 */
function ruh_get_comment_report_count($comment_id) {
    global $wpdb;
    $reports_table = $wpdb->prefix . 'ruh_reports';
    
    return (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $reports_table WHERE comment_id = %d",
        $comment_id
    ));
}

/**
 * Kullanıcı durumu kontrolü
 */
function ruh_is_user_banned($user_id) {
    if (!$user_id) return false;
    
    $ban_status = get_user_meta($user_id, 'ruh_ban_status', true);
    $timeout_until = get_user_meta($user_id, 'ruh_timeout_until', true);
    
    return $ban_status === 'banned' || ($timeout_until && current_time('timestamp') < $timeout_until);
}

/**
 * Widget sınıfı
 */
class Ruh_Comment_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'ruh_comment_widget',
            'Ruh Comment İstatistikleri',
            array('description' => 'Topluluk istatistiklerini gösterir')
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $stats = ruh_get_weekly_stats();
        $top_users = ruh_get_top_users(5);
        
        echo '<div class="ruh-widget-content">';
        echo '<div class="stats-section">';
        echo '<h4>Bu Hafta</h4>';
        echo '<ul>';
        echo '<li><strong>' . $stats['comments'] . '</strong> yeni yorum</li>';
        echo '<li><strong>' . $stats['new_users'] . '</strong> yeni üye</li>';
        echo '<li><strong>' . $stats['reactions'] . '</strong> tepki</li>';
        echo '</ul>';
        echo '</div>';
        
        if (!empty($top_users)) {
            echo '<div class="top-users-section">';
            echo '<h4>En Aktif Üyeler</h4>';
            echo '<ol>';
            foreach ($top_users as $user) {
                $profile_url = ruh_get_user_profile_url($user->ID);
                echo '<li><a href="' . esc_url($profile_url) . '">' . esc_html($user->display_name) . '</a> ';
                echo '<span class="level">(Lv.' . $user->level . ')</span></li>';
            }
            echo '</ol>';
            echo '</div>';
        }
        echo '</div>';
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Topluluk İstatistikleri';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Başlık:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        return $instance;
    }
}

/**
 * En aktif kullanıcıları getir
 */
function ruh_get_top_users($limit = 10) {
    global $wpdb;
    
    $sql = "
        SELECT u.ID, u.display_name, 
               COALESCE(ul.level, 1) as level,
               COALESCE(ul.xp, 0) as xp,
               COUNT(c.comment_ID) as comment_count,
               COALESCE(SUM(CAST(cm.meta_value AS UNSIGNED)), 0) as total_likes
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->prefix}ruh_user_levels ul ON u.ID = ul.user_id
        LEFT JOIN {$wpdb->comments} c ON u.ID = c.user_id AND c.comment_approved = '1'
        LEFT JOIN {$wpdb->commentmeta} cm ON c.comment_ID = cm.comment_id AND cm.meta_key = '_likes'
        GROUP BY u.ID
        ORDER BY (COALESCE(ul.level, 1) * 10 + COUNT(c.comment_ID) + COALESCE(SUM(CAST(cm.meta_value AS UNSIGNED)), 0)) DESC
        LIMIT %d
    ";
    
    return $wpdb->get_results($wpdb->prepare($sql, $limit));
}

/**
 * Haftalık istatistikler
 */
function ruh_get_weekly_stats() {
    global $wpdb;
    
    // WordPress timezone'u kullan, PHP date() yerine
    $week_ago = gmdate('Y-m-d H:i:s', strtotime('-1 week'));
    
    $stats = array(
        'comments' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_date >= %s AND comment_approved = '1'",
            $week_ago
        )),
        'reactions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ruh_reactions"),
        'new_users' => $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->users} WHERE user_registered >= %s",
            $week_ago
        )),
        'badges_earned' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}ruh_user_badges")
    );
    
    return $stats;
}

/**
 * Widget'ları kaydet
 */
function ruh_register_widgets() {
    register_widget('Ruh_Comment_Widget');
}
add_action('widgets_init', 'ruh_register_widgets');

/**
 * Güvenli seviye rengi döndür
 */
function ruh_get_level_color_safe($level) {
    $level = intval($level);
    
    $colors = array(
        1 => '#6b7280', // Gray
        2 => '#10b981', // Green
        3 => '#3b82f6', // Blue
        4 => '#8b5cf6', // Purple
        5 => '#f59e0b', // Yellow
        6 => '#ef4444', // Red
        7 => '#ec4899', // Pink
        8 => '#14b8a6', // Teal
        9 => '#f97316', // Orange
        10 => '#dc2626' // Dark Red
    );
    
    if ($level <= 10) {
        return $colors[$level] ?? $colors[1];
    }
    
    // 10+ seviyeler için gradient renkler
    $high_colors = array('#8b5cf6', '#ec4899', '#f59e0b', '#ef4444');
    return $high_colors[($level - 11) % 4];
}

/**
 * Yorum için post başlığını güvenli şekilde al
 * Dinamik post ID'ler ve manga sistemi uyumlu
 */
function ruh_get_comment_post_title($comment_post_id) {
    if (!$comment_post_id || !is_numeric($comment_post_id)) {
        return 'Geçersiz Sayfa';
    }
    
    // Önce normal WordPress post sistemini dene
    $title = get_the_title($comment_post_id);
    
    if (!empty($title) && $title !== 'Auto Draft') {
        return $title;
    }
    
    // Eğer title bulunamazsa, dinamik sistem için özel title oluştur
    $current_url = '';
    if (isset($_SERVER['HTTP_REFERER']) && filter_var($_SERVER['HTTP_REFERER'], FILTER_VALIDATE_URL)) {
        $current_url = $_SERVER['HTTP_REFERER'];
    }
    
    if (empty($current_url)) {
        // Son çare olarak dinamik ID'den title üret
        if ($comment_post_id > 1000000) { // Dinamik ID'ler genelde büyük sayılar
            // CRC32'den geri çıkarma mümkün olmadığı için genel bir title
            return 'Manga Bölümü #' . substr(strval($comment_post_id), -4);
        }
        
        return 'Bilinmeyen Sayfa';
    }
    
    $parsed_url = parse_url($current_url);
    if (!$parsed_url || !isset($parsed_url['path'])) {
        return 'Geçersiz URL';
    }
    
    $url_path = $parsed_url['path'];
    
    // Manga chapter pattern'ini kontrol et - Güvenli regex
    if (preg_match('/\/manga\/([a-zA-Z0-9\-_]+)\/chapter[_-]?([0-9]+(?:[._-][0-9]+)?)/i', $url_path, $matches)) {
        $manga_slug = sanitize_title($matches[1]);
        $chapter_number = intval($matches[2]);
        return ucwords(str_replace('-', ' ', $manga_slug)) . ' - Bölüm ' . $chapter_number;
    }
    
    // Manga ana sayfa pattern'ini kontrol et - Güvenli regex
    if (preg_match('/\/manga\/([a-zA-Z0-9\-_]+)\/?$/i', $url_path, $matches)) {
        $manga_slug = sanitize_title($matches[1]);
        return ucwords(str_replace('-', ' ', $manga_slug)) . ' - Ana Sayfa';
    }
    
    // URL'den genel bir title oluştur
    $path_parts = array_filter(explode('/', trim($url_path, '/')));
    if (!empty($path_parts)) {
        $last_part = sanitize_title(end($path_parts));
        return ucwords(str_replace('-', ' ', $last_part));
    }
    
    return 'Web Sayfası';
}

/**
 * Yorum için doğru URL oluştur - manga sitesi uyumlu
 */
function ruh_get_comment_link($comment) {
    if (!is_object($comment)) {
        $comment = get_comment($comment);
    }
    
    if (!$comment) {
        return '#';
    }
    
    // Dinamik post ID'ler için özel link oluşturma
    $post_id = $comment->comment_post_ID;
    
    // Önce metadata'dan orijinal URL'i kontrol et (tüm yorumlar için)
    $original_url = get_comment_meta($comment->comment_ID, '_original_post_url', true);
    if ($original_url) {
        // Fragment kısmını temizle ve comment ID ekle
        $clean_url = strtok($original_url, '#');
        return $clean_url . '#comment-' . $comment->comment_ID;
    }
    
    // Eğer post ID büyükse (dinamik CRC32 hash), özel URL oluştur
    if ($post_id > 1000000) {
        // Admin panelinde mi kontrol et
        $is_admin = is_admin();
        
        if ($is_admin) {
            // Admin panelinde metadata yoksa, site URL'si + manga path dene
            $site_url = home_url();
            return $site_url . '/manga/#comment-' . $comment->comment_ID;
        }
        
        // Frontend'de mevcut sayfa URL'sini kullan
        if (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
            
            // Eğer mevcut sayfa manga URL'si içeriyorsa
            if (isset($_SERVER['REQUEST_URI']) && preg_match('/\/manga\/([^\/]+)/', $_SERVER['REQUEST_URI'], $matches)) {
                $manga_slug = $matches[1];
                $manga_url = $scheme . $host . '/manga/' . $manga_slug . '/';
                
                // Chapter URL'si olabilir mi kontrol et
                if (preg_match('/\/manga\/([^\/]+)\/chapter[_-]?([0-9]+(?:[._-][0-9]+)?)/', $_SERVER['REQUEST_URI'], $chapter_matches)) {
                    $chapter_url = $scheme . $host . '/manga/' . $chapter_matches[1] . '/chapter-' . $chapter_matches[2] . '/';
                    return $chapter_url . '#comment-' . $comment->comment_ID;
                }
                
                return $manga_url . '#comment-' . $comment->comment_ID;
            }
            
            // Son çare: referrer kullan
            if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
                $referer = $_SERVER['HTTP_REFERER'];
                $parsed = parse_url($referer);
                
                if ($parsed && isset($parsed['path']) && preg_match('/\/manga\/[^\/]+/', $parsed['path'])) {
                    $base_url = $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'];
                    if (isset($parsed['query'])) {
                        $base_url .= '?' . $parsed['query'];
                    }
                    return $base_url . '#comment-' . $comment->comment_ID;
                }
            }
            
            // Manga ana directory'sine git
            return $scheme . $host . '/manga/#comment-' . $comment->comment_ID;
        }
        
        // WordPress fallback
        return get_comment_link($comment);
    }
    
    // Normal WordPress post'lar için standart link
    return get_comment_link($comment);
}

/**
 * Post permalink'i dinamik sistemle uyumlu şekilde al
 */
function ruh_get_post_permalink($post_id, $comment = null) {
    // Normal WordPress post kontrolü
    $permalink = get_permalink($post_id);
    
    if ($permalink && $permalink !== false) {
        return $permalink;
    }
    
    // Dinamik post ID'ler için
    if ($post_id > 1000000 && $comment) {
        // Comment metadata'dan URL al
        $original_url = get_comment_meta($comment->comment_ID, '_original_post_url', true);
        
        if ($original_url) {
            // Fragment (#comment-123) kısmını temizle
            return strtok($original_url, '#');
        }
        
        // Son çare: referrer URL'den çıkarım
        if (isset($_SERVER['HTTP_REFERER'])) {
            $referer = $_SERVER['HTTP_REFERER'];
            $parsed = parse_url($referer);
            
            if ($parsed && isset($parsed['path'])) {
                if (preg_match('/\/manga\/[^\/]+/', $parsed['path'])) {
                    return $parsed['scheme'] . '://' . $parsed['host'] . $parsed['path'];
                }
            }
        }
        
        // Manga ana sayfasına yönlendir
        return home_url('/manga/');
    }
    
    return home_url();
}

/**
 * Giphy API Server-Side Proxy
 * API key'i client-side'a expose etmeden güvenli GIF arama
 * 
 * @since 7.0
 */
function ruh_gif_search_proxy() {
    // Nonce doğrulama
    check_ajax_referer('ruh-comment-nonce', 'nonce');
    
    $search = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
    
    if (strlen($search) < 2) {
        wp_send_json_error(array('message' => 'En az 2 karakter girin.'));
    }
    
    $options = get_option('ruh_comment_options', array());
    $api_key = isset($options['giphy_api_key']) ? $options['giphy_api_key'] : '';
    
    if (empty($api_key)) {
        wp_send_json_error(array('message' => 'Giphy API key yapılandırılmamış.'));
    }
    
    $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 20) : 12;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    
    $url = 'https://api.giphy.com/v1/gifs/search?' . http_build_query(array(
        'api_key' => $api_key,
        'q' => $search,
        'limit' => $limit,
        'offset' => $offset,
        'rating' => 'pg-13',
        'lang' => 'tr'
    ));
    
    $response = wp_remote_get($url, array(
        'timeout' => 5,
        'headers' => array('Accept' => 'application/json')
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Giphy bağlantı hatası.'));
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!$data || !isset($data['data'])) {
        wp_send_json_error(array('message' => 'Giphy yanıt hatası.'));
    }
    
    $gifs = array();
    foreach ($data['data'] as $gif) {
        $gifs[] = array(
            'id' => $gif['id'],
            'url' => $gif['images']['fixed_width_small']['url'],
            'preview_url' => $gif['images']['fixed_width_small']['url'],
            'original_url' => $gif['images']['original']['url'],
            'width' => intval($gif['images']['fixed_width_small']['width']),
            'height' => intval($gif['images']['fixed_width_small']['height']),
        );
    }
    
    wp_send_json_success(array('gifs' => $gifs));
}
add_action('wp_ajax_ruh_gif_search', 'ruh_gif_search_proxy');
add_action('wp_ajax_nopriv_ruh_gif_search', 'ruh_gif_search_proxy');

/**
 * Mention bildirim e-postası gönder
 * Bir yorumda @kullanıcı bahsedildiğinde o kullanıcıya bildirim gönderir
 * 
 * @since 7.0
 * @param int $comment_id Yorum ID
 * @param int $mentioned_user_id Bahsedilen kullanıcı ID
 */
function ruh_send_mention_notification($comment_id, $mentioned_user_id) {
    $comment = get_comment($comment_id);
    if (!$comment) return;
    
    $mentioned_user = get_userdata($mentioned_user_id);
    if (!$mentioned_user) return;
    
    // Kendi yorumunda kendini etiketleme bildirimi gönderme
    if ($mentioned_user_id == $comment->user_id) return;
    
    // Kullanıcının e-posta bildirimlerini kapatmış olup olmadığını kontrol et
    $notifications_disabled = get_user_meta($mentioned_user_id, 'ruh_disable_mention_notifications', true);
    if ($notifications_disabled) return;
    
    $commenter = $comment->user_id ? get_userdata($comment->user_id) : null;
    $commenter_name = $commenter ? $commenter->display_name : ($comment->comment_author ?: 'Anonim');
    
    $post_title = get_the_title($comment->comment_post_ID);
    $comment_link = get_comment_link($comment_id);
    
    // E-posta içeriğini dil ayarına göre hazırla
    $is_en = ruh_option('language', 'tr_TR') === 'en_US';
    
    $subject = $is_en 
        ? sprintf('%s mentioned you in a comment', $commenter_name)
        : sprintf('%s bir yorumda sizi andı', $commenter_name);
    
    $body = $is_en 
        ? sprintf(
            "Hi %s,\n\n%s mentioned you in a comment on \"%s\":\n\n\"%s\"\n\nView comment: %s\n\n---\nYou can disable mention notifications from your profile settings.",
            $mentioned_user->display_name,
            $commenter_name,
            $post_title,
            wp_trim_words(strip_tags($comment->comment_content), 30),
            $comment_link
        )
        : sprintf(
            "Merhaba %s,\n\n%s, \"%s\" yazısındaki bir yorumda sizi andı:\n\n\"%s\"\n\nYorumu görüntüle: %s\n\n---\nBahsedilme bildirimlerini profil ayarlarınızdan kapatabilirsiniz.",
            $mentioned_user->display_name,
            $commenter_name,
            $post_title,
            wp_trim_words(strip_tags($comment->comment_content), 30),
            $comment_link
        );
    
    wp_mail($mentioned_user->user_email, $subject, $body);
}

/**
 * Yorumdaki @mention'ları işle ve bildirim gönder
 * advanced-features.php'deki Ruh_Mention_System ile uyumlu çalışır
 * 
 * @since 7.0
 * @param int $comment_id Yorum ID
 */
function ruh_process_mentions($comment_id) {
    $comment = get_comment($comment_id);
    if (!$comment || !$comment->user_id) return;
    
    $content = $comment->comment_content;
    
    // @kullaniciadi kalıplarını bul
    if (!preg_match_all('/@([a-zA-Z0-9_]+)/', $content, $matches)) return;
    
    $already_notified = array();
    
    foreach ($matches[1] as $username) {
        // Kendi kendini etiketleme
        $user = get_user_by('login', $username);
        if (!$user) continue;
        if ($user->ID == $comment->user_id) continue;
        
        // Her kullanıcıya sadece bir bildirim
        if (in_array($user->ID, $already_notified)) continue;
        $already_notified[] = $user->ID;
        
        ruh_send_mention_notification($comment_id, $user->ID);
    }
}

// Yorum yayınlandığında mention'ları işle
add_action('wp_insert_comment', 'ruh_process_mentions', 10, 1);
// Yorum onaylandığında da mention'ları işle
add_action('comment_approved_comment', 'ruh_process_mentions', 10, 1);

/**
 * Yorum düzenleme geçmişi
 * Düzenlenen yorumlara "düzenlendi" işareti ve geçmiş tutar
 * 
 * @since 7.0
 */

/**
 * Yorum düzenlendiğinde geçmiş kaydet
 */
function ruh_save_edit_history($comment_id, $new_content) {
    $comment = get_comment($comment_id);
    if (!$comment) return;
    
    // Mevcut içeriği geçmişe ekle (sadece içerik değişmişse)
    $original_content = $comment->comment_content;
    if ($original_content === $new_content) return;
    
    $history = get_comment_meta($comment_id, '_edit_history', true);
    if (!is_array($history)) {
        $history = array();
    }
    
    $history[] = array(
        'content' => $original_content,
        'edited_at' => current_time('mysql'),
        'edited_by' => get_current_user_id()
    );
    
    // En fazla 10 düzenleme geçmişi tut
    if (count($history) > 10) {
        $history = array_slice($history, -10);
    }
    
    update_comment_meta($comment_id, '_edit_history', $history);
    update_comment_meta($comment_id, '_edited', '1');
}

/**
 * Yorumun düzenlenip düzenlenmediğini kontrol et
 * @param int|WP_Comment $comment Yorum ID veya obje
 * @return bool
 */
function ruh_is_comment_edited($comment) {
    if (is_numeric($comment)) {
        $comment = get_comment($comment);
    }
    if (!$comment) return false;
    
    return (bool) get_comment_meta($comment->comment_ID, '_edited', true);
}

/**
 * Yorum düzenleme geçmişini al
 * @param int $comment_id Yorum ID
 * @return array
 */
function ruh_get_edit_history($comment_id) {
    $history = get_comment_meta($comment_id, '_edit_history', true);
    return is_array($history) ? $history : array();
}


