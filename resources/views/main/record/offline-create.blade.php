@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-record-create-page">
    <div class="container-xl">
        <!-- TOMBOL BACK RATA KIRI ATAS -->
        <div class="mb-3">
            <a href="{{ route('manage.record.offline') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                <i class="ti ti-arrow-left"></i>
                <span>Back to Non KP OWL</span>
            </a>
        </div>

        <!-- SECTION HEADER: Add Non KP OWL Training -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0 record-section-icon"
                     style="width: 54px; height: 54px; background-color: #fef3c7; color: #d97706; border-radius: 12px;">
                    <i class="ti ti-plus fs-1"></i>
                </div>
                <div>
                    <h2 class="h3 fw-bold mb-1 text-dark">Add Non KP OWL Training</h2>
                    <p class="text-muted small mb-0">Create an offline employee training record.</p>
                </div>
            </div>
        </div>

        <!-- 2-COLUMN LAYOUT: STICKY LEFT NAV + CONTINUOUS SCROLL FORM -->
        <div class="row g-4">
            <!-- LEFT NAVIGATION COLUMN (Sticky on desktop, horizontal on mobile) -->
            <div class="col-12 col-lg-3">
                <div class="card border-0 shadow-sm rounded-3 p-3 p-lg-4 add-training-sidebar sticky-top" style="top: 85px; z-index: 10;">
                    <div class="text-uppercase text-muted fw-bold extra-small mb-3 d-none d-lg-block" style="letter-spacing: 0.05em; font-size: 0.75rem;">
                        Form Navigation
                    </div>
                    <div class="nav flex-lg-column gap-2" id="page-step-nav" role="tablist">
                        <a href="#section-1-training-info" class="page-step-item active d-flex align-items-center gap-2 gap-lg-3 p-2 p-lg-2.5 rounded-3 text-decoration-none flex-fill" id="step-nav-1" data-step="1">
                            <span class="step-badge rounded-circle d-flex align-items-center justify-content-center fw-bold">1</span>
                            <div class="step-info d-none d-sm-block">
                                <div class="step-title fw-bold text-dark">Training Info</div>
                                <div class="step-desc text-muted extra-small d-none d-lg-block">Event &amp; schedule details</div>
                            </div>
                        </a>

                        <a href="#section-2-participants" class="page-step-item d-flex align-items-center gap-2 gap-lg-3 p-2 p-lg-2.5 rounded-3 text-decoration-none flex-fill" id="step-nav-2" data-step="2">
                            <span class="step-badge rounded-circle d-flex align-items-center justify-content-center fw-bold">2</span>
                            <div class="step-info d-none d-sm-block">
                                <div class="step-title fw-bold text-dark">Participants</div>
                                <div class="step-desc text-muted extra-small d-none d-lg-block">Search or manual entry</div>
                            </div>
                        </a>

                        <a href="#section-3-results" class="page-step-item d-flex align-items-center gap-2 gap-lg-3 p-2 p-lg-2.5 rounded-3 text-decoration-none flex-fill" id="step-nav-3" data-step="3">
                            <span class="step-badge rounded-circle d-flex align-items-center justify-content-center fw-bold">3</span>
                            <div class="step-info d-none d-sm-block">
                                <div class="step-title fw-bold text-dark">Learning Results</div>
                                <div class="step-desc text-muted extra-small d-none d-lg-block">Scores, attendance &amp; bond</div>
                            </div>
                        </a>

                        <a href="#section-4-review" class="page-step-item d-flex align-items-center gap-2 gap-lg-3 p-2 p-lg-2.5 rounded-3 text-decoration-none flex-fill" id="step-nav-4" data-step="4">
                            <span class="step-badge rounded-circle d-flex align-items-center justify-content-center fw-bold">4</span>
                            <div class="step-info d-none d-sm-block">
                                <div class="step-title fw-bold text-dark">Review &amp; Save</div>
                                <div class="step-desc text-muted extra-small d-none d-lg-block">Confirm and persist</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- RIGHT FORM COLUMN (ONE CONTINUOUS PAGE SCROLL) -->
            <div class="col-12 col-lg-9">
                @include('main.record.components.offline-add-form')
            </div>
        </div>
    </div>
</div>
@endsection

@section('style')
<style>
    .manage-record-create-page {
        background: #f8fafc;
        min-height: 100vh;
    }

    [data-bs-theme="dark"] .manage-record-create-page,
    body.dark-mode .manage-record-create-page,
    body.theme-dark .manage-record-create-page {
        background: var(--tblr-body-bg, #111827);
    }

    .btn-back {
        color: #0c5b9e !important;
        font-weight: 500;
        transition: transform 0.2s ease, opacity 0.2s ease;
    }
    .btn-back:hover {
        opacity: 0.85;
        transform: translateX(-3px);
    }
    [data-bs-theme="dark"] .btn-back,
    body.dark-mode .btn-back,
    body.theme-dark .btn-back {
        color: #60a5fa !important;
    }

    .record-section-header {
        border-left: 4px solid #d97706 !important;
    }

    /* STEP NAVIGATION STYLING */
    .page-step-item {
        border-left: 3px solid transparent;
        transition: all 0.15s ease;
        background-color: transparent;
    }
    .page-step-item:hover {
        background-color: rgba(217, 119, 6, 0.08);
    }
    .page-step-item.active {
        background-color: #fef3c7 !important;
        border-left-color: #d97706 !important;
    }
    .page-step-item.active .step-badge {
        background-color: #d97706 !important;
        color: #fff !important;
    }
    .page-step-item .step-badge {
        width: 32px;
        height: 32px;
        background-color: #e2e8f0;
        color: #64748b;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    @media (max-width: 991.98px) {
        .page-step-item {
            border-left: none;
            border-bottom: 3px solid transparent;
            justify-content: center;
        }
        .page-step-item.active {
            border-bottom-color: #d97706 !important;
        }
        .add-training-sidebar {
            position: static !important;
            margin-bottom: 1rem;
        }
    }

    /* DARK MODE OVERRIDES */
    [data-bs-theme="dark"] .record-section-header,
    body.dark-mode .record-section-header,
    body.theme-dark .record-section-header,
    [data-bs-theme="dark"] .add-training-sidebar,
    body.dark-mode .add-training-sidebar,
    body.theme-dark .add-training-sidebar,
    [data-bs-theme="dark"] .form-section,
    body.dark-mode .form-section,
    body.theme-dark .form-section,
    [data-bs-theme="dark"] .sticky-bottom-actions,
    body.dark-mode .sticky-bottom-actions,
    body.theme-dark .sticky-bottom-actions {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .page-step-item.active,
    body.dark-mode .page-step-item.active,
    body.theme-dark .page-step-item.active {
        background-color: rgba(217, 119, 6, 0.2) !important;
    }

    [data-bs-theme="dark"] .text-dark,
    body.dark-mode .text-dark,
    body.theme-dark .text-dark {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .bg-light,
    body.dark-mode .bg-light,
    body.theme-dark .bg-light,
    [data-bs-theme="dark"] .bg-light-subtle,
    body.dark-mode .bg-light-subtle,
    body.theme-dark .bg-light-subtle {
        background-color: #111827 !important;
    }
</style>
@endsection

@section('script')
@include('main.record.components.offline-add-form-script')
@endsection
