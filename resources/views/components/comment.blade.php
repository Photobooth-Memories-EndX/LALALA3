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

<div class="mt-3 mb-4">
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
                            placeholder="Tulis komentar..."
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
                    <h6 class="mt-2 mb-1">Belum ada komentar</h6>
                    <div class="text-muted small">Jadilah yang pertama memberikan komentar.</div>
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

    // Event delegation: keeps working even after comments are re-rendered/paginated,
    // and does NOT depend on the Bootstrap JS bundle being present.
    document.addEventListener('click', function (e) {

        // Reply button -> toggle the (shared) reply form for that root comment.
        var replyBtn = e.target.closest('.comment-reply-toggle');
        if (replyBtn) {
            var form = document.getElementById(replyBtn.dataset.target);
            if (form) {
                form.classList.toggle('d-none');
                if (!form.classList.contains('d-none')) {
                    var ta = form.querySelector('textarea');
                    if (ta) ta.focus();
                }
            }
            return;
        }

        // View replies / Hide replies -> toggle the reply list + swap the label.
        var repliesBtn = e.target.closest('.comment-replies-toggle');
        if (repliesBtn) {
            var list = document.getElementById(repliesBtn.dataset.target);
            if (list) {
                list.classList.toggle('d-none');
                repliesBtn.classList.toggle('open', !list.classList.contains('d-none'));
            }
            return;
        }
    });
</script>
@endpush
