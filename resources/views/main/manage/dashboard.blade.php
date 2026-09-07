@extends('layouts.sidebar-master')

@section('content')
<div class="page-body">
    <div class="container-xl">

        @include('main.manage.components.dashboard-hero')

        @include('main.manage.components.dashboard-stats')

        @include('main.manage.components.dashboard-modules')

        {{-- @include('main.manage.components.quick-actions') --}}

        <div class="row g-3">
            <div class="col-lg-8">
                @include('main.manage.components.recent-activity')
            </div>

            <div class="col-lg-4">
                @include('main.manage.components.system-status')
            </div>
        </div>

    </div>
</div>
@endsection

@section('style')
<style>
    /* ========================================================
       OWL LMS MANAGE DASHBOARD - CENTRALIZED DUAL THEME SYSTEM
       ======================================================== */

    /* --------------------------------------------------------
       1. LIGHT MODE (DEFAULT / BASE STYLES)
       -------------------------------------------------------- */

    /* Typography & Section Headings */
    .page-body h2 {
        color: #0f172a;
        font-weight: 700;
        letter-spacing: -0.2px;
    }

    .page-body h3 {
        color: #0f172a;
        font-weight: 600;
        letter-spacing: -0.1px;
    }

    .admin-section-divider {
        border-color: #e2e8f0 !important;
    }

    /* Hero Admin Badge (Light Mode) */
    .hero-admin-badge {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.5px;
        background: #e0f2fe !important;
        color: #002b66 !important;
        border: 1px solid #bae6fd !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
    }

    .hero-admin-badge-icon {
        color: #0284c7 !important;
        font-size: 12px;
    }

    /* Management Module Cards (Light Mode) */
    .admin-menu-card {
        border-radius: 18px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04) !important;
        transition: all .25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .admin-menu-card:hover {
        transform: translateY(-4px);
        border-color: #93c5fd !important;
        box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08), 0 0 12px rgba(2, 132, 199, 0.08) !important;
    }

    .admin-card-title {
        font-size: 1.05rem;
        font-weight: 700;
        color: #0f172a !important;
        transition: color .2s ease;
    }

    .admin-menu-card:hover .admin-card-title {
        color: #0284c7 !important;
    }

    .admin-card-desc {
        font-size: 12.5px;
        line-height: 1.45;
        color: #64748b !important;
        min-height: 52px;
    }

    .admin-card-icon .avatar {
        width: 48px;
        height: 48px;
        font-size: 22px;
        border-radius: 14px;
        background: #e0f2fe !important;
        color: #0284c7 !important;
        border: 1px solid #bae6fd;
        transition: all .25s ease;
    }

    .admin-menu-card:hover .admin-card-icon .avatar {
        background: #0284c7 !important;
        border-color: #0284c7 !important;
        color: #ffffff !important;
        transform: scale(1.05);
    }

    .admin-card-action {
        font-size: 13px;
        font-weight: 600;
        color: #0284c7 !important;
        transition: all .2s ease;
    }

    .admin-card-action i {
        transition: transform .2s ease;
    }

    .admin-menu-card:hover .admin-card-action {
        color: #0369a1 !important;
    }

    .admin-menu-card:hover .admin-card-action i {
        transform: translateX(4px);
    }

    /* KPI / Stats Cards (Light Mode) */
    .admin-stat-card {
        border-radius: 16px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04) !important;
        transition: all .2s ease;
    }

    .admin-stat-card:hover {
        transform: translateY(-2px);
        border-color: #cbd5e1 !important;
    }

    .stat-label {
        color: #64748b;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .stat-number {
        color: #0f172a;
        font-size: 1.65rem;
        font-weight: 700;
        margin-top: 4px;
        line-height: 1.2;
    }

    /* Quick Actions (Light Mode) */
    .admin-quick-actions-card {
        border-radius: 18px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04) !important;
    }

    .admin-quick-actions-card .btn {
        border-radius: 10px;
        font-size: 13px;
        transition: all .2s ease;
    }

    .admin-quick-actions-card .btn-outline-primary {
        background-color: #ffffff !important;
        border: 1px solid #cbd5e1 !important;
        color: #334155 !important;
    }

    .admin-quick-actions-card .btn-outline-primary i {
        color: #0284c7 !important;
    }

    .admin-quick-actions-card .btn-outline-primary:hover {
        background-color: #f0f9ff !important;
        border-color: #0284c7 !important;
        color: #0284c7 !important;
    }

    /* Recent Activity (Light Mode) */
    .admin-activity-card {
        border-radius: 18px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04) !important;
    }

    .admin-activity-list .list-group-item {
        background: transparent !important;
        border-color: #f1f5f9 !important;
        transition: background .15s ease;
    }

    .admin-activity-list .list-group-item:hover {
        background: #f8fafc !important;
    }

    .activity-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        font-size: 18px;
    }

    .activity-title {
        color: #0f172a;
        font-weight: 600;
        font-size: 13.5px;
    }

    .activity-desc {
        color: #64748b;
        font-size: 12.5px;
    }

    .activity-time {
        color: #64748b;
        font-size: 12px;
        font-weight: 500;
        white-space: nowrap;
    }

    /* System Status (Light Mode) */
    .admin-system-card {
        border-radius: 18px !important;
        border: 1px solid #e2e8f0 !important;
        background: #ffffff !important;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04) !important;
    }

    .upload-label {
        font-size: 13.5px;
        color: #334155;
        font-weight: 500;
    }

    .upload-pill {
        font-size: 12.5px;
        font-weight: 700;
        color: #0284c7;
        background: #e0f2fe;
        padding: 2px 10px;
        border-radius: 999px;
        border: 1px solid #bae6fd;
    }

    .contributor-name {
        font-size: 13px;
        color: #0f172a;
        font-weight: 500;
    }

    /* --------------------------------------------------------
       2. DARK MODE (SCOPED TO [data-bs-theme="dark"], body.dark-mode)
       -------------------------------------------------------- */

    body[data-bs-theme="dark"] .page-body h2,
    body.dark-mode .page-body h2,
    [data-bs-theme="dark"] .page-body h2 {
        color: #f8fafc !important;
    }

    body[data-bs-theme="dark"] .page-body h3,
    body.dark-mode .page-body h3,
    [data-bs-theme="dark"] .page-body h3 {
        color: #f8fafc !important;
    }

    body[data-bs-theme="dark"] .admin-section-divider,
    body.dark-mode .admin-section-divider,
    [data-bs-theme="dark"] .admin-section-divider {
        border-color: rgba(148, 163, 184, 0.15) !important;
    }

    /* Dark Mode: Management Module Cards */
    body[data-bs-theme="dark"] .admin-menu-card,
    body.dark-mode .admin-menu-card,
    [data-bs-theme="dark"] .admin-menu-card {
        background: #152238 !important;
        border: 1px solid rgba(148, 163, 184, 0.14) !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.22) !important;
    }

    body[data-bs-theme="dark"] .admin-menu-card:hover,
    body.dark-mode .admin-menu-card:hover,
    [data-bs-theme="dark"] .admin-menu-card:hover {
        border-color: rgba(56, 189, 248, 0.45) !important;
        box-shadow: 0 14px 32px rgba(0, 0, 0, 0.35), 0 0 16px rgba(56, 189, 248, 0.12) !important;
    }

    body[data-bs-theme="dark"] .admin-card-title,
    body.dark-mode .admin-card-title,
    [data-bs-theme="dark"] .admin-card-title {
        color: #f8fafc !important;
    }

    body[data-bs-theme="dark"] .admin-menu-card:hover .admin-card-title,
    body.dark-mode .admin-menu-card:hover .admin-card-title,
    [data-bs-theme="dark"] .admin-menu-card:hover .admin-card-title {
        color: #ffffff !important;
    }

    body[data-bs-theme="dark"] .admin-card-desc,
    body.dark-mode .admin-card-desc,
    [data-bs-theme="dark"] .admin-card-desc {
        color: #94a3b8 !important;
    }

    body[data-bs-theme="dark"] .admin-card-icon .avatar,
    body.dark-mode .admin-card-icon .avatar,
    [data-bs-theme="dark"] .admin-card-icon .avatar {
        background: rgba(56, 189, 248, 0.14) !important;
        color: #38bdf8 !important;
        border: 1px solid rgba(56, 189, 248, 0.25) !important;
    }

    body[data-bs-theme="dark"] .admin-menu-card:hover .admin-card-icon .avatar,
    body.dark-mode .admin-menu-card:hover .admin-card-icon .avatar,
    [data-bs-theme="dark"] .admin-menu-card:hover .admin-card-icon .avatar {
        background: rgba(56, 189, 248, 0.22) !important;
        border-color: #38bdf8 !important;
        color: #7dd3fc !important;
    }

    body[data-bs-theme="dark"] .admin-card-action,
    body.dark-mode .admin-card-action,
    [data-bs-theme="dark"] .admin-card-action {
        color: #38bdf8 !important;
    }

    body[data-bs-theme="dark"] .admin-menu-card:hover .admin-card-action,
    body.dark-mode .admin-menu-card:hover .admin-card-action,
    [data-bs-theme="dark"] .admin-menu-card:hover .admin-card-action {
        color: #7dd3fc !important;
    }

    /* Dark Mode: KPI / Stats Cards */
    body[data-bs-theme="dark"] .admin-stat-card,
    body.dark-mode .admin-stat-card,
    [data-bs-theme="dark"] .admin-stat-card {
        background: #152238 !important;
        border: 1px solid rgba(148, 163, 184, 0.14) !important;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.18) !important;
    }

    body[data-bs-theme="dark"] .admin-stat-card:hover,
    body.dark-mode .admin-stat-card:hover,
    [data-bs-theme="dark"] .admin-stat-card:hover {
        border-color: rgba(56, 189, 248, 0.35) !important;
    }

    body[data-bs-theme="dark"] .stat-label,
    body.dark-mode .stat-label,
    [data-bs-theme="dark"] .stat-label {
        color: #94a3b8 !important;
    }

    body[data-bs-theme="dark"] .stat-number,
    body.dark-mode .stat-number,
    [data-bs-theme="dark"] .stat-number {
        color: #ffffff !important;
    }

    /* Dark Mode: Quick Actions */
    body[data-bs-theme="dark"] .admin-quick-actions-card,
    body.dark-mode .admin-quick-actions-card,
    [data-bs-theme="dark"] .admin-quick-actions-card {
        background: #152238 !important;
        border: 1px solid rgba(148, 163, 184, 0.14) !important;
    }

    body[data-bs-theme="dark"] .admin-quick-actions-card .btn-outline-primary,
    body.dark-mode .admin-quick-actions-card .btn-outline-primary,
    [data-bs-theme="dark"] .admin-quick-actions-card .btn-outline-primary {
        background-color: rgba(255, 255, 255, 0.04) !important;
        border: 1px solid rgba(148, 163, 184, 0.3) !important;
        color: #e2e8f0 !important;
    }

    body[data-bs-theme="dark"] .admin-quick-actions-card .btn-outline-primary i,
    body.dark-mode .admin-quick-actions-card .btn-outline-primary i,
    [data-bs-theme="dark"] .admin-quick-actions-card .btn-outline-primary i {
        color: #38bdf8 !important;
    }

    body[data-bs-theme="dark"] .admin-quick-actions-card .btn-outline-primary:hover,
    body.dark-mode .admin-quick-actions-card .btn-outline-primary:hover,
    [data-bs-theme="dark"] .admin-quick-actions-card .btn-outline-primary:hover {
        background-color: rgba(56, 189, 248, 0.16) !important;
        border-color: #38bdf8 !important;
        color: #ffffff !important;
    }

    /* Dark Mode: Recent Activity */
    body[data-bs-theme="dark"] .admin-activity-card,
    body.dark-mode .admin-activity-card,
    [data-bs-theme="dark"] .admin-activity-card {
        background: #152238 !important;
        border: 1px solid rgba(148, 163, 184, 0.14) !important;
    }

    body[data-bs-theme="dark"] .admin-activity-list .list-group-item,
    body.dark-mode .admin-activity-list .list-group-item,
    [data-bs-theme="dark"] .admin-activity-list .list-group-item {
        border-color: rgba(148, 163, 184, 0.1) !important;
    }

    body[data-bs-theme="dark"] .admin-activity-list .list-group-item:hover,
    body.dark-mode .admin-activity-list .list-group-item:hover,
    [data-bs-theme="dark"] .admin-activity-list .list-group-item:hover {
        background: rgba(255, 255, 255, 0.03) !important;
    }

    body[data-bs-theme="dark"] .activity-avatar,
    body.dark-mode .activity-avatar,
    [data-bs-theme="dark"] .activity-avatar {
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
    }

    body[data-bs-theme="dark"] .activity-title,
    body.dark-mode .activity-title,
    [data-bs-theme="dark"] .activity-title {
        color: #f1f5f9 !important;
    }

    body[data-bs-theme="dark"] .activity-desc,
    body.dark-mode .activity-desc,
    [data-bs-theme="dark"] .activity-desc {
        color: #cbd5e1 !important;
    }

    body[data-bs-theme="dark"] .activity-time,
    body.dark-mode .activity-time,
    [data-bs-theme="dark"] .activity-time {
        color: #94a3b8 !important;
    }

    /* Dark Mode: System Status */
    body[data-bs-theme="dark"] .admin-system-card,
    body.dark-mode .admin-system-card,
    [data-bs-theme="dark"] .admin-system-card {
        background: #152238 !important;
        border: 1px solid rgba(148, 163, 184, 0.14) !important;
    }

    body[data-bs-theme="dark"] .upload-label,
    body.dark-mode .upload-label,
    [data-bs-theme="dark"] .upload-label {
        color: #cbd5e1 !important;
    }

    body[data-bs-theme="dark"] .upload-pill,
    body.dark-mode .upload-pill,
    [data-bs-theme="dark"] .upload-pill {
        color: #38bdf8 !important;
        background: rgba(56, 189, 248, 0.14) !important;
        border: 1px solid rgba(56, 189, 248, 0.25) !important;
    }

    body[data-bs-theme="dark"] .contributor-name,
    body.dark-mode .contributor-name,
    [data-bs-theme="dark"] .contributor-name {
        color: #f1f5f9 !important;
    }

    /* Hero Admin Badge (Dark Mode) */
    body[data-bs-theme="dark"] .hero-admin-badge,
    body.dark-mode .hero-admin-badge,
    body.theme-dark .hero-admin-badge,
    [data-bs-theme="dark"] .hero-admin-badge,
    [data-theme="dark"] .hero-admin-badge {
        background: rgba(56, 189, 248, 0.12) !important;
        color: #bae6fd !important;
        border: 1px solid rgba(56, 189, 248, 0.35) !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25) !important;
    }

    body[data-bs-theme="dark"] .hero-admin-badge-icon,
    body.dark-mode .hero-admin-badge-icon,
    body.theme-dark .hero-admin-badge-icon,
    [data-bs-theme="dark"] .hero-admin-badge-icon,
    [data-theme="dark"] .hero-admin-badge-icon {
        color: #38bdf8 !important;
    }
</style>
@endsection