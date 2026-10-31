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
                    <!-- Download Excel Button for All Learning Records -->
                    <a href="{{ route('manage.record.all.export') }}" id="btn-all-export-excel" class="btn btn-sm btn-success rounded-3 px-3 d-inline-flex align-items-center gap-1.5" title="Export All Learning Records to Excel (.xlsx)">
                        <i class="ti ti-file-spreadsheet fs-3"></i>
                        <span>Export Excel (.xlsx)</span>
                    </a>
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
});
</script>
@endsection
