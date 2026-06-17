@php
    $commentTotal = method_exists($comment, 'total') ? $comment->total() : $comment->count();
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-3">
    <div class="card-body p-4">

        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <div class="text-muted small mb-1">
                    {{ date('d F Y', strtotime($content->created_at)) }}
                    by {{ $content->author ?? '-' }}
                </div>

                <h2 class="mb-2 fs-2 fw-bold">
                    {{ $content->title }}
                </h2>

                <div class="d-flex flex-wrap gap-2">
                    <span class="badge bg-blue-lt text-blue">
                        {{ $content->classification->classification_name ?? 'Learning' }}
                    </span>

                    <span class="badge bg-green-lt text-green">
                        {{ $content->type ?? 'Content' }}
                    </span>
                </div>
            </div>

            <div class="d-flex gap-2">
                {{-- <button type="button" class="btn btn-icon btn-outline-secondary rounded-3">
                    <i class="far fa-bookmark"></i>
                </button> --}}

            <button type="button"
                    class="btn btn-icon btn-outline-secondary rounded-3"
                    onclick="shareContent()">
                <i class="fas fa-share-alt"></i>
            </button>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="#" class="btn btn-light border-0 shadow-none text-muted">
                <i class="fas fa-eye me-2"></i>
                {{ $content->views ?? 0 }} Views
            </a>

            <a href="#comment" class="btn btn-light border-0 shadow-none text-muted">
                <i class="far fa-comment-dots me-2"></i>
                {{ $commentTotal }} Comments
            </a>

            <span class="btn btn-light border-0 shadow-none text-muted">
                <i class="far fa-clock me-2"></i>
                {{ $content->duration ?? '-' }}
            </span>
        </div>

        <div class="chapter-description-box">
            <h4 class="fs-5 fw-bold mb-2">Overview</h4>

            <p class="mb-3 text-muted">
                {!! nl2br($content->description) !!}
            </p>

            <h4 class="fs-5 fw-bold mb-3">What you'll learn:</h4>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="learn-item">
                        <i class="fas fa-check-circle text-success"></i>
                        <span>Memahami materi utama pada chapter ini</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="learn-item">
                        <i class="fas fa-check-circle text-success"></i>
                        <span>Menyelesaikan progress pembelajaran</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="learn-item">
                        <i class="fas fa-check-circle text-success"></i>
                        <span>Meningkatkan pemahaman materi</span>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="learn-item">
                        <i class="fas fa-check-circle text-success"></i>
                        <span>Siap lanjut ke chapter berikutnya</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>