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

        <div class="card-header border-0 bg-white p-4">

            <div class="mb-3">
                <span class="comment-modern-badge">
                    {{ $commentTotal }} Comments
                </span>
            </div>

            <x-form action="{{ $url }}" method="post">
                <input type="hidden" value="{{ $hiddenplaylistslug }}" name="playlistslug">

                <div class="d-flex gap-3 align-items-start w-100">

                    <div class="flex-shrink-0">
                        @if(!empty($user->avatar))
                            <span class="avatar avatar-sm rounded-circle"
                                  style="background-image:url('{{ asset('storage/profile/' . $user->avatar) }}')">
                            </span>
                        @else
                            <span class="avatar avatar-sm rounded-circle bg-primary-lt text-primary fw-bold">
                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                            </span>
                        @endif
                    </div>

                    <div class="comment-modern-input-wrap">
                        <x-textarea
                            class="form-control comment-modern-textarea"
                            name="description"
                            placeholder="Tulis komentar..."
                            onkeyup="countWord(this)"
                            maxlength="1000"
                            rows="3">
                        </x-textarea>

                        <div class="comment-modern-submit-row">
                            <span id="note_remaining" class="text-muted small">
                                1000 characters remaining
                            </span>

                            <button class="btn btn-primary btn-sm rounded-pill px-3">
                                <i class="ti ti-send me-1"></i>
                                Submit
                            </button>
                        </div>
                    </div>

                </div>
            </x-form>
        </div>

        <div class="card-body p-0 bg-white">
            @forelse($comment as $item)
                @include('components.comment-thread', [
                    'comment' => $item,
                    'content' => $content,
                    'url' => $url,
                    'hiddenplaylistslug' => $hiddenplaylistslug,
                    'depth' => 0,
                ])
            @empty
                <div class="text-center p-4">
                    <i class="ti ti-message-circle-off text-muted" style="font-size:40px;"></i>
                    <h6 class="mt-2 mb-1">Belum ada komentar</h6>
                    <div class="text-muted small">
                        Jadilah yang pertama memberikan komentar.
                    </div>
                </div>
            @endforelse

            @if(method_exists($comment, 'hasPages') && $comment->hasPages())
                <div class="comment-modern-pagination p-3 border-top">
                    {{ $comment->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

@push('styles')
<style>
    /* ── Badge ── */
    .comment-modern-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 16px;
        border-radius: 999px;
        background: #eaf1ff;
        color: #2563eb;
        font-size: 13px;
        font-weight: 600;
    }

    /* ── Input area fills remaining space ── */
    .comment-modern-input-wrap {
        flex: 1 1 0;
        min-width: 0;
        width: 100%;
    }

    /* ── Textarea full width, taller ── */
    .comment-modern-textarea {
        width: 100% !important;
        min-height: 90px;
        resize: vertical;
        border-radius: 6px;
    }

    /* ── Submit row ── */
    .comment-modern-submit-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 8px;
        flex-wrap: wrap;
    }

    /* ── Thread root items ── */
    .comment-thread-root {
        padding: 16px;
        width: 100%;
    }

    .comment-thread-reply {
        padding: 10px 0 0 20px;
        width: 100%;
    }

    /* ── Pagination ── */
    .comment-modern-pagination {
        display: flex;
        justify-content: center;
    }

    .comment-modern-pagination nav {
        width: 100%;
    }

    .comment-modern-pagination nav > div:first-child {
        display: none !important;
    }

    .comment-modern-pagination nav > div:last-child {
        display: flex !important;
        justify-content: center !important;
        width: 100% !important;
    }

    .comment-modern-pagination .pagination {
        justify-content: center !important;
        margin: 0 !important;
    }

    /* ── Dark mode ── */
    [data-bs-theme="dark"] .comment-modern-badge {
        background: rgba(37, 99, 235, 0.15);
        color: #93c5fd;
    }

    [data-bs-theme="dark"] .card-header,
    [data-bs-theme="dark"] .card-body {
        background-color: var(--tblr-card-bg, #1e293b) !important;
    }

    /* ── Mobile ── */
    @media (max-width: 576px) {
        .comment-modern-submit-row {
            flex-direction: column;
            align-items: flex-start;
        }

        .comment-thread-reply {
            padding-left: 12px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    function countWord(val) {
        var len = val.value.length;

        if (len >= 1000) {
            $('#note_remaining').text('');
        } else {
            $('#note_remaining').text(1000 - len + ' characters remaining');
        }
    }
</script>
@endpush
