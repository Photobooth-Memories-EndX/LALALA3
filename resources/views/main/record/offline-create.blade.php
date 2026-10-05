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

    /* SELECT2 IN INPUT GROUP & [+] BUTTON FIX */
    .input-group > .select2-container--bootstrap-5,
    .input-group > .select2-container {
        flex: 1 1 auto;
        width: 1% !important;
    }
    .input-group > .select2-container--bootstrap-5 .select2-selection,
    .input-group > .select2-container .select2-selection {
        border-top-right-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
    }
    .btn-add-option-modal {
        border-color: #d1d5db;
        color: #4b5563;
        background-color: #f9fafb;
        transition: all 0.15s ease-in-out;
    }
    .btn-add-option-modal:hover {
        background-color: #0c5b9e;
        border-color: #0c5b9e;
        color: #ffffff;
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

    /* ============================================================= */
    /* STEP 4 REVIEW & SAVE — FULL RECORD PREVIEW TABLE STYLING      */
    /* ============================================================= */
    .offline-review-scroll-wrapper {
        overflow-x: auto !important;
        overflow-y: auto !important;
        max-height: 560px;
        -webkit-overflow-scrolling: touch;
        width: 100%;
        border: 1px solid #e2e8f0;
        position: relative;
    }

    .offline-review-scroll-wrapper::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    .offline-review-scroll-wrapper::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .offline-review-scroll-wrapper::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .offline-review-scroll-wrapper::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    #offline-review-record-table {
        font-size: 13px;
        width: max-content !important;
        min-width: 100% !important;
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }

    #offline-review-record-table thead th {
        background: #f8fafc;
        color: #334155;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap !important;
        vertical-align: bottom;
        padding: 8px 12px;
        border-bottom: 2px solid #e2e8f0;
        position: sticky;
        top: 0;
        z-index: 5;
    }

    #offline-review-record-table thead th .col-group-label {
        font-size: 9.5px;
        font-weight: 700;
        letter-spacing: .05em;
        color: #94a3b8;
        line-height: 1.2;
        margin-bottom: 2px;
    }

    #offline-review-record-table thead th .col-title {
        font-weight: 700;
        color: #1e293b;
    }

    #offline-review-record-table tbody td {
        vertical-align: middle;
        padding: 9px 12px;
        white-space: nowrap !important;
        border-bottom: 1px solid #f1f5f9;
        background-color: inherit;
    }

    /* Sticky Columns: No (1), ID (2), Participant (3) */
    #offline-review-record-table .sticky-col {
        position: sticky !important;
    }

    #offline-review-record-table th.sticky-col-1,
    #offline-review-record-table td.sticky-col-1 {
        left: 0;
        width: 50px;
        min-width: 50px;
        max-width: 50px;
    }

    #offline-review-record-table th.sticky-col-2,
    #offline-review-record-table td.sticky-col-2 {
        left: 50px;
        width: 110px;
        min-width: 110px;
    }

    #offline-review-record-table th.sticky-col-3,
    #offline-review-record-table td.sticky-col-3 {
        left: 160px;
        width: 190px;
        min-width: 190px;
        border-right: 2px solid #e2e8f0 !important;
    }

    #offline-review-record-table thead th.sticky-col {
        z-index: 10;
        background: #f8fafc;
    }

    #offline-review-record-table tbody td.sticky-col {
        z-index: 3;
    }

    #offline-review-record-table tbody tr:nth-of-type(odd) td.sticky-col {
        background: #ffffff;
    }

    #offline-review-record-table tbody tr:nth-of-type(even) td.sticky-col {
        background: #fbfcfd;
    }

    #offline-review-record-table tbody tr:hover td.sticky-col {
        background: #f1f5f9;
    }

    /* DARK MODE OVERRIDES FOR REVIEW TABLE */
    [data-bs-theme="dark"] .offline-review-scroll-wrapper,
    body.dark-mode .offline-review-scroll-wrapper,
    body.theme-dark .offline-review-scroll-wrapper {
        border-color: #334155 !important;
        background-color: var(--tblr-bg-surface, #1f2937) !important;
    }

    [data-bs-theme="dark"] .offline-review-scroll-wrapper::-webkit-scrollbar-track,
    body.dark-mode .offline-review-scroll-wrapper::-webkit-scrollbar-track,
    body.theme-dark .offline-review-scroll-wrapper::-webkit-scrollbar-track {
        background: #111827;
    }
    [data-bs-theme="dark"] .offline-review-scroll-wrapper::-webkit-scrollbar-thumb,
    body.dark-mode .offline-review-scroll-wrapper::-webkit-scrollbar-thumb,
    body.theme-dark .offline-review-scroll-wrapper::-webkit-scrollbar-thumb {
        background: #475569;
    }

    [data-bs-theme="dark"] #offline-review-record-table thead th,
    body.dark-mode #offline-review-record-table thead th,
    body.theme-dark #offline-review-record-table thead th {
        background: #1e293b !important;
        color: #94a3b8 !important;
        border-bottom-color: #334155 !important;
    }

    [data-bs-theme="dark"] #offline-review-record-table thead th .col-title,
    body.dark-mode #offline-review-record-table thead th .col-title,
    body.theme-dark #offline-review-record-table thead th .col-title {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #offline-review-record-table thead th.sticky-col,
    body.dark-mode #offline-review-record-table thead th.sticky-col,
    body.theme-dark #offline-review-record-table thead th.sticky-col {
        background: #1e293b !important;
    }

    [data-bs-theme="dark"] #offline-review-record-table tbody td,
    body.dark-mode #offline-review-record-table tbody td,
    body.theme-dark #offline-review-record-table tbody td {
        border-bottom-color: #334155 !important;
        color: #e2e8f0 !important;
    }

    [data-bs-theme="dark"] #offline-review-record-table th.sticky-col-3,
    [data-bs-theme="dark"] #offline-review-record-table td.sticky-col-3,
    body.dark-mode #offline-review-record-table th.sticky-col-3,
    body.dark-mode #offline-review-record-table td.sticky-col-3,
    body.theme-dark #offline-review-record-table th.sticky-col-3,
    body.theme-dark #offline-review-record-table td.sticky-col-3 {
        border-right-color: #334155 !important;
    }

    [data-bs-theme="dark"] #offline-review-record-table tbody tr:nth-of-type(odd) td.sticky-col,
    body.dark-mode #offline-review-record-table tbody tr:nth-of-type(odd) td.sticky-col,
    body.theme-dark #offline-review-record-table tbody tr:nth-of-type(odd) td.sticky-col {
        background: #1f2937 !important;
    }

    [data-bs-theme="dark"] #offline-review-record-table tbody tr:nth-of-type(even) td.sticky-col,
    body.dark-mode #offline-review-record-table tbody tr:nth-of-type(even) td.sticky-col,
    body.theme-dark #offline-review-record-table tbody tr:nth-of-type(even) td.sticky-col {
        background: #1a2234 !important;
    }

    [data-bs-theme="dark"] #offline-review-record-table tbody tr:hover td.sticky-col,
    body.dark-mode #offline-review-record-table tbody tr:hover td.sticky-col,
    body.theme-dark #offline-review-record-table tbody tr:hover td.sticky-col {
        background: #2d3748 !important;
    }
</style>
@endsection

@section('script')
@include('main.record.components.offline-add-form-script')
@endsection
