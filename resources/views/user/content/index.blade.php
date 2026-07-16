@extends('layouts.sidebar-master')

@section('style')
    <style>
        .content-filter-card {
            border-radius: 18px;
        }

        .content-hero-card {
            border-radius: 18px;
        }

        .content-hero-surface {
            background:
                radial-gradient(circle at 85% 20%, rgba(255, 255, 255, .24), transparent 28%),
                linear-gradient(135deg, #2f6de1 0%, #4d8df5 58%, #5da0ff 100%);
        }

        .content-hero-count {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            line-height: 1;
            color: #fff;
        }

        .content-hero-count strong {
            font-size: clamp(48px, 7vw, 82px);
            font-weight: 800;
            letter-spacing: 0;
            text-shadow: 0 12px 34px rgba(15, 23, 42, .22);
        }

        .content-hero-count span {
            margin-top: 8px;
            font-size: 15px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255, 255, 255, .78);
        }

        .content-reset-btn {
            border: 1px solid transparent;
            background: transparent;
            color: #2563eb;
            font-weight: 600;
        }

        .content-reset-btn:hover {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .content-reset-btn.is-active {
            background: #2563eb;
            border-color: #2563eb;
            color: #fff;
            box-shadow: 0 6px 16px rgba(37, 99, 235, .22);
        }

        .content-reset-btn.is-active:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
            color: #fff;
        }

        .content-quick-filter {
            border-radius: 18px;
        }

        .content-quick-filter .card-body {
            padding: 18px 20px;
        }

        .content-quick-filter-list {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .content-quick-filter-item {
            display: flex;
            align-items: center;
            gap: 10px;
            min-height: 54px;
            padding: 12px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #fff;
            color: #0f172a;
            font-weight: 600;
            text-decoration: none;
            transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease, background-color .18s ease;
        }

        .content-quick-filter-item:hover {
            transform: translateY(-2px);
            border-color: #bfdbfe;
            box-shadow: 0 10px 24px rgba(37, 99, 235, .1);
            color: #1d4ed8;
        }

        .content-quick-filter-icon {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 34px;
            border-radius: 10px;
            background: #eff6ff;
            font-size: 18px;
        }

        .content-card {
            height: 100%;
        }

        .content-card .card-body {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .content-card-action {
            margin-top: auto;
        }

        .learning-path-card {
            border-radius: 18px;
        }

        .learning-path-card .card-body {
            padding: 24px;
        }

        .learning-path-card:hover,
        .content-card:hover {
            transform: translateY(-3px);
            transition: all .2s ease;
        }

        /* ── Deadline row in content cards ────────────────────────── */
        .content-card-deadline {
            display: flex;
            align-items: center;
            gap: 6px;
            min-height: 22px;
            margin-top: 6px;
            font-size: 12px;
            color: #64748b;
        }
        .content-card-deadline i { flex: 0 0 auto; font-size: 14px; }

        [data-bs-theme="dark"] .content-card-deadline,
        body.dark-mode .content-card-deadline { color: #94a3b8; }

        .content-advanced-empty {
            display: none;
        }

        [data-bs-theme="dark"] .content-hero-surface,
        body.dark-mode .content-hero-surface {
            background:
                radial-gradient(circle at 85% 20%, rgba(96, 165, 250, .22), transparent 30%),
                linear-gradient(135deg, #0b1f44 0%, #12376f 58%, #175c8f 100%);
        }

        [data-bs-theme="dark"] .content-reset-btn,
        body.dark-mode .content-reset-btn {
            color: #93c5fd;
        }

        [data-bs-theme="dark"] .content-reset-btn:hover,
        body.dark-mode .content-reset-btn:hover {
            background: rgba(37, 99, 235, .16);
            border-color: rgba(147, 197, 253, .26);
            color: #bfdbfe;
        }

        [data-bs-theme="dark"] .content-reset-btn.is-active,
        body.dark-mode .content-reset-btn.is-active {
            background: #3b82f6;
            border-color: #3b82f6;
            color: #fff;
            box-shadow: 0 8px 20px rgba(59, 130, 246, .18);
        }

        [data-bs-theme="dark"] .content-quick-filter,
        body.dark-mode .content-quick-filter {
            background: #152235;
        }

        [data-bs-theme="dark"] .content-quick-filter-item,
        body.dark-mode .content-quick-filter-item {
            background: #192d45;
            border-color: rgba(255, 255, 255, .08);
            color: #e2e8f0;
        }

        [data-bs-theme="dark"] .content-quick-filter-item:hover,
        body.dark-mode .content-quick-filter-item:hover {
            border-color: rgba(96, 165, 250, .36);
            color: #93c5fd;
            box-shadow: 0 10px 24px rgba(0, 0, 0, .22);
        }

        [data-bs-theme="dark"] .content-quick-filter-icon,
        body.dark-mode .content-quick-filter-icon {
            background: rgba(37, 99, 235, .2);
        }

        @media (max-width: 991.98px) {
            .content-quick-filter-list {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 767.98px) {
            .content-hero-count {
                align-items: flex-start;
            }

            .content-hero-count strong {
                font-size: 44px;
            }

            .content-quick-filter-list {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection

@section('content')
<div class="page-body">
    <div class="container-xl">

        @php
            $selectedCategory = $classifications->firstWhere('id', $filterClassification);
            $hasActiveFilter = filled($filterSearch) || filled($filterClassification) || filled($filterType) || filled($filterAuthor) || filled($filterViewOrder) || $filterDateOrder !== 'newest' || filled(request('from_date')) || filled(request('to_date')) || request('duration', 'all') !== 'all' || filled(request('total_pages'));
        @endphp

        {{-- HERO --}}
        <div class="card content-hero-card mb-4 overflow-hidden border-0 shadow-sm">
            <div class="card-body content-hero-surface p-4 p-lg-5 text-white">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8">
                        <div class="d-inline-flex align-items-center gap-2 badge bg-white text-primary mb-3 px-3 py-2 rounded-pill">
                            <i class="ti ti-sparkles"></i>
                            Learning Content
                        </div>

                        <h1 class="mb-2 fw-bold">
                            {{ $selectedCategory->classification_name ?? 'All Learning Content' }}
                        </h1>

                        <p class="mb-0 text-white-75">
                           Find learning materials by category, content type, and newest additions on a single page.
                        </p>
                    </div>

                    <div class="col-lg-4 text-lg-end">
                        <div class="content-hero-count">
                            <strong>{{ $contents->total() }}</strong>
                            <span>Content</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- @include('user.content.components.learning-paths') --}}

        @include('user.content.components.content-search-filter')

        {{-- QUICK FILTER --}}
        <div class="card content-quick-filter mb-4 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h3 class="card-title mb-1">
                            <i class="ti ti-bolt me-1 text-primary"></i>
                            Quick Filter
                        </h3>
                        <div class="text-muted small">Access common content filters faster.</div>
                    </div>
                </div>

                <div class="content-quick-filter-list">
                    <a href="{{ route('content.user.index', ['type' => 'Video']) }}"
                       class="content-quick-filter-item">
                        <span class="content-quick-filter-icon text-primary">
                            <i class="ti ti-video"></i>
                        </span>
                        <span>Video Content</span>
                    </a>

                    <a href="{{ route('content.user.index', ['type' => 'YouTube']) }}"
                       class="content-quick-filter-item">
                        <span class="content-quick-filter-icon text-danger">
                            <i class="ti ti-brand-youtube"></i>
                        </span>
                        <span>YouTube Content</span>
                    </a>

                    <a href="{{ route('content.user.index', ['type' => 'Document']) }}"
                       class="content-quick-filter-item">
                        <span class="content-quick-filter-icon text-success">
                            <i class="ti ti-file-text"></i>
                        </span>
                        <span>Document Content</span>
                    </a>

                    <a href="{{ route('content.user.index', ['date_order' => 'newest']) }}"
                       class="content-quick-filter-item">
                        <span class="content-quick-filter-icon text-warning">
                            <i class="ti ti-clock"></i>
                        </span>
                        <span>Newest Content</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- MAIN CONTENT --}}
        <div class="d-flex align-items-center mb-3">
            <div>
                <h2 class="mb-0">Learning Materials</h2>
                <div class="text-muted">
                    Explore learning materials available in OWL LMS.
                </div>
            </div>
        </div>

        <div class="row row-cards">
            @forelse ($contents as $item)
                <div class="col-12 col-md-6 col-lg-4 col-xl-3 content-grid-item"
                     data-content-duration="{{ $item->duration }}">
                    @if (in_array($item->type, ['Video', 'YouTube']))
                        @include('user.content.components.card-video', ['item' => $item])
                    @else
                        @include('user.content.components.card-document', ['item' => $item])
                    @endif
                </div>
            @empty
                <div class="col-12">
                    <div class="empty card card-body rounded-4 shadow-sm border-0">
                        <div class="empty-img">
                            <i class="ti ti-folder-off" style="font-size: 64px;"></i>
                        </div>
                        <p class="empty-title">Content not found</p>
                        <p class="empty-subtitle text-muted">
                            Tidak ada content yang sesuai dengan filter yang dipilih.
                        </p>
                        <div class="empty-action">
                            <a href="{{ route('content.user.index') }}" class="btn btn-primary">
                                Reset Filter
                            </a>
                        </div>
                    </div>
                </div>
            @endforelse
        </div>

        <div id="contentAdvancedEmpty" class="content-advanced-empty mt-3">
            <div class="empty card card-body rounded-4 shadow-sm border-0">
                <div class="empty-img">
                    <i class="ti ti-filter-off" style="font-size: 64px;"></i>
                </div>
                <p class="empty-title">Content not found for selected filters</p>
                <p class="empty-subtitle text-muted">
                    Tidak ada content pada halaman ini yang sesuai dengan kombinasi filter lanjutan.
                </p>
            </div>
        </div>

        <div class="mt-4">
            {{ $contents->links() }}
        </div>

    </div>
</div>
@endsection

@section('script')
    <script>
        $(function () {
            const selectedDuration = @json(request('duration', 'all'));

            function parseDurationToSeconds(value) {
                if (!value) {
                    return null;
                }

                const parts = String(value)
                    .trim()
                    .split(':')
                    .map(function (part) {
                        return Number(part);
                    });

                if (parts.some(function (part) { return Number.isNaN(part); })) {
                    return null;
                }

                if (parts.length === 2) {
                    return (parts[0] * 60) + parts[1];
                }

                if (parts.length === 3) {
                    return (parts[0] * 3600) + (parts[1] * 60) + parts[2];
                }

                return null;
            }

            function durationMatchesBucket(seconds, bucket) {
                if (!bucket || bucket === 'all') {
                    return true;
                }

                if (seconds === null) {
                    return false;
                }

                switch (bucket) {
                    case 'lt10':
                        return seconds < 600;
                    case '10-30':
                        return seconds >= 600 && seconds <= 1800;
                    case '30-60':
                        return seconds > 1800 && seconds <= 3600;
                    case 'gt60':
                        return seconds > 3600;
                    default:
                        return true;
                }
            }

            function applyAdvancedContentFilters() {
                if (selectedDuration === 'all') {
                    return;
                }

                let visibleCount = 0;

                $('.content-grid-item').each(function () {
                    const $item = $(this);
                    const seconds = parseDurationToSeconds($item.data('content-duration'));
                    const durationMatches = durationMatchesBucket(seconds, selectedDuration);
                    const isVisible = durationMatches;

                    $item.toggleClass('d-none', !isVisible);

                    if (isVisible) {
                        visibleCount++;
                    }
                });

                $('#contentAdvancedEmpty').toggle(visibleCount === 0);
            }

            applyAdvancedContentFilters();
        });
    </script>
@endsection
