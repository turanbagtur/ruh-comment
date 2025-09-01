<?php
if (!defined('ABSPATH')) exit;

// Kullanıcı profil sayfası
add_shortcode('ruh_user_profile', 'ruh_user_profile_shortcode_handler');
function ruh_user_profile_shortcode_handler($atts) {
    $atts = shortcode_atts(array('user_id' => ''), $atts, 'ruh_user_profile');
    
    if (!empty($atts['user_id'])) {
        $user_id = intval($atts['user_id']);
    } else if (isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
    } else if (is_user_logged_in()) {
        $user_id = get_current_user_id();
    } else {
        return '<div class="ruh-error-message"><p>Profil görüntülemek için giriş yapmalısınız veya bir kullanıcı ID belirtmelisiniz.</p></div>';
    }

    if (!$user_id || !get_user_by('id', $user_id)) {
        return '<div class="ruh-error-message"><p>Kullanıcı bulunamadı.</p></div>';
    }
    
    $user_data_obj = get_user_by('id', $user_id);
    $is_own_profile = (is_user_logged_in() && get_current_user_id() == $user_id);
    
    if (!function_exists('ruh_get_user_level_info')) {
        return '<div class="ruh-error-message"><p>Profil sistemi henüz yüklenmedi.</p></div>';
    }
    
    $user_data = array(
        'info' => $user_data_obj,
        'level_info' => ruh_get_user_level_info($user_id),
        'badges' => function_exists('ruh_get_user_badges') ? ruh_get_user_badges($user_id) : array(),
        'comments' => get_comments(array('user_id' => $user_id, 'number' => 10, 'status' => 'approve')),
        'total_comments' => get_comments(array('user_id' => $user_id, 'count' => true, 'status' => 'approve')),
        'total_likes' => function_exists('ruh_get_user_total_likes') ? ruh_get_user_total_likes($user_id) : 0,
        'stats' => function_exists('ruh_get_user_stats') ? ruh_get_user_stats($user_id) : array(),
        'is_own_profile' => $is_own_profile
    );

    ob_start();
    if (file_exists(RUH_COMMENT_PATH . 'templates/user-profile-template.php')) {
        include(RUH_COMMENT_PATH . 'templates/user-profile-template.php');
    } else {
        echo '<div class="ruh-error-message"><p>Profil şablonu bulunamadı.</p></div>';
    }
    return ob_get_clean();
}

// Giriş formu
add_shortcode('ruh_login', 'ruh_login_shortcode_handler');
function ruh_login_shortcode_handler($atts) {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $profile_url = ruh_get_user_profile_url($user->ID);
        return '<div class="ruh-info-message">
                    <h3>Zaten giriş yapmışsınız!</h3>
                    <p>Merhaba <strong>' . esc_html($user->display_name) . '</strong>, zaten giriş yapmışsınız.</p>
                    <div class="ruh-action-buttons">
                        <a href="' . esc_url($profile_url) . '" class="ruh-button primary">Profilim</a>
                        <a href="' . esc_url(ruh_logout_url()) . '" class="ruh-button secondary">Çıkış Yap</a>
                    </div>
                </div>';
    }
    
    $atts = shortcode_atts(array('redirect_to' => ''), $atts, 'ruh_login');
    
    ob_start();
    ?>
    <div class="ruh-auth-form ruh-login-form">
        <div class="auth-form-header">
            <h2>Giriş Yap</h2>
            <p>Hesabınıza giriş yaparak yorumlarınızı paylaşabilir, tepkilerinizi gösterebilir ve topluluğa katılabilirsiniz.</p>
        </div>
        
        <form id="ruh-login-form" method="post" class="auth-form">
            <?php wp_nonce_field('ruh_auth_nonce', 'nonce'); ?>
            <input type="hidden" name="action" value="ruh_login">
            <input type="hidden" name="redirect_to" value="<?php echo esc_url($atts['redirect_to'] ? $atts['redirect_to'] : get_permalink()); ?>">
            
            <div class="form-group">
                <label for="login_username">Kullanıcı Adı veya E-posta</label>
                <input type="text" id="login_username" name="username" required placeholder="kullaniciadi@example.com">
                <div class="field-icon">👤</div>
            </div>
            
            <div class="form-group">
                <label for="login_password">Şifre</label>
                <input type="password" id="login_password" name="password" required placeholder="••••••••">
                <div class="field-icon">🔒</div>
                <button type="button" class="password-toggle" onclick="togglePassword('login_password')">👁️</button>
            </div>
            
            <div class="form-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="remember" value="1">
                    <span class="checkmark"></span>
                    Beni hatırla
                </label>
            </div>
            
            <button type="submit" class="ruh-submit">
                <span class="button-text">Giriş Yap</span>
                <span class="button-loader" style="display:none;">⏳ Giriş yapılıyor...</span>
            </button>
            
            <div class="form-links">
                <?php 
                $options = get_option('ruh_comment_options', array());
                $register_page = isset($options['register_page_id']) ? $options['register_page_id'] : 0;
                if ($register_page && get_post($register_page)) : ?>
                    <a href="<?php echo get_permalink($register_page); ?>" class="auth-link">Hesabınız yok mu? Kayıt olun</a>
                <?php endif; ?>
                <a href="<?php echo wp_lostpassword_url(); ?>" class="auth-link">Şifremi unuttum</a>
            </div>
        </form>
    </div>
    
    <script>
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const toggle = input.nextElementSibling.nextElementSibling;
        if (input.type === 'password') {
            input.type = 'text';
            toggle.textContent = '🙈';
        } else {
            input.type = 'password';
            toggle.textContent = '👁️';
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('ruh-login-form');
        if (!form) return;
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.ruh-submit');
            const buttonText = submitBtn.querySelector('.button-text');
            const buttonLoader = submitBtn.querySelector('.button-loader');
            
            buttonText.style.display = 'none';
            buttonLoader.style.display = 'inline';
            submitBtn.disabled = true;
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (window.showNotification) {
                        window.showNotification(data.data.message, 'success');
                    }
                    setTimeout(() => {
                        window.location.href = data.data.redirect;
                    }, 1500);
                } else {
                    if (window.showNotification) {
                        window.showNotification(data.data.message, 'error');
                    } else {
                        alert(data.data.message);
                    }
                }
            })
            .catch(error => {
                if (window.showNotification) {
                    window.showNotification('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
                } else {
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                }
            })
            .finally(() => {
                buttonText.style.display = 'inline';
                buttonLoader.style.display = 'none';
                submitBtn.disabled = false;
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

// Kayıt formu
add_shortcode('ruh_register', 'ruh_register_shortcode_handler');
function ruh_register_shortcode_handler($atts) {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $profile_url = ruh_get_user_profile_url($user->ID);
        return '<div class="ruh-info-message">
                    <h3>Zaten giriş yapmışsınız!</h3>
                    <p>Merhaba <strong>' . esc_html($user->display_name) . '</strong>, zaten giriş yapmışsınız.</p>
                    <div class="ruh-action-buttons">
                        <a href="' . esc_url($profile_url) . '" class="ruh-button primary">Profilim</a>
                        <a href="' . esc_url(ruh_logout_url()) . '" class="ruh-button secondary">Çıkış Yap</a>
                    </div>
                </div>';
    }
    
    if (!get_option('users_can_register')) {
        return '<div class="ruh-error-message">
                    <h3>Kayıt Kapalı</h3>
                    <p>Şu anda yeni kullanıcı kaydı kabul edilmemektedir.</p>
                </div>';
    }
    
    $atts = shortcode_atts(array('redirect_to' => ''), $atts, 'ruh_register');
    
    ob_start();
    ?>
    <div class="ruh-auth-form ruh-register-form">
        <div class="auth-form-header">
            <h2>Kayıt Ol</h2>
            <p>Topluluğumuza katılın! Yorumlarınızı paylaşın, tepkilerinizi gösterin ve seviye atlayarak rozet kazanın.</p>
        </div>
        
        <form id="ruh-register-form" method="post" class="auth-form">
            <?php wp_nonce_field('ruh_auth_nonce', 'nonce'); ?>
            <input type="hidden" name="action" value="ruh_register">
            <input type="hidden" name="redirect_to" value="<?php echo esc_url($atts['redirect_to'] ? $atts['redirect_to'] : get_permalink()); ?>">
            
            <div class="form-group">
                <label for="register_username">Kullanıcı Adı</label>
                <input type="text" id="register_username" name="username" required placeholder="kullaniciadi" pattern="[a-zA-Z0-9_]+" title="Sadece harf, rakam ve alt çizgi kullanabilirsiniz">
                <div class="field-icon">👤</div>
                <small>Sadece harf, rakam ve alt çizgi (_) kullanabilirsiniz.</small>
            </div>
            
            <div class="form-group">
                <label for="register_email">E-posta Adresi</label>
                <input type="email" id="register_email" name="email" required placeholder="email@example.com">
                <div class="field-icon">📧</div>
                <small>Geçerli bir e-posta adresi girin.</small>
            </div>
            
            <div class="form-group">
                <label for="register_password">Şifre</label>
                <input type="password" id="register_password" name="password" required minlength="6" placeholder="••••••••">
                <div class="field-icon">🔒</div>
                <button type="button" class="password-toggle" onclick="togglePassword('register_password')">👁️</button>
                <small>En az 6 karakter olmalıdır.</small>
                <div class="password-strength">
                    <div class="strength-bar"></div>
                    <span class="strength-text"></span>
                </div>
            </div>
            
            <div class="form-group">
                <label for="register_password_confirm">Şifre Tekrar</label>
                <input type="password" id="register_password_confirm" name="password_confirm" required placeholder="••••••••">
                <div class="field-icon">🔒</div>
                <button type="button" class="password-toggle" onclick="togglePassword('register_password_confirm')">👁️</button>
                <small>Şifrenizi tekrar girin.</small>
            </div>
            
            <div class="form-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms_accepted" required>
                    <span class="checkmark"></span>
                    <a href="#" class="terms-link">Kullanım koşullarını</a> okudum ve kabul ediyorum
                </label>
            </div>
            
            <button type="submit" class="ruh-submit">
                <span class="button-text">Kayıt Ol</span>
                <span class="button-loader" style="display:none;">⏳ Hesap oluşturuluyor...</span>
            </button>
            
            <div class="form-links">
                <?php 
                $options = get_option('ruh_comment_options', array());
                $login_page = isset($options['login_page_id']) ? $options['login_page_id'] : 0;
                if ($login_page && get_post($login_page)) : ?>
                    <a href="<?php echo get_permalink($login_page); ?>" class="auth-link">Zaten hesabınız var mı? Giriş yapın</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('ruh-register-form');
        if (!form) return;
        
        const passwordInput = document.getElementById('register_password');
        const confirmInput = document.getElementById('register_password_confirm');
        const strengthBar = document.querySelector('.strength-bar');
        const strengthText = document.querySelector('.strength-text');
        
        // Şifre gücü kontrolü
        if (passwordInput && strengthBar) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                let feedback = '';
                
                if (password.length >= 6) strength += 1;
                if (password.length >= 8) strength += 1;
                if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength += 1;
                if (/\d/.test(password)) strength += 1;
                if (/[^A-Za-z0-9]/.test(password)) strength += 1;
                
                const strengthPercentage = (strength / 5) * 100;
                strengthBar.style.width = strengthPercentage + '%';
                
                if (strength <= 1) {
                    strengthBar.className = 'strength-bar weak';
                    feedback = 'Zayıf';
                } else if (strength <= 2) {
                    strengthBar.className = 'strength-bar fair';
                    feedback = 'Orta';
                } else if (strength <= 3) {
                    strengthBar.className = 'strength-bar good';
                    feedback = 'İyi';
                } else {
                    strengthBar.className = 'strength-bar strong';
                    feedback = 'Güçlü';
                }
                
                strengthText.textContent = feedback;
            });
        }
        
        // Form gönderimi
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const password = this.querySelector('[name="password"]').value;
            const passwordConfirm = this.querySelector('[name="password_confirm"]').value;
            
            if (password !== passwordConfirm) {
                if (window.showNotification) {
                    window.showNotification('Şifreler eşleşmiyor.', 'error');
                } else {
                    alert('Şifreler eşleşmiyor.');
                }
                return;
            }
            
            const formData = new FormData(this);
            const submitBtn = this.querySelector('.ruh-submit');
            const buttonText = submitBtn.querySelector('.button-text');
            const buttonLoader = submitBtn.querySelector('.button-loader');
            
            buttonText.style.display = 'none';
            buttonLoader.style.display = 'inline';
            submitBtn.disabled = true;
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (window.showNotification) {
                        window.showNotification(data.data.message, 'success');
                    }
                    setTimeout(() => {
                        window.location.href = data.data.redirect;
                    }, 1500);
                } else {
                    if (window.showNotification) {
                        window.showNotification(data.data.message, 'error');
                    } else {
                        alert(data.data.message);
                    }
                }
            })
            .catch(error => {
                if (window.showNotification) {
                    window.showNotification('Bir hata oluştu. Lütfen tekrar deneyin.', 'error');
                } else {
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                }
            })
            .finally(() => {
                buttonText.style.display = 'inline';
                buttonLoader.style.display = 'none';
                submitBtn.disabled = false;
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

// Auth handler için profil güncelleme AJAX
add_action('wp_ajax_ruh_update_profile', 'ruh_handle_profile_update');
function ruh_handle_profile_update() {
    if (!wp_verify_nonce($_POST['nonce'], 'ruh_profile_nonce')) {
        wp_send_json_error(['message' => 'Güvenlik kontrolü başarısız.']);
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Bu işlem için giriş yapmalısınız.']);
    }
    
    $user_id = get_current_user_id();
    $action_type = sanitize_key($_POST['action_type']);
    
    switch ($action_type) {
        case 'update_profile':
            $display_name = sanitize_text_field($_POST['display_name']);
            $email = sanitize_email($_POST['email']);
            $description = sanitize_textarea_field($_POST['description']);
            
            if (empty($display_name) || !is_email($email)) {
                wp_send_json_error(['message' => 'Lütfen tüm gerekli alanları doğru şekilde doldurun.']);
            }
            
            // E-posta adresi başka kullanıcıda var mı kontrol et
            $existing_user = get_user_by('email', $email);
            if ($existing_user && $existing_user->ID !== $user_id) {
                wp_send_json_error(['message' => 'Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.']);
            }
            
            $result = wp_update_user([
                'ID' => $user_id,
                'display_name' => $display_name,
                'user_email' => $email,
                'description' => $description
            ]);
            
            if (is_wp_error($result)) {
                wp_send_json_error(['message' => $result->get_error_message()]);
            }
            
            wp_send_json_success(['message' => 'Profil bilgileri başarıyla güncellendi.']);
            break;
            
        case 'change_password':
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            
            if (empty($current_password) || empty($new_password)) {
                wp_send_json_error(['message' => 'Lütfen tüm şifre alanlarını doldurun.']);
            }
            
            if (strlen($new_password) < 6) {
                wp_send_json_error(['message' => 'Yeni şifre en az 6 karakter olmalıdır.']);
            }
            
            $user = wp_get_current_user();
            if (!wp_check_password($current_password, $user->user_pass, $user_id)) {
                wp_send_json_error(['message' => 'Mevcut şifre yanlış.']);
            }
            
            wp_set_password($new_password, $user_id);
            
            // Kullanıcıyı tekrar giriş yaptır
            wp_set_auth_cookie($user_id, true, is_ssl());
            
            wp_send_json_success(['message' => 'Şifre başarıyla güncellendi.']);
            break;
            
        case 'upload_avatar':
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                wp_send_json_error(['message' => 'Avatar yükleme hatası.']);
            }
            
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
                wp_send_json_error(['message' => 'Sadece JPG, PNG ve GIF dosyaları desteklenir.']);
            }
            
            if ($_FILES['avatar']['size'] > 2 * 1024 * 1024) { // 2MB
                wp_send_json_error(['message' => 'Dosya boyutu 2MB\'dan büyük olamaz.']);
            }
            
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            $upload = wp_handle_upload($_FILES['avatar'], ['test_form' => false]);
            
            if (isset($upload['error'])) {
                wp_send_json_error(['message' => $upload['error']]);
            }
            
            // Eski avatar'ı sil
            $old_avatar = get_user_meta($user_id, 'ruh_custom_avatar', true);
            if ($old_avatar) {
                wp_delete_file($old_avatar);
            }
            
            update_user_meta($user_id, 'ruh_custom_avatar', $upload['file']);
            
            wp_send_json_success([
                'message' => 'Avatar başarıyla güncellendi.',
                'avatar_url' => $upload['url']
            ]);
            break;
            
        default:
            wp_send_json_error(['message' => 'Geçersiz işlem.']);
    }
}

// CSS stilleri ekle
add_action('wp_head', function() {
    $current_post = get_post();
    if (!$current_post) return;
    
    $content = $current_post->post_content;
    if (has_shortcode($content, 'ruh_login') || 
        has_shortcode($content, 'ruh_register') || 
        has_shortcode($content, 'ruh_user_profile')) {
        ?>
        <style>
        :root {
            --ruh-primary: #005B43;
            --ruh-primary-hover: #007a5a;
            --ruh-primary-light: rgba(0, 91, 67, 0.1);
            --ruh-bg-card: #1a2332;
            --ruh-bg-secondary: #2d3e52;
            --ruh-border: #334155;
            --ruh-text-primary: #ffffff;
            --ruh-text-secondary: #e2e8f0;
            --ruh-text-muted: #94a3b8;
            --ruh-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --ruh-border-radius: 12px;
            --ruh-transition: all 0.2s ease;
        }
        
        .ruh-auth-form, .ruh-info-message, .ruh-error-message {
            max-width: 450px;
            margin: 2rem auto;
            padding: 2.5rem;
            background: var(--ruh-bg-card);
            border-radius: var(--ruh-border-radius);
            border: 1px solid var(--ruh-border);
            color: var(--ruh-text-primary);
            box-shadow: var(--ruh-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .ruh-auth-form::before, .ruh-info-message::before, .ruh-error-message::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--ruh-primary), #00b894);
        }
        
        .auth-form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .auth-form-header h2 {
            margin: 0 0 1rem;
            color: var(--ruh-primary);
            font-size: 1.8rem;
            font-weight: 700;
        }
        
        .auth-form-header p {
            margin: 0;
            color: var(--ruh-text-secondary);
            line-height: 1.6;
            opacity: 0.9;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--ruh-text-secondary);
            font-size: 0.9rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem 3rem 1rem 1rem;
            border: 2px solid var(--ruh-border);
            border-radius: 8px;
            background: var(--ruh-bg-secondary);
            color: var(--ruh-text-primary);
            font-size: 1rem;
            transition: var(--ruh-transition);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--ruh-primary);
            box-shadow: 0 0 0 3px var(--ruh-primary-light);
        }
        
        .field-icon {
            position: absolute;
            right: 1rem;
            top: 2.2rem;
            font-size: 1.2rem;
            opacity: 0.6;
        }
        
        .password-toggle {
            position: absolute;
            right: 0.5rem;
            top: 2.2rem;
            background: none;
            border: none;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.2rem;
            border-radius: 4px;
            transition: var(--ruh-transition);
        }
        
        .password-toggle:hover {
            background: var(--ruh-primary-light);
        }
        
        .form-group small {
            display: block;
            margin-top: 0.3rem;
            color: var(--ruh-text-muted);
            font-size: 0.8rem;
        }
        
        .checkbox-group {
            margin: 1.5rem 0;
        }
        
        .checkbox-label {
            display: flex !important;
            align-items: flex-start;
            gap: 0.8rem;
            cursor: pointer;
            line-height: 1.5;
        }
        
        .checkbox-label input {
            width: auto !important;
            margin: 0;
            opacity: 0;
            position: absolute;
        }
        
        .checkmark {
            width: 20px;
            height: 20px;
            background: var(--ruh-bg-secondary);
            border: 2px solid var(--ruh-border);
            border-radius: 4px;
            position: relative;
            flex-shrink: 0;
            margin-top: 2px;
            transition: var(--ruh-transition);
        }
        
        .checkbox-label input:checked + .checkmark {
            background: var(--ruh-primary);
            border-color: var(--ruh-primary);
        }
        
        .checkbox-label input:checked + .checkmark::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 12px;
            font-weight: bold;
            top: 1px;
            left: 4px;
        }
        
        .password-strength {
            margin-top: 0.5rem;
        }
        
        .strength-bar {
            height: 4px;
            border-radius: 2px;
            transition: var(--ruh-transition);
            width: 0%;
        }
        
        .strength-bar.weak { background: #ef4444; }
        .strength-bar.fair { background: #f59e0b; }
        .strength-bar.good { background: #10b981; }
        .strength-bar.strong { background: #059669; }
        
        .strength-text {
            font-size: 0.8rem;
            margin-top: 0.25rem;
            display: block;
            font-weight: 600;
        }
        
        .ruh-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--ruh-primary), #007a5a);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--ruh-transition);
            position: relative;
            overflow: hidden;
        }
        
        .ruh-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .ruh-submit:hover::before {
            left: 100%;
        }
        
        .ruh-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 91, 67, 0.4);
        }
        
        .ruh-submit:disabled {
            background: var(--ruh-text-muted);
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .form-links {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--ruh-border);
        }
        
        .auth-link, .terms-link {
            color: var(--ruh-primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--ruh-transition);
            display: inline-block;
            margin: 0.25rem 0;
        }
        
        .auth-link:hover, .terms-link:hover {
            color: var(--ruh-primary-hover);
            text-decoration: underline;
        }
        
        .ruh-info-message, .ruh-error-message {
            text-align: center;
        }
        
        .ruh-info-message h3, .ruh-error-message h3 {
            color: var(--ruh-primary);
            margin: 0 0 1rem;
        }
        
        .ruh-error-message h3 {
            color: #ef4444;
        }
        
        .ruh-action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .ruh-button {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--ruh-transition);
            display: inline-block;
        }
        
        .ruh-button.primary {
            background: var(--ruh-primary);
            color: white;
        }
        
        .ruh-button.primary:hover {
            background: var(--ruh-primary-hover);
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
        }
        
        .ruh-button.secondary {
            background: transparent;
            color: var(--ruh-text-secondary);
            border: 1px solid var(--ruh-border);
        }
        
        .ruh-button.secondary:hover {
            background: var(--ruh-bg-secondary);
            color: var(--ruh-text-primary);
            text-decoration: none;
        }
        
        @media (max-width: 480px) {
            .ruh-auth-form, .ruh-info-message, .ruh-error-message {
                margin: 1rem;
                padding: 2rem 1.5rem;
            }
            
            .ruh-action-buttons {
                flex-direction: column;
            }
        }
        </style>
        <?php
    }
});