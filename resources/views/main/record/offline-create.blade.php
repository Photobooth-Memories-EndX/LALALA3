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

        <!-- FULL-WIDTH CONTINUOUS VERTICAL SCROLL FORM -->
        <div class="add-training-form-wrapper">
            @include('main.record.components.offline-add-form')
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

    /* DARK MODE OVERRIDES */
    [data-bs-theme="dark"] .record-section-header,
    body.dark-mode .record-section-header,
    body.theme-dark .record-section-header,
    [data-bs-theme="dark"] .form-section,
    body.dark-mode .form-section,
    body.theme-dark .form-section,
    [data-bs-theme="dark"] .sticky-bottom-actions,
    body.dark-mode .sticky-bottom-actions,
    body.theme-dark .sticky-bottom-actions {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
        color: #f8fafc !important;
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
