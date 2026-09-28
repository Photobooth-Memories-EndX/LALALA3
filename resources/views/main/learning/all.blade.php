@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">
        @include('main.learning.components.learning-header', ['variant' => 'table'])
        @include('main.learning.components.learning-filter')
        @include('main.learning.components.learning-table')
        @include('main.learning.components.learning-detail-modal')
    </div>
</div>
@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">

<style>
    #learning-table {
        font-size: 13px;
    }

    #learning-table thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    [data-bs-theme="dark"] #learning-table thead th,
    body.dark-mode #learning-table thead th,
    body.theme-dark #learning-table thead th {
        background: var(--tblr-bg-surface-secondary, #1a2942);
        color: #94a3b8;
        border-color: rgba(255, 255, 255, 0.1);
    }

    #learning-table tbody td {
        vertical-align: middle;
        padding-top: 14px;
        padding-bottom: 14px;
    }

    #learning-table tbody td a.text-reset:hover {
        color: var(--tblr-primary, #0c5b9e) !important;
        text-decoration: underline !important;
    }
    [data-bs-theme="dark"] #learning-table tbody td a.text-reset:hover,
    body.dark-mode #learning-table tbody td a.text-reset:hover,
    body.theme-dark #learning-table tbody td a.text-reset:hover {
        color: #60a5fa !important;
    }

    /* Action Buttons: Compact Rounded Border */
    .owl-action-btn {
        width: 32px !important;
        height: 32px !important;
        min-width: 32px !important;
        padding: 0 !important;
        border-radius: 8px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        line-height: 1 !important;
        border: 1px solid !important;
        transition: all 0.15s ease-in-out !important;
        background-color: #ffffff;
    }
    .owl-action-btn i {
        font-size: 15px;
        line-height: 1;
    }

    /* Light Mode */
    .owl-action-btn-view {
        color: #0c5b9e !important;
        border-color: #bfdbfe !important;
        background-color: #ffffff !important;
    }
    .owl-action-btn-view:hover,
    .owl-action-btn-view:focus {
        color: #0c5b9e !important;
        border-color: #0c5b9e !important;
        background-color: #eff6ff !important;
        box-shadow: 0 2px 4px rgba(12, 91, 158, 0.12) !important;
        transform: translateY(-1px);
    }

    .owl-action-btn-edit {
        color: #d97706 !important;
        border-color: #fed7aa !important;
        background-color: #ffffff !important;
    }
    .owl-action-btn-edit:hover,
    .owl-action-btn-edit:focus {
        color: #b45309 !important;
        border-color: #d97706 !important;
        background-color: #fffbeb !important;
        box-shadow: 0 2px 4px rgba(217, 119, 6, 0.12) !important;
        transform: translateY(-1px);
    }

    .owl-action-btn-reset {
        color: #dc2626 !important;
        border-color: #fecaca !important;
        background-color: #ffffff !important;
    }
    .owl-action-btn-reset:hover,
    .owl-action-btn-reset:focus {
        color: #b91c1c !important;
        border-color: #dc2626 !important;
        background-color: #fef2f2 !important;
        box-shadow: 0 2px 4px rgba(220, 38, 38, 0.12) !important;
        transform: translateY(-1px);
    }

    .owl-action-btn:active {
        transform: translateY(0) !important;
    }

    /* Dark Mode */
    [data-bs-theme="dark"] .owl-action-btn,
    body.dark-mode .owl-action-btn,
    body.theme-dark .owl-action-btn {
        background-color: rgba(255, 255, 255, 0.04) !important;
    }

    [data-bs-theme="dark"] .owl-action-btn-view,
    body.dark-mode .owl-action-btn-view,
    body.theme-dark .owl-action-btn-view {
        color: #60a5fa !important;
        border-color: rgba(96, 165, 250, 0.35) !important;
        background-color: rgba(96, 165, 250, 0.08) !important;
    }
    [data-bs-theme="dark"] .owl-action-btn-view:hover,
    [data-bs-theme="dark"] .owl-action-btn-view:focus,
    body.dark-mode .owl-action-btn-view:hover,
    body.dark-mode .owl-action-btn-view:focus,
    body.theme-dark .owl-action-btn-view:hover,
    body.theme-dark .owl-action-btn-view:focus {
        color: #93c5fd !important;
        border-color: rgba(96, 165, 250, 0.7) !important;
        background-color: rgba(96, 165, 250, 0.22) !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3) !important;
    }

    [data-bs-theme="dark"] .owl-action-btn-edit,
    body.dark-mode .owl-action-btn-edit,
    body.theme-dark .owl-action-btn-edit {
        color: #fbbf24 !important;
        border-color: rgba(251, 191, 36, 0.35) !important;
        background-color: rgba(251, 191, 36, 0.08) !important;
    }
    [data-bs-theme="dark"] .owl-action-btn-edit:hover,
    [data-bs-theme="dark"] .owl-action-btn-edit:focus,
    body.dark-mode .owl-action-btn-edit:hover,
    body.dark-mode .owl-action-btn-edit:focus,
    body.theme-dark .owl-action-btn-edit:hover,
    body.theme-dark .owl-action-btn-edit:focus {
        color: #fde68a !important;
        border-color: rgba(251, 191, 36, 0.7) !important;
        background-color: rgba(251, 191, 36, 0.22) !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3) !important;
    }

    [data-bs-theme="dark"] .owl-action-btn-reset,
    body.dark-mode .owl-action-btn-reset,
    body.theme-dark .owl-action-btn-reset {
        color: #f87171 !important;
        border-color: rgba(248, 113, 113, 0.35) !important;
        background-color: rgba(248, 113, 113, 0.08) !important;
    }
    [data-bs-theme="dark"] .owl-action-btn-reset:hover,
    [data-bs-theme="dark"] .owl-action-btn-reset:focus,
    body.dark-mode .owl-action-btn-reset:hover,
    body.dark-mode .owl-action-btn-reset:focus,
    body.theme-dark .owl-action-btn-reset:hover,
    body.theme-dark .owl-action-btn-reset:focus {
        color: #fca5a5 !important;
        border-color: rgba(248, 113, 113, 0.7) !important;
        background-color: rgba(248, 113, 113, 0.22) !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3) !important;
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

    .btn-more-filters,
    #btn-more-filters {
        border-color: #0c5b9e !important;
        color: #0c5b9e !important;
        background-color: transparent !important;
        transition: all 0.2s ease;
    }
    .btn-more-filters:hover,
    .btn-more-filters:focus,
    .btn-more-filters:active,
    #btn-more-filters:hover,
    #btn-more-filters:focus,
    #btn-more-filters:active {
        background-color: #0c5b9e !important;
        color: #ffffff !important;
        border-color: #0c5b9e !important;
    }
    .btn-more-filters:hover i,
    .btn-more-filters:focus i,
    .btn-more-filters:active i,
    #btn-more-filters:hover i,
    #btn-more-filters:focus i,
    #btn-more-filters:active i {
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] .btn-more-filters,
    [data-bs-theme="dark"] #btn-more-filters,
    body.dark-mode .btn-more-filters,
    body.dark-mode #btn-more-filters,
    body.theme-dark .btn-more-filters,
    body.theme-dark #btn-more-filters {
        background-color: transparent !important;
        border-color: #3b82f6 !important;
        color: #60a5fa !important;
    }
    [data-bs-theme="dark"] .btn-more-filters:hover,
    [data-bs-theme="dark"] #btn-more-filters:hover,
    body.dark-mode .btn-more-filters:hover,
    body.dark-mode #btn-more-filters:hover,
    body.theme-dark .btn-more-filters:hover,
    body.theme-dark #btn-more-filters:hover {
        background-color: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
    }

    .btn-download {
        background-color: #2fb344 !important;
        border-color: #2fb344 !important;
        color: #ffffff !important;
        transition: all 0.2s ease;
    }
    .btn-download:hover,
    .btn-download:focus,
    .btn-download:active {
        background-color: #269639 !important;
        border-color: #269639 !important;
        color: #ffffff !important;
    }
    .btn-download:hover i,
    .btn-download:focus i,
    .btn-download:active i {
        color: #ffffff !important;
    }

    .btn-quick-reset {
        transition: all 0.2s ease;
    }
    .btn-quick-reset:hover {
        background-color: #0c5b9e !important;
        color: #ffffff !important;
    }
    [data-bs-theme="dark"] .btn-quick-reset,
    body.dark-mode .btn-quick-reset,
    body.theme-dark .btn-quick-reset {
        background-color: transparent !important;
    }
    [data-bs-theme="dark"] .btn-quick-reset:hover,
    body.dark-mode .btn-quick-reset:hover,
    body.theme-dark .btn-quick-reset:hover {
        background-color: #0c5b9e !important;
        color: #ffffff !important;
    }

    [data-bs-theme="dark"] #moreLearningFiltersModal .modal-content,
    body.dark-mode #moreLearningFiltersModal .modal-content,
    body.theme-dark #moreLearningFiltersModal .modal-content {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #moreLearningFiltersModal .text-dark,
    body.dark-mode #moreLearningFiltersModal .text-dark,
    body.theme-dark #moreLearningFiltersModal .text-dark {
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #moreLearningFiltersModal .form-control,
    [data-bs-theme="dark"] #moreLearningFiltersModal .form-select,
    body.dark-mode #moreLearningFiltersModal .form-control,
    body.dark-mode #moreLearningFiltersModal .form-select,
    body.theme-dark #moreLearningFiltersModal .form-control,
    body.theme-dark #moreLearningFiltersModal .form-select {
        background-color: var(--tblr-bg-surface-secondary, #111827) !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #learningDetailModal .modal-content,
    [data-bs-theme="dark"] #learningEditModal .modal-content,
    [data-bs-theme="dark"] #learningResetModal .modal-content,
    body.dark-mode #learningDetailModal .modal-content,
    body.dark-mode #learningEditModal .modal-content,
    body.dark-mode #learningResetModal .modal-content,
    body.theme-dark #learningDetailModal .modal-content,
    body.theme-dark #learningEditModal .modal-content,
    body.theme-dark #learningResetModal .modal-content {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #learningDetailModal .text-dark,
    [data-bs-theme="dark"] #learningEditModal .text-dark,
    [data-bs-theme="dark"] #learningResetModal .text-dark,
    body.dark-mode #learningDetailModal .text-dark,
    body.dark-mode #learningEditModal .text-dark,
    body.dark-mode #learningResetModal .text-dark,
    body.theme-dark #learningDetailModal .text-dark,
    body.theme-dark #learningEditModal .text-dark,
    body.theme-dark #learningResetModal .text-dark {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #learningDetailModal .owl-modal-card,
    [data-bs-theme="dark"] #learningEditModal .owl-modal-card,
    [data-bs-theme="dark"] #learningResetModal .owl-modal-card,
    body.dark-mode #learningDetailModal .owl-modal-card,
    body.dark-mode #learningEditModal .owl-modal-card,
    body.dark-mode #learningResetModal .owl-modal-card,
    body.theme-dark #learningDetailModal .owl-modal-card,
    body.theme-dark #learningEditModal .owl-modal-card,
    body.theme-dark #learningResetModal .owl-modal-card {
        background-color: var(--tblr-bg-surface-secondary, #111827) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] #learningDetailModal .owl-modal-subcard,
    [data-bs-theme="dark"] #learningEditModal .owl-modal-subcard,
    body.dark-mode #learningDetailModal .owl-modal-subcard,
    body.dark-mode #learningEditModal .owl-modal-subcard,
    body.theme-dark #learningDetailModal .owl-modal-subcard,
    body.theme-dark #learningEditModal .owl-modal-subcard {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] #learningEditModal .form-control,
    [data-bs-theme="dark"] #learningEditModal .input-group-text,
    body.dark-mode #learningEditModal .form-control,
    body.dark-mode #learningEditModal .input-group-text,
    body.theme-dark #learningEditModal .form-control,
    body.theme-dark #learningEditModal .input-group-text {
        background-color: var(--tblr-bg-surface-secondary, #111827) !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] #learningDetailModal .modal-header,
    [data-bs-theme="dark"] #learningDetailModal .modal-footer,
    [data-bs-theme="dark"] #learningEditModal .modal-header,
    [data-bs-theme="dark"] #learningEditModal .modal-footer,
    [data-bs-theme="dark"] #learningResetModal .modal-header,
    [data-bs-theme="dark"] #learningResetModal .modal-footer,
    body.dark-mode #learningDetailModal .modal-header,
    body.dark-mode #learningDetailModal .modal-footer,
    body.dark-mode #learningEditModal .modal-header,
    body.dark-mode #learningEditModal .modal-footer,
    body.dark-mode #learningResetModal .modal-header,
    body.dark-mode #learningResetModal .modal-footer,
    body.theme-dark #learningDetailModal .modal-header,
    body.theme-dark #learningDetailModal .modal-footer,
    body.theme-dark #learningEditModal .modal-header,
    body.theme-dark #learningEditModal .modal-footer,
    body.theme-dark #learningResetModal .modal-header,
    body.theme-dark #learningResetModal .modal-footer {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
</style>
@endsection

@section('script')
    @include('main.learning.components.learning-table-script')
@endsection
