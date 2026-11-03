@extends('layouts.sidebar-master')

@section('content')
<div class="page-body manage-record-page">
    <div class="container-xl">
        <!-- TOMBOL BACK RATA KIRI ATAS -->
        <div class="mb-3">
            <a href="{{ route('manage.index') }}" class="btn-back d-inline-flex align-items-center gap-1 text-decoration-none fw-medium">
                <i class="ti ti-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        @include('main.record.components.record-hero')

        <!-- 4 KPI SUMMARY CARDS + SOURCE SUMMARY (ASYNC SKELETON SHELL) -->
        <div id="all-kpi-summary-container">
            @include('main.record.components.all-kpi-skeleton')
        </div>

        @include('main.record.components.record-navigation')

        @if(session('error'))
        <div class="alert alert-warning alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
            <div class="d-flex align-items-center gap-2">
                <i class="ti ti-alert-triangle fs-2 text-warning"></i>
                <div>
                    {{ session('error') }}
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        @endif

        <!-- Action Button: Download Dropdown -->
        @can('learning_management')
        <div class="d-flex justify-content-end mb-4" id="btn-export-all-combined">
            <div class="dropdown">
                <button type="button" class="btn btn-outline-secondary dropdown-toggle rounded-3 px-3 d-inline-flex align-items-center gap-1.5"
                        data-bs-toggle="dropdown" data-toggle="dropdown" aria-expanded="false" id="btn-download-dropdown">
                    <i class="ti ti-download fs-2"></i>
                    <span>Download</span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border rounded-3 mt-1 py-1" aria-labelledby="btn-download-dropdown" style="min-width: 250px;">
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.record.all.export', request()->query()) }}" id="btn-all-export-excel" data-testid="btn-export-all-xlsx" title="Download All Record (.xlsx)">
                            <i class="ti ti-file-spreadsheet text-success fs-3"></i>
                            <span>Download All Record (.xlsx)</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.record.all.export.pdf', request()->query()) }}" id="btn-export-all-pdf">
                            <i class="ti ti-file-type-pdf text-danger fs-3"></i>
                            <span>Download PDF (.pdf)</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('manage.record.all.export.csv', request()->query()) }}" id="btn-export-all-csv">
                            <i class="ti ti-file-type-csv text-primary fs-3"></i>
                            <span>Download CSV (.csv)</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        @endcan

        <!-- CLASSIFICATION SUMMARY CARD (COLLAPSIBLE / ASYNC) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-xs rounded-2 bg-purple-lt text-purple">
                        <i class="ti ti-category fs-3"></i>
                    </span>
                    <h4 class="card-title fw-bold text-dark mb-0">Classification Summary</h4>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted small">Combined Online &amp; Offline Training Categories</span>
                </div>
            </div>
            <div class="card-body p-4 pt-2" id="all-classification-container">
                @include('main.record.components.all-classification-skeleton')
            </div>
        </div>
    </div>
</div>
@endsection

@section('style')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
    .manage-record-page {
        background: #fff;
    }

    [data-bs-theme="dark"] .manage-record-page,
    body.dark-mode .manage-record-page,
    body.theme-dark .manage-record-page {
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

    .stat-kpi-card {
        border-radius: 12px !important;
        transition: transform 0.18s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .stat-kpi-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08) !important;
    }

    /* Download button styling & OWL blue consistency */
    #btn-download-dropdown {
        border-color: #0c5b9e;
        color: #0c5b9e;
        background-color: transparent;
        font-weight: 500;
        transition: all 0.2s ease-in-out;
    }
    #btn-download-dropdown:hover,
    #btn-download-dropdown:focus,
    #btn-download-dropdown:active,
    #btn-download-dropdown.show,
    #btn-download-dropdown[aria-expanded="true"] {
        color: #ffffff !important;
        background-color: #0c5b9e !important;
        border-color: #0c5b9e !important;
    }

    /* Download button & dropdown dark mode support */
    [data-bs-theme="dark"] #btn-download-dropdown,
    body.dark-mode #btn-download-dropdown,
    body.theme-dark #btn-download-dropdown {
        color: #cbd5e1 !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
        background-color: transparent !important;
    }

    [data-bs-theme="dark"] #btn-download-dropdown:hover,
    body.dark-mode #btn-download-dropdown:hover,
    body.theme-dark #btn-download-dropdown:hover {
        color: #ffffff !important;
        background-color: rgba(255, 255, 255, 0.08) !important;
        border-color: rgba(255, 255, 255, 0.3) !important;
    }

    [data-bs-theme="dark"] .manage-record-page .dropdown-menu,
    body.dark-mode .manage-record-page .dropdown-menu,
    body.theme-dark .manage-record-page .dropdown-menu {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] .manage-record-page .dropdown-item,
    body.dark-mode .manage-record-page .dropdown-item,
    body.theme-dark .manage-record-page .dropdown-item {
        color: #f8fafc !important;
    }

    [data-bs-theme="dark"] .manage-record-page .dropdown-item:hover,
    body.dark-mode .manage-record-page .dropdown-item:hover,
    body.theme-dark .manage-record-page .dropdown-item:hover {
        background-color: rgba(255, 255, 255, 0.08) !important;
        color: #38bdf8 !important;
    }
</style>
@endsection

@section('script')
<script>
$(function () {
    const summaryUrl = @json(route('manage.record.all.ajax.summary'));
    const classificationUrl = @json(route('manage.record.all.ajax.classification'));

    let kpiXhr = null;
    let classXhr = null;

    // 1. Asynchronous Combined KPI Summary Loader
    window.loadAllKpiSummary = function () {
        if (kpiXhr) {
            kpiXhr.abort();
            kpiXhr = null;
        }

        const skeletonHtml = @json(view('main.record.components.all-kpi-skeleton')->render());
        $('#all-kpi-summary-container').html(skeletonHtml);

        kpiXhr = $.ajax({
            url: summaryUrl,
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function (res) {
                kpiXhr = null;
                if (res && res.html) {
                    $('#all-kpi-summary-container').html(res.html);
                }
            },
            error: function (xhr, status) {
                if (status === 'abort') return;
                kpiXhr = null;
            }
        });
    };

    // 2. Asynchronous Combined Classification Summary Loader
    window.loadAllClassificationSummary = function () {
        if (classXhr) {
            classXhr.abort();
            classXhr = null;
        }

        const skeletonHtml = @json(view('main.record.components.all-classification-skeleton')->render());
        $('#all-classification-container').html(skeletonHtml);

        classXhr = $.ajax({
            url: classificationUrl,
            type: 'GET',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function (res) {
                classXhr = null;
                if (res && res.html) {
                    $('#all-classification-container').html(res.html);
                }
            },
            error: function (xhr, status) {
                if (status === 'abort') return;
                classXhr = null;
            }
        });
    };

    loadAllKpiSummary();
    loadAllClassificationSummary();

    // 3. Download Dropdown & Double-Click Prevention
    let isExporting = false;
    $(document).on('click', '#btn-all-export-excel, #btn-export-all-pdf, #btn-export-all-csv', function (e) {
        if (isExporting) {
            e.preventDefault();
            return false;
        }

        const href = $(this).attr('href');
        if (!href || href === '#' || href.startsWith('javascript:')) {
            return;
        }

        isExporting = true;
        const $btn = $('#btn-download-dropdown');
        const $icon = $btn.find('i');
        const originalIconClass = $icon.attr('class') || 'ti ti-download fs-2';

        $btn.addClass('disabled').css('pointer-events', 'none');
        $icon.attr('class', 'spinner-border spinner-border-sm');

        setTimeout(function () {
            isExporting = false;
            $btn.removeClass('disabled').css('pointer-events', '');
            $icon.attr('class', originalIconClass);
        }, 2500);
    });
});
</script>
@endsection
