@extends('layouts.sidebar-master')

@php
    $totalLearning = count($content);
    $totalProgress = 0;
    $totalOngoing = 0;
    $totalFinished = 0;

    foreach ($content as $item) {
        $totalProgress += (int) ($item->progress ?? 0);

        if ((int) ($item->finish ?? 0) === 1) {
            $totalFinished++;
        } else {
            $totalOngoing++;
        }
    }

    $percentage = $totalLearning > 0
        ? round($totalProgress / $totalLearning, 2)
        : 0;
@endphp

@section('content')
<div class="page-body">
    <div class="container-xl">

        @include('main.profile.components.profile-hero')

        @include('main.profile.components.profile-stats')

        @include('main.profile.components.profile-table')

        @include('main.profile.components.profile-more-filters-modal')

        @include('main.learning.components.learning-detail-modal')

    </div>
</div>
@endsection

@section('style')
<link rel="stylesheet" href="{{ asset('css/dataTables.bootstrap5.min.css') }}">

<style>
    .rounded-4 {
        border-radius: 20px !important;
    }

    /* ── Dual Synchronized Horizontal Scrollbars ─────────────────────── */
    .table-scroll-top-wrapper {
        overflow-x: auto;
        overflow-y: hidden;
        height: 12px;
        margin-bottom: 0px;
        border-bottom: 1px solid #f1f5f9;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f8fafc;
    }
    .table-scroll-top-wrapper::-webkit-scrollbar {
        height: 8px;
    }
    .table-scroll-top-wrapper::-webkit-scrollbar-track {
        background: #f8fafc;
    }
    .table-scroll-top-wrapper::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .table-scroll-top-wrapper::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    .table-scroll-top-inner {
        height: 1px;
    }

    .table-scroll-container {
        overflow-x: auto;
        overflow-y: hidden;
        width: 100%;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f8fafc;
    }
    .table-scroll-container::-webkit-scrollbar {
        height: 8px;
    }
    .table-scroll-container::-webkit-scrollbar-track {
        background: #f8fafc;
    }
    .table-scroll-container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .table-scroll-container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* ── Standardized Table Styling ──────────────────────────────────── */
    #MyTable_filter {
        display: none !important;
    }
    #MyTable {
        border-collapse: separate;
        border-spacing: 0;
        width: max-content !important;
        min-width: 100%;
        margin-bottom: 0;
    }
    #MyTable thead th {
        background-color: #f8fafc !important;
        color: #64748b !important;
        font-weight: 700 !important;
        font-size: 0.72rem !important;
        letter-spacing: 0.03em !important;
        border-bottom: 2px solid #e2e8f0 !important;
        padding: 10px 12px !important;
        text-transform: uppercase !important;
        vertical-align: middle !important;
        white-space: nowrap !important;
    }
    #MyTable tbody td {
        vertical-align: middle !important;
        padding: 10px 12px !important;
        font-size: 0.825rem !important;
        border-bottom: 1px solid #f1f5f9 !important;
        white-space: nowrap !important;
    }
    #MyTable tbody tr:hover td {
        background-color: #f8fafc;
    }

    /* ── Action Dropdown & Ghost Buttons ─────────────────────────────── */
    .btn-ghost-secondary {
        color: #64748b;
        background-color: transparent;
        border-color: transparent;
        width: 28px;
        height: 28px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    .btn-ghost-secondary:hover,
    .btn-ghost-secondary:focus,
    .btn-ghost-secondary:active,
    .btn-ghost-secondary.show {
        color: #1e293b;
        background-color: #f1f5f9;
        border-color: transparent;
    }
    [data-bs-theme="dark"] .btn-ghost-secondary,
    body.dark-mode .btn-ghost-secondary,
    body.theme-dark .btn-ghost-secondary {
        color: #94a3b8;
    }
    [data-bs-theme="dark"] .btn-ghost-secondary:hover,
    [data-bs-theme="dark"] .btn-ghost-secondary:focus,
    [data-bs-theme="dark"] .btn-ghost-secondary:active,
    [data-bs-theme="dark"] .btn-ghost-secondary.show,
    body.dark-mode .btn-ghost-secondary:hover,
    body.theme-dark .btn-ghost-secondary:hover {
        color: #f8fafc;
        background-color: rgba(255, 255, 255, 0.1);
    }
    #MyTable .dropdown-menu {
        z-index: 1060 !important;
        font-size: 0.8125rem;
        min-width: 175px;
    }

    /* ── Quick Filter Flex Toolbar Layout ────────────────────────────── */
    .quick-filter-toolbar {
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
    }
    @media (min-width: 1200px) {
        .quick-filter-toolbar {
            flex-wrap: nowrap !important;
        }
    }
    @media (max-width: 1199.98px) {
        .quick-filter-toolbar {
            flex-wrap: wrap;
        }
    }

    .quick-filter-title {
        flex: 1 1 auto;
        min-width: 180px;
    }
    .quick-filter-classification {
        flex: 0 0 160px;
        width: 160px;
        min-width: 140px;
    }
    .quick-filter-status {
        flex: 0 0 115px;
        width: 115px;
        min-width: 105px;
    }
    .quick-filter-type {
        flex: 0 0 110px;
        width: 110px;
        min-width: 100px;
    }
    .quick-filter-year {
        flex: 0 0 95px;
        width: 95px;
        min-width: 85px;
    }

    .quick-filter-select {
        padding-left: 0.5rem !important;
        padding-right: 1.5rem !important;
        font-size: 0.85rem !important;
        height: 36px !important;
    }
    .quick-filter-action-btn {
        width: 36px !important;
        height: 36px !important;
        min-width: 36px !important;
        padding: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0 !important;
    }
    .quick-filter-download {
        flex-shrink: 0 !important;
    }
    .quick-filter-download .btn-download {
        height: 36px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
    }
    .quick-filter-toolbar .form-control {
        height: 36px !important;
        font-size: 0.875rem !important;
    }

    /* ── Action Buttons ──────────────────────────────────────────────── */
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

    .btn-more-filters,
    #btn-profile-more-filters {
        border-color: #0c5b9e !important;
        color: #0c5b9e !important;
        background-color: transparent !important;
        transition: all 0.2s ease;
    }
    .btn-more-filters:hover,
    .btn-more-filters:focus,
    .btn-more-filters:active,
    #btn-profile-more-filters:hover,
    #btn-profile-more-filters:focus,
    #btn-profile-more-filters:active {
        background-color: #0c5b9e !important;
        color: #ffffff !important;
        border-color: #0c5b9e !important;
    }
    .btn-more-filters:hover i,
    .btn-more-filters:focus i,
    .btn-more-filters:active i,
    #btn-profile-more-filters:hover i,
    #btn-profile-more-filters:focus i,
    #btn-profile-more-filters:active i {
        color: #ffffff !important;
    }

    .btn-quick-reset {
        transition: all 0.2s ease;
    }
    .btn-quick-reset:hover {
        background-color: #0c5b9e !important;
        color: #ffffff !important;
    }

    /* Modal Safeguards */
    .modal-backdrop {
        z-index: 1050 !important;
    }
    #profileMoreFiltersModal {
        z-index: 1060 !important;
    }

    /* ── Dark Mode Standardization ───────────────────────────────────── */
    [data-bs-theme="dark"] #MyTable thead th,
    body.dark-mode #MyTable thead th,
    body.theme-dark #MyTable thead th {
        background-color: var(--tblr-bg-surface-secondary, #1a2942) !important;
        color: #94a3b8 !important;
        border-bottom-color: rgba(255, 255, 255, 0.1) !important;
    }
    [data-bs-theme="dark"] #MyTable tbody td,
    body.dark-mode #MyTable tbody td,
    body.theme-dark #MyTable tbody td {
        border-bottom-color: rgba(255, 255, 255, 0.05) !important;
    }
    [data-bs-theme="dark"] .table-scroll-top-wrapper,
    [data-bs-theme="dark"] .table-scroll-container,
    body.dark-mode .table-scroll-top-wrapper,
    body.dark-mode .table-scroll-container,
    body.theme-dark .table-scroll-top-wrapper,
    body.theme-dark .table-scroll-container {
        scrollbar-color: #334155 #1e293b;
    }
    [data-bs-theme="dark"] .table-scroll-top-wrapper::-webkit-scrollbar-track,
    [data-bs-theme="dark"] .table-scroll-container::-webkit-scrollbar-track,
    body.dark-mode .table-scroll-top-wrapper::-webkit-scrollbar-track,
    body.dark-mode .table-scroll-container::-webkit-scrollbar-track,
    body.theme-dark .table-scroll-top-wrapper::-webkit-scrollbar-track,
    body.theme-dark .table-scroll-container::-webkit-scrollbar-track {
        background: #1e293b;
    }
    [data-bs-theme="dark"] .table-scroll-top-wrapper::-webkit-scrollbar-thumb,
    [data-bs-theme="dark"] .table-scroll-container::-webkit-scrollbar-thumb,
    body.dark-mode .table-scroll-top-wrapper::-webkit-scrollbar-thumb,
    body.dark-mode .table-scroll-container::-webkit-scrollbar-thumb,
    body.theme-dark .table-scroll-top-wrapper::-webkit-scrollbar-thumb,
    body.theme-dark .table-scroll-container::-webkit-scrollbar-thumb {
        background: #334155;
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
    [data-bs-theme="dark"] .btn-more-filters,
    [data-bs-theme="dark"] #btn-profile-more-filters,
    body.dark-mode .btn-more-filters,
    body.dark-mode #btn-profile-more-filters,
    body.theme-dark .btn-more-filters,
    body.theme-dark #btn-profile-more-filters {
        background-color: transparent !important;
        border-color: #3b82f6 !important;
        color: #60a5fa !important;
    }
    [data-bs-theme="dark"] .btn-more-filters:hover,
    [data-bs-theme="dark"] #btn-profile-more-filters:hover,
    body.dark-mode .btn-more-filters:hover,
    body.dark-mode #btn-profile-more-filters:hover,
    body.theme-dark .btn-more-filters:hover,
    body.theme-dark #btn-profile-more-filters:hover {
        background-color: #2563eb !important;
        color: #ffffff !important;
        border-color: #2563eb !important;
    }
    [data-bs-theme="dark"] #profileMoreFiltersModal .modal-content,
    body.dark-mode #profileMoreFiltersModal .modal-content,
    body.theme-dark #profileMoreFiltersModal .modal-content {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #profileMoreFiltersModal .text-dark,
    body.dark-mode #profileMoreFiltersModal .text-dark,
    body.theme-dark #profileMoreFiltersModal .text-dark {
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #profileMoreFiltersModal .form-control,
    [data-bs-theme="dark"] #profileMoreFiltersModal .form-select,
    body.dark-mode #profileMoreFiltersModal .form-control,
    body.dark-mode #profileMoreFiltersModal .form-select,
    body.theme-dark #profileMoreFiltersModal .form-control,
    body.theme-dark #profileMoreFiltersModal .form-select {
        background-color: var(--tblr-bg-surface-secondary, #111827) !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #profileMoreFiltersModal .owl-modal-card,
    body.dark-mode #profileMoreFiltersModal .owl-modal-card,
    body.theme-dark #profileMoreFiltersModal .owl-modal-card {
        background-color: var(--tblr-bg-surface-secondary, #111827) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    [data-bs-theme="dark"] #profileMoreFiltersModal .modal-header,
    [data-bs-theme="dark"] #profileMoreFiltersModal .modal-footer,
    body.dark-mode #profileMoreFiltersModal .modal-header,
    body.dark-mode #profileMoreFiltersModal .modal-footer,
    body.theme-dark #profileMoreFiltersModal .modal-header,
    body.theme-dark #profileMoreFiltersModal .modal-footer {
        background-color: var(--tblr-bg-surface, #1f2937) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    [data-bs-theme="dark"] #profileMoreFiltersModal .modal-body,
    body.dark-mode #profileMoreFiltersModal .modal-body,
    body.theme-dark #profileMoreFiltersModal .modal-body {
        background-color: var(--tblr-bg-surface-dark, #0f172a) !important;
    }
    [data-bs-theme="dark"] #profile-table-footer,
    body.dark-mode #profile-table-footer,
    body.theme-dark #profile-table-footer {
        background-color: var(--tblr-bg-surface, #1e293b) !important;
        border-top-color: rgba(255, 255, 255, 0.1) !important;
    }
    [data-bs-theme="dark"] #profile-table-entries-select,
    body.dark-mode #profile-table-entries-select,
    body.theme-dark #profile-table-entries-select {
        background-color: var(--tblr-bg-surface-secondary, #111827) !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .profile-compact-header,
    body.dark-mode .profile-compact-header,
    body.theme-dark .profile-compact-header {
        background-color: var(--tblr-bg-surface, #1e293b) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    [data-bs-theme="dark"] .profile-compact-header .text-dark,
    body.dark-mode .profile-compact-header .text-dark,
    body.theme-dark .profile-compact-header .text-dark {
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] .profile-compact-header .profile-avatar-box,
    body.dark-mode .profile-compact-header .profile-avatar-box,
    body.theme-dark .profile-compact-header .profile-avatar-box {
        background-color: rgba(29, 78, 216, 0.2) !important;
        color: #60a5fa !important;
    }
    [data-bs-theme="dark"] .profile-compact-header .progress,
    body.dark-mode .profile-compact-header .progress,
    body.theme-dark .profile-compact-header .progress {
        background-color: rgba(255, 255, 255, 0.1) !important;
    }
    [data-bs-theme="dark"] .profile-compact-header .border-top,
    body.dark-mode .profile-compact-header .border-top,
    body.theme-dark .profile-compact-header .border-top {
        border-color: rgba(255, 255, 255, 0.08) !important;
    }
    [data-bs-theme="dark"] .profile-compact-header .badge.border,
    body.dark-mode .profile-compact-header .badge.border,
    body.theme-dark .profile-compact-header .badge.border {
        background-color: rgba(255, 255, 255, 0.06) !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
        color: #cbd5e1 !important;
    }

    /* ── Stat KPI Cards (Offline Participant Style) ─────────────────── */
    .stat-kpi-card {
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .stat-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08) !important;
    }
    @media (max-width: 575.98px) {
        .stat-kpi-card {
            padding: 10px 12px !important;
        }
        .stat-kpi-card .kpi-icon-box {
            width: 38px !important;
            height: 38px !important;
            margin-right: 8px !important;
        }
        .stat-kpi-card .kpi-icon-box i {
            font-size: 1.25rem !important;
        }
        .stat-kpi-card .fs-3 {
            font-size: 1.15rem !important;
        }
    }
    [data-bs-theme="dark"] .stat-kpi-card,
    body.dark-mode .stat-kpi-card,
    body.theme-dark .stat-kpi-card {
        background-color: var(--tblr-bg-surface, #1e293b) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    [data-bs-theme="dark"] .stat-kpi-card .text-dark,
    body.dark-mode .stat-kpi-card .text-dark,
    body.theme-dark .stat-kpi-card .text-dark {
        color: #f8fafc !important;
    }

    /* ── Dark Mode for Learning Record Detail Modal ─────────────────── */
    [data-bs-theme="dark"] #learningDetailModal .modal-content,
    body.dark-mode #learningDetailModal .modal-content,
    body.theme-dark #learningDetailModal .modal-content {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #learningDetailModal .text-dark,
    body.dark-mode #learningDetailModal .text-dark,
    body.theme-dark #learningDetailModal .text-dark {
        color: #f8fafc !important;
    }
    [data-bs-theme="dark"] #learningDetailModal .owl-modal-card,
    body.dark-mode #learningDetailModal .owl-modal-card,
    body.theme-dark #learningDetailModal .owl-modal-card {
        background-color: #0f172a !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
    }
    [data-bs-theme="dark"] #learningDetailModal .owl-modal-subcard,
    body.dark-mode #learningDetailModal .owl-modal-subcard,
    body.theme-dark #learningDetailModal .owl-modal-subcard {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
    [data-bs-theme="dark"] #learningDetailModal .modal-header,
    [data-bs-theme="dark"] #learningDetailModal .modal-footer,
    body.dark-mode #learningDetailModal .modal-header,
    body.dark-mode #learningDetailModal .modal-footer,
    body.theme-dark #learningDetailModal .modal-header,
    body.theme-dark #learningDetailModal .modal-footer {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
</style>
@endsection

@section('script')
    @include('main.profile.components.profile-table-script')
    @include('main.profile.components.profile-record-detail-script')
@endsection
