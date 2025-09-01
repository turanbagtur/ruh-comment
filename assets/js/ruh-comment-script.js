jQuery(function ($) {
    if (!$('#ruh-comments').length) return;

    const RuhComments = {
        state: { 
            currentPage: 1, 
            currentSort: 'newest', // Varsayılan en yeniler
            isLoading: false, 
            allCommentsLoaded: false,
            replyForms: new Map()
        },
        
        elements: {
            container: $('#ruh-comments'),
            commentList: $('.comment-list'),
            loadMoreBtn: $('#load-more-comments'),
            loader: $('#comment-loader'),
            sortButtons: $('.sort-button'),
            reactions: $('.reactions'),
            commentForm: $('#commentform'),
            commentTextarea: $('#comment'),
            submitBtn: $('#submit'),
            toolbar: $('#ruh-editor-toolbar'),
            commentCountSpan: $('.comment-count'),
            totalReactionCount: $('#total-reaction-count')
        },

        init: function () {
            this.loadInitialData();
            this.setupEventListeners();
        },

        showNotification: function(message, type = 'info') {
            if (window.showNotification) {
                window.showNotification(message, type);
            } else {
                alert(message);
            }
        },

        loadInitialData: function () {
            this.getComments(true);
            this.getReactions();
        },
        
        getComments: function (replace = false) {
            if (this.state.isLoading || (this.state.allCommentsLoaded && !replace)) return;
            
            this.state.isLoading = true;
            this.elements.loader.show();
            this.elements.loadMoreBtn.prop('disabled', true);
            
            if (replace) {
                this.elements.commentList.empty();
                this.state.allCommentsLoaded = false;
                this.state.currentPage = 1;
                $('.no-comments').hide();
            }

            $.post(ruh_comment_ajax.ajax_url, {
                action: 'ruh_get_comments',
                nonce: ruh_comment_ajax.nonce,
                post_id: ruh_comment_ajax.post_id,
                page: this.state.currentPage,
                sort: this.state.currentSort
            })
            .done(response => {
                if (response.success && response.data.html.trim() !== '') {
                    const $newComments = $(response.data.html);
                    this.elements.commentList.append($newComments);
                    this.state.currentPage++;
                    
                    // Animasyon
                    $newComments.hide().fadeIn(400);
                    
                    if (!response.data.has_more) {
                        this.state.allCommentsLoaded = true;
                        this.elements.loadMoreBtn.hide();
                    } else {
                        this.elements.loadMoreBtn.show();
                    }
                } else {
                    this.state.allCommentsLoaded = true;
                    this.elements.loadMoreBtn.hide();
                    if (this.state.currentPage === 1) {
                        $('.no-comments').show();
                    }
                }
            })
            .fail(() => {
                this.showNotification(ruh_comment_ajax.text.error, 'error');
            })
            .always(() => {
                this.state.isLoading = false;
                this.elements.loader.hide();
                this.elements.loadMoreBtn.prop('disabled', false);
            });
        },

        getReactions: function () {
            $.post(ruh_comment_ajax.ajax_url, {
                action: 'ruh_get_initial_data',
                nonce: ruh_comment_ajax.nonce,
                post_id: ruh_comment_ajax.post_id
            })
            .done(response => {
                if (response.success) {
                    this.updateReactionUI(response.data);
                }
            });
        },

        updateReactionUI: function(data) {
            let total = 0;
            
            // Her tepki için sayıları güncelle
            this.elements.reactions.find('.reaction').each(function() {
                const $this = $(this);
                const reaction = $this.data('reaction');
                const count = data.counts && data.counts[reaction] ? parseInt(data.counts[reaction].count, 10) : 0;
                $this.find('.count').text(count);
                total += count;
            });

            this.elements.totalReactionCount.text(total);
            
            // Kullanıcının tepkisini işaretle
            this.elements.reactions.find('.reaction').removeClass('selected');
            if (data.user_reaction) {
                this.elements.reactions.find(`.reaction[data-reaction="${data.user_reaction}"]`).addClass('selected');
            }
        },

        createInlineReplyForm: function(commentId) {
            if (this.state.replyForms.has(commentId)) {
                return this.state.replyForms.get(commentId);
            }

            const userAvatar = this.getUserAvatar();
            const $form = $(`
                <div class="inline-reply-container" data-comment-id="${commentId}">
                    <div class="comment-user-info">
                        ${userAvatar ? `<img src="${userAvatar}" alt="" class="avatar">` : ''}
                    </div>
                    <form class="inline-reply-form">
                        <div class="inline-reply-editor">
                            <div class="inline-reply-toolbar">
                                <button type="button" class="ruh-toolbar-button" data-tag="b"><b>B</b></button>
                                <button type="button" class="ruh-toolbar-button" data-tag="i"><i>I</i></button>
                                <button type="button" class="ruh-toolbar-button" data-tag="spoiler">[S]</button>
                            </div>
                            <textarea name="comment" placeholder="${ruh_comment_ajax.text.reply_placeholder || 'Yanıtınızı yazın...'}" required></textarea>
                        </div>
                        <div class="inline-reply-actions">
                            <input type="hidden" name="comment_post_ID" value="${ruh_comment_ajax.post_id}">
                            <input type="hidden" name="comment_parent" value="${commentId}">
                            <div></div>
                            <div>
                                <button type="button" class="inline-reply-cancel">${ruh_comment_ajax.text.reply_cancel}</button>
                                <button type="submit" class="inline-reply-submit">${ruh_comment_ajax.text.reply_send}</button>
                            </div>
                        </div>
                    </form>
                </div>
            `);

            this.state.replyForms.set(commentId, $form);
            return $form;
        },

        getUserAvatar: function() {
            const $existingAvatar = this.elements.commentForm.find('.comment-user-info img');
            return $existingAvatar.length ? $existingAvatar.attr('src') : '';
        },

        wrapText: function(textarea, tag) {
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;
            const selectedText = text.substring(start, end);
            let replacement;

            if (tag === 'spoiler') {
                replacement = `[spoiler]${selectedText}[/spoiler]`;
            } else if (tag === 'link') {
                const url = prompt('Link URL\'sini girin:');
                if (url) {
                    replacement = `<a href="${url}">${selectedText || 'Link metni'}</a>`;
                } else {
                    return;
                }
            } else {
                replacement = `<${tag}>${selectedText}</${tag}>`;
            }

            textarea.value = text.substring(0, start) + replacement + text.substring(end);
            textarea.focus();
            textarea.selectionStart = start + replacement.length - selectedText.length;
            textarea.selectionEnd = textarea.selectionStart;
        },

        setupEventListeners: function () {
            // Spoiler açma/kapama
            $('body').on('click', '.spoiler-header', function() {
                $(this).next('.spoiler-content').slideToggle(200);
                $(this).toggleClass('open');
            });

            // Sıralama değişikliği - yan yana butonlar
            this.elements.sortButtons.on('click', e => {
                const $btn = $(e.currentTarget);
                if ($btn.hasClass('active')) return;
                
                this.elements.sortButtons.removeClass('active');
                $btn.addClass('active');
                
                this.state.currentSort = $btn.data('sort');
                this.state.currentPage = 1;
                this.getComments(true);
            });

            // Daha fazla yorum yükle
            this.elements.loadMoreBtn.on('click', () => {
                this.getComments();
            });

            // Tepkiler
            this.elements.reactions.on('click', '.reaction', e => {
                if (!ruh_comment_ajax.logged_in) {
                    this.showNotification(ruh_comment_ajax.text.login_required, 'warning');
                    return;
                }

                const $btn = $(e.currentTarget);
                const reaction = $btn.data('reaction');
                const wasSelected = $btn.hasClass('selected');

                $btn.addClass('loading');

                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_handle_reaction',
                    nonce: ruh_comment_ajax.nonce,
                    post_id: ruh_comment_ajax.post_id,
                    reaction: reaction
                })
                .done(response => {
                    if (response.success) {
                        this.updateReactionUI({
                            counts: response.data.counts,
                            user_reaction: wasSelected ? null : reaction
                        });
                    }
                })
                .fail(() => {
                    this.showNotification(ruh_comment_ajax.text.error, 'error');
                })
                .always(() => {
                    $btn.removeClass('loading');
                });
            });

            // Beğeni/beğenmeme
            this.elements.commentList.on('click', '.like-btn, .dislike-btn', e => {
                if (!ruh_comment_ajax.logged_in) {
                    this.showNotification(ruh_comment_ajax.text.login_required, 'warning');
                    return;
                }

                const $btn = $(e.currentTarget);
                const $container = $btn.closest('.comment-like-buttons');
                const commentId = $container.data('comment-id');
                const type = $btn.hasClass('like-btn') ? 'like' : 'dislike';

                $btn.prop('disabled', true);

                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_handle_like',
                    nonce: ruh_comment_ajax.nonce,
                    comment_id: commentId,
                    type: type
                })
                .done(response => {
                    if (response.success) {
                        $container.find('.like-btn .count').text(response.data.likes);
                        $container.find('.dislike-btn .count').text(response.data.dislikes);
                        
                        // Active state güncelle
                        $container.find('button').removeClass('active');
                        if (response.data.user_vote === 'liked') {
                            $container.find('.like-btn').addClass('active');
                        } else if (response.data.user_vote === 'disliked') {
                            $container.find('.dislike-btn').addClass('active');
                        }
                    }
                })
                .always(() => {
                    $btn.prop('disabled', false);
                });
            });

            // Şikayet et
            this.elements.commentList.on('click', '.report-btn', e => {
                e.preventDefault();
                
                if (!ruh_comment_ajax.logged_in) {
                    this.showNotification(ruh_comment_ajax.text.login_required, 'warning');
                    return;
                }

                if (!confirm(ruh_comment_ajax.text.report_confirm)) {
                    return;
                }

                const $btn = $(e.currentTarget);
                const commentId = $btn.data('comment-id');

                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_flag_comment',
                    nonce: ruh_comment_ajax.nonce,
                    comment_id: commentId
                })
                .done(response => {
                    if (response.success) {
                        this.showNotification(response.data.message, 'success');
                        $btn.text('Şikayet Edildi').prop('disabled', true);
                    } else {
                        this.showNotification(response.data.message || ruh_comment_ajax.text.error, 'error');
                    }
                });
            });

            // Ana yorum formu
            this.elements.commentForm.on('submit', e => {
                e.preventDefault();
                
                if (this.state.isLoading) return;
                
                const commentText = this.elements.commentTextarea.val().trim();
                if (commentText === '') {
                    this.showNotification(ruh_comment_ajax.text.comment_empty, 'warning');
                    return;
                }

                this.state.isLoading = true;
                const originalBtnText = this.elements.submitBtn.text();
                this.elements.submitBtn.text(ruh_comment_ajax.text.commenting).prop('disabled', true);

                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_submit_comment',
                    nonce: ruh_comment_ajax.nonce,
                    ...this.elements.commentForm.serializeObject()
                })
                .done(response => {
                    if (response.success) {
                        if (response.data.html) {
                            const $newComment = $(response.data.html).hide();
                            this.elements.commentList.prepend($newComment);
                            $newComment.fadeIn(400);
                        }
                        
                        this.elements.commentTextarea.val('');
                        
                        const currentCount = parseInt(this.elements.commentCountSpan.text(), 10);
                        this.elements.commentCountSpan.text(currentCount + 1);
                        $('.no-comments').hide();
                        
                        this.showNotification(response.data.message || ruh_comment_ajax.text.success, 'success');
                    } else {
                        this.showNotification(response.data.message || ruh_comment_ajax.text.error, 'error');
                    }
                })
                .always(() => {
                    this.state.isLoading = false;
                    this.elements.submitBtn.text(originalBtnText).prop('disabled', false);
                });
            });

            // Toolbar butonları
            this.elements.toolbar.on('click', '.ruh-toolbar-button', e => {
                e.preventDefault();
                const tag = $(e.currentTarget).data('tag');
                this.wrapText(this.elements.commentTextarea[0], tag);
            });

            // Yanıtla linki
            this.elements.commentList.on('click', '.comment-reply-link', e => {
                e.preventDefault();
                
                if (!ruh_comment_ajax.logged_in) {
                    this.showNotification(ruh_comment_ajax.text.login_required, 'warning');
                    return;
                }

                const $link = $(e.currentTarget);
                const commentId = $link.data('comment-id');
                const $commentItem = $link.closest('.ruh-comment-item');
                
                // Varsa kaldır
                $commentItem.find('.inline-reply-container').remove();
                
                // Yeni form oluştur ve ekle
                const $replyForm = this.createInlineReplyForm(commentId);
                $commentItem.append($replyForm);
                $replyForm.hide().slideDown(300);
                $replyForm.find('textarea').focus();
            });

            // Inline yanıt formu - iptal
            this.elements.commentList.on('click', '.inline-reply-cancel', e => {
                const $container = $(e.currentTarget).closest('.inline-reply-container');
                const commentId = $container.data('comment-id');
                
                $container.slideUp(300, () => {
                    $container.remove();
                    this.state.replyForms.delete(commentId);
                });
            });

            // Inline yanıt formu - gönder
            this.elements.commentList.on('submit', '.inline-reply-form', e => {
                e.preventDefault();
                
                const $form = $(e.currentTarget);
                const $container = $form.closest('.inline-reply-container');
                const $submitBtn = $form.find('.inline-reply-submit');
                const $textarea = $form.find('textarea');
                
                if ($textarea.val().trim() === '') {
                    this.showNotification(ruh_comment_ajax.text.comment_empty, 'warning');
                    return;
                }

                const originalBtnText = $submitBtn.text();
                $submitBtn.text(ruh_comment_ajax.text.commenting).prop('disabled', true);

                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_submit_comment',
                    nonce: ruh_comment_ajax.nonce,
                    ...$form.serializeObject()
                })
                .done(response => {
                    if (response.success) {
                        if (response.data.html) {
                            const $newReply = $(response.data.html).hide();
                            
                            if (response.data.parent_id && response.data.parent_id != '0') {
                                // Alt yorum olarak ekle
                                const $parentComment = $(`#comment-${response.data.parent_id}`);
                                let $children = $parentComment.children('.children');
                                
                                if (!$children.length) {
                                    $children = $('<ol class="children"></ol>');
                                    $parentComment.append($children);
                                }
                                
                                $children.append($newReply);
                            } else {
                                // Ana yorum olarak ekle
                                this.elements.commentList.prepend($newReply);
                            }
                            
                            $newReply.fadeIn(400);
                        }
                        
                        // Formu kaldır
                        const commentId = $container.data('comment-id');
                        $container.slideUp(300, () => {
                            $container.remove();
                            this.state.replyForms.delete(commentId);
                        });
                        
                        // Sayacı güncelle
                        const currentCount = parseInt(this.elements.commentCountSpan.text(), 10);
                        this.elements.commentCountSpan.text(currentCount + 1);
                        
                        this.showNotification(response.data.message || ruh_comment_ajax.text.success, 'success');
                    } else {
                        this.showNotification(response.data.message || ruh_comment_ajax.text.error, 'error');
                    }
                })
                .always(() => {
                    $submitBtn.text(originalBtnText).prop('disabled', false);
                });
            });

            // Inline yanıt toolbar
            this.elements.commentList.on('click', '.inline-reply-toolbar .ruh-toolbar-button', e => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const $textarea = $btn.closest('.inline-reply-editor').find('textarea')[0];
                const tag = $btn.data('tag');
                
                this.wrapText($textarea, tag);
            });
        }
    };

    // jQuery serializeObject plugin
    $.fn.serializeObject = function() {
        var o = {};
        var a = this.serializeArray();
        $.each(a, function() {
            if (o[this.name]) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

    // Başlat
    RuhComments.init();
});