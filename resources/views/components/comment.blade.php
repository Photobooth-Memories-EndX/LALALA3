@props(['content', 'comment'])

@php
    if ($attributes->get('type') == 'content') {
        $hiddenplaylistslug = '';
        $url = route('content.comment.store', $content->slug);
    } elseif ($attributes->get('type') == 'news') {
        $hiddenplaylistslug = '';
        $url = route('news.comment.store', $content->slug);
    } elseif ($attributes->get('type') == 'contentplaylist') {
        $hiddenplaylistslug = $attributes->get('playlistslug');
        $url = route('contentplaylist.comment.store', $content->slug);
    } else {
        $hiddenplaylistslug = '';
        $url = route('gallery.comment.store', $content->slug);
    }

    $user = auth()->user();
@endphp

<div class="mt-3 mb-4">
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden" id="comment">

        <div class="card-body p-3 border-bottom bg-white">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h5 class="fw-bold mb-1">
                        <i class="ti ti-message-circle text-primary me-1"></i>
                        Diskusi Kursus
                    </h5>
                    <div class="text-muted small">
                        Bagikan pertanyaan atau insight Anda.
                    </div>
                </div>

                <span class="badge bg-primary-lt text-primary rounded-pill px-3 py-2 small">
                    {{ $comment->total() }} Comments
                </span>
            </div>

            <div class="d-flex gap-2 align-items-start">
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

                <div class="flex-fill">
                    <x-form action="{{ $url }}" method="post">
                        <x-textarea
                            class="form-control modern-comment-input"
                            name="description"
                            placeholder="Tulis komentar..."
                            onkeyup="countWord(this)"
                            maxlength="1000"
                            rows="2">
                        </x-textarea>

                        <input type="hidden" value="{{ $hiddenplaylistslug }}" name="playlistslug">

                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span id="note_remaining" class="text-muted small">
                                1000 characters remaining
                            </span>

                            <button class="btn btn-primary btn-sm rounded-pill px-3">
                                <i class="ti ti-send me-1"></i>
                                Submit
                            </button>
                        </div>
                    </x-form>
                </div>
            </div>
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

            @if($comment->hasPages())
                <div class="p-3 border-top d-flex justify-content-center">
                    {{ $comment->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

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