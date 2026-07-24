@props(['content', 'comment'])

@php
    $slug = optional($content)->slug;

    if ($attributes->get('type') == 'content') {
        $hiddenplaylistslug = '';
        $url = $slug ? route('content.comment.store', $slug) : '#';
    } elseif ($attributes->get('type') == 'news') {
        $hiddenplaylistslug = '';
        $url = $slug ? route('news.comment.store', $slug) : '#';
    } elseif ($attributes->get('type') == 'contentplaylist') {
        $hiddenplaylistslug = $attributes->get('playlistslug');
        $url = $slug ? route('contentplaylist.comment.store', $slug) : '#';
    } else {
        $hiddenplaylistslug = '';
        $url = $slug ? route('gallery.comment.store', $slug) : '#';
    }

    $user = auth()->user();
    $commentTotal = method_exists($comment, 'total') ? $comment->total() : $comment->count();
@endphp

<div class="mt-3 mb-4 comment-ajax-root" data-comment-refresh-url="{{ request()->fullUrl() }}">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden" id="comment">

        {{-- ── Composer ── --}}
        <div class="card-header border-0 bg-white p-4">

            <div class="comment-count mb-3">{{ $commentTotal }} Comments</div>

            <x-form action="{{ $url }}" method="post" class="comment-form w-100">
                <input type="hidden" value="{{ $hiddenplaylistslug }}" name="playlistslug">

                <div class="d-flex gap-3 align-items-start w-100">

                    <div class="flex-shrink-0">
                        @if(!empty($user->avatar))
                            <span class="avatar avatar-sm rounded-circle"
                                  style="background-image:url('{{ asset('storage/profile/' . $user->avatar) }}')"></span>
                        @else
                            <span class="avatar avatar-sm rounded-circle bg-primary-lt text-primary fw-bold">
                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                            </span>
                        @endif
                    </div>

                    <div class="comment-input-wrapper flex-grow-1 w-100">
                        <x-textarea
                            class="comment-textarea"
                            name="description"
                            placeholder="Add a comment..."
                            onkeyup="countWord(this)"
                            maxlength="1000"
                            rows="3"></x-textarea>

                        <div class="comment-submit-row">
                            <span id="note_remaining" class="text-muted small">1000 characters remaining</span>

                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">
                                <i class="ti ti-send me-1"></i>Submit
                            </button>
                        </div>
                    </div>

                </div>
            </x-form>
        </div>

        {{-- ── Comment list ── --}}
        <div class="card-body p-0 bg-white">
            @forelse($comment as $item)
                @include('components.comment-thread', [
                    'comment'            => $item,
                    'content'            => $content,
                    'url'                => $url,
                    'hiddenplaylistslug' => $hiddenplaylistslug,
                ])
            @empty
                <div class="text-center p-4">
                    <i class="ti ti-message-circle-off text-muted" style="font-size:40px;"></i>
                    <h6 class="mt-2 mb-1">No Comments Yet</h6>
                    <div class="text-muted small">Be the first to leave a comment.</div>
                </div>
            @endforelse

            @if(method_exists($comment, 'hasPages') && $comment->hasPages())
                <div class="comment-pagination p-3 border-top">
                    {{ $comment->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

@push('styles')
<style>
    /* ── Comment count heading (YouTube-style) ── */
    .comment-count {
        font-size: 16px;
        font-weight: 700;
        color: #1f2937;
        padding-bottom: 12px;
        border-bottom: 1px solid #e5e7eb;
    }

    /* ── Full-width composer / reply input (beats any inherited width) ── */
    .comment-form { width: 100% !important; }

    .comment-ajax-root.is-loading-comments {
        opacity: .65;
        pointer-events: none;
        transition: opacity .18s ease;
    }

    .comment-input-wrapper {
        flex: 1 1 auto !important;
        min-width: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }

    .comment-textarea {
        display: block;
        width: 100% !important;
        max-width: 100% !important;
        min-height: 84px;
        resize: vertical;
        border-radius: 8px;
    }

    .comment-reply-textarea { min-height: 40px; }

    .comment-submit-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 8px;
        flex-wrap: wrap;
    }

    /* ── Comment / reply rows ── */
    .comment-thread { padding: 14px 16px; }

    .comment-item-root { width: 100%; }

    /* Replies stay compact: no extra gap between reply items */
    .comment-item-reply {
        width: 100%;
        margin-top: 0;
    }

    .comment-bubble {
        background: #f4f6fb;
        border-radius: 14px;
        padding: 10px 14px;
        line-height: 1.45;
        word-break: break-word;
    }

    /* Author on top, comment text below (Facebook-style) */
    .comment-name {
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 4px;
    }
    .comment-text {
        font-size: 13px;
        line-height: 1.5;
        color: #1f2937;
    }

    .comment-meta { font-size: 12px; padding-left: 4px; }

    /* Action buttons (like / reply) as inline text links */
    .comment-action {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 12px;
        font-weight: 600;
        color: #6b7280 !important;
        text-decoration: none;
        line-height: 1;
    }
    .comment-action:hover { color: #374151 !important; }
    .comment-action.is-liked { color: #e0245e !important; }
    .comment-action i { font-size: 16px; }

    /* ── Reply form (indented to align with replies) ── */
    .reply-form {
        width: auto;
        margin-left: 40px;
    }

    /* ── Replies block: indented under the parent, flat left-aligned list ── */
    .replies-block {
        margin-top: 8px;
        margin-left: 40px;
    }

    /* Gap only between the parent comment and the reply list, not between replies */
    .reply-list { margin-top: 18px; }

    /* ── Show/hide replies toggle (Facebook/YouTube-style) ── */
    .comment-replies-toggle {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px !important;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        color: #2563eb !important;
        transition: background-color .15s ease, color .15s ease;
    }
    .comment-replies-toggle:hover {
        background: #eaf1ff;
        color: #1d4ed8 !important;
    }

    .replies-chevron {
        font-size: 16px;
        line-height: 1;
        transition: transform .2s ease;
    }
    .comment-replies-toggle.open .replies-chevron { transform: rotate(180deg); }

    /* Label swaps based on the `open` class toggled by JS */
    .comment-replies-toggle .replies-label-hide { display: none; }
    .comment-replies-toggle.open .replies-label-show { display: none; }
    .comment-replies-toggle.open .replies-label-hide { display: inline; }

    /* ── Pagination ── */
    .comment-pagination { display: flex; justify-content: center; }
    .comment-pagination nav { width: 100%; }
    .comment-pagination nav > div:first-child { display: none !important; }
    .comment-pagination nav > div:last-child {
        display: flex !important;
        justify-content: center !important;
        width: 100% !important;
    }
    .comment-pagination .pagination {
        justify-content: center !important;
        margin: 0 !important;
    }

    /* ── Dark mode ── */
    [data-bs-theme="dark"] .comment-count {
        color: #e5e7eb;
        border-bottom-color: rgba(255, 255, 255, 0.1);
    }
    [data-bs-theme="dark"] .comment-replies-toggle { color: #93c5fd !important; }
    [data-bs-theme="dark"] .comment-replies-toggle:hover {
        background: rgba(37, 99, 235, 0.15);
        color: #bfdbfe !important;
    }
    [data-bs-theme="dark"] .card-header,
    [data-bs-theme="dark"] .card-body {
        background-color: var(--tblr-card-bg, #1e293b) !important;
    }
    [data-bs-theme="dark"] .comment-bubble { background: rgba(255, 255, 255, 0.06); }
    [data-bs-theme="dark"] .comment-text { color: #e5e7eb; }

    /* ── Mobile ── */
    @media (max-width: 576px) {
        .comment-submit-row {
            flex-direction: column;
            align-items: flex-start;
        }
        .comment-thread { padding: 12px; }
    }
</style>
@endpush

@push('scripts')
<script>
    // Character counter for the main composer.
    function countWord(val) {
        var remaining = document.getElementById('note_remaining');
        if (!remaining) return;

        var len = val.value.length;
        remaining.textContent = len >= 1000 ? '' : (1000 - len) + ' characters remaining';
    }

    (function ($) {
        if (!$ || window.owlCommentAjaxInitialized) return;
        window.owlCommentAjaxInitialized = true;

        function selectorEscape(value) {
            if ($.escapeSelector) return $.escapeSelector(value);
            return String(value).replace(/([ #;&,.+*~':"!^$[\]()=>|\/@])/g, '\\$1');
        }

        function getScrollable($root) {
            var $modalBody = $root.closest('.modal').find('.modal-body').first();
            return $modalBody.length ? $modalBody : $();
        }

        function getState($root) {
            var $scrollable = getScrollable($root);

            return {
                pageScrollTop: $(window).scrollTop(),
                modalScrollTop: $scrollable.length ? $scrollable.scrollTop() : 0,
                activeElementId: document.activeElement ? document.activeElement.id : '',
                openReplyForms: $root.find('.reply-form:not(.d-none)').map(function () {
                    return this.id;
                }).get(),
                openReplyLists: $root.find('.reply-list:not(.d-none)').map(function () {
                    return this.id;
                }).get(),
                openReplyToggles: $root.find('.comment-replies-toggle.open').map(function () {
                    return $(this).data('target');
                }).get()
            };
        }

        function restoreState($root, state) {
            var $scrollable = getScrollable($root);

            state.openReplyForms.forEach(function (id) {
                $root.find('#' + selectorEscape(id)).removeClass('d-none');
            });

            state.openReplyLists.forEach(function (id) {
                $root.find('#' + selectorEscape(id)).removeClass('d-none');
            });

            state.openReplyToggles.forEach(function (target) {
                $root.find('.comment-replies-toggle[data-target="' + target + '"]').addClass('open');
            });

            if ($scrollable.length) {
                $scrollable.scrollTop(state.modalScrollTop);
            }

            $(window).scrollTop(state.pageScrollTop);
        }

        function findFreshRoot(response, $root) {
            var parsedHtml = $.parseHTML(response, document, false);
            var $html = $('<div>').append(parsedHtml);
            var modalId = $root.closest('.modal').attr('id');
            var $freshRoot = $();

            if (modalId) {
                $freshRoot = $html.find('#' + selectorEscape(modalId) + ' .comment-ajax-root').first();
            }

            if (!$freshRoot.length) {
                $freshRoot = $html.find('.comment-ajax-root').first();
            }

            return $freshRoot;
        }

        function replaceRoot($root, response, state) {
            var $freshRoot = findFreshRoot(response, $root);

            if (!$freshRoot.length) return $root;

            $root.html($freshRoot.html());
            $root.attr('data-comment-refresh-url', $freshRoot.attr('data-comment-refresh-url') || $root.attr('data-comment-refresh-url'));
            restoreState($root, state);

            return $root;
        }

        function refreshComments($root, url, state, updateHistory) {
            if (!$root.length || $root.hasClass('is-loading-comments')) return;

            $root.addClass('is-loading-comments');

            $.ajax({
                url: url,
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).done(function (response) {
                replaceRoot($root, response, state);
                $root.attr('data-comment-refresh-url', url);

                if (updateHistory && window.history && window.history.replaceState) {
                    window.history.replaceState({}, document.title, url);
                }
            }).fail(function (xhr) {
                showCommentError($root, xhr, 'Daftar komentar tidak dapat dimuat ulang.');
            }).always(function () {
                $root.removeClass('is-loading-comments');
                restoreState($root, state);
            });
        }

        function clearCommentError($root) {
            $root.find('.comment-ajax-error').remove();
        }

        function getCommentErrorMessage(xhr, fallback) {
            var response = xhr && xhr.responseJSON ? xhr.responseJSON : {};
            var errors = response.errors || {};
            var firstField = Object.keys(errors)[0];

            if (firstField && errors[firstField] && errors[firstField][0]) {
                return errors[firstField][0];
            }

            if (response.message && xhr.status !== 500) return response.message;
            if (xhr.status === 404) return 'Komentar atau endpoint tidak ditemukan.';
            if (xhr.status === 419) return 'Sesi telah berakhir. Muat ulang halaman lalu coba lagi.';
            if (xhr.status === 422) return 'Data komentar tidak valid.';
            if (xhr.status >= 500) return 'Server gagal memproses komentar. Silakan coba lagi.';

            return fallback;
        }

        function showCommentError($root, xhr, fallback) {
            var message = getCommentErrorMessage(xhr, fallback);
            var $error = $('<div>', {
                class: 'alert alert-danger comment-ajax-error mx-3 mt-3 mb-0',
                role: 'alert',
                text: message
            });

            clearCommentError($root);
            $root.find('#comment').prepend($error);
            console.error('Comment request failed:', xhr.status, message);
        }

        function csrfToken() {
            return $('meta[name="csrf-token"]').attr('content') || '';
        }

        $(document).on('click', '.comment-ajax-root .comment-like-toggle', function (event) {
            event.preventDefault();

            var $button = $(this);
            var $root = $button.closest('.comment-ajax-root');
            var commentId = $button.data('comment-id');
            var url = $button.data('url');

            if (!commentId || !url || $button.data('requestPending')) return;

            var shouldLike = $button.attr('aria-pressed') !== 'true';
            $button.data('requestPending', true).prop('disabled', true).attr('aria-busy', 'true');
            clearCommentError($root);

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    liked: shouldLike ? 1 : 0
                },
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).done(function (response) {
                var liked = Boolean(response.liked);

                $button.attr('aria-pressed', liked ? 'true' : 'false');
                $button.toggleClass('is-liked', liked);
                $button.find('i')
                    .toggleClass('ti-heart', !liked)
                    .toggleClass('ti-heart-filled', liked);
                $button.find('.comment-like-count').text(response.likes_count);
            }).fail(function (xhr) {
                showCommentError($root, xhr, 'Like tidak dapat diperbarui.');
            }).always(function () {
                $button.removeData('requestPending').prop('disabled', false).removeAttr('aria-busy');
            });
        });

        $(document).on('click', '.comment-reply-toggle', function (event) {
            event.preventDefault();

            var $button = $(this);
            var $root = $button.closest('.comment-ajax-root');
            var targetId = $button.data('target');
            var $form = $root.find('#' + selectorEscape(targetId));

            if (!$form.length) return;

            var shouldOpen = $form.hasClass('d-none');
            $root.find('.reply-form').addClass('d-none');
            $form.toggleClass('d-none', !shouldOpen);

            if (shouldOpen) {
                var textarea = $form.find('textarea').first();
                if (textarea.length) textarea.trigger('focus');
            }

            if ($root.length) {
                $root.attr('data-comment-refresh-url', $root.attr('data-comment-refresh-url') || document.URL);
            }
        });

        $(document).on('submit', '.comment-ajax-root .comment-reply-form', function (event) {
            event.preventDefault();

            var $form = $(this);
            var $root = $form.closest('.comment-ajax-root');
            var rootCommentId = $form.data('comment-id');
            var action = $form.attr('action');

            if (!action || action === '#' || !rootCommentId || $form.data('requestPending')) return;

            $form.data('requestPending', true);
            $form.find(':submit').prop('disabled', true);
            clearCommentError($root);

            $.ajax({
                url: action,
                method: 'POST',
                data: $form.serialize(),
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).done(function () {
                var state = getState($root);
                var repliesId = 'replies-' + rootCommentId;

                state.openReplyForms = [];
                if (state.openReplyLists.indexOf(repliesId) === -1) state.openReplyLists.push(repliesId);
                if (state.openReplyToggles.indexOf(repliesId) === -1) state.openReplyToggles.push(repliesId);

                $form.get(0).reset();
                refreshComments(
                    $root,
                    $root.attr('data-comment-refresh-url') || document.URL,
                    state,
                    false
                );
            }).fail(function (xhr) {
                showCommentError($root, xhr, 'Reply tidak dapat dikirim.');
            }).always(function () {
                $form.removeData('requestPending');
                $form.find(':submit').prop('disabled', false);
            });
        });

        $(document).on('click', '.comment-replies-toggle', function (event) {
            event.preventDefault();

            var $button = $(this);
            var $root = $button.closest('.comment-ajax-root');
            var targetId = $button.data('target');
            var $list = $root.find('#' + selectorEscape(targetId));

            if (!$list.length) return;

            $list.toggleClass('d-none');
            $button.toggleClass('open', !$list.hasClass('d-none'));
        });

        $(document).on('click', '.comment-ajax-root .comment-pagination a, .comment-ajax-root .pagination a', function (event) {
            event.preventDefault();

            var $link = $(this);
            var href = $link.attr('href');
            var $root = $link.closest('.comment-ajax-root');

            if (!href || !$root.length) return;

            refreshComments($root, href, getState($root), true);
        });

        $(document).on('submit', '.comment-ajax-root form:not(.comment-reply-form)', function (event) {
            event.preventDefault();

            var $form = $(this);
            var $root = $form.closest('.comment-ajax-root');
            var action = $form.attr('action');
            var method = ($form.attr('method') || 'POST').toUpperCase();
            var state = getState($root);
            var requestSucceeded = false;

            if (
                !action ||
                action === '#' ||
                !$root.length ||
                $root.hasClass('is-loading-comments') ||
                $form.data('requestPending')
            ) return;

            $form.data('requestPending', true);
            $form.find(':submit').prop('disabled', true).attr('aria-busy', 'true');
            $root.addClass('is-loading-comments');
            clearCommentError($root);

            $.ajax({
                url: action,
                method: method,
                data: $form.serialize(),
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).done(function (response, textStatus, jqXHR) {
                requestSucceeded = true;
                $root.removeClass('is-loading-comments');

                var currentUrl = $root.attr('data-comment-refresh-url') || document.URL;
                var responseUrl = jqXHR && jqXHR.responseURL ? jqXHR.responseURL : currentUrl;
                var freshRoot = typeof response === 'string'
                    ? findFreshRoot(response, $root)
                    : $();

                if (freshRoot.length) {
                    replaceRoot($root, response, state);
                    $root.attr('data-comment-refresh-url', responseUrl);
                } else {
                    refreshComments($root, currentUrl, state, false);
                }

                $form.get(0).reset();
            }).fail(function (xhr) {
                showCommentError($root, xhr, 'Komentar tidak dapat dikirim.');
                restoreState($root, state);
            }).always(function () {
                if (!requestSucceeded) {
                    $root.removeClass('is-loading-comments');
                }

                $form.removeData('requestPending');
                $form.find(':submit').prop('disabled', false).removeAttr('aria-busy');
            });
        });
    })(window.jQuery);
</script>
@endpush
