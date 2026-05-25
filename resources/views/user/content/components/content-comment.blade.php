<div class="card border-0 shadow-sm rounded-4 mb-4" id="comment">
    <div class="card-body p-4 border-bottom">

        <h3 class="card-title mb-4">
            <i class="ti ti-message-circle text-primary me-1"></i>
            Diskusi Kursus
        </h3>

        {{-- FORM COMMENT --}}
        <div class="d-flex gap-3">
            <span class="avatar avatar-md rounded-circle bg-primary-lt text-primary">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </span>

            <div class="flex-fill">
                <form action="{{ route('content.comment.store', $content->slug) }}" method="POST">
                    @csrf

                    <textarea
                        name="comment"
                        rows="3"
                        class="form-control modern-comment-input"
                        placeholder="Tulis pendapat atau pertanyaan Anda..."
                    ></textarea>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <span class="text-muted small">
                            Berikan komentar yang sopan dan membangun.
                        </span>

                        <button type="submit" class="btn btn-primary rounded-pill px-4">
                            <i class="ti ti-send me-1"></i>
                            Submit Comment
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    {{-- LIST COMMENT --}}
    <div class="card-body p-0">
        @forelse ($comment as $item)
            <div class="p-4 border-bottom">
                <div class="d-flex gap-3">

                    <span class="avatar rounded-circle bg-primary-lt text-primary">
                        {{ strtoupper(substr($item->user->name ?? 'U', 0, 1)) }}
                    </span>

                    <div class="flex-fill">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <div>
                                <div class="fw-bold">
                                    {{ $item->user->name ?? 'User' }}
                                </div>
                                <div class="text-muted small">
                                    {{ $item->created_at->diffForHumans() }}
                                </div>
                            </div>

                            <button type="button" class="btn btn-ghost-secondary btn-sm rounded-pill">
                                <i class="ti ti-dots"></i>
                            </button>
                        </div>

                        <div class="comment-content">
                            {{ $item->comment }}
                        </div>

                        <div class="d-flex gap-2 mt-3">
                            <button type="button" class="btn btn-sm btn-ghost-secondary rounded-pill">
                                <i class="ti ti-thumb-up me-1"></i>
                                Like
                            </button>

                            <button type="button" class="btn btn-sm btn-ghost-secondary rounded-pill">
                                <i class="ti ti-message-circle me-1"></i>
                                Reply
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        @empty
            <div class="p-4 text-center text-muted">
                <i class="ti ti-message-off" style="font-size: 42px;"></i>
                <p class="mt-2 mb-0">
                    Belum ada komentar. Jadilah yang pertama berdiskusi.
                </p>
            </div>
        @endforelse
    </div>
</div>