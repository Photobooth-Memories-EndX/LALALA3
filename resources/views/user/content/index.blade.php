@extends('layouts.sidebar-master')

@section('style')
    <link href="{{ asset('Tabler/dist/css/tabler-vendors.min.css?1684106062') }}" rel="stylesheet"/>
    @include('components.Home.styles')
    <style>
        :root {
            --owl-ui-radius: 20px;
            --owl-ui-shadow: 0 2px 12px rgba(15, 23, 42, .05);
            --owl-ui-shadow-hover: 0 10px 28px rgba(15, 23, 42, .10);
            --owl-ui-border: #e2e8f0;
            --owl-ui-surface: #fff;
            --owl-ui-text: #0f172a;
            --owl-ui-muted: #64748b;
        }

        .content-filter-card {
            border-radius: var(--owl-ui-radius) !important;
            border: 1px solid var(--owl-ui-border) !important;
            box-shadow: var(--owl-ui-shadow) !important;
            overflow: hidden;
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

        .content-card-thumb {
            border-radius: 0;
        }

        .content-card {
            height: 100%;
            border: 1px solid var(--owl-ui-border);
            border-radius: var(--owl-ui-radius);
            box-shadow: var(--owl-ui-shadow);
            overflow: hidden;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .content-card .card-body {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 18px;
        }

        .content-card-action {
            margin-top: auto;
        }

        .learning-path-card {
            border-radius: var(--owl-ui-radius) !important;
        }

        .learning-path-card .card-body {
            padding: 24px;
        }

        .learning-path-card:hover,
        .content-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--owl-ui-shadow-hover);
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

        .content-empty-card,
        .content-filter-empty-card {
            border-radius: var(--owl-ui-radius) !important;
            border: 1px solid var(--owl-ui-border) !important;
            box-shadow: var(--owl-ui-shadow) !important;
            overflow: hidden;
        }

        .learning-filter-modal .modal-content {
            border-radius: var(--owl-ui-radius) !important;
            overflow: hidden;
        }

        .learning-filter-modal .form-control,
        .learning-filter-modal .form-select,
        .content-filter-card .form-control,
        .content-filter-card .form-select,
        .content-filter-card .btn {
            border-radius: 12px !important;
        }

        .content-pagination-wrap .pagination {
            gap: .4rem;
        }

        .content-pagination-wrap .page-link {
            min-width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px !important;
            border-color: #dbe4f0;
            font-weight: 600;
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

        [data-bs-theme="dark"] .content-filter-card,
        body.dark-mode .content-filter-card,
        [data-bs-theme="dark"] .content-card,
        body.dark-mode .content-card,
        [data-bs-theme="dark"] .content-empty-card,
        body.dark-mode .content-empty-card,
        [data-bs-theme="dark"] .content-filter-empty-card,
        body.dark-mode .content-filter-empty-card {
            background: #152235;
            border-color: rgba(255, 255, 255, .07);
            color: #f1f5f9;
            box-shadow: none !important;
        }

        [data-bs-theme="dark"] .content-card .card-body,
        body.dark-mode .content-card .card-body {
            background: #152235 !important;
            border-radius: 0 0 var(--owl-ui-radius) var(--owl-ui-radius);
        }

        [data-bs-theme="dark"] .content-card-title a,
        body.dark-mode .content-card-title a {
            color: #f1f5f9;
        }

        [data-bs-theme="dark"] .content-card-meta,
        [data-bs-theme="dark"] .content-card .text-muted,
        body.dark-mode .content-card-meta,
        body.dark-mode .content-card .text-muted {
            color: #94a3b8 !important;
        }

        [data-bs-theme="dark"] .learning-filter-modal .modal-content,
        body.dark-mode .learning-filter-modal .modal-content {
            background: #152235;
            color: #f1f5f9;
            border: 1px solid rgba(255, 255, 255, .07);
            box-shadow: 0 20px 48px rgba(0, 0, 0, .34);
        }

        [data-bs-theme="dark"] .learning-filter-modal .form-control,
        [data-bs-theme="dark"] .learning-filter-modal .form-select,
        [data-bs-theme="dark"] .content-filter-card .form-control,
        [data-bs-theme="dark"] .content-filter-card .form-select,
        body.dark-mode .learning-filter-modal .form-control,
        body.dark-mode .learning-filter-modal .form-select,
        body.dark-mode .content-filter-card .form-control,
        body.dark-mode .content-filter-card .form-select {
            background: #192d45;
            border-color: rgba(255, 255, 255, .08);
            color: #f1f5f9;
        }

        [data-bs-theme="dark"] .content-pagination-wrap .page-link,
        body.dark-mode .content-pagination-wrap .page-link {
            background: #152235;
            border-color: rgba(255, 255, 255, .08);
            color: #93c5fd;
        }

        [data-bs-theme="dark"] .content-pagination-wrap .page-item.active .page-link,
        body.dark-mode .content-pagination-wrap .page-item.active .page-link {
            background: #3b82f6;
            border-color: #3b82f6;
            color: #fff;
        }

    </style>
@endsection

@section('content')
<div class="page-body">
    <div class="container-xl py-4">

        {{-- HERO --}}
        @include('components.Home.page-header', [
            'heroPageTitle' => 'All Content',
            'heroPageSubtitle' => 'Browse all available learning materials, videos, documents, and playlists.',
        ])

        <div class="row g-4 mb-4">
            @include('components.Home.hero')
        </div>

        {{-- @include('user.content.components.learning-paths') --}}

        @include('user.content.components.content-search-filter')

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
            @forelse ($contents->getCollection()->take(12) as $item)
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
                    <div class="empty card card-body content-empty-card">
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
            <div class="empty card card-body content-filter-empty-card">
                <div class="empty-img">
                    <i class="ti ti-filter-off" style="font-size: 64px;"></i>
                </div>
                <p class="empty-title">Content not found for selected filters</p>
                <p class="empty-subtitle text-muted">
                    Tidak ada content pada halaman ini yang sesuai dengan kombinasi filter lanjutan.
                </p>
            </div>
        </div>

        <div class="mt-4 content-pagination-wrap">
            {{ $contents->links() }}
        </div>

    </div>
</div>

@include('components.Home.assessment-modal')
@endsection

@section('script')
    @include('components.Home.scripts')
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
