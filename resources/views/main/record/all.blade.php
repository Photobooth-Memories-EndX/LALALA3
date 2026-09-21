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
                    <p class="text-muted small mb-0">Combined Online & Offline Learning Record — Consolidated overview across both online OWL and offline classroom training.</p>
                </div>
            </div>
        </div>

        <!-- PLACEHOLDER / COMING SOON CARD -->
        <div class="card border-0 shadow-sm rounded-4 p-5 text-center bg-white placeholder-content-card">
            <div class="py-4">
                <div class="avatar avatar-xl rounded-circle mx-auto mb-3" style="background-color: #ede9fe; color: #7c3aed; width: 72px; height: 72px;">
                    <i class="ti ti-layers-intersect fs-1"></i>
                </div>
                <h3 class="fw-bold text-dark mb-2">All KP OWL (Combined Learning Record)</h3>
                <div class="badge bg-purple-lt text-purple fw-semibold px-3 py-1.5 rounded-pill mb-3" style="font-size: 0.825rem;">
                    Coming Soon — Planned Module
                </div>
                <p class="text-muted mx-auto" style="max-width: 520px; line-height: 1.6;">
                    This module will provide a unified learning record view combining online records (KP OWL) and offline training records (Non KP OWL) using a canonical query architecture.
                </p>
                <div class="pt-2">
                    <a href="{{ route('manage.record.online') }}" class="btn btn-outline-primary rounded-pill px-3">
                        <i class="ti ti-arrow-left me-1"></i> Back to KP OWL (Online)
                    </a>
                </div>
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

    .placeholder-content-card {
        border: 1px dashed #cbd5e1 !important;
    }

    [data-bs-theme="dark"] .placeholder-content-card,
    body.dark-mode .placeholder-content-card,
    body.theme-dark .placeholder-content-card {
        background-color: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.15) !important;
    }

    [data-bs-theme="dark"] .placeholder-content-card .text-dark,
    body.dark-mode .placeholder-content-card .text-dark,
    body.theme-dark .placeholder-content-card .text-dark {
        color: #f8fafc !important;
    }
</style>
@endsection
