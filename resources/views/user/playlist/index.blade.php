@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">

        @include('user.playlist.components.playlist-hero')

        @include('user.playlist.components.playlist-continue-learning')

        @include('user.playlist.components.playlist-filters')

        @if(count($trainingplan) > 0)

            @include('user.playlist.components.playlist-grid')

            <div class="playlist-pagination mt-4 d-flex justify-content-center">
                {{ $trainingplan->links() }}
            </div>

        @else

            @include('user.playlist.components.playlist-empty')

        @endif

    </div>
</div>
@endsection

@section('style')
<style>
    /* ============================================================
       1. PAGINATION & CONTAINER STYLES
       ============================================================ */
    .playlist-pagination nav {
        width: 100%;
    }

    .playlist-pagination nav > div:first-child {
        display: none !important;
    }

    .playlist-pagination nav > div:last-child {
        display: flex !important;
        justify-content: center !important;
        width: 100% !important;
    }

    .playlist-pagination .pagination {
        justify-content: center !important;
        margin-bottom: 0 !important;
    }

    .rounded-4 {
        border-radius: 20px !important;
    }

    /* ============================================================
       2. HERO TOP BANNER (.playlist-hero) & TYPOGRAPHY FIX
       ============================================================ */
    .playlist-hero {
        background:
            radial-gradient(circle at top right, rgba(5, 80, 125, .12), transparent 35%),
            linear-gradient(135deg, rgba(5, 80, 125, .05), var(--tblr-bg-surface));
        border: 1px solid var(--tblr-border-color);
    }

    /* Memperbaiki warna teks banner agar kontras dengan latar belakang terang */
    .playlist-hero h1,
    .playlist-hero h2,
    .playlist-hero h3,
    .playlist-hero .h1,
    .playlist-hero .h2,
    .playlist-hero .h3,
    .playlist-hero .playlist-hero-title {
        color: #001b44 !important; /* Warna judul biru gelap pekat */
        font-weight: 800 !important;
    }

    .playlist-hero p,
    .playlist-hero .text-muted,
    .playlist-hero .playlist-hero-subtitle {
        color: #4a5568 !important; /* Warna deskripsi abu-abu gelap pekat */
        font-weight: 500 !important;
    }

    .playlist-hero .text-uppercase,
    .playlist-hero .small,
    .playlist-hero .playlist-hero-badge {
        color: #004b99 !important; /* Warna tag "LEARNING PLAYLIST" biru medium tegas */
        font-weight: 700 !important;
        letter-spacing: 0.05em;
    }

    /* KOTAK STATISTIK HIJAU (21 Playlists & 217 Contents) */
    .playlist-hero .card,
    .playlist-hero .bg-surface,
    .playlist-hero .col-auto .card,
    .playlist-hero div[class*="rounded"] {
        background: #22c55e !important; /* Warna Hijau Emerald */
        border: none !important;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.25) !important;
        border-radius: 12px !important;
    }

    /* Angka & Label di dalam Kotak Hijau Tetap Putih */
    .playlist-hero .card *,
    .playlist-hero .col-auto .card *,
    .playlist-hero div[class*="rounded"] * {
        color: #ffffff !important;
    }

    .playlist-hero .card .h1, 
    .playlist-hero .card .h2, 
    .playlist-hero .card .display-6,
    .playlist-hero .card .fw-bold {
        font-size: 1.75rem;
        font-weight: 800 !important;
    }

    .playlist-hero .card .small,
    .playlist-hero .card span,
    .playlist-hero .card .text-muted {
        font-weight: 700 !important;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    /* ============================================================
       3. CONTINUE LEARNING BANNER (.playlist-continue-card) - BLUE GRADIENT
       ============================================================ */
    .playlist-continue-card {
        border-radius: 18px !important;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: 
            radial-gradient(circle at 85% 20%, rgba(255, 255, 255, 0.2) 0%, transparent 28%),
            linear-gradient(135deg, #001b44 0%, #002b66 25%, #004b99 50%, #2a7bd1 75%, #60a5fa 100%) !important;
        color: #ffffff !important;
        box-shadow: 0 8px 24px rgba(0, 27, 68, 0.15);
    }

    .playlist-continue-card h1,
    .playlist-continue-card h2,
    .playlist-continue-card h3,
    .playlist-continue-card h4,
    .playlist-continue-card p,
    .playlist-continue-card span,
    .playlist-continue-card div {
        color: #ffffff !important;
    }

    .playlist-continue-card .btn,
    .playlist-continue-card a.btn {
        background: #ffffff !important;
        color: #002b66 !important;
        font-weight: 700;
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }

    .playlist-continue-card .btn:hover,
    .playlist-continue-card a.btn:hover {
        background: #f1f5ff !important;
        color: #00112c !important;
        transform: translateY(-2px);
    }

    .playlist-progress-badge {
        min-width: 112px;
        padding: 14px 16px;
        border-radius: 16px;
        text-align: center;
        background: rgba(255, 255, 255, 0.25) !important;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.35);
        backdrop-filter: blur(8px);
    }

    .playlist-progress-badge .small {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: .05em;
        opacity: .95;
    }

    .playlist-white-progress {
        height: 8px;
        background: rgba(255, 255, 255, 0.25) !important;
        border-radius: 999px;
    }

    .playlist-white-progress .progress-bar {
        background-color: #ffffff !important;
    }

    .playlist-continue-title {
        max-width: 560px;
        line-height: 1.25;
    }

    .text-white-75 {
        color: rgba(255, 255, 255, 0.85) !important;
    }

    .playlist-learning-path-wrap {
        padding-left: 2px;
        padding-right: 2px;
    }

    /* ============================================================
       4. PLAYLIST CARD GRID & PLACEHOLDERS
       ============================================================ */
    .playlist-card {
        transition: all .2s ease;
    }

    .playlist-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 30px rgba(15, 23, 42, .12) !important;
    }

    .playlist-thumbnail {
        height: 180px;
        object-fit: cover;
        background: var(--tblr-bg-surface-secondary);
    }

    .playlist-card-image {
        width: 100%;
        height: 180px;
        object-fit: cover;
        display: block;
    }

    .playlist-card-placeholder {
        width: 100%;
        height: 180px;
        background: #D9D9D6;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .playlist-card-placeholder-icon {
        width: 64px;
        height: 64px;
        border-radius: 8px;
        background: #D9D9D6;
        border: 1px solid #BBBCBC;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .playlist-card-placeholder-icon i {
        font-size: 30px;
        color: #05507D;
    }

    .playlist-card-placeholder-text {
        font-size: 13px;
        color: #8A8D8F;
    }

    .playlist-title {
        font-size: 16px;
        font-weight: 700;
        min-height: 44px;
    }

    .playlist-content-list {
        min-height: 72px;
    }

    .playlist-filter-card {
        border: 1px solid var(--tblr-border-color);
    }

    /* ============================================================
       5. DARK MODE SUPPORT
       ============================================================ */
    [data-bs-theme="dark"] .playlist-hero,
    body.theme-dark .playlist-hero,
    body.dark .playlist-hero {
        background:
            radial-gradient(circle at top right, rgba(5, 80, 125, .25), transparent 35%),
            linear-gradient(135deg, rgba(5, 80, 125, .12), var(--tblr-bg-surface));
    }

    [data-bs-theme="dark"] .playlist-hero h1,
    [data-bs-theme="dark"] .playlist-hero .playlist-hero-title,
    body.theme-dark .playlist-hero h1 {
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .playlist-hero p,
    [data-bs-theme="dark"] .playlist-hero .text-muted,
    body.theme-dark .playlist-hero p {
        color: #cbd5e1 !important;
    }

    [data-bs-theme="dark"] .playlist-card-placeholder,
    body.theme-dark .playlist-card-placeholder,
    body.dark .playlist-card-placeholder {
        background: #53565A;
    }

    [data-bs-theme="dark"] .playlist-card-placeholder-icon,
    body.theme-dark .playlist-card-placeholder-icon,
    body.dark .playlist-card-placeholder-icon {
        background: #53565A;
        border-color: rgba(255, 255, 255, .12);
    }

    @media (max-width: 991.98px) {
        .playlist-progress-badge {
            min-width: 96px;
        }
    }
</style>
@endsection

@section('script')
@include('user.playlist.components.playlist-script')
@endsection