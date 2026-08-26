@once
<style>
    .assessment-page-header {
        margin-bottom: 1.25rem !important;
        padding-top: 0.5rem;
    }

    .assessment-back-link,
    .assessment-page-header .assessment-back a {
        display: inline-flex;
        align-items: center;
        color: var(--tblr-primary, #206bc4) !important;
        font-size: 0.9rem;
        font-weight: 600;
        text-decoration: none !important;
        background: transparent !important;
        border: none !important;
        outline: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
        transition: color 0.15s ease-in-out;
        cursor: pointer;
    }

    .assessment-back-link:hover,
    .assessment-page-header .assessment-back a:hover {
        color: var(--tblr-primary-dark, #185294) !important;
        text-decoration: underline !important;
        background: transparent !important;
        box-shadow: none !important;
        border: none !important;
    }

    .assessment-back-link i,
    .assessment-page-header .assessment-back a i {
        font-size: 1rem;
        transition: transform 0.15s ease-in-out;
    }

    .assessment-back-link:hover i,
    .assessment-page-header .assessment-back a:hover i {
        transform: translateX(-2px);
    }

    [data-bs-theme="dark"] .assessment-back-link,
    [data-theme="dark"] .assessment-back-link,
    body.dark-mode .assessment-back-link,
    body.theme-dark .assessment-back-link,
    [data-bs-theme="dark"] .assessment-page-header .assessment-back a,
    [data-theme="dark"] .assessment-page-header .assessment-back a,
    body.dark-mode .assessment-page-header .assessment-back a,
    body.theme-dark .assessment-page-header .assessment-back a {
        color: #60a5fa !important;
    }

    [data-bs-theme="dark"] .assessment-back-link:hover,
    [data-theme="dark"] .assessment-back-link:hover,
    body.dark-mode .assessment-back-link:hover,
    body.theme-dark .assessment-back-link:hover,
    [data-bs-theme="dark"] .assessment-page-header .assessment-back a:hover,
    [data-theme="dark"] .assessment-page-header .assessment-back a:hover,
    body.dark-mode .assessment-page-header .assessment-back a:hover,
    body.theme-dark .assessment-page-header .assessment-back a:hover {
        color: #93c5fd !important;
    }

    .assessment-page-header .breadcrumb {
        font-size: 0.85rem;
        margin-bottom: 0;
    }

    .assessment-page-header .page-title {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.3;
        color: #1f2937;
    }

    [data-bs-theme="dark"] .assessment-page-header .page-title,
    [data-theme="dark"] .assessment-page-header .page-title,
    body.dark-mode .assessment-page-header .page-title,
    body.theme-dark .assessment-page-header .page-title {
        color: #f8fafc !important;
    }
</style>
@endonce

<div class="assessment-page-header page-header d-print-none mb-3">
    <div class="container-xl">
        @if (!empty($containerClass))
            <div class="row justify-content-center">
                <div class="{{ $containerClass }}">
        @endif

        {{-- Top Navigation Row: Back Button (Left) and Breadcrumb (Right) --}}
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div class="assessment-back">
                <a href="{{ $backUrl ?? route('assesment.index') }}" class="assessment-back-link">
                    <i class="ti ti-arrow-left me-1" aria-hidden="true"></i>
                    Back
                </a>
            </div>

            <div class="assessment-breadcrumb">
                <ol class="breadcrumb breadcrumb-arrows mb-0" aria-label="breadcrumbs">
                    <li class="breadcrumb-item"><a href="{{ route('manage.dashboard') }}">Manage</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('assesment.index') }}">Assessment</a></li>
                    @if (isset($breadcrumb))
                        @if (is_array($breadcrumb))
                            @foreach ($breadcrumb as $item)
                                @if ($loop->last)
                                    <li class="breadcrumb-item active" aria-current="page">{{ $item }}</li>
                                @else
                                    <li class="breadcrumb-item">{{ $item }}</li>
                                @endif
                            @endforeach
                        @else
                            <li class="breadcrumb-item active" aria-current="page">{{ $breadcrumb }}</li>
                        @endif
                    @endif
                </ol>
            </div>
        </div>

        {{-- Page Title Row (Below Back & Breadcrumb) --}}
        <div class="assessment-page-title-row">
            <h2 class="page-title fw-bold text-dark mb-0" @if(!empty($titleId)) id="{{ $titleId }}" data-default-title="{{ $title ?? 'Manage Assessment' }}" @endif>
                {{ $title ?? 'Manage Assessment' }}
            </h2>
        </div>

        @if (!empty($containerClass))
                </div>
            </div>
        @endif
    </div>
</div>
