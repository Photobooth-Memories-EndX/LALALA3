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
            <x-form action="{{ $url }}" method="post">
                <input type="hidden" value="{{ $hiddenplaylistslug }}" name="playlistslug">

                <div class="comment-compose-row">

                    <div class="comment-count-box">
                        {{ $commentTotal }} Comments
                    </div>

                    <div class="comment-avatar">
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

                    <div class="comment-input-area">
                        <x-textarea
                            class="form-control modern-comment-input"
                            name="description"
                            placeholder="Tulis komentar..."
                            onkeyup="countWord(this)"
                            maxlength="1000"
                            rows="2">
                        </x-textarea>

                        <div class="comment-submit-row">
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
                <div class="comment-pagination p-3 border-top">
                    {{ $comment->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

@push('styles')
<style>


    .comment-pagination {
        display: flex;
        justify-content: center;
    }

    .comment-pagination nav {
        width: 100%;
    }

    .comment-pagination nav > div:first-child {
        display: none !important;
    }

    .comment-pagination nav > div:last-child {
        display: flex !important;
        justify-content: center !important;
        width: 100% !important;
    }

    .comment-pagination .pagination {
        justify-content: center !important;
        margin: 0 !important;
    }
    .comment-compose-row {
        display: grid;
        grid-template-columns: auto auto minmax(260px, 420px);
        align-items: start;
        gap: 12px;
    }

    .comment-count-box {
        min-width: 120px;
        height: 32px;
        padding: 0 16px;
        border-radius: 999px;
        background: #eaf1ff;
        color: #2563eb;
        font-size: 13px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 17px;
    }

    .comment-avatar {
        margin-top: 16px;
    }

    .modern-comment-input {
        width: 100%;
        min-height: 52px;
        resize: vertical;
        border-radius: 6px;
    }

    .comment-submit-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-top: 8px;
    }

    @media (max-width: 768px) {
        .comment-compose-row {
            grid-template-columns: auto 1fr;
        }

        .comment-count-box {
            grid-column: 1 / -1;
            margin-top: 0;
            justify-self: start;
        }

        .comment-avatar {
            margin-top: 8px;
        }

        .comment-input-area {
            width: 100%;
        }

        .comment-submit-row {
            flex-direction: column;
            align-items: flex-start;
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