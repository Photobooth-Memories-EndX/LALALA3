@php
    $featured = $news->first();
    $readTime = ceil(str_word_count(strip_tags($featured->description ?? '')) / 200);
    $readTime = max(1, $readTime);
    $category = $featured->category ?? 'Technology';
@endphp

@if($featured)

@include('components.shared.hero-style')

<div class="card news-hero-card mb-4 overflow-hidden border-0 shadow-sm">
    <div class="card-body news-hero-surface text-white">
        <div class="row align-items-center g-3">

            {{-- Sisi Kiri: Badge, Judul, Deskripsi, Metadata --}}
            <div class="col-lg-8 news-hero-copy">
                
                {{-- Badge Top --}}
                <div class="d-inline-flex align-items-center gap-1 badge bg-white text-primary mb-2 rounded-pill news-hero-badge">
                    <i class="ti ti-star-filled text-warning"></i>
                    NEWS
                </div>

                {{-- Judul Artikel --}}
                <h1 class="mb-1 fw-bold news-hero-title text-truncate-2">
                    {{ Str::limit($featured->title ?? '', 65) }}
                </h1>

                {{-- Deskripsi Singkat --}}
                <p class="mb-2 text-white-75 news-hero-description text-truncate-1">
                    {{ Str::limit(strip_tags($featured->description ?? ''), 90) }}
                </p>

                {{-- Metadata Pills --}}
                <div class="d-flex flex-wrap gap-2 align-items-center mt-2">
                    <span class="news-hero-pill">
                        <i class="ti ti-clock me-1"></i>
                        {{ $readTime }} min read
                    </span>

                    <span class="news-hero-pill">
                        <i class="ti ti-eye me-1"></i>
                        {{ number_format($featured->views ?? 0) }} views
                    </span>

                    <span class="news-hero-pill">
                        <i class="ti ti-tag me-1"></i>
                        {{ $category }}
                    </span>
                </div>
            </div>

            {{-- Sisi Kanan: Tombol Aksi --}}
            <div class="col-lg-4 d-flex justify-content-lg-end align-items-center">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('news.user.view', $featured->slug) }}" class="btn btn-sm btn-white text-primary fw-bold rounded-pill px-3 py-2 shadow-sm news-btn-white">
                        <i class="ti ti-book-open me-1"></i> Read Article
                    </a>

                    {{-- Tombol Save Putih Solid --}}
                    <button type="button" class="btn btn-sm btn-white text-primary fw-bold rounded-pill px-3 py-2 shadow-sm news-btn-white">
                        <i class="ti ti-bookmark me-1"></i> Save
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* ============================================================
   NEWS HERO STYLES (PRESISI DENGAN HERO GALLERY)
   ============================================================ */
.news-hero-card {
    border-radius: 22px !important;
}

.news-hero-surface {
    height: 170px !important;
    min-height: 170px !important;
    max-height: 170px !important;
    padding: 24px 34px !important;
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
    
    /* Gradasi Biru */
    background: linear-gradient(
        135deg,
        #001b44 0%,
        #002b66 20%,
        #004b99 45%,
        #2a7bd1 70%,
        #82b8f5 88%,
        #dbeeff 100%
    ) !important;
}

/* Layer Radial Glow Efek Cahaya */
.news-hero-surface::before {
    content: "" !important;
    position: absolute !important;
    inset: 0 !important;
    display: block !important;
    background: radial-gradient(
        circle at 85% 20%,
        rgba(255, 255, 255, 0.22) 0%,
        transparent 28%
    ) !important;
    pointer-events: none !important;
    z-index: 1 !important;
}

.news-hero-surface > .row {
    width: 100%;
    position: relative;
    z-index: 2;
}

/* Typography Consistency */
.news-hero-badge {
    padding: 4px 10px !important;
    font-size: 10px !important;
    font-weight: 700 !important;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.news-hero-title {
    font-size: 1.35rem !important;
    line-height: 1.25;
    letter-spacing: -0.2px;
}

.news-hero-description {
    font-size: 13px !important;
    line-height: 1.35;
    max-width: 620px;
    opacity: 0.85;
}

/* Helper Class untuk membatasi jumlah baris teks */
.text-truncate-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.text-truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Metadata Pill */
.news-hero-pill {
    display: inline-flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.18) !important;
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.25) !important;
    border-radius: 999px;
    padding: 3px 10px;
    font-size: 11px;
    font-weight: 500;
    color: #ffffff !important;
}

/* Tombol Aksi (Putih Solid) */
.news-btn-white {
    background: #ffffff !important;
    color: #002b66 !important;
    border: none !important;
    transition: all 0.2s ease;
}

.news-btn-white:hover {
    background: #f8fafc !important;
    color: #001b44 !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15) !important;
}

/* Dark Mode Support */
[data-bs-theme="dark"] .news-hero-surface,
body.dark-mode .news-hero-surface {
    background: linear-gradient(
        135deg,
        #00112c 0%,
        #001b44 20%,
        #003366 45%,
        #1d5996 70%,
        #4e82be 88%,
        #82b8f5 100%
    ) !important;
}

@media (max-width: 991.98px) {
    .news-hero-surface {
        height: auto !important;
        min-height: 170px !important;
        max-height: none !important;
        padding: 20px !important;
    }
    .news-hero-title {
        font-size: 1.2rem !important;
    }
}
</style>

@endif