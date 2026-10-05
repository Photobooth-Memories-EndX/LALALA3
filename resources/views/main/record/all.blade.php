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

        @include('main.record.components.record-navigation')

        <!-- SECTION HEADER: All KP OWL -->
        <div class="card border-0 shadow-sm p-3 p-md-4 mb-4 rounded-3 bg-white record-section-header">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                     style="width: 54px; height: 54px; background-color: #ede9fe; color: #7c3aed; border-radius: 12px;">
                    <i class="ti ti-stack-2 fs-1"></i>
                </div>
                <div>
                    <h2 class="h3 fw-bold mb-1 text-dark">All KP OWL</h2>
                    <p class="text-muted small mb-0">Combined Learning Overview — Unified overview of KP OWL online learning and Non KP OWL offline training records.</p>
                </div>
            </div>
        </div>

        <!-- 4 KPI SUMMARY CARDS + SOURCE SUMMARY (ASYNC SKELETON SHELL) -->
        <div id="all-kpi-summary-container">
            @include('main.record.components.all-kpi-skeleton')
        </div>

        <!-- CLASSIFICATION SUMMARY CARD (ASYNC SKELETON SHELL) -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-xs rounded-2 bg-purple-lt text-purple">
                        <i class="ti ti-category fs-3"></i>
                    </span>
                    <h4 class="card-title fw-bold text-dark mb-0">Classification Summary</h4>
                </div>
                <span class="text-muted small">Combined Online &amp; Offline Training Categories</span>
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

    .record-section-header {
        border-radius: 14px !important;
        border: 1px solid #e2e8f0 !important;
    }

    [data-bs-theme="dark"] .record-section-header,
    body.dark-mode .record-section-header,
    body.theme-dark .record-section-header {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    [data-bs-theme="dark"] .record-section-header .text-dark,
    body.dark-mode .record-section-header .text-dark,
    body.theme-dark .record-section-header .text-dark {
        color: #f8fafc !important;
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
                $('#all-kpi-summary-container').html(`
                    <div class="card border-0 shadow-sm p-3 mb-4 rounded-3 bg-white stat-kpi-card">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2 text-danger small">
                                <i class="ti ti-alert-circle fs-3"></i>
                                <span>Unable to load combined learning summary.</span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadAllKpiSummary()">
                                <i class="ti ti-refresh me-1"></i> Retry
                            </button>
                        </div>
                    </div>
                `);
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
                $('#all-classification-container').html(`
                    <div class="p-3 text-center">
                        <div class="text-danger small mb-2">
                            <i class="ti ti-alert-circle me-1"></i> Unable to load classification summary.
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadAllClassificationSummary()">
                            <i class="ti ti-refresh me-1"></i> Retry
                        </button>
                    </div>
                `);
            }
        });
    };

    // Initial Async Triggers
    loadAllKpiSummary();
    loadAllClassificationSummary();
});
</script>
@endsection
