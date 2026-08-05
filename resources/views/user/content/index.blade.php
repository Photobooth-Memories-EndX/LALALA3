@extends('layouts.sidebar-master')

@section('style')
    <style>
        :root {
            --owl-ui-radius: 20px;
            --owl-hero-radius: 24px;
            --owl-ui-shadow: 0 2px 12px rgba(83, 86, 90, .05);
            --owl-ui-shadow-hover: 0 10px 28px rgba(83, 86, 90, .10);
            --owl-ui-border: #D9D9D6;
            --owl-ui-surface: #fff;
            --owl-ui-text: #53565A;
            --owl-ui-muted: #8A8D8F;
        }

        .content-filter-card {
            border-radius: var(--owl-ui-radius) !important;
            border: 1px solid var(--owl-ui-border) !important;
            box-shadow: var(--owl-ui-shadow) !important;
            overflow: hidden;
        }

        .content-hero-card {
            border-radius: 22px !important;
        }

        .content-hero-surface {
            min-height: 170px;
            padding: 15px 34px !important;
            display: flex;
            align-items: center;
            background: linear-gradient(
                135deg,
                #001b44 0%,
                #002b66 20%,
                #004b99 45%,
                #2a7bd1 70%,
                #82b8f5 88%,
                #dbeeff 100%
            ) !important;
        }

        .content-hero-surface > .row {
            width: 100%;
            min-height: 140px;
        }

        .content-hero-count {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            width: 100%;
            line-height: 1;
            color: #fff;
            text-align: right;
        }

        .content-hero-count strong {
            font-size: clamp(38px, 5.3vw, 60px);
            font-weight: 800;
            letter-spacing: 0;
            text-shadow: 0 12px 34px rgba(15, 23, 42, .22);
        }

        .content-hero-count span {
            margin-bottom: 4px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: rgba(255, 255, 255, .78);
        }

        .content-hero-copy h1 {
            font-size: clamp(24px, 2.5vw, 32px);
            line-height: 1.04;
            letter-spacing: 0;
        }

        .content-hero-copy p {
            max-width: 580px;
            font-size: 15px;
            line-height: 1.4;
        }

        .content-hero-badge {
            padding: .25rem .5rem !important;
            font-size: 12px;
            line-height: 1.2;
        }

        .content-reset-btn {
            border: 1px solid transparent;
            background: transparent;
            color: #05507D;
            font-weight: 600;
        }

        .content-reset-btn:hover {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #05507D;
        }

        .content-reset-btn.is-active {
            background: #05507D;
            border-color: #05507D;
            color: #fff;
            box-shadow: 0 6px 16px rgba(37, 99, 235, .22);
        }

        .content-reset-btn.is-active:hover {
            background: #05507D;
            border-color: #05507D;
            color: #fff;
        }

        .content-card-thumb {
            border-radius: 0;
        }

        .content-card {
            height: 100%;
            border: 1px solid var(--owl-ui-border);
            border-radius: var(--owl-ui-radius);
            box-shadow: var(--owl-ui-shadow);
            overflow: hidden;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }

        .content-card .card-body {
            display: flex;
            flex-direction: column;
            height: 100%;
            padding: 18px;
        }

        .content-card-title {
            min-height: 48px;
            line-height: 1.45;
        }

        .content-card-title a {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .content-card-details {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
        }

        .content-card-meta {
            min-height: 22px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .content-card-dates {
            min-height: 96px;
            padding: 10px 0;
            border-top: 1px solid rgba(98, 105, 118, .14);
        }

        .content-card-date-row {
            display: flex;
            align-items: flex-start;
            gap: 7px;
            min-height: 22px;
            font-size: 12px;
            color: #64748b;
        }

        .content-card-date-row i {
            flex: 0 0 auto;
            margin-top: 2px;
            font-size: 14px;
        }

        .content-card-deadline-state {
            font-weight: 600;
        }

        .content-card-deadline-state.is-expired {
            color: #d63939;
        }

        .content-card-deadline-state.is-today {
            color: #f59f00;
        }

        .content-card-deadline-state.is-upcoming {
            color: #2fb344;
        }

        .content-card-action {
            margin-top: auto;
        }

        .content-card-action.is-expired:disabled {
            cursor: not-allowed;
            opacity: 1;
        }

        .learning-path-card {
            border-radius: var(--owl-ui-radius) !important;
        }

        .learning-path-card .card-body {
            padding: 24px;
        }

        .learning-path-card:hover,
        .content-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--owl-ui-shadow-hover);
        }

        .content-empty-card {
            border-radius: var(--owl-ui-radius) !important;
            border: 1px solid var(--owl-ui-border) !important;
            box-shadow: var(--owl-ui-shadow) !important;
            overflow: hidden;
        }

        .learning-filter-modal .modal-content {
            border-radius: var(--owl-ui-radius) !important;
            overflow: hidden;
        }

        .learning-filter-modal .form-control,
        .learning-filter-modal .form-select,
        .content-filter-card .form-control,
        .content-filter-card .form-select,
        .content-filter-card .btn {
            border-radius: 12px !important;
        }

        .content-pagination-wrap .pagination {
            gap: .4rem;
        }

        .content-pagination-wrap .page-link {
            min-width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px !important;
            border-color: #dbe4f0;
            font-weight: 600;
        }

        [data-bs-theme="dark"] .content-reset-btn,
        body.dark-mode .content-reset-btn {
            color: #93c5fd;
        }

        [data-bs-theme="dark"] .content-reset-btn:hover,
        body.dark-mode .content-reset-btn:hover {
            background: rgba(37, 99, 235, .16);
            border-color: rgba(147, 197, 253, .26);
            color: #bfdbfe;
        }

        [data-bs-theme="dark"] .content-reset-btn.is-active,
        body.dark-mode .content-reset-btn.is-active {
            background: #6AAED6;
            border-color: #6AAED6;
            color: #fff;
            box-shadow: 0 8px 20px rgba(59, 130, 246, .18);
        }

        [data-bs-theme="dark"] .content-filter-card,
        body.dark-mode .content-filter-card,
        [data-bs-theme="dark"] .content-card,
        body.dark-mode .content-card,
        [data-bs-theme="dark"] .content-empty-card,
        body.dark-mode .content-empty-card {
            background: #152235;
            border-color: rgba(255, 255, 255, .07);
            color: #f1f5f9;
            box-shadow: none !important;
        }

        [data-bs-theme="dark"] .content-card .card-body,
        body.dark-mode .content-card .card-body {
            background: #152235 !important;
            border-radius: 0 0 var(--owl-ui-radius) var(--owl-ui-radius);
        }

        [data-bs-theme="dark"] .content-card-title a,
        body.dark-mode .content-card-title a {
            color: #f1f5f9;
        }

        [data-bs-theme="dark"] .content-card-meta,
        [data-bs-theme="dark"] .content-card-date-row,
        [data-bs-theme="dark"] .content-card .text-muted,
        body.dark-mode .content-card-meta,
        body.dark-mode .content-card-date-row,
        body.dark-mode .content-card .text-muted {
            color: #94a3b8 !important;
        }

        [data-bs-theme="dark"] .content-card-dates,
        body.dark-mode .content-card-dates {
            border-top-color: rgba(255, 255, 255, .08);
        }

        [data-bs-theme="dark"] .learning-filter-modal .modal-content,
        body.dark-mode .learning-filter-modal .modal-content {
            background: #152235;
            color: #f1f5f9;
            border: 1px solid rgba(255, 255, 255, .07);
            box-shadow: 0 20px 48px rgba(0, 0, 0, .34);
        }

        [data-bs-theme="dark"] .learning-filter-modal .form-control,
        [data-bs-theme="dark"] .learning-filter-modal .form-select,
        [data-bs-theme="dark"] .content-filter-card .form-control,
        [data-bs-theme="dark"] .content-filter-card .form-select,
        body.dark-mode .learning-filter-modal .form-control,
        body.dark-mode .learning-filter-modal .form-select,
        body.dark-mode .content-filter-card .form-control,
        body.dark-mode .content-filter-card .form-select {
            background: #192d45;
            border-color: rgba(255, 255, 255, .08);
            color: #f1f5f9;
        }

        [data-bs-theme="dark"] .content-pagination-wrap .page-link,
        body.dark-mode .content-pagination-wrap .page-link {
            background: #152235;
            border-color: rgba(255, 255, 255, .08);
            color: #93c5fd;
        }

        [data-bs-theme="dark"] .content-pagination-wrap .page-item.active .page-link,
        body.dark-mode .content-pagination-wrap .page-item.active .page-link {
            background: #6AAED6;
            border-color: #6AAED6;
            color: #fff;
        }

        @media (max-width: 767.98px) {
            .content-hero-surface {
                min-height: auto;
            }

            .content-hero-count {
                align-items: flex-start;
                text-align: left;
            }

            .content-hero-count strong {
                font-size: 36px;
            }
        }
    </style>
@endsection

@section('content')
<div class="page-body">
    <div class="container-xl">
        @php
            $selectedCategory = $classifications->firstWhere('id', $filterClassification);
            $activeCategoryLabel = $selectedCategory->classification_name ?? 'All Categories';
        @endphp

        <div class="card content-hero-card mb-4 overflow-hidden border-0 shadow-sm">
            <div class="card-body content-hero-surface text-white">
                <div class="row align-items-center g-4">
                    <div class="col-lg-8 content-hero-copy">
                        <div class="d-inline-flex align-items-center gap-2 badge bg-white text-primary mb-2 rounded-pill content-hero-badge">
                            <i class="ti ti-sparkles"></i>
                            Learning Content
                        </div>

                        <h1 class="mb-1 fw-bold">All Content</h1>
                        <p class="mb-0 text-white-75">
                            Explore curated videos, documents, and learning materials that help you keep growing every day.
                        </p>
                    </div>

                    <div class="col-lg-4 d-flex justify-content-lg-end">
                        <div class="content-hero-count">
                            <span>{{ $activeCategoryLabel }}</span>
                            <strong>{{ $contents->total() }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @include('user.content.components.content-search-filter')
        @include('user.content.components.content-grid')
    </div>
</div>
@endsection

