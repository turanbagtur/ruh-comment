jQuery(document).ready(function($) {
    'use strict';
    
    if (!window.ruh_comment_ajax) {
        console.error('RuhComments: AJAX config yok');
        return;
    }
    
    const RuhComments = {
        currentSort: 'newest',
        currentPage: 1,
        
        init: function() {
            this.setupFormSubmission();
            this.setupReactions();
            this.setupCommentActions();
            this.setupSorting();
            this.setupToolbar();
            this.setupCharCounter();
            this.setupGifModal();
            this.setupSpoilers();
            this.loadInitialData();
            this.loadComments();
        },
        
        // FORM GONDERME
        setupFormSubmission: function() {
            const self = this;
            
            $('#commentform').on('submit', function(e) {
                e.preventDefault();
                
                const $textarea = $('#comment');
                const $submitBtn = $('#submit');
                const content = $textarea.val().trim();
                const parentId = $('#comment_parent').val() || 0;
                
                if (!content) {
                    self.showMessage('Yorum bos olamaz.', 'error');
                    return;
                }
                
                if (!ruh_comment_ajax.logged_in) {
                    self.showMessage('Giris yapmalisiniz.', 'error');
                    return;
                }
                
                $submitBtn.prop('disabled', true).find('svg').hide();
                $submitBtn.prepend('<span class="loading">...</span>');
                
                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_submit_comment',
                    nonce: ruh_comment_ajax.nonce,
                    comment: content,
                    post_id: ruh_comment_ajax.post_id,
                    comment_post_ID: ruh_comment_ajax.post_id,
                    comment_parent: parentId,
                    current_url: window.location.href
                })
                .done(function(response) {
                    if (response.success) {
                        if (response.data.html) {
                            if (parseInt(parentId) > 0) {
                                // Yanit - parent yorumun altina ekle
                                const $parentReplies = $('#replies-' + parentId);
                                $parentReplies.append(response.data.html);
                            } else {
                                // Ana yorum - listenin basina ekle
                                $('.comment-list').prepend(response.data.html);
                            }
                            $('#no-comments').hide();
                        }
                        
                        // Formu temizle
                        $textarea.val('');
                        $('#char-count').text('0');
                        $('#comment_parent').val('0');
                        $('#reply-indicator').hide();
                        
                        // Sayaci guncelle
                        const currentCount = parseInt($('.comment-count').text()) || 0;
                        $('.comment-count').text(currentCount + 1);
                        
                        self.showMessage('Yorum gonderildi!', 'success');
                    } else {
                        self.showMessage(response.data?.message || 'Hata olustu.', 'error');
                    }
                })
                .fail(function() {
                    self.showMessage('Ag hatasi.', 'error');
                })
                .always(function() {
                    $submitBtn.prop('disabled', false).find('.loading').remove();
                    $submitBtn.find('svg').show();
                });
            });
            
            // Yanit iptal
            $('#cancel-reply').on('click', function() {
                $('#comment_parent').val('0');
                $('#reply-indicator').hide();
            });
        },
        
        // TEPKILER - Optimize
        setupReactions: function() {
            const self = this;
            let isProcessing = false;
            
            $(document).on('click', '.content-reaction-btn', function(e) {
                e.preventDefault();
                
                if (isProcessing) return;
                
                if (!ruh_comment_ajax.logged_in) {
                    self.showMessage('Giris yapmalisiniz.', 'error');
                    return;
                }
                
                const $btn = $(this);
                const reaction = $btn.data('reaction');
                
                if (!reaction) return;
                
                isProcessing = true;
                
                // Hemen gorsel guncelle (optimistik UI)
                const wasActive = $btn.hasClass('active');
                $('.content-reaction-btn').removeClass('active');
                if (!wasActive) {
                    $btn.addClass('active');
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
                const reaction = $this.data('reaction');
                const count = counts && counts[reaction] ? parseInt(counts[reaction].count) : 0;
                $this.closest('.reaction-item').find('.reaction-count').text(count);
                total += count;
            });
            $('#total-reaction-count').text(total);
        },
        
        // YORUM AKSIYONLARI
        setupCommentActions: function() {
            const self = this;
            
            // Begeni
            $(document).on('click', '.action-btn.like-btn, .like-btn', function(e) {
                e.preventDefault();
                
                if (!ruh_comment_ajax.logged_in) {
                    self.showMessage('Giris yapmalisiniz.', 'error');
                    return;
                }
                
                const $btn = $(this);
                const commentId = $btn.data('comment-id');
                
                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_handle_like',
                    nonce: ruh_comment_ajax.nonce,
                    comment_id: commentId
                })
                .done(function(response) {
                    if (response.success) {
                        $btn.find('.like-count').text(response.data.likes);
                        if (response.data.user_vote === 'liked') {
                            $btn.addClass('liked');
                        } else {
                            $btn.removeClass('liked');
                        }
                    }
                });
            });
            
            // Yanitlama - Inline form ac
            $(document).on('click', '.action-btn.reply-btn, .reply-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (!ruh_comment_ajax.logged_in) {
                    self.showMessage('Giris yapmalisiniz.', 'error');
                    return;
                }
                
                const commentId = $(this).data('comment-id');
                const authorName = $(this).data('author');
                const $comment = $('#comment-' + commentId);
                
                // Mevcut inline formlari kaldir
                $('.inline-reply-form').remove();
                
                // Inline form olustur - toolbar ile
                const inlineForm = `
                    <div class="inline-reply-form" id="reply-form-${commentId}">
                        <div class="reply-form-header">
                            <span>@${authorName} kullanicisina yanit</span>
                            <button type="button" class="cancel-inline-reply">&times;</button>
                        </div>
                        <div class="inline-toolbar">
                            <button type="button" class="inline-toolbar-btn" data-action="bold" title="Kalin">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M13.5,15.5H10V12.5H13.5A1.5,1.5 0 0,1 15,14A1.5,1.5 0 0,1 13.5,15.5M10,6.5H13A1.5,1.5 0 0,1 14.5,8A1.5,1.5 0 0,1 13,9.5H10M15.6,10.79C16.57,10.11 17.25,9 17.25,8C17.25,5.74 15.5,4 13.25,4H7V18H14.04C16.14,18 17.75,16.3 17.75,14.21C17.75,12.69 16.89,11.39 15.6,10.79Z"/></svg>
                            </button>
                            <button type="button" class="inline-toolbar-btn" data-action="italic" title="Italik">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M10,4V7H12.21L8.79,15H6V18H14V15H11.79L15.21,7H18V4H10Z"/></svg>
                            </button>
                            <button type="button" class="inline-toolbar-btn" data-action="spoiler" title="Spoiler">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M12,9A3,3 0 0,0 9,12A3,3 0 0,0 12,15A3,3 0 0,0 15,12A3,3 0 0,0 12,9M12,17A5,5 0 0,1 7,12A5,5 0 0,1 12,7A5,5 0 0,1 17,12A5,5 0 0,1 12,17M12,4.5C7,4.5 2.73,7.61 1,12C2.73,16.39 7,19.5 12,19.5C17,19.5 21.27,16.39 23,12C21.27,7.61 17,4.5 12,4.5Z"/></svg>
                            </button>
                            <button type="button" class="inline-toolbar-btn inline-gif-btn" data-action="gif" data-form-id="${commentId}" title="GIF">
                                <svg viewBox="0 0 24 24" width="14" height="14"><path fill="currentColor" d="M11.5,9H13V15H11.5V9M9,9V15H6A1.5,1.5 0 0,1 4.5,13.5V10.5A1.5,1.5 0 0,1 6,9H9M7.5,10.5H6V13.5H7.5V10.5M19,10.5V9H14.5V15H16V13H18V11.5H16V10.5H19Z"/></svg>
                            </button>
                        </div>
                        <textarea class="inline-reply-textarea" id="inline-textarea-${commentId}" placeholder="Yanitinizi yazin..." maxlength="5000"></textarea>
                        <div class="reply-form-actions">
                            <button type="button" class="submit-inline-reply" data-comment-id="${commentId}">Gonder</button>
                        </div>
                    </div>
                `;
                
                // Formu comment-body'den sonra ekle
                $comment.find('.comment-body').after(inlineForm);
                $comment.find('.inline-reply-textarea').focus();
            });
            
            // Inline yanit iptal
            $(document).on('click', '.cancel-inline-reply', function() {
                $(this).closest('.inline-reply-form').remove();
            });
            
            // Inline yanit gonder
            $(document).on('click', '.submit-inline-reply', function() {
                const $form = $(this).closest('.inline-reply-form');
                const commentId = $(this).data('comment-id');
                const content = $form.find('.inline-reply-textarea').val().trim();
                
                if (!content) {
                    self.showMessage('Yanit bos olamaz.', 'error');
                    return;
                }
                
                const $btn = $(this);
                $btn.prop('disabled', true).text('Gonderiliyor...');
                
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
                        $('#replies-' + commentId).append(response.data.html);
                        $form.remove();
                        self.showMessage('Yanit gonderildi!', 'success');
                        const $count = $('.comment-count');
                        $count.text(parseInt($count.text()) + 1);
                    } else {
                        self.showMessage(response.data?.message || 'Hata.', 'error');
                        $btn.prop('disabled', false).text('Gonder');
                    }
                })
                .fail(function() {
                    self.showMessage('Ag hatasi.', 'error');
                    $btn.prop('disabled', false).text('Gonder');
                });
            });
            
            // Duzenleme
            $(document).on('click', '.action-btn.edit-btn, .edit-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const commentId = $(this).data('comment-id');
                // Menuyu kapat
                $(this).closest('.more-dropdown').removeClass('show');
                self.editComment(commentId);
            });
            
            // Silme
            $(document).on('click', '.action-btn.delete-btn, .delete-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (confirm('Yorumu silmek istediginizden emin misiniz?')) {
                    const commentId = $(this).data('comment-id');
                    self.deleteComment(commentId);
                }
                // Menuyu kapat
                $(this).closest('.more-dropdown').removeClass('show');
            });
            
            // 3 Nokta Menu Toggle
            $(document).on('click', '.more-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const $dropdown = $(this).siblings('.more-dropdown');
                
                // Diger acik menuleri kapat
                $('.more-dropdown').not($dropdown).removeClass('show');
                
                // Bu menuyu toggle et
                $dropdown.toggleClass('show');
            });
            
            // Disari tiklaninca menuleri kapat
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.comment-more-menu').length) {
                    $('.more-dropdown').removeClass('show');
                }
            });
            
            // Sikayet - Modal ac
            $(document).on('click', '.report-btn', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const commentId = $(this).data('comment-id');
                $('#report-comment-id').val(commentId);
                $('#report-type').val('');
                $('#report-reason').val('');
                $('#report-modal').show();
                
                // Menuyu kapat
                $(this).closest('.more-dropdown').removeClass('show');
            });
            
            // Sikayet formu gonder
            $('#report-form').on('submit', function(e) {
                e.preventDefault();
                const commentId = $('#report-comment-id').val();
                const reportType = $('#report-type').val();
                const reason = $('#report-reason').val();
                
                if (!reportType) {
                    self.showMessage('Sikayet turu secin.', 'error');
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
                    self.showMessage('Sikayet gonderildi. Tesekkurler!', 'success');
                    
                    // Yorum moderasyona alindiysa DOM'dan kaldir
                    if (response.data?.hidden && response.data?.comment_id) {
                        const $comment = $('#comment-' + response.data.comment_id);
                        if ($comment.length) {
                            $comment.slideUp(300, function() {
                                $(this).remove();
                                // Yorum sayisini guncelle
                                const currentCount = parseInt($('.comment-count').text()) || 0;
                                if (currentCount > 0) {
                                    $('.comment-count').text(currentCount - 1);
                                }
                            });
                        }
                    }
                } else {
                    self.showMessage(response.data?.message || 'Hata olustu.', 'error');
                }
            })
            .fail(function() {
                self.showMessage('Ag hatasi.', 'error');
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
                        <button type="button" class="edit-cancel-btn">Iptal</button>
                        <button type="button" class="edit-save-btn" data-comment-id="${commentId}">Kaydet</button>
                    </div>
                </div>
            `;
            
            $textDiv.hide().after(editForm);
            $comment.find('.edit-form textarea').focus();
            
            // Iptal
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
                        self.showMessage('Guncellendi!', 'success');
                    } else {
                        self.showMessage(response.data?.message || 'Hata', 'error');
                    }
                });
            });
        },
        
        deleteComment: function(commentId) {
            const self = this;
            
            $.post(ruh_comment_ajax.ajax_url, {
                action: 'ruh_delete_comment',
                nonce: ruh_comment_ajax.nonce,
                comment_id: commentId
            })
            .done(function(response) {
                if (response.success) {
                    $('#comment-' + commentId).fadeOut(function() {
                        $(this).remove();
                        const currentCount = parseInt($('.comment-count').text()) || 0;
                        $('.comment-count').text(Math.max(0, currentCount - 1));
                    });
                    self.showMessage('Yorum silindi!', 'success');
                }
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
            
            // Inline toolbar icin de ayni islem
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
            $(document).on('click', '.modal-close, .modal', function(e) {
                if (e.target === this || $(this).hasClass('modal-close')) {
                    $('#gif-modal').hide();
                }
            });
            
            // GIF ara
            $('#gif-search').on('input', function() {
                clearTimeout(searchTimeout);
                const query = $(this).val().trim();
                
                if (query.length < 2) {
                    $('#gif-results').empty();
                    return;
                }
                
                searchTimeout = setTimeout(function() {
                    self.searchGifs(query);
                }, 500);
            });
            
            // GIF sec
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
                
                // Temizle
                self.currentInlineTextarea = null;
                self.updateCharCount();
            });
        },
        
        searchGifs: function(query) {
            const apiKey = ruh_comment_ajax.giphy_api_key || 'GlVGYHkr3WSBnllca54iNt0yFbjz7L65';
            
            $.get('https://api.giphy.com/v1/gifs/search', {
                api_key: apiKey,
                q: query,
                limit: 12,
                rating: 'pg-13'
            })
            .done(function(response) {
                let html = '';
                if (response.data && response.data.length) {
                    response.data.forEach(function(gif) {
                        const url = gif.images.fixed_height.url;
                        const preview = gif.images.fixed_height_small.url || url;
                        html += '<img src="' + preview + '" data-url="' + url + '" alt="GIF">';
                    });
                } else {
                    html = '<p style="text-align:center;color:#888;">GIF bulunamadi</p>';
                }
                $('#gif-results').html(html);
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
        
        updateCharCount: function() {
            const len = $('#comment').val().length;
            $('#char-count').text(len);
        },
        
        // YORUMLARI YUKLE
        loadComments: function(replace) {
            const self = this;
            
            if (replace) {
                this.currentPage = 1;
                $('.comment-list').empty();
            }
            
            $('#comment-loader').show();
            
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
                $('#comment-loader').hide();
            });
            
            // Load more
            $('#load-more-comments').off('click').on('click', function() {
                self.loadComments(false);
            });
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
                        $('.content-reaction-btn[data-reaction="' + response.data.user_reaction + '"]').addClass('active');
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
        }
    };
    
    // Toast CSS
    $('<style>')
        .text('.ruh-toast{position:fixed;bottom:20px;right:20px;padding:12px 20px;background:#333;color:#fff;border-radius:8px;font-size:14px;z-index:99999;opacity:0;transform:translateY(20px);transition:all 0.3s}.ruh-toast.show{opacity:1;transform:translateY(0)}.ruh-toast.success{background:#10b981}.ruh-toast.error{background:#ef4444}')
        .appendTo('head');
    
    // Baslat
    RuhComments.init();
});
