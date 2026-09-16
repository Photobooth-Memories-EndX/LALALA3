@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">
        @include('main.learning.components.learning-header', ['variant' => 'table'])
        @include('main.learning.components.learning-filter')
        @include('main.learning.components.learning-table')
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

    #learning-table tbody td {
        vertical-align: middle;
        padding-top: 14px;
        padding-bottom: 14px;
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
</style>
@endsection

@section('script')
    @include('main.learning.components.learning-table-script')
@endsection
