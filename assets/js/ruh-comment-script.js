jQuery(function ($) {
    if (!$('#ruh-comments').length) return;

    const RuhComments = {
        state: { 
            currentPage: 1, 
            currentSort: 'newest',
            isLoading: false, 
            allCommentsLoaded: false,
            replyForms: new Map(),
            uploadedImages: []
        },
        
        elements: {
            container: $('#ruh-comments'),
            commentList: $('.comment-list'),
            loadMoreBtn: $('#load-more-comments'),
            loader: $('#comment-loader'),
            reactions: $('.reactions'),
            commentForm: $('#commentform'),
            commentTextarea: $('#comment'),
            submitBtn: $('#submit'),
            toolbar: $('#ruh-editor-toolbar'),
            commentCountSpan: $('.comment-count'),
            totalReactionCount: $('#total-reaction-count'),
            charCounter: $('#char-count')
        },

        init: function () {
            this.loadInitialData();
            this.setupEventListeners();
            this.initDropdownFilter();
            this.setupImageUpload();
            this.setupCharCounter();
        },

        showNotification: function(message, type = 'info') {
            if (window.showNotification) {
                window.showNotification(message, type);
            } else {
                // Fallback notification
                const notification = $(`
                    <div class="ruh-notification ${type}">
                        <span class="notification-text">${message}</span>
                        <button class="notification-close">&times;</button>
                    </div>
                `);
                
                $('body').append(notification);
                
                notification.find('.notification-close').on('click', function() {
                    notification.remove();
                });
                
                setTimeout(() => notification.remove(), 5000);
            }
        },

        setupCharCounter: function() {
            if (this.elements.charCounter.length) {
                this.elements.commentTextarea.on('input', () => {
                    const count = this.elements.commentTextarea.val().length;
                    this.elements.charCounter.text(count);
                    
                    // Renk değişimi
                    const counterContainer = this.elements.charCounter.parent();
                    counterContainer.removeClass('warning danger');
                    
                    if (count > 4500) {
                        counterContainer.addClass('danger');
                    } else if (count > 4000) {
                        counterContainer.addClass('warning');
                    }
                });
            }
        },

        setupImageUpload: function() {
    // Önce mevcut butonları temizle
    this.elements.toolbar.find('.image-upload').remove();
    
    const imageUploadButton = `
        <button type="button" class="ruh-toolbar-button image-upload" title="Görsel Yükle">
            🖼️
            <input type="file" accept="image/*" multiple style="position: absolute; left: -9999px; opacity: 0;">
        </button>
    `;
    
    this.elements.toolbar.append(imageUploadButton);
    
    // Event delegation kullanarak tek bir handler ekle
    this.elements.toolbar.off('change.imageUpload').on('change.imageUpload', '.image-upload input[type="file"]', (e) => {
        this.handleImageUpload(e.target.files);
        // Input'u temizle
        e.target.value = '';
    });
    
    this.elements.toolbar.off('click.imageUpload').on('click.imageUpload', '.image-upload', (e) => {
        e.preventDefault();
        $(e.currentTarget).find('input[type="file"]').click();
    });
},

       handleImageUpload: function(files) {
    if (!files || files.length === 0) return;
    
    if (!ruh_comment_ajax.logged_in) {
        this.showNotification('Görsel yüklemek için giriş yapmalısınız.', 'warning');
        return;
    }
    
    Array.from(files).forEach(file => {
        if (!file.type.startsWith('image/')) {
            this.showNotification('Sadece görsel dosyalarını yükleyebilirsiniz.', 'error');
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) { // 5MB limit
            this.showNotification('Görsel dosyası 5MB\'dan küçük olmalıdır.', 'error');
            return;
        }
        
        this.uploadImage(file);
    });
},

uploadImage: function(file) {
    const formData = new FormData();
    formData.append('action', 'ruh_upload_image');
    formData.append('nonce', ruh_comment_ajax.nonce);
    formData.append('image', file);
    
    // Loading indicator
    const loadingId = 'upload-' + Date.now() + Math.random();
    this.showImagePreview(file, loadingId, true);
    
    $.ajax({
        url: ruh_comment_ajax.ajax_url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        timeout: 30000, // 30 saniye timeout
        success: (response) => {
            if (response.success) {
                this.state.uploadedImages.push(response.data.url);
                this.updateImagePreview(loadingId, response.data.url);
                
                // Textarea'ya görsel linkini ekle
                const currentText = this.elements.commentTextarea.val();
                const imageMarkdown = `\n![${file.name}](${response.data.url})\n`;
                this.elements.commentTextarea.val(currentText + imageMarkdown);
                this.elements.commentTextarea.trigger('input'); // Char counter update
                
                this.showNotification('Görsel başarıyla yüklendi!', 'success');
            } else {
                this.removeImagePreview(loadingId);
                this.showNotification(response.data.message || 'Görsel yüklenirken hata oluştu.', 'error');
            }
        },
        error: (xhr, status, error) => {
            this.removeImagePreview(loadingId);
            if (status === 'timeout') {
                this.showNotification('Görsel yükleme zaman aşımına uğradı. Daha küçük bir dosya deneyin.', 'error');
            } else {
                this.showNotification('Görsel yüklenirken hata oluştu.', 'error');
            }
            console.error('Upload error:', error);
        }
    });
},

        showImagePreview: function(file, id, isLoading = false) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const preview = $(`
                    <div class="image-preview" data-id="${id}">
                        <img src="${e.target.result}" alt="Preview">
                        ${isLoading ? '<div class="upload-loading">Yükleniyor...</div>' : ''}
                        <button type="button" class="remove-image" data-id="${id}">×</button>
                    </div>
                `);
                
                let previewContainer = $('.image-previews');
                if (!previewContainer.length) {
                    previewContainer = $('<div class="image-previews"></div>');
                    this.elements.commentForm.find('.form-submit').before(previewContainer);
                }
                
                previewContainer.append(preview);
            };
            reader.readAsDataURL(file);
        },

        updateImagePreview: function(id, url) {
            $(`.image-preview[data-id="${id}"] .upload-loading`).remove();
            $(`.image-preview[data-id="${id}"]`).attr('data-url', url);
        },

        removeImagePreview: function(id) {
            $(`.image-preview[data-id="${id}"]`).remove();
            
            // Remove from uploaded images array
            const urlToRemove = $(`.image-preview[data-id="${id}"]`).attr('data-url');
            if (urlToRemove) {
                this.state.uploadedImages = this.state.uploadedImages.filter(url => url !== urlToRemove);
            }
        },

        loadInitialData: function () {
            this.getComments(true);
            this.getReactions();
        },

        initDropdownFilter: function() {
            const $sortButtons = $('.sort-buttons');
            if ($sortButtons.length) {
                const currentSort = $('.sort-button.active').data('sort') || 'newest';
                const currentText = $('.sort-button.active').text() || 'En Yeniler';
                
                const dropdownHTML = `
                    <div class="sort-dropdown">
                        <button type="button" class="sort-dropdown-btn" data-current="${currentSort}">
                            ${currentText}
                        </button>
                        <div class="sort-dropdown-menu">
                            <div class="sort-option" data-sort="newest">En Yeniler</div>
                            <div class="sort-option" data-sort="best">En Beğenilenler</div>
                            <div class="sort-option" data-sort="oldest">En Eskiler</div>
                            <div class="sort-option" data-sort="most_replied">En Çok Yanıtlanan</div>
                        </div>
                    </div>
                `;
                
                $sortButtons.replaceWith(dropdownHTML);
                $(`.sort-option[data-sort="${currentSort}"]`).addClass('active');
            }
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
            
            this.elements.reactions.find('.reaction').each(function() {
                const $this = $(this);
                const reaction = $this.data('reaction');
                const count = data.counts && data.counts[reaction] ? parseInt(data.counts[reaction].count, 10) : 0;
                $this.find('.count').text(count);
                total += count;
            });

            this.elements.totalReactionCount.text(total);
            
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
                                <button type="button" class="ruh-toolbar-button image-upload" title="Görsel Yükle">
                                    🖼️
                                    <input type="file" accept="image/*" multiple style="position: absolute; left: -9999px; opacity: 0;">
                                </button>
                            </div>
                            <textarea name="comment" placeholder="${ruh_comment_ajax.text.reply_placeholder || 'Yanıtınızı yazın...'}" required></textarea>
                        </div>
                        <div class="inline-reply-actions">
                            <input type="hidden" name="comment_post_ID" value="${ruh_comment_ajax.post_id}">
                            <input type="hidden" name="comment_parent" value="${commentId}">
                            <div></div>
                            <div>
                                <button type="button" class="inline-reply-cancel">${ruh_comment_ajax.text.reply_cancel || 'İptal'}</button>
                                <button type="submit" class="inline-reply-submit">${ruh_comment_ajax.text.reply_send || 'Yanıtla'}</button>
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
            } else {
                replacement = `<${tag}>${selectedText}</${tag}>`;
            }

            textarea.value = text.substring(0, start) + replacement + text.substring(end);
            textarea.focus();
            textarea.selectionStart = start + replacement.length - selectedText.length;
            textarea.selectionEnd = textarea.selectionStart;
        },

        setupEventListeners: function () {
            // Görsel önizleme silme
            $(document).on('click', '.remove-image', (e) => {
                const id = $(e.target).data('id');
                this.removeImagePreview(id);
            });

            // Spoiler açma/kapama
            $('body').on('click', '.spoiler-header', function() {
                $(this).next('.spoiler-content').slideToggle(200);
                $(this).toggleClass('open');
            });

            // Dropdown toggle
            $(document).on('click', '.sort-dropdown-btn', e => {
                e.stopPropagation();
                $('.sort-dropdown').toggleClass('open');
            });

            // Option seçimi
            $(document).on('click', '.sort-option', e => {
                e.stopPropagation();
                
                const $option = $(e.currentTarget);
                const sortValue = $option.data('sort');
                const sortText = $option.text();
                
                $('.sort-option').removeClass('active');
                $option.addClass('active');
                
                $('.sort-dropdown-btn').text(sortText).attr('data-current', sortValue);
                $('.sort-dropdown').removeClass('open');
                
                this.state.currentSort = sortValue;
                this.getComments(true);
            });

            // Dışarı tıklamada dropdown'ı kapat
            $(document).on('click', e => {
                if (!$(e.target).closest('.sort-dropdown').length) {
                    $('.sort-dropdown').removeClass('open');
                }
            });

            // ESC tuşu ile kapat
            $(document).on('keydown', e => {
                if (e.keyCode === 27) {
                    $('.sort-dropdown').removeClass('open');
                }
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

            // Yorum düzenle
            this.elements.commentList.on('click', '.comment-edit-btn', e => {
                e.preventDefault();
                
                const $btn = $(e.currentTarget);
                const commentId = $btn.data('comment-id');
                const $commentItem = $btn.closest('.ruh-comment-item');
                const $commentText = $commentItem.find('.comment-text');
                
                // Zaten düzenleme modunda mı kontrol et
                if ($commentItem.find('.comment-edit-form').length > 0) {
                    return;
                }
                
                const currentText = $commentText.text().trim();
                
                const $editForm = $(`
                    <div class="comment-edit-form">
                        <div class="comment-edit-toolbar">
                            <button type="button" class="ruh-toolbar-button" data-tag="b"><b>B</b></button>
                            <button type="button" class="ruh-toolbar-button" data-tag="i"><i>I</i></button>
                            <button type="button" class="ruh-toolbar-button" data-tag="spoiler">[S]</button>
                        </div>
                        <textarea name="comment_content" required>${currentText}</textarea>
                        <div class="comment-edit-actions">
                            <div></div>
                            <div>
                                <button type="button" class="comment-edit-cancel">İptal</button>
                                <button type="button" class="comment-edit-save" data-comment-id="${commentId}">Kaydet</button>
                            </div>
                        </div>
                    </div>
                `);
                
                $commentText.hide();
                $commentText.after($editForm);
                $editForm.find('textarea').focus();
            });

            // Yorum düzenleme iptal
            this.elements.commentList.on('click', '.comment-edit-cancel', e => {
                const $form = $(e.currentTarget).closest('.comment-edit-form');
                const $commentText = $form.prev('.comment-text');
                
                $form.remove();
                $commentText.show();
            });

            // Yorum düzenleme kaydet
            this.elements.commentList.on('click', '.comment-edit-save', e => {
                const $btn = $(e.currentTarget);
                const $form = $btn.closest('.comment-edit-form');
                const $textarea = $form.find('textarea');
                const commentId = $btn.data('comment-id');
                const newContent = $textarea.val().trim();
                
                if (!newContent) {
                    this.showNotification('Yorum içeriği boş olamaz.', 'warning');
                    return;
                }
                
                const originalText = $btn.text();
                $btn.text('Kaydediliyor...').prop('disabled', true);
                
                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_edit_comment',
                    nonce: ruh_comment_ajax.nonce,
                    comment_id: commentId,
                    content: newContent
                })
                .done(response => {
                    if (response.success) {
                        const $commentText = $form.prev('.comment-text');
                        $commentText.html(response.data.content).show();
                        $form.remove();
                        this.showNotification(response.data.message, 'success');
                    } else {
                        this.showNotification(response.data.message || 'Yorum güncellenemedi.', 'error');
                    }
                })
                .always(() => {
                    $btn.text(originalText).prop('disabled', false);
                });
            });

            // Yorum sil
            this.elements.commentList.on('click', '.comment-delete-btn', e => {
                e.preventDefault();
                
                if (!confirm('Bu yorumu silmek istediğinizden emin misiniz?')) {
                    return;
                }
                
                const $btn = $(e.currentTarget);
                const commentId = $btn.data('comment-id');
                const $commentItem = $btn.closest('.ruh-comment-item');
                
                $btn.text('Siliniyor...').prop('disabled', true);
                
                $.post(ruh_comment_ajax.ajax_url, {
                    action: 'ruh_delete_comment',
                    nonce: ruh_comment_ajax.nonce,
                    comment_id: commentId
                })
                .done(response => {
                    if (response.success) {
                        $commentItem.fadeOut(400, () => {
                            $commentItem.remove();
                            // Yorum sayısını güncelle
                            const currentCount = parseInt(this.elements.commentCountSpan.text(), 10);
                            this.elements.commentCountSpan.text(Math.max(0, currentCount - 1));
                        });
                        this.showNotification(response.data.message, 'success');
                    } else {
                        this.showNotification(response.data.message || 'Yorum silinemedi.', 'error');
                        $btn.text('Sil').prop('disabled', false);
                    }
                })
                .fail(() => {
                    this.showNotification('Bir hata oluştu.', 'error');
                    $btn.text('Sil').prop('disabled', false);
                });
            });

            // Yorum düzenleme toolbar
            this.elements.commentList.on('click', '.comment-edit-toolbar .ruh-toolbar-button', e => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const $textarea = $btn.closest('.comment-edit-form').find('textarea')[0];
                const tag = $btn.data('tag');
                
                if (tag && $textarea) {
                    this.wrapText($textarea, tag);
                }
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
                        $('.image-previews').remove(); // Clear image previews
                        this.state.uploadedImages = []; // Clear uploaded images array
                        
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

            // Toolbar butonları - görsel butonunu hariç tut
            this.elements.toolbar.on('click', '.ruh-toolbar-button:not(.image-upload)', e => {
                e.preventDefault();
                const tag = $(e.currentTarget).data('tag');
                if (tag) {
                    this.wrapText(this.elements.commentTextarea[0], tag);
                }
            });

            // Kullanıcı profil linklerine tıklama - profil sayfasına git
            this.elements.commentList.on('click', '.avatar-link, .author-name', e => {
                e.preventDefault();
                const href = $(e.currentTarget).attr('href');
                if (href && href !== '#') {
                    window.open(href, '_blank');
                }
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
                
                $commentItem.find('.inline-reply-container').remove();
                
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
                                const $parentComment = $(`#comment-${response.data.parent_id}`);
                                let $children = $parentComment.children('.children');
                                
                                if (!$children.length) {
                                    $children = $('<ol class="children"></ol>');
                                    $parentComment.append($children);
                                }
                                
                                $children.append($newReply);
                            } else {
                                this.elements.commentList.prepend($newReply);
                            }
                            
                            $newReply.fadeIn(400);
                        }
                        
                        const commentId = $container.data('comment-id');
                        $container.slideUp(300, () => {
                            $container.remove();
                            this.state.replyForms.delete(commentId);
                        });
                        
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
            this.elements.commentList.on('click', '.inline-reply-toolbar .ruh-toolbar-button:not(.image-upload)', e => {
                e.preventDefault();
                const $btn = $(e.currentTarget);
                const $textarea = $btn.closest('.inline-reply-editor').find('textarea')[0];
                const tag = $btn.data('tag');
                
                if (tag && $textarea) {
                    this.wrapText($textarea, tag);
                }
            });

            // Inline reply görsel upload
            this.elements.commentList.on('change', '.inline-reply-toolbar .image-upload input[type="file"]', e => {
                this.handleImageUpload(e.target.files);
            });
            
            // Inline reply görsel upload button click
            this.elements.commentList.on('click', '.inline-reply-toolbar .image-upload', e => {
                e.preventDefault();
                $(e.currentTarget).find('input[type="file"]').click();
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
    
    // Global olarak erişilebilir yap
    window.RuhComments = RuhComments;
});