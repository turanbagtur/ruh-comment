jQuery(document).ready(function($) {
    'use strict';
    
    if (!window.ruh_comment_ajax) {
        console.error('RuhComments: AJAX config yok');
        return;
    }
    
    
    // ========== AUTH MODAL SYSTEM (Her zaman çalışır) ==========
    const RuhAuth = {
        init: function() {
            this.setupModalToggle();
            this.setupTabSwitching();
            this.setupLoginForm();
            this.setupRegisterForm();
            this.setupModalClose();
        },
        
        setupModalToggle: function() {
            const self = this;
            
            $(document).on('click', '#ruh-open-login', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.openModal('login');
            });
            
            $(document).on('click', '#ruh-open-register', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.openModal('register');
            });
        },
        
        openModal: function(tab) {
            $('#ruh-auth-modal').css('display', 'flex');
            this.switchTab(tab);
        },
        
        closeModal: function() {
            $('#ruh-auth-modal').hide();
            this.clearMessages();
            this.clearForms();
        },
        
        setupTabSwitching: function() {
            const self = this;
            
            $(document).on('click', '.ruh-auth-tab', function() {
                const tab = $(this).data('tab');
                self.switchTab(tab);
            });
        },
        
        switchTab: function(tab) {
            $('.ruh-auth-tab').removeClass('active');
            $('.ruh-auth-tab[data-tab="' + tab + '"]').addClass('active');
            
            if (tab === 'login') {
                $('#ruh-login-form').show();
                $('#ruh-register-form').hide();
            } else {
                $('#ruh-login-form').hide();
                $('#ruh-register-form').show();
            }
            
            this.clearMessages();
        },
        
        setupModalClose: function() {
            const self = this;
            
            $(document).on('click', '#ruh-auth-modal .ruh-modal-close', function(e) {
                e.preventDefault();
                self.closeModal();
            });
            
            $(document).on('click', '#ruh-auth-modal', function(e) {
                if (e.target === this) {
                    self.closeModal();
                }
            });
            
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#ruh-auth-modal').is(':visible')) {
                    self.closeModal();
                }
            });
        },
        
        setupLoginForm: function() {
            const self = this;
            
            $(document).on('submit', '#ruh-login-form-el', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $btn = $form.find('.ruh-auth-submit');
                const $msg = $('#ruh-login-message');
                
                const username = $('#ruh-login-user').val().trim();
                const password = $('#ruh-login-pass').val();
                const remember = $form.find('input[name="rememberme"]').is(':checked');
                const nonce = $form.find('#ruh_auth_nonce_field').val();
                
                if (!username || !password) {
                    self.showMessage($msg, 'error', 'Tüm alanları doldurun.');
                    return;
                }
                
                self.setLoading($btn, true);
                
                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_login',
                    username: username,
                    password: password,
                    remember: remember,
                    nonce: nonce
                })
                .done(function(response) {
                    if (response.success) {
                        self.showMessage($msg, 'success', response.data.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        self.showMessage($msg, 'error', response.data.message || 'Hata oluştu.');
                    }
                })
                .fail(function() {
                    self.showMessage($msg, 'error', ruh_comment_ajax.texts.connection_error || 'Connection error.');
                })
                .always(function() {
                    self.setLoading($btn, false);
                });
            });
        },
        
        setupRegisterForm: function() {
            const self = this;
            
            $(document).on('submit', '#ruh-register-form-el', function(e) {
                e.preventDefault();
                
                const $form = $(this);
                const $btn = $form.find('.ruh-auth-submit');
                const $msg = $('#ruh-register-message');
                
                const username = $('#ruh-reg-user').val().trim();
                const email = $('#ruh-reg-email').val().trim();
                const password = $('#ruh-reg-pass').val();
                const passwordConfirm = $('#ruh-reg-pass-confirm').val();
                const nonce = $form.find('#ruh_register_nonce_field').val();
                
                if (!username || !email || !password || !passwordConfirm) {
                    self.showMessage($msg, 'error', 'Tüm alanları doldurun.');
                    return;
                }
                
                if (username.length < 3) {
                    self.showMessage($msg, 'error', 'Kullanıcı adı en az 3 karakter olmalı.');
                    return;
                }
                
                if (password.length < 8) {
                    self.showMessage($msg, 'error', 'Şifre en az 8 karakter olmalı.');
                    return;
                }
                
                if (password !== passwordConfirm) {
                    self.showMessage($msg, 'error', 'Şifreler eşleşmıyor.');
                    return;
                }
                
                self.setLoading($btn, true);
                
                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_register',
                    username: username,
                    email: email,
                    password: password,
                    nonce: nonce
                })
                .done(function(response) {
                    if (response.success) {
                        self.showMessage($msg, 'success', response.data.message);
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        self.showMessage($msg, 'error', response.data.message || 'Hata oluştu.');
                    }
                })
                .fail(function() {
                    self.showMessage($msg, 'error', ruh_comment_ajax.texts.connection_error || 'Connection error.');
                })
                .always(function() {
                    self.setLoading($btn, false);
                });
            });
        },
        
        showMessage: function($el, type, msg) {
            $el.removeClass('error success').addClass(type).text(msg).show();
        },
        
        clearMessages: function() {
            $('.ruh-form-message').removeClass('error success').text('').hide();
        },
        
        clearForms: function() {
            $('#ruh-login-form-el')[0]?.reset();
            $('#ruh-register-form-el')[0]?.reset();
        },
        
        setLoading: function($btn, isLoading) {
            if (isLoading) {
                $btn.prop('disabled', true);
                $btn.find('.btn-text').hide();
                $btn.find('.btn-loader').show();
            } else {
                $btn.prop('disabled', false);
                $btn.find('.btn-text').show();
                $btn.find('.btn-loader').hide();
            }
        }
    };
    
    function ruhCloseMoreMenus() {
        $('.more-dropdown.show').removeClass('show').each(function() {
            const $d = $(this);
            const origin = $d.data('ruh-origin');
            if (origin && origin.length) {
                $d.appendTo(origin).css({ top: '', left: '', right: '', position: '', zIndex: '', display: '', visibility: '' });
            }
        });
    }

    // Auth sistemı her zaman başlatilsin
    RuhAuth.init();
    
    // Post ID yoksa veya 0 ise yorum sistemini başlatma
    if (!ruh_comment_ajax.post_id || ruh_comment_ajax.post_id == 0) {
        console.log('RuhComments: Post ID yok, yorum sistemı devre disi');
        return;
    }
    
    const RuhComments = {
        currentSort: 'newest',
        currentPage: 1,
        
        init: function() {
            this.setupFormSubmıssion();
            this.setupReactions();
            this.setupCommentActions();
            this.setupSorting();
            this.setupToolbar();
            this.setupCharCounter();
            this.setupGifModal();
            this.setupSpoilers();
            this.setupCommentRules();
            this.setupPinning();
            this.loadInitialData();
            this.loadComments();
        },
        
        // FORM GONDERME
        setupFormSubmıssion: function() {
            const self = this;
            
            $('#commentform').on('submit', function(e) {
                e.preventDefault();
                
                const $textarea = $('#comment');
                const $submitBtn = $('#submit');
                const content = $textarea.val().trim();
                const parentId = $('#comment_parent').val() || 0;
                
                if (!content) {
                    self.showMessage(ruh_comment_ajax.texts.comment_empty || 'Yorum boş olamaz.', 'error');
                    return;
                }
                
                if (!ruh_comment_ajax.logged_in) {
                    self.showMessage(ruh_comment_ajax.texts.login_required || 'Giriş yapmalısınız.', 'error');
                    return;
                }
                
                $submitBtn.prop('disabled', true).find('svg').hide();
                $submitBtn.prepend('<span class="ruh-btn-loading">...</span>');
                
                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_submit_comment',
                    nonce: ruh_comment_ajax.nonce,
                    comment: content,
                    post_id: ruh_comment_ajax.post_id,
                    comment_post_ID: ruh_comment_ajax.post_id,
                    comment_parent: parentId,
                    current_url: window.location.href,
                    ruh_honeypot: $('input[name="ruh_honeypot"]').val() || ''
                })
                .done(function(response) {
                    if (response.success) {
                        if (response.data.html) {
                            if (parseInt(parentId) > 0) {
                                const $parentReplies = $('#replies-' + parentId);
                                $parentReplies.append(response.data.html);
                                $parentReplies.removeClass('is-collapsed').addClass('is-open').removeAttr('hidden').css('display', '');
                            } else {
                                // Ana yorum - sabitlenmiş yorumların altına ekle
                                const $pinnedComments = $('.comment-list > .comment-item.pinned');
                                if ($pinnedComments.length > 0) {
                                    $pinnedComments.last().after(response.data.html);
                                } else {
                                    $('.comment-list').prepend(response.data.html);
                                }
                            }
                            $('#no-comments').hide();
                        }
                        
                        // Formu temızle
                        $textarea.val('');
                        $('#char-count').text('0');
                        $('#comment_parent').val('0');
                        $('#reply-indicator').hide();
                        
                        // Sayacı güncelle
                        const currentCount = parseInt($('.comment-count').text()) || 0;
                        $('.comment-count').text(currentCount + 1);
                        
                        self.showMessage(ruh_comment_ajax.texts.comment_sent || 'Yorum gönderildi!', 'success');
                    } else {
                        self.showMessage(response.data?.message || ruh_comment_ajax.texts.error || 'Hata oluştu.', 'error');
                    }
                })
                .fail(function() {
                    self.showMessage(ruh_comment_ajax.texts.network_error || 'Network error.', 'error');
                })
                .always(function() {
                    $submitBtn.prop('disabled', false).find('.ruh-btn-loading').remove();
                    $submitBtn.find('svg').show();
                });
            });
            
            // Yanıt iptal
            $('#cancel-reply').on('click', function() {
                $('#comment_parent').val('0');
                $('#reply-indicator').hide();
            });
        },
        
        // TEPKILER - Optimıze
        setupReactions: function() {
            const self = this;
            let isProcessing = false;
            
            $(document).on('click', '.content-reaction-btn', function(e) {
                e.preventDefault();
                
                if (isProcessing) return;
                
                const $btn = $(this);
                const reaction = $btn.data('reaction');
                
                if (!reaction) return;
                
                isProcessing = true;
                
                const wasActive = $btn.hasClass('active');
                $('.content-reaction-btn').removeClass('active');
                $('.reaction-item').removeClass('is-active');
                if (!wasActive) {
                    $btn.addClass('active');
                    $btn.closest('.reaction-item').addClass('is-active');
                    self.animateReaction($btn);
                }
                
                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_handle_reaction',
                    nonce: ruh_comment_ajax.nonce,
                    post_id: ruh_comment_ajax.post_id,
                    reaction: reaction
                })
                .done(function(response) {
                    if (response.success) {
                        self.updateReactionCounts(response.data.counts);
                    }
                })
                .always(function() {
                    isProcessing = false;
                });
            });
        },
        
        updateReactionCounts: function(counts) {
            let total = 0;
            $('.content-reaction-btn').each(function() {
                const $this = $(this);
                const $item = $this.closest('.reaction-item');
                const reaction = $this.data('reaction');
                const count = counts && counts[reaction] ? parseInt(counts[reaction].count) : 0;
                const $countEl = $item.find('.reaction-count');
                const prev = parseInt($countEl.text()) || 0;
                $countEl.text(count);
                if (count !== prev) {
                    $countEl.addClass('count-bump');
                    setTimeout(function() { $countEl.removeClass('count-bump'); }, 280);
                }
                $item.toggleClass('has-votes', count > 0);
                total += count;
            });
            $('#total-reaction-count').text(total);
        },
        
        // Tepki animasyonu
        animateReaction: function($btn) {
            const $emoji = $btn.find('.reaction-emoji');
            
            // Pop efekti
            $emoji.addClass('reaction-pop');
            setTimeout(() => $emoji.removeClass('reaction-pop'), 400);
            
            // Parçacık efekti
            const emoji = $emoji.text();
            for (let i = 0; i < 6; i++) {
                const $particle = $('<span class="reaction-particle">' + emoji + '</span>');
                const angle = (i * 60) * (Math.PI / 180);
                const distance = 40 + Math.random() * 20;
                const x = Math.cos(angle) * distance;
                const y = Math.sin(angle) * distance;
                
                $particle.css({
                    '--x': x + 'px',
                    '--y': y + 'px'
                });
                
                $btn.append($particle);
                setTimeout(() => $particle.remove(), 600);
            }
        },
        
        // YORUM AKSIYONLARI
        setupCommentActions: function() {
            const self = this;
            
            // Beğeni
            $(document).on('click', '.action-btn.like-btn, .like-btn', function(e) {
                e.preventDefault();
                
                if (!ruh_comment_ajax.logged_in) {
                    self.showMessage(ruh_comment_ajax.texts.login_required || 'Giriş yapmalısınız.', 'error');
                    return;
                }
                
                const $btn = $(this);
                const $comment = $btn.closest('.comment-item');
                const commentId = $btn.data('comment-id');
                
                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_handle_like',
                    nonce: ruh_comment_ajax.nonce,
                    comment_id: commentId
                })
                .done(function(response) {
                    if (response.success) {
                        $btn.find('.like-count').text(response.data.likes);
                        $comment.find('.dislike-btn .dislike-count').text(response.data.dislikes);
                        
                        if (response.data.user_vote === 'liked') {
                            $btn.addClass('liked');
                            $comment.find('.dislike-btn').removeClass('disliked');
                        } else {
                            $btn.removeClass('liked');
                        }
                    }
                });
            });
            
            // Beğenmeme
            $(document).on('click', '.action-btn.dislike-btn, .dislike-btn', function(e) {
                e.preventDefault();
                
                if (!ruh_comment_ajax.logged_in) {
                    self.showMessage(ruh_comment_ajax.texts.login_required || 'Giriş yapmalısınız.', 'error');
                    return;
                }
                
                const $btn = $(this);
                const $comment = $btn.closest('.comment-item');
                const commentId = $btn.data('comment-id');
                
                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_handle_dislike',
                    nonce: ruh_comment_ajax.nonce,
                    comment_id: commentId
                })
                .done(function(response) {
                    if (response.success) {
                        $btn.find('.dislike-count').text(response.data.dislikes);
                        $comment.find('.like-btn .like-count').text(response.data.likes);
                        
                        if (response.data.user_vote === 'disliked') {
                            $btn.addClass('disliked');
                            $comment.find('.like-btn').removeClass('liked');
                        } else {
                            $btn.removeClass('disliked');
                        }
                    }
                });
            });
            
            // Yanıtlama - Inline form ac
            $(document).on('click', '.action-btn.reply-btn, .reply-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (!ruh_comment_ajax.logged_in) {
                    self.showMessage(ruh_comment_ajax.texts.login_required || 'Giriş yapmalısınız.', 'error');
                    return;
                }
                
                const commentId = $(this).data('comment-id');
                const authorName = $('<div>').text($(this).data('author') || '').html();
                const $comment = $('#comment-' + commentId);
                
                // Mevcut inline formlari kaldır
                $('.inline-reply-form').remove();
                
                // Inline form oluştür - toolbar ile
                const inlineForm = `
                    <div class="inline-reply-form" id="reply-form-${commentId}">
                        <div class="reply-form-header">
                            <span>@${authorName} kullanıcısına yanıt</span>
                            <button type="button" class="cancel-inline-reply">&times;</button>
                        </div>
                        <div class="inline-toolbar">
                            <button type="button" class="inline-toolbar-btn" data-action="bold" title="Kalın">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M13.5,15.5H10V12.5H13.5A1.5,1.5 0 0,1 15,14A1.5,1.5 0 0,1 13.5,15.5M10,6.5H13A1.5,1.5 0 0,1 14.5,8A1.5,1.5 0 0,1 13,9.5H10M15.6,10.79C16.57,10.11 17.25,9 17.25,8C17.25,5.74 15.5,4 13.25,4H7V18H14.04C16.14,18 17.75,16.3 17.75,14.21C17.75,12.69 16.89,11.39 15.6,10.79Z"/></svg>
                            </button>
                            <button type="button" class="inline-toolbar-btn" data-action="italic" title="İtalik">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M10,4V7H12.21L8.79,15H6V18H14V15H11.79L15.21,7H18V4H10Z"/></svg>
                            </button>
                            <button type="button" class="inline-toolbar-btn" data-action="spoiler" title="Spoiler">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z"/></svg>
                            </button>
                            <button type="button" class="inline-toolbar-btn inline-gif-btn" data-action="gif" data-form-id="${commentId}" title="GIF">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M11.5,9H13V15H11.5V9M9,9V15H6A1.5,1.5 0 0,1 4.5,13.5V10.5A1.5,1.5 0 0,1 6,9H9M7.5,10.5H6V13.5H7.5V10.5M19,10.5V9H14.5V15H16V13H18V11.5H16V10.5H19Z"/></svg>
                            </button>
                        </div>
                        <textarea class="inline-reply-textarea" id="inline-textarea-${commentId}" placeholder="${ruh_comment_ajax.texts.reply_placeholder || 'Yanıtınızı yazın...'}" maxlength="${ruh_comment_ajax.max_comment_length || 1000}"></textarea>
                        <div class="reply-form-actions">
                            <button type="button" class="submit-inline-reply" data-comment-id="${commentId}">${ruh_comment_ajax.texts.send || 'Send'}</button>
                        </div>
                    </div>
                `;
                
                $comment.children('.comment-body').first().after(inlineForm);
                $comment.children('.inline-reply-form').find('.inline-reply-textarea').focus();
            });
            
            // Inline yanıt iptal
            $(document).on('click', '.cancel-inline-reply', function() {
                $(this).closest('.inline-reply-form').remove();
            });
            
            // Inline yanıt gönder
            $(document).on('click', '.submit-inline-reply', function() {
                const $form = $(this).closest('.inline-reply-form');
                const commentId = $(this).data('comment-id');
                const content = $form.find('.inline-reply-textarea').val().trim();
                
                if (!content) {
                    self.showMessage(ruh_comment_ajax.texts.reply_empty || 'Yanıt boş olamaz.', 'error');
                    return;
                }
                
                const $btn = $(this);
                $btn.prop('disabled', true).text(ruh_comment_ajax.texts.sending || 'Sending...');
                
                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_submit_comment',
                    nonce: ruh_comment_ajax.nonce,
                    comment: content,
                    post_id: ruh_comment_ajax.post_id,
                    comment_parent: commentId,
                    current_url: window.location.href
                })
                .done(function(response) {
                    if (response.success && response.data.html) {
                        const $repliesContainer = $('#replies-' + commentId);
                        $repliesContainer.append(response.data.html);
                        $repliesContainer.removeClass('is-collapsed').addClass('is-open').removeAttr('hidden').css('display', '');
                        $repliesContainer.data('loaded', true);
                        $form.remove();
                        self.showMessage(ruh_comment_ajax.texts.reply_sent || 'Yanıt gönderildi!', 'success');
                        const $count = $('.comment-count');
                        $count.text(parseInt($count.text()) + 1);
                        
                        // Yanıt sayısını güncelle veya toggle butonu oluştur
                        const $comment = $('#comment-' + commentId);
                        let $toggleBtn = $comment.find('> .comment-body .replies-toggle-btn').first();
                        if (!$toggleBtn.length) {
                            $toggleBtn = $comment.children('.replies-toggle-container').find('.replies-toggle-btn');
                        }
                        
                         if ($toggleBtn.length) {
                            const currentCount = parseInt($toggleBtn.data('replies-count')) || 0;
                            const nextCount = currentCount + 1;
                            $toggleBtn.data('replies-count', nextCount);
                            $toggleBtn.attr('data-replies-count', nextCount);
                            const hideLabel = self.getReplyToggleLabel(nextCount, true);
                            const showLabel = self.getReplyToggleLabel(nextCount, false);
                            $toggleBtn.attr('data-hide-text', hideLabel);
                            $toggleBtn.attr('data-show-text', showLabel);
                            $toggleBtn.find('.toggle-text').text(hideLabel);
                            $toggleBtn.addClass('expanded');
                        } else {
                            const hideLabel = self.getReplyToggleLabel(1, true);
                            const showLabel = self.getReplyToggleLabel(1, false);
                            const toggleBtn = `<button type="button" class="replies-toggle-btn expanded" 
                                        data-comment-id="${commentId}" 
                                        data-replies-count="1" 
                                        data-parent-id="${commentId}"
                                        data-show-text="${showLabel}"
                                        data-hide-text="${hideLabel}">
                                    <svg class="toggle-icon" viewBox="0 0 24 24" width="16" height="16">
                                        <path fill="currentColor" d="M7.41,8.58L12,13.17L16.59,8.58L18,10L12,16L6,10L7.41,8.58Z"/>
                                    </svg>
                                    <span class="toggle-text">${hideLabel}</span>
                                </button>`;
                            
                            // Önce comment-interaction-buttons içine eklemeyi dene
                            const $actions = $comment.find('> .comment-body .comment-actions').first();
                            if ($actions.length) {
                                $actions.after('<div class="replies-toggle-container">' + toggleBtn + '</div>');
                            } else {
                                $repliesContainer.before('<div class="replies-toggle-container">' + toggleBtn + '</div>');
                            }
                        }
                    } else {
                        self.showMessage(response.data?.message || ruh_comment_ajax.texts.error || 'Hata.', 'error');
                        $btn.prop('disabled', false).text(ruh_comment_ajax.texts.send || 'Gönder');
                    }
                })
                .fail(function() {
                    self.showMessage(ruh_comment_ajax.texts.network_error || 'Network error.', 'error');
                    $btn.prop('disabled', false).text(ruh_comment_ajax.texts.send || 'Send');
                });
            });
            
            // Düzenleme
            $(document).on('click', '.action-btn.edit-btn, .edit-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const commentId = $(this).data('comment-id');
                ruhCloseMoreMenus();
                self.editComment(commentId);
            });
            
            // Silme - Modal aç
            $(document).on('click', '.action-btn.delete-btn, .delete-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const commentId = $(this).data('comment-id');
                $('#delete-comment-id').val(commentId);
                $('#delete-confirm-modal').show();
                ruhCloseMoreMenus();
            });
            
            // Silme Modal - İptal
            $(document).on('click', '#delete-cancel-btn, #delete-confirm-modal .ruh-modal-close', function(e) {
                e.preventDefault();
                $('#delete-confirm-modal').hide();
            });
            
            // Silme Modal - Dışarı tıklama
            $(document).on('click', '#delete-confirm-modal', function(e) {
                if ($(e.target).is('#delete-confirm-modal')) {
                    $('#delete-confirm-modal').hide();
                }
            });
            
            // Silme Modal - Onayla
            $(document).on('click', '#delete-confirm-btn', function(e) {
                e.preventDefault();
                const commentId = $('#delete-comment-id').val();
                $('#delete-confirm-modal').hide();
                self.deleteComment(commentId);
            });
            
            $(document).on('click', '.replies-toggle-btn', function(e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                
                const $btn = $(this);
                const parentId = $btn.data('parent-id') || $btn.data('comment-id');
                const $container = $('#replies-' + parentId);
                const count = parseInt($btn.attr('data-replies-count') || $btn.data('replies-count') || 0, 10);
                const isExpanded = $btn.hasClass('expanded');
                
                if (isExpanded) {
                    $container.addClass('is-collapsed').removeClass('is-open');
                    $container.attr('hidden', 'hidden').css('display', '');
                    $btn.removeClass('expanded');
                    $btn.find('.toggle-text').text($btn.attr('data-show-text') || self.getReplyToggleLabel(count, false));
                    $btn.find('.toggle-icon').css('transform', 'rotate(0deg)');
                    return;
                }
                
                const isLoaded = $container.data('loaded') === true || $container.data('loaded') === 'true';
                const hasContent = $container.children().length > 0;
                
                const openReplies = function() {
                    $container.removeClass('is-collapsed').addClass('is-open').removeAttr('hidden').css('display', '');
                    $btn.addClass('expanded');
                    $btn.find('.toggle-text').text($btn.attr('data-hide-text') || self.getReplyToggleLabel(count, true));
                    $btn.find('.toggle-icon').css('transform', 'rotate(180deg)');
                };
                
                if (!isLoaded && !hasContent) {
                    $btn.prop('disabled', true);
                    $btn.find('.toggle-text').text(ruh_comment_ajax.texts.sending || 'Loading...');
                    
                    $.post(ruh_comment_ajax.ajax_url, {
                        action: 'ruh_load_replies',
                        nonce: ruh_comment_ajax.nonce,
                        parent_id: parentId
                    })
                    .done(function(response) {
                        if (response.success && response.data.html) {
                            $container.html(response.data.html);
                            $container.data('loaded', true);
                            const newCount = parseInt(response.data.count || count, 10);
                            if (newCount) {
                                $btn.data('replies-count', newCount);
                                $btn.attr('data-replies-count', newCount);
                                $btn.attr('data-show-text', self.getReplyToggleLabel(newCount, false));
                                $btn.attr('data-hide-text', self.getReplyToggleLabel(newCount, true));
                            }
                        }
                        openReplies();
                    })
                    .fail(function() {
                        self.showMessage(ruh_comment_ajax.texts.reply_failed || 'Yanıtlar yüklenemedi.', 'error');
                        $btn.find('.toggle-text').text($btn.attr('data-show-text') || self.getReplyToggleLabel(count, false));
                    })
                    .always(function() {
                        $btn.prop('disabled', false);
                    });
                } else {
                    openReplies();
                }
            });
            
            // 3 Nokta Menü - body'ye taşı (kart overflow/stacking menüyü kesmesin)
            $(document).on('click', '.more-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $btn = $(this);
                let $dropdown = $btn.siblings('.more-dropdown');
                if (!$dropdown.length) {
                    $dropdown = $('.more-dropdown').filter(function() {
                        return $(this).data('ruh-origin') && $(this).data('ruh-origin')[0] === $btn.parent()[0];
                    });
                }
                const isOpen = $dropdown.hasClass('show') && $dropdown.parent().is('body');
                
                ruhCloseMoreMenus();
                
                if (!isOpen && $dropdown.length) {
                    if (!$dropdown.data('ruh-origin')) {
                        $dropdown.data('ruh-origin', $btn.parent());
                    }
                    const btnRect = this.getBoundingClientRect();
                    const dropWidth = Math.min(180, window.innerWidth - 16);
                    let left = btnRect.right - dropWidth;
                    let top = btnRect.bottom + 6;
                    
                    if (left < 8) left = 8;
                    if (left + dropWidth > window.innerWidth - 8) left = window.innerWidth - dropWidth - 8;
                    
                    $dropdown.appendTo('body').css({
                        position: 'fixed',
                        top: top + 'px',
                        left: left + 'px',
                        right: 'auto',
                        zIndex: 2147483646,
                        visibility: 'hidden',
                        display: 'block'
                    });
                    const dropHeight = $dropdown.outerHeight() || 180;
                    if (top + dropHeight > window.innerHeight - 8) {
                        top = Math.max(8, btnRect.top - dropHeight - 6);
                    }
                    $dropdown.css({ top: top + 'px', visibility: '' }).addClass('show');
                }
            });
            
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.comment-more-menu, .more-dropdown').length) {
                    ruhCloseMoreMenus();
                }
            });
            
            $(window).on('scroll resize', function() {
                ruhCloseMoreMenus();
            });
            
            // Şikayet - Modal ac
            $(document).on('click', '.report-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const commentId = $(this).data('comment-id');
                $('#report-comment-id').val(commentId);
                $('#report-type').val('');
                $('#report-reason').val('');
                $('#report-modal').show();
                ruhCloseMoreMenus();
            });
            
            // Report modal kapat (X butonu)
            $(document).on('click', '#report-modal .ruh-modal-close', function(e) {
                e.preventDefault();
                $('#report-modal').hide();
            });
            
            // Report modal dışına tıklayınca kapat
            $(document).on('click', '#report-modal', function(e) {
                if (e.target === this) {
                    $('#report-modal').hide();
                }
            });
            
            // Şikayet formu gönder
            $('#report-form').on('submit', function(e) {
                e.preventDefault();
                const commentId = $('#report-comment-id').val();
                const reportType = $('#report-type').val();
                const reason = $('#report-reason').val();
                
                if (!reportType) {
                    self.showMessage(ruh_comment_ajax.texts.report_type_required || 'Şikayet türü seçin.', 'error');
                    return;
                }
                
                self.reportComment(commentId, reportType, reason);
                $('#report-modal').hide();
            });
        },
        
        reportComment: function(commentId, reportType, reason) {
            const self = this;
            const fullReason = reportType + (reason ? ': ' + reason : '');
            
            $.post(ruh_comment_ajax.ajax_url, {
                action: 'ruh_flag_comment',
                nonce: ruh_comment_ajax.nonce,
                comment_id: commentId,
                reason: fullReason
            })
            .done(function(response) {
                if (response.success) {
                    self.showMessage(ruh_comment_ajax.texts.report_sent || 'Şikayet gönderildi. Teşekkürler!', 'success');
                    
                    // Yorum moderasyona alındıysa DOM'dan kaldır
                    if (response.data?.hidden && response.data?.comment_id) {
                        const $comment = $('#comment-' + response.data.comment_id);
                        if ($comment.length) {
                            $comment.slideUp(300, function() {
                                $(this).remove();
                                // Yorum sayısıni güncelle
                                const currentCount = parseInt($('.comment-count').text()) || 0;
                                if (currentCount > 0) {
                                    $('.comment-count').text(currentCount - 1);
                                }
                            });
                        }
                    }
                } else {
                    self.showMessage(response.data?.message || 'Hata oluştu.', 'error');
                }
            })
            .fail(function() {
                self.showMessage(ruh_comment_ajax.texts.network_error || 'Network error.', 'error');
            });
        },
        
        editComment: function(commentId) {
            const self = this;
            const $comment = $('#comment-' + commentId);
            const $textDiv = $comment.find('.comment-text').first();
            
            if ($comment.find('.edit-form').length) return;
            
            const currentText = $textDiv.text().trim();
            
            const editForm = `
                <div class="edit-form">
                    <textarea>${currentText}</textarea>
                    <div class="edit-form-actions">
                        <button type="button" class="edit-cancel-btn">İptal</button>
                        <button type="button" class="edit-save-btn" data-comment-id="${commentId}">Kaydet</button>
                    </div>
                </div>
            `;
            
            $textDiv.hide().after(editForm);
            $comment.find('.edit-form textarea').focus();
            
            // İptal
            $comment.find('.edit-cancel-btn').on('click', function() {
                $comment.find('.edit-form').remove();
                $textDiv.show();
            });
            
            // Kaydet
            $comment.find('.edit-save-btn').on('click', function() {
                const content = $comment.find('.edit-form textarea').val().trim();
                
                if (!content) return;
                
                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_edit_comment',
                    nonce: ruh_comment_ajax.nonce,
                    comment_id: commentId,
                    content: content
                })
                .done(function(response) {
                    if (response.success) {
                        $textDiv.html(response.data.content).show();
                        $comment.find('.edit-form').remove();
                        self.showMessage('Güncellendi!', 'success');
                    } else {
                        self.showMessage(response.data?.message || 'Hata', 'error');
                    }
                });
            });
        },
        
        deleteComment: function(commentId) {
            const self = this;
            const $comment = $('#comment-' + commentId);
            
            // Hemen görsel geri bildirim ver
            $comment.css('opacity', '0.5');
            
            $.ajax({
                url: ruh_comment_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'ruh_delete_comment',
                    nonce: ruh_comment_ajax.nonce,
                    comment_id: commentId
                },
                timeout: 10000
            })
            .done(function(response) {
                if (response.success) {
                    $comment.slideUp(300, function() {
                        $(this).remove();
                        const currentCount = parseInt($('.comment-count').text()) || 0;
                        $('.comment-count').text(Math.max(0, currentCount - 1));
                    });
                    self.showMessage(ruh_comment_ajax.texts.comment_deleted || 'Yorum silindi!', 'success');
                } else {
                    $comment.css('opacity', '1');
                    self.showMessage(response.data?.message || 'Silme başarısız.', 'error');
                }
            })
            .fail(function(xhr, status, error) {
                $comment.css('opacity', '1');
                self.showMessage((ruh_comment_ajax.texts.connection_error || 'Connection error.') + ' ' + error, 'error');
            });
        },
        
        // SIRALAMA
        setupSorting: function() {
            const self = this;
            
            $(document).on('click', '.sort-btn', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const sort = $btn.data('sort');
                
                if (sort === self.currentSort) return;
                
                $('.sort-btn').removeClass('active');
                $btn.addClass('active');
                
                self.currentSort = sort;
                self.loadComments(true);
            });
        },
        
        // TOOLBAR
        setupToolbar: function() {
            const self = this;
            
            $(document).on('keydown', '#comment', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    $('#commentform').trigger('submit');
                    return;
                }
            });
            $(document).on('keydown', '.inline-reply-textarea', function(e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    e.preventDefault();
                    $(this).closest('.inline-reply-form').find('.submit-inline-reply').trigger('click');
                    return;
                }
            });
            
            $(document).on('keydown', '#comment, .inline-reply-textarea', function(e) {
                if (!(e.ctrlKey || e.metaKey)) return;
                const key = (e.key || '').toLowerCase();
                let action = '';
                if (key === 'b') action = 'bold';
                else if (key === 'i') action = 'italic';
                else if (key === 's' && e.shiftKey) action = 'spoiler';
                if (!action) return;
                e.preventDefault();
                const $field = $(this);
                if ($field.is('#comment')) {
                    $('.toolbar-btn[data-action="' + action + '"]').first().trigger('click');
                } else {
                    $field.closest('.inline-reply-form').find('.inline-toolbar-btn[data-action="' + action + '"]').first().trigger('click');
                }
            });
            
            $(document).on('click', '.toolbar-btn', function(e) {
                e.preventDefault();
                
                const action = $(this).data('action');
                const $textarea = $('#comment');
                const start = $textarea[0].selectionStart;
                const end = $textarea[0].selectionEnd;
                const text = $textarea.val();
                const selected = text.substring(start, end);
                
                let newText = '';
                let cursorPos = start;
                
                switch(action) {
                    case 'bold':
                        newText = text.substring(0, start) + '**' + selected + '**' + text.substring(end);
                        cursorPos = selected ? end + 4 : start + 2;
                        break;
                    case 'italic':
                        newText = text.substring(0, start) + '*' + selected + '*' + text.substring(end);
                        cursorPos = selected ? end + 2 : start + 1;
                        break;
                    case 'spoiler':
                        newText = text.substring(0, start) + '||' + selected + '||' + text.substring(end);
                        cursorPos = selected ? end + 4 : start + 2;
                        break;
                    case 'gif':
                        $('#gif-modal').show();
                        $('#gif-search').focus();
                        return;
                }
                
                $textarea.val(newText);
                $textarea[0].setSelectionRange(cursorPos, cursorPos);
                $textarea.focus();
                self.updateCharCount();
            });
            
            // Inline toolbar için de aynı işlem
            $(document).on('click', '.inline-toolbar-btn', function(e) {
                e.preventDefault();
                
                const $btn = $(this);
                const action = $btn.data('action');
                const $form = $btn.closest('.inline-reply-form');
                const $textarea = $form.find('.inline-reply-textarea');
                
                if (!$textarea.length) return;
                
                const start = $textarea[0].selectionStart;
                const end = $textarea[0].selectionEnd;
                const text = $textarea.val();
                const selected = text.substring(start, end);
                
                let newText = '';
                let cursorPos = start;
                
                switch(action) {
                    case 'bold':
                        newText = text.substring(0, start) + '**' + selected + '**' + text.substring(end);
                        cursorPos = selected ? end + 4 : start + 2;
                        break;
                    case 'italic':
                        newText = text.substring(0, start) + '*' + selected + '*' + text.substring(end);
                        cursorPos = selected ? end + 2 : start + 1;
                        break;
                    case 'spoiler':
                        newText = text.substring(0, start) + '||' + selected + '||' + text.substring(end);
                        cursorPos = selected ? end + 4 : start + 2;
                        break;
                    case 'gif':
                        self.currentInlineTextarea = $textarea;
                        $('#gif-modal').show();
                        $('#gif-search').focus();
                        return;
                }
                
                $textarea.val(newText);
                $textarea[0].setSelectionRange(cursorPos, cursorPos);
                $textarea.focus();
            });
        },
        
        currentInlineTextarea: null,
        
        // GIF MODAL
        setupGifModal: function() {
            const self = this;
            let searchTimeout;
            
            // Modal kapat
            $(document).on('click', '.ruh-modal-close, .ruh-modal', function(e) {
                if (e.target === this || $(this).hasClass('ruh-modal-close')) {
                    $('#gif-modal').hide();
                }
            });
            
            // GIF ara - Hızlandırılmış debounce
            $('#gif-search').on('input', function() {
                clearTimeout(searchTimeout);
                const query = $(this).val().trim();
                
                if (query.length < 2) {
                    $('#gif-results').empty();
                    return;
                }
                
                // Loading göster
                $('#gif-results').html('<p style="text-align:center;color:#888;">Aranıyor...</p>');
                
                // Debounce süresini 300ms'ye düşür (daha hızlı yanıt)
                searchTimeout = setTimeout(function() {
                    self.searchGifs(query);
                }, 300);
            });
            
            // GIF seç
            $(document).on('click', '#gif-results img', function() {
                const gifUrl = $(this).data('url');
                
                // Inline textarea varsa ona ekle, yoksa ana forma
                let $textarea;
                if (self.currentInlineTextarea && self.currentInlineTextarea.length) {
                    $textarea = self.currentInlineTextarea;
                } else {
                    $textarea = $('#comment');
                }
                
                const currentVal = $textarea.val();
                $textarea.val(currentVal + '\n![GIF](' + gifUrl + ')');
                $('#gif-modal').hide();
                
                // Temızle
                self.currentInlineTextarea = null;
                self.updateCharCount();
            });
        },
        
        searchGifs: function(query) {
            var self = this;
            
            // Server-side proxy kullan (API key gizli kalır)
            var proxyUrl = ruh_comment_ajax.gif_proxy || ruh_comment_ajax.ajax_url;
            
            $.ajax({
                url: proxyUrl,
                type: 'GET',
                data: {
                    action: 'ruh_gif_search',
                    nonce: ruh_comment_ajax.nonce,
                    q: query,
                    limit: 15
                },
                timeout: 10000, // 10 saniye timeout
                cache: true
            })
            .done(function(response) {
                var html = '';
                if (response.success && response.data && response.data.gifs && response.data.gifs.length) {
                    response.data.gifs.forEach(function(gif) {
                        html += '<img src="' + gif.preview_url + '" data-url="' + gif.original_url + '" alt="GIF" loading="lazy">';
                    });
                } else if (response.data && response.data.message) {
                    html = '<p style="text-align:center;color:#888;">' + response.data.message + '</p>';
                } else {
                    html = '<p style="text-align:center;color:#888;">GIF bulunamadı</p>';
                }
                $('#gif-results').html(html);
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error('GIF arama hatası:', textStatus, errorThrown);
                $('#gif-results').html('<p style="text-align:center;color:#888;">GIF arama hatası oluştu</p>');
            });
        },
        
        // SPOILER
        setupSpoilers: function() {
            $(document).on('click', '.spoiler', function() {
                $(this).toggleClass('revealed');
            });
        },
        
        // KARAKTER SAYACI
        setupCharCounter: function() {
            const self = this;
            
            $('#comment').on('input', function() {
                self.updateCharCount();
            });
        },
        
        getReplyToggleLabel: function(count, hide) {
            const n = parseInt(count, 10) || 0;
            const isEn = (ruh_comment_ajax.lang || '') === 'en_US';
            if (hide) {
                if (isEn) {
                    return 'Hide ' + n + ' ' + (n === 1 ? 'reply' : 'replies');
                }
                return n + ' yanıtı gizle';
            }
            if (isEn) {
                return n + ' ' + (n === 1 ? 'reply' : 'replies');
            }
            return n + ' yanıtı göster';
        },
        
        updateCharCount: function() {
            const $textarea = $('#comment');
            if (!$textarea.length) return;
            const len = $textarea.val().length;
            const max = parseInt($textarea.attr('maxlength') || ruh_comment_ajax.max_comment_length || 1000, 10);
            $('#char-count').text(len);
            const $counter = $('#char-counter, .char-counter').first();
            $counter.removeClass('warning danger');
            if (len >= max) {
                $counter.addClass('danger');
            } else if (len >= max * 0.85) {
                $counter.addClass('warning');
            }
        },
        
        // YORUMLARI YUKLE
        loadComments: function(replace) {
            const self = this;
            
            if (replace) {
                this.currentPage = 1;
                $('.comment-list').empty();
            }
            
            if (this._loadingComments) return; // Çift yükleme engelle
            this._loadingComments = true;
            $('#comment-loader').show();
            $('#load-more-comments').data('loading', true);
            
            $.post(ruh_comment_ajax.ajax_url, {
                action: 'ruh_get_comments',
                nonce: ruh_comment_ajax.nonce,
                post_id: ruh_comment_ajax.post_id,
                page: this.currentPage,
                sort: this.currentSort,
                parent_id: 0,
                current_url: window.location.href
            })
            .done(function(response) {
                if (response.success) {
                    if (response.data.html) {
                        if (replace) {
                            $('.comment-list').html(response.data.html);
                        } else {
                            $('.comment-list').append(response.data.html);
                        }
                        self.currentPage++;
                        $('#no-comments').hide();
                    }
                    
                    if (response.data.has_more) {
                        $('#load-more-comments').show();
                    } else {
                        $('#load-more-comments').hide();
                    }
                }
            })
            .always(function() {
                self._loadingComments = false;
                $('#load-more-comments').data('loading', false);
                $('#comment-loader').hide();
            });
            
            // Butona tıklama
            $('#load-more-comments').off('click').on('click', function() {
                self.loadComments(false);
            });
            
            // Infinite scroll: Load More butonu görüntüye girince otomatik yükle
            if (!this._infiniteScrollInit) {
                this._infiniteScrollInit = true;
                if ('IntersectionObserver' in window) {
                    const observer = new IntersectionObserver(function(entries) {
                        entries.forEach(function(entry) {
                            if (entry.isIntersecting && !self._loadingComments) {
                                const $btn = $('#load-more-comments');
                                if ($btn.is(':visible') && !$btn.data('loading')) {
                                    self.loadComments(false);
                                }
                            }
                        });
                    }, { rootMargin: '200px 0px' }); // 200px öncesinde tetikle
                    
                    const loadMoreEl = document.getElementById('load-more-comments');
                    if (loadMoreEl) {
                        observer.observe(loadMoreEl);
                        self._infiniteObserver = observer;
                    }
                }
            }
        },
        
        // BASLANGIC VERISI
        loadInitialData: function() {
            const self = this;
            
            $.post(ruh_comment_ajax.ajax_url, {
                action: 'ruh_get_initial_data',
                nonce: ruh_comment_ajax.nonce,
                post_id: ruh_comment_ajax.post_id
            })
            .done(function(response) {
                if (response.success && response.data) {
                    if (response.data.counts) {
                        self.updateReactionCounts(response.data.counts);
                    }
                    if (response.data.user_reaction) {
                        const $activeBtn = $('.content-reaction-btn[data-reaction="' + response.data.user_reaction + '"]');
                        $activeBtn.addClass('active');
                        $activeBtn.closest('.reaction-item').addClass('is-active');
                    }
                }
            });
        },
        
        // MESAJ GOSTER
        showMessage: function(msg, type) {
            const $msg = $('<div class="ruh-toast ' + type + '">' + msg + '</div>');
            $('body').append($msg);
            
            setTimeout(function() {
                $msg.addClass('show');
            }, 10);
            
            setTimeout(function() {
                $msg.removeClass('show');
                setTimeout(function() {
                    $msg.remove();
                }, 300);
            }, 3000);
        },
        
        // YORUM KURALLARI TOGGLE
        setupCommentRules: function() {
            $(document).on('click', '#ruh-rules-toggle', function() {
                const $btn = $(this);
                const $content = $('#ruh-rules-content');
                
                $btn.toggleClass('active');
                $content.slideToggle(200);
            });
        },
        
        // YORUM SABITLEME (Admin)
        setupPinning: function() {
            const self = this;
            
            $(document).on('click', '.pin-btn', function() {
                const $btn = $(this);
                const commentId = $btn.data('comment-id');
                
                if (!commentId) return;
                
                $btn.prop('disabled', true);
                
                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_pin_comment',
                    comment_id: commentId,
                    nonce: ruh_comment_ajax.nonce
                })
                .done(function(response) {
                    if (response.success) {
                        self.showMessage(response.data.message, 'success');
                        
                        const $comment = $btn.closest('li.comment-item');
                        
                        if (response.data.pinned) {
                            $comment.addClass('pinned is-pinned');
                            $btn.addClass('pinned');
                            $btn.find('.pin-text').text(ruh_comment_ajax.texts.unpin || 'Unpin');
                            
                            if (!$comment.find('.pinned-badge').length) {
                                const pinnedLabel = ruh_comment_ajax.texts.pinned || 'Pinned';
                                $comment.find('.comment-author').first().after('<span class="pinned-badge"><svg viewBox="0 0 24 24"><path fill="currentColor" d="M16,12V4H17V2H7V4H8V12L6,14V16H11.2V22H12.8V16H18V14L16,12Z"/></svg> ' + pinnedLabel + '</span>');
                            }
                            
                            const $list = $('#comment-list');
                            $comment.prependTo($list);
                        } else {
                            $comment.removeClass('pinned is-pinned');
                            $btn.removeClass('pinned');
                            $btn.find('.pin-text').text(ruh_comment_ajax.texts.pin || 'Pin');
                            $comment.find('.pinned-badge').remove();
                        }
                    } else {
                        self.showMessage(response.data.message || 'Hata oluştu.', 'error');
                    }
                })
                .fail(function() {
                    self.showMessage(ruh_comment_ajax.texts.connection_error || 'Connection error.', 'error');
                })
                .always(function() {
                    $btn.prop('disabled', false);
                });
            });
        }
    };
    
    // Toast CSS
    $('<style>')
        .text('.ruh-toast{position:fixed;bottom:20px;right:20px;padding:12px 20px;background:#333;color:#fff;border-radius:8px;font-size:14px;z-index:99999;opacity:0;transform:translateY(20px);transition:all 0.3s}.ruh-toast.show{opacity:1;transform:translateY(0)}.ruh-toast.success{background:#10b981}.ruh-toast.error{background:#ef4444}')
        .appendTo('head');
    
    // ========== MENTION AUTOCOMPLETE SISTEMI ==========
    const RuhMention = {
        _debounceTimer: null,
        _$dropdown: null,
        _currentField: null,
        _mentionStart: -1,
        
        init: function() {
            // Mention CSS
            $('<style>')
                .text('#ruh-mention-dropdown{position:fixed;background:#1e1e1e;border:1px solid #333;border-radius:8px;max-height:220px;overflow-y:auto;z-index:99998;box-shadow:0 8px 24px rgba(0,0,0,0.5);min-width:200px}.ruh-mention-item{display:flex;align-items:center;gap:8px;padding:8px 12px;cursor:pointer;color:#ccc;font-size:13px;transition:background 0.15s}.ruh-mention-item:hover,.ruh-mention-item.active{background:#2a2a2a;color:#fff}.ruh-mention-item img{width:28px;height:28px;border-radius:50%}.ruh-mention-item .mention-name{font-weight:600;color:#fff}.ruh-mention-item .mention-user{color:#888;font-size:11px}.ruh-mention-item .mention-level{font-size:10px;background:#333;padding:1px 5px;border-radius:4px;color:#aaa}')
                .appendTo('head');
            
            this._$dropdown = $('<div id="ruh-mention-dropdown" style="display:none"></div>').appendTo('body');
            
            const self = this;
            
            // Textarea input dinle
            $(document).on('input', '#comment, .inline-reply-textarea', function(e) {
                self._currentField = this;
                self._handleInput(this);
            });
            
            // Klavye navigasyonu
            $(document).on('keydown', '#comment, .inline-reply-textarea', function(e) {
                if (!self._$dropdown.is(':visible')) return;
                const $items = self._$dropdown.find('.ruh-mention-item');
                const $active = $items.filter('.active');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const next = $active.length ? $active.next() : $items.first();
                    $items.removeClass('active');
                    next.addClass('active');
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const prev = $active.length ? $active.prev() : $items.last();
                    $items.removeClass('active');
                    prev.addClass('active');
                } else if (e.key === 'Enter' || e.key === 'Tab') {
                    const $sel = $items.filter('.active');
                    if ($sel.length) {
                        e.preventDefault();
                        self._selectMention($sel.data('username'));
                    } else {
                        self._hideDropdown();
                    }
                } else if (e.key === 'Escape') {
                    self._hideDropdown();
                }
            });
            
            // Dışarı tıklanınca kapat
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#ruh-mention-dropdown').length) {
                    self._hideDropdown();
                }
            });
            
            // Mention seçimi
            $(document).on('click', '.ruh-mention-item', function() {
                self._selectMention($(this).data('username'));
            });
        },
        
        _handleInput: function(field) {
            const val = field.value;
            const pos = field.selectionStart;
            
            // @ karakterini bul (son boşluktan sonra)
            let start = pos - 1;
            while (start >= 0 && val[start] !== ' ' && val[start] !== '\n') {
                start--;
            }
            start++;
            
            const word = val.substring(start, pos);
            if (word.startsWith('@') && word.length >= 2) {
                this._mentionStart = start;
                this._search(word.substring(1));
            } else {
                this._hideDropdown();
            }
        },
        
        _search: function(query) {
            const self = this;
            clearTimeout(this._debounceTimer);
            this._debounceTimer = setTimeout(function() {
                $.get(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_search_users',
                    nonce: ruh_comment_ajax.nonce,
                    q: query
                })
                .done(function(response) {
                    if (response.success && response.data.users && response.data.users.length) {
                        self._showDropdown(response.data.users);
                    } else {
                        self._hideDropdown();
                    }
                });
            }, 250);
        },
        
        _showDropdown: function(users) {
            const field = this._currentField;
            if (!field) return;
            
            const rect = field.getBoundingClientRect();
            const top = rect.bottom + 4;
            const left = rect.left;
            
            let html = '';
            users.forEach(function(user) {
                const avatarHtml = user.avatar ? '<img src="' + user.avatar + '" alt="">' : '<span style="width:28px;height:28px;border-radius:50%;background:#333;display:inline-block"></span>';
                html += '<div class="ruh-mention-item" data-username="' + user.username + '">' +
                    avatarHtml +
                    '<div><div class="mention-name">' + user.display_name + '</div>' +
                    '<div class="mention-user">@' + user.username + ' <span class="mention-level">Lv.' + user.level + '</span></div>' +
                    '</div></div>';
            });
            
            this._$dropdown
                .html(html)
                .css({ top: Math.min(top, window.innerHeight - 240), left: left })
                .show();
        },
        
        _selectMention: function(username) {
            const field = this._currentField;
            if (!field) return;
            
            const val = field.value;
            const pos = field.selectionStart;
            const before = val.substring(0, this._mentionStart);
            const after = val.substring(pos);
            
            field.value = before + '@' + username + ' ' + after;
            
            // Cursor'ı mention sonrasına taşı
            const newPos = this._mentionStart + username.length + 2;
            field.setSelectionRange(newPos, newPos);
            field.focus();
            
            this._hideDropdown();
            $(field).trigger('input'); // char counter güncelle
        },
        
        _hideDropdown: function() {
            if (this._$dropdown) this._$dropdown.hide();
            this._mentionStart = -1;
        }
    };
    
    // Mention sistemini başlat (giriş yapılmışsa)
    if (ruh_comment_ajax.logged_in) {
        RuhMention.init();
    }
    // ========== MENTION SONU ==========

    // ========== A11Y: MODAL FOCUS TRAP ==========
    // Klavye kullanıcıları Tab ile modal dışına çıkamasın (WCAG 2.4.3).
    // Modal açıldığında ilk odaklanabilir öğeye focus verilir, Tab/Shift+Tab
    // döngüsü modal içinde tutulur, modal kapanınca focus tetikleyici öğeye döner.
    let ruhLastFocusedEl = null;

    function ruhGetFocusable($modal) {
        return $modal.find('a[href], button:not([disabled]), textarea:not([disabled]), input:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])').filter(':visible');
    }

    $(document).on('keydown', function(e) {
        if (e.key !== 'Tab') return;
        const $openModal = $('.ruh-modal:visible').first();
        if (!$openModal.length) return;

        const $focusable = ruhGetFocusable($openModal);
        if (!$focusable.length) return;

        const first = $focusable[0];
        const last = $focusable[$focusable.length - 1];

        if (e.shiftKey && document.activeElement === first) {
            e.preventDefault();
            last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
            e.preventDefault();
            first.focus();
        }
    });

    // Modal görünürlük değişimini gözlemleyip odağı yönet
    const ruhModalObserver = new MutationObserver(function(mutations) {
        mutations.forEach(function(m) {
            if (m.attributeName !== 'style') return;
            const $modal = $(m.target);
            const isVisible = $modal.is(':visible');

            if (isVisible && !$modal.data('ruh-was-visible')) {
                ruhLastFocusedEl = document.activeElement;
                const $focusable = ruhGetFocusable($modal);
                if ($focusable.length) $focusable[0].focus();
                $modal.data('ruh-was-visible', true);
            } else if (!isVisible && $modal.data('ruh-was-visible')) {
                $modal.data('ruh-was-visible', false);
                if (ruhLastFocusedEl && document.body.contains(ruhLastFocusedEl)) {
                    ruhLastFocusedEl.focus();
                }
            }
        });
    });

    document.querySelectorAll('.ruh-modal').forEach(function(modal) {
        ruhModalObserver.observe(modal, { attributes: true, attributeFilter: ['style', 'class'] });
    });
    // ========== A11Y MODAL FOCUS TRAP SONU ==========

    // Başlat
    RuhComments.init();
});
