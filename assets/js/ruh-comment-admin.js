jQuery(document).ready(function($) {
    // Renk seçiciyi başlat - Modern renk paleti
    $('.ruh-color-picker').wpColorPicker({
        palettes: [
            '#667eea', '#764ba2', '#a855f7', '#ec4899',
            '#3b82f6', '#06b6d4', '#10b981', '#f59e0b',
            '#ef4444', '#8b5cf6'
        ]
    });

    // Rozet şablonu seçimi - Modern animasyonlarla
    function setupTemplateSelector(containerClass, hiddenInputId) {
        var container = $('.' + containerClass);
        
        // Click handler
        container.on('click', '.template-item, .template-item-auto', function() {
            var $this = $(this);
            
            // Remove selected from all
            container.find('.template-item, .template-item-auto').removeClass('selected');
            
            // Add selected to clicked item with animation
            $this.addClass('selected');
            
            // Animate selection
            $this.css('transform', 'scale(1.2) rotate(10deg)');
            setTimeout(function() {
                $this.css('transform', '');
            }, 300);
            
            // Update hidden input
            $('#' + hiddenInputId).val($this.data('template'));
            
            // Show selection feedback
            showFeedback('Şablon seçildi: ' + $this.attr('title'));
        });
        
        // İlk template'i seç
        var firstItem = container.find('.template-item, .template-item-auto').first();
        firstItem.addClass('selected');
        $('#' + hiddenInputId).val(firstItem.data('template'));
    }
    
    setupTemplateSelector('badge-templates', 'selected_badge_template');
    setupTemplateSelector('badge-templates-auto', 'selected_auto_badge_template');
    
    // Feedback mesajı göster
    function showFeedback(message) {
        var $feedback = $('<div class="admin-feedback">' + message + '</div>');
        $feedback.css({
            position: 'fixed',
            bottom: '20px',
            right: '20px',
            background: 'linear-gradient(135deg, #667eea, #764ba2)',
            color: '#ffffff',
            padding: '12px 24px',
            borderRadius: '25px',
            boxShadow: '0 4px 12px rgba(102, 126, 234, 0.4)',
            zIndex: 10000,
            fontSize: '14px',
            fontWeight: '600',
            animation: 'slideInRight 0.3s ease-out'
        });
        
        $('body').append($feedback);
        
        setTimeout(function() {
            $feedback.fadeOut(300, function() {
                $(this).remove();
            });
        }, 2000);
    }
    
    // Form validation
    $('.badge-form').on('submit', function(e) {
        var $form = $(this);
        var badgeName = $form.find('input[name*="badge_name"]').val();
        
        if (!badgeName || badgeName.trim() === '') {
            e.preventDefault();
            alert('Lütfen rozet adı girin!');
            return false;
        }
        
        // Loading state
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.text();
        $submitBtn.text('İşleniyor...').prop('disabled', true);
    });

    // Yorum Yönetimi - Hızlı Düzenle
    $('#comments-filter').on('click', '.quick-edit-comment', function(e) {
        e.preventDefault();
        var commentId = $(this).data('comment-id');
        $('#comment-text-' + commentId).hide();
        $('#edit-comment-' + commentId).show();
    });

    $('#comments-filter').on('click', '.cancel-edit-comment', function() {
        var wrapper = $(this).closest('div');
        wrapper.hide();
        wrapper.prev().show();
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
                $('#comment-text-' + commentId).text(response.data.content).show();
                $('#edit-comment-' + commentId).hide();
            } else {
                alert('Hata: ' + response.data.message);
            }
        }).always(function() {
            button.prop('disabled', false).text('Kaydet');
        });
    });
});