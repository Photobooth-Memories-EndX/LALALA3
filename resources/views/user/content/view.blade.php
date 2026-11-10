@extends('layouts.sidebar-master')

@php
    $url_ytplayer_js = asset('Tabler/dist/libs/plyr/dist/plyr.min.js?1684106062');
    $url_ytplayer_css = asset('Tabler/dist/libs/plyr/dist/plyr.css?1684106062');

    $contentfile = is_string($content->file) ? json_decode($content->file, true) : [];
    $getcontentfile = is_array($contentfile) ? ($contentfile[0] ?? '') : ($content->file ?? '');

    $canUsePlaylistDebug = (bool) auth()->user()?->canAccessManageModule('playlist_debug_tools');
    $playlistDebugState = ($canUsePlaylistDebug && isset($playlist) && $playlist)
        ? session('playlist_debug.' . $playlist->getKey(), [])
        : [];
    $playlistDebugActive = !empty($playlistDebugState);

    $playlistUiState = [
        'learning_progress' => (float) ($completionRequirements['learning_progress'] ?? ($content_user->progress ?? 0)),
        'content_assessment_available' => (bool) ($completionRequirements['content_assessment_available'] ?? false),
        'playlist_assessment_available' => (bool) ($completionRequirements['playlist_assessment_available'] ?? false),
        'certificate_available' => (bool) ($completionRequirements['certificate_available'] ?? false),
    ];

    if (($playlistDebugState['learning_progress'] ?? null) === 'pass') {
        $playlistUiState['learning_progress'] = 100;
    } elseif (($playlistDebugState['learning_progress'] ?? null) === 'fail') {
        $playlistUiState['learning_progress'] = 0;
    }

    foreach ([
        'content_assessment' => 'content_assessment_available',
        'playlist_assessment' => 'playlist_assessment_available',
        'certificate' => 'certificate_available',
    ] as $debugKey => $uiKey) {
        if (($playlistDebugState[$debugKey] ?? null) === 'available') {
            $playlistUiState[$uiKey] = true;
        } elseif (($playlistDebugState[$debugKey] ?? null) === 'not_available') {
            $playlistUiState[$uiKey] = false;
        }
    }
@endphp

@section('content')
<div class="page-body content-detail-page">
    <div class="container-xl">

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <a href="{{ url()->previous() }}" class="btn btn-link px-0 text-decoration-none">
                <i class="fas fa-arrow-left me-2"></i> Back
            </a>

            <button type="button"
                id="contentSidebarToggle"
                class="content-sidebar-toggle"
                aria-controls="contentLearningSidebar"
                aria-expanded="true">
                <i class="ti ti-layout-sidebar-right-collapse me-1" id="contentSidebarToggleIcon"></i>
                <span id="contentSidebarToggleLabel">Hide Sidebar</span>
            </button>
        </div>

        <div class="row g-4 content-learning-layout" id="contentLearningLayout">
            <div class="col-lg-8 col-xl-9 content-learning-main" id="contentLearningMain">
                @include('user.content.components.content-player')
                @include('user.content.components.content-description')

                @if ($canUsePlaylistDebug && isset($playlist) && $playlist)
                    @include('user.playlist.components.view-debug-panel')
                @endif
            </div>

            <div class="col-lg-4 col-xl-3 content-learning-sidebar" id="contentLearningSidebar">
                @include('user.content.components.content-learning-sidebar', [
                    'discussionCount' => $discussionCount,
                    'bookmarkCount' => $bookmarkCount,
                    'isBookmarked' => $isBookmarked,
                    'activeDraft' => $activeDraft ?? null,
                    'isExpired' => $isExpired ?? false,
                    'isInactive' => $isInactive ?? false,
                    'isBeforeStart' => $isBeforeStart ?? false,
                    'canStart' => $canStart ?? false,
                    'canContinue' => $canContinue ?? false,
                    'canRemedial' => $canRemedial ?? false,
                    'actionType' => $actionType ?? 'start',
                    'effectiveDeadline' => $effectiveDeadline ?? null,
                    'maxAttempts' => $maxAttempts ?? 1,
                    'isPassed' => $isPassed ?? false,
                    'answeredCount' => $answeredCount ?? 0,
                    'totalCount' => $totalCount ?? 0,
                    'denialReason' => $denialReason ?? null,
                ])
            </div>
        </div>

    </div>
</div>

@include('user.content.components.content-comment', [
    'content' => $content,
    'comment' => $comment,
])

<x-shared.assessment-confirmation-modal />
<x-shared.assessment-continue-modal />

<script>
    (function () {
        var STORAGE_KEY = 'contentLearningSidebarCollapsed';

        var layout = document.getElementById('contentLearningLayout');
        var main = document.getElementById('contentLearningMain');
        var sidebar = document.getElementById('contentLearningSidebar');
        var toggleBtn = document.getElementById('contentSidebarToggle');
        var toggleIcon = document.getElementById('contentSidebarToggleIcon');
        var toggleLabel = document.getElementById('contentSidebarToggleLabel');

        if (!layout || !main || !sidebar || !toggleBtn) return;

        function applyState(collapsed) {
            layout.classList.toggle('sidebar-collapsed', collapsed);
            sidebar.classList.toggle('is-collapsed', collapsed);
            sidebar.classList.toggle('d-none', collapsed);

            main.classList.toggle('col-lg-8', !collapsed);
            main.classList.toggle('col-xl-9', !collapsed);
            main.classList.toggle('col-12', collapsed);

            toggleIcon.className = collapsed
                ? 'ti ti-layout-sidebar-right-expand me-1'
                : 'ti ti-layout-sidebar-right-collapse me-1';
            toggleLabel.textContent = collapsed ? 'Show Sidebar' : 'Hide Sidebar';
            toggleBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        }

        var collapsed = false;
        try {
            collapsed = localStorage.getItem(STORAGE_KEY) === '1';
        } catch (e) {}

        applyState(collapsed);

        toggleBtn.addEventListener('click', function () {
            var nowCollapsed = !layout.classList.contains('sidebar-collapsed');
            applyState(nowCollapsed);
            try {
                localStorage.setItem(STORAGE_KEY, nowCollapsed ? '1' : '0');
            } catch (e) {}
        });
    })();
</script>
@endsection

@section('style')
    @if (($content->type == 'Video') || ($content->type == 'YouTube'))
        <link rel="stylesheet" href="{{ $url_ytplayer_css }}">
    @endif

    @if ($content->type == 'Document' || $content->type == 'SOP/Regulation')
        <link rel="stylesheet" href="{{ asset('Tabler/dist/pdfjs-viewer.min.css') }}">
        <link rel="stylesheet" href="{{ asset('css/owl-pdf-reader.css') }}">
        @php
            $rawContentDuration = $content->page_duration ?? ($content->duration_page ?? null);
            $isContentScrollMode = $content->type === 'Document' && $rawContentDuration !== null && (int) $rawContentDuration === 0;
        @endphp
        @if ($isContentScrollMode)
            <link rel="stylesheet" href="{{ asset('css/document-scroll-mode.css') }}">
        @endif
    @endif

    @if (auth()->user()?->canAccessManageModule('playlist_debug_tools') && \App\Services\EngagementTime\EngagementTimeService::isTargetContent($content))
        <link rel="stylesheet" href="{{ asset('css/engagement-time.css') }}">
    @endif

    <style>
        .content-detail-page {
            background: linear-gradient(180deg, #f8fbff 0%, #f3f7fc 100%);
        }

        body[data-bs-theme="dark"] .content-detail-page,
        body.dark-mode .content-detail-page {
            background: linear-gradient(180deg, #0b1220 0%, #111827 100%);
        }

        .content-hero-card,
        .comment-shell,
        .modern-player-shell,
        .glass-surface {
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            background: rgba(255, 255, 255, 0.82);
            border: 1px solid rgba(148, 163, 184, 0.18);
        }

        .content-hero-actions {
            min-width: 2px;
        }

        .content-meta-chip,
        .content-stat-chip {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            border-radius: 999px;
            padding: .5rem .9rem;
            font-size: .875rem;
            font-weight: 600;
            color: #475569;
            background: #f8fafc;
        }

        .content-stat-chip {
            background: rgba(32, 107, 196, .08);
            color: #05507D;
        }

        .content-action-btn,
        .comment-action-btn {
            min-height: 42px;
            transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, color .18s ease, border-color .18s ease;
        }

        .content-action-btn.btn-light:hover,
        .comment-action-btn.btn-light:hover {
            background: #eff6ff;
            color: #05507D;
            border-color: #bfdbfe;
        }

        .comment-action-btn.btn-outline-secondary:hover {
            background: #05507D;
            color: #fff;
            border-color: #05507D;
        }

        .content-action-btn:hover,
        .comment-action-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 .75rem 1.5rem rgba(15, 23, 42, .08);
        }

        .min-w-0 {
            min-width: 0;
        }

        .content-detail-title {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.25;
            color: #111827;
        }

        .content-detail-subtitle {
            font-size: 15px;
            line-height: 1.7;
        }

        .comment-panel .card,
        .content-sidebar-card,
        .modern-player-shell {
            border-radius: 1.5rem !important;
        }

        /* Cleaning Modal Duplicate Definitions */
        .content-comment-modal .modal-dialog {
            display: flex;
            width: min(70vw, 920px);
            max-width: min(70vw, 920px);
            height: 88vh;
            margin-left: auto;
            margin-right: auto;
        }

        .content-comment-modal .modal-content {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            max-height: 100%;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .24);
        }

        .content-comment-modal .modal-body {
            display: flex;
            flex: 1 1 auto;
            min-height: 0;
            padding: 0;
            background: #f8fafc;
            overflow-y: auto;
        }

        .content-comment-modal .view-comment-wrapper {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            padding: 12px 14px 14px;
            overflow: hidden;
        }

        /* Keep the shared PDF toolbar centered in the content detail shell. */
        .content-detail-page .owl-pdf-toolbar {
            display: flex;
            justify-content: center;
        }

        .content-detail-page .owl-pdf-toolbar .pdf-tb-inner {
            width: 100%;
            justify-content: center;
        }

        .content-detail-page .badge-completed,
        .content-detail-page .badge-completed i,
        .content-detail-page .badge-completed svg {
            color: #0f5132 !important;
            fill: #0f5132 !important;
        }

        .badge-learning-completed {
            color: #0f5132 !important;
            background-color: #d1e7dd !important;
        }

        .badge-learning-completed i {
            color: #0f5132 !important;
        }

        .content-overview-box {
            background-color: #f8fafc;
            border: 1px solid #f1f5f9;
        }

        .content-overview-text {
            color: #212529 !important;
            font-size: 1.05rem !important;
        }

        .content-comment-modal .view-comment-wrapper .comment-ajax-root {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }

        .content-comment-modal .view-comment-wrapper .comment-ajax-root > #comment {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
            margin: 0 !important;
            overflow: hidden;
        }

        .content-comment-modal .view-comment-wrapper .comment-ajax-root > #comment > .card-body:last-child {
            flex: 1 1 auto;
            overflow-y: auto;
        }

        .overview-text.is-collapsed {
            display: -webkit-box;
            overflow: hidden;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 3;
        }

        .overview-meta-button {
            padding: 0;
            border: 0;
            background: transparent;
            color: inherit;
            cursor: pointer;
        }

        .overview-meta-button:hover {
            color: #05507D;
        }

        .comment-composer {
            max-width: 100%;
        }

        .comment-avatar {
            box-shadow: 0 .5rem 1rem rgba(15, 23, 42, .08);
        }

        .modern-comment-input {
            border-radius: 1.25rem;
            padding: 1rem 1.1rem;
            resize: none;
            min-height: 120px;
            border: 1px solid #dbe4f0;
            background: #fff;
            transition: border-color .18s ease, box-shadow .18s ease;
        }

        .modern-comment-input:focus {
            border-color: #05507D;
            box-shadow: 0 0 0 .25rem rgba(32, 107, 196, .12);
        }

        .comment-thread-root {
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(148, 163, 184, .16);
        }

        .comment-thread-reply {
            margin-top: .8rem;
            padding-left: 1.25rem;
            border-left: 2px solid rgba(32, 107, 196, .18);
        }

        .comment-card {
            background: rgba(248, 250, 252, .9);
            border: 1px solid rgba(148, 163, 184, .16);
            border-radius: 1.25rem;
            padding: 1rem 1.1rem;
            box-shadow: 0 .5rem 1rem rgba(15, 23, 42, .03);
        }

        .comment-card-reply {
            background: rgba(255, 255, 255, .88);
        }

        .comment-body {
            color: #334155;
            line-height: 1.7;
            word-break: break-word;
        }

        .reply-form-shell {
            border-left: 2px solid rgba(32, 107, 196, .2);
            padding-left: 1rem;
        }

        .comment-replies {
            margin-top: .9rem;
            padding-left: .35rem;
        }

        .comment-replies-nested {
            margin-top: .75rem;
        }

        .sidebar-stat-card {
            padding: 1rem;
            border-radius: 1rem;
            background: rgba(248, 250, 252, .95);
            border: 1px solid rgba(148, 163, 184, .14);
            transition: transform .18s ease, box-shadow .18s ease;
        }

        .sidebar-stat-card:hover,
        .content-sidebar-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 1rem 2rem rgba(15, 23, 42, .08);
        }

        .sidebar-progress {
            height: .7rem;
            border-radius: 999px;
            background: #e2e8f0;
        }

        .tips-card {
            background: linear-gradient(135deg, #05507D 0%, #6AAED6 100%);
        }

        .content-sidebar-toggle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .35rem;
            min-height: 42px;
            padding: .55rem 1.15rem;
            border: 1px solid #e9edf5;
            border-radius: 999px;
            background: rgba(255, 255, 255, .85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            color: #475569;
            font-size: .875rem;
            font-weight: 600;
            line-height: 1;
            cursor: pointer;
            box-shadow: 0 .25rem .75rem rgba(15, 23, 42, .05);
            transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, color .18s ease, border-color .18s ease;
        }

        .content-sidebar-toggle i {
            font-size: 1.1rem;
            color: inherit;
            transition: color .18s ease;
        }

        .content-sidebar-toggle:hover {
            background: #eff6ff;
            border-color: #05507D;
            color: #05507D;
            transform: translateY(-1px);
            box-shadow: 0 .75rem 1.5rem rgba(15, 23, 42, .1);
        }

        .content-sidebar-toggle:focus,
        .content-sidebar-toggle:focus-visible {
            outline: none;
            box-shadow: 0 0 0 .25rem rgba(32, 107, 196, .18);
        }

        .content-sidebar-toggle:active {
            transform: translateY(0);
        }

        body[data-bs-theme="dark"] .content-sidebar-toggle,
        body.dark-mode .content-sidebar-toggle {
            background: rgba(17, 24, 39, .82);
            border-color: rgba(148, 163, 184, .18);
            color: #e5e7eb;
        }

        body[data-bs-theme="dark"] .content-sidebar-toggle:hover,
        body.dark-mode .content-sidebar-toggle:hover {
            background: rgba(32, 107, 196, .18);
            border-color: #05507D;
            color: #93c5fd;
        }

        .content-learning-main {
            min-width: 0;
        }

        .content-learning-main .player-frame,
        .content-learning-main video,
        .content-learning-main iframe {
            width: 100%;
        }

        .ai-assistant-card .ai-online-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #2fb344;
            animation: ai-online-pulse 1.6s ease infinite;
        }

        @keyframes ai-online-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(47, 179, 68, .45); }
            50% { box-shadow: 0 0 0 4px rgba(47, 179, 68, 0); }
        }

        .ai-chat-messages {
            max-height: 240px;
            overflow-y: auto;
        }

        .ai-chat-bubble {
            max-width: 90%;
            padding: .65rem .9rem;
            border-radius: 1rem;
            font-size: .875rem;
            line-height: 1.55;
            word-break: break-word;
        }

        .ai-chat-bubble-assistant {
            align-self: flex-start;
            background: rgba(32, 107, 196, .08);
            color: #1e293b;
            border-bottom-left-radius: .25rem;
        }

        .ai-chat-bubble-user {
            align-self: flex-end;
            background: #05507D;
            color: #fff;
            border-bottom-right-radius: .25rem;
        }

        .ai-suggestion-btn {
            font-size: .82rem;
            font-weight: 500;
            border-radius: .75rem;
            border-color: rgba(32, 107, 196, .25);
            color: #05507D;
            background: rgba(32, 107, 196, .04);
            transition: background-color .18s ease, color .18s ease, border-color .18s ease;
        }

        .ai-suggestion-btn:hover {
            background: #05507D;
            border-color: #05507D;
            color: #fff;
        }

        .ai-chat-input {
            border-radius: .75rem;
        }

        body[data-bs-theme="dark"] .ai-chat-bubble-assistant,
        body.dark-mode .ai-chat-bubble-assistant {
            background: rgba(255, 255, 255, .08);
            color: #e5e7eb;
        }

        body[data-bs-theme="dark"] .ai-suggestion-btn,
        body.dark-mode .ai-suggestion-btn {
            background: rgba(255, 255, 255, .04);
            border-color: rgba(148, 163, 184, .25);
            color: #93c5fd;
        }

        body[data-bs-theme="dark"] .ai-suggestion-btn:hover,
        body.dark-mode .ai-suggestion-btn:hover {
            background: #05507D;
            border-color: #05507D;
            color: #fff;
        }

        .player-frame,
        .player-frame iframe,
        .player-frame video {
            border-radius: 1.25rem;
            overflow: hidden;
        }

        .player-frame {
            background: #0f172a;
        }

        .player-skeleton {
            position: absolute;
            inset: 0;
            z-index: 2;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: .75rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(15, 23, 42, .95), rgba(30, 41, 59, .9));
        }

        .player-skeleton-block {
            height: 1rem;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(255,255,255,.08) 25%, rgba(255,255,255,.18) 37%, rgba(255,255,255,.08) 63%);
            background-size: 400% 100%;
            animation: content-skeleton 1.4s ease infinite;
        }

        .player-skeleton-block-lg {
            width: 72%;
            height: 1.5rem;
        }

        .player-skeleton-block-sm {
            width: 46%;
        }

        .comment-empty-icon {
            font-size: 48px;
        }

        @keyframes content-skeleton {
            0% { background-position: 0 0; }
            100% { background-position: 400% 0; }
        }

        body[data-bs-theme="dark"] .content-hero-card,
        body[data-bs-theme="dark"] .comment-shell,
        body[data-bs-theme="dark"] .modern-player-shell,
        body[data-bs-theme="dark"] .glass-surface,
        body.dark-mode .content-hero-card,
        body.dark-mode .comment-shell,
        body.dark-mode .modern-player-shell,
        body.dark-mode .glass-surface {
            background: rgba(17, 24, 39, .82);
            border-color: rgba(148, 163, 184, .14);
        }

        body[data-bs-theme="dark"] .content-meta-chip,
        body[data-bs-theme="dark"] .content-stat-chip,
        body.dark-mode .content-meta-chip,
        body.dark-mode .content-stat-chip {
            background: rgba(255, 255, 255, .06);
            color: #e5e7eb;
        }

        body[data-bs-theme="dark"] .content-detail-title,
        body.dark-mode .content-detail-title,
        body[data-bs-theme="dark"] .content-detail-subtitle,
        body.dark-mode .content-detail-subtitle,
        body[data-bs-theme="dark"] .comment-body,
        body.dark-mode .comment-body {
            color: #e5e7eb;
        }

        body[data-bs-theme="dark"] .comment-card,
        body.dark-mode .comment-card {
            background: rgba(15, 23, 42, .72);
            border-color: rgba(148, 163, 184, .14);
        }

        body[data-bs-theme="dark"] .modern-comment-input,
        body.dark-mode .modern-comment-input {
            background: rgba(15, 23, 42, .72);
            color: #f8fafc;
            border-color: rgba(148, 163, 184, .18);
        }

        /* =====================================================
           MAIN CONTENT - DARK MODE
           ===================================================== */
        body[data-bs-theme="dark"] .content-detail-page a.btn-link,
        body.dark-mode .content-detail-page a.btn-link {
            color: #93c5fd !important;
        }

        body[data-bs-theme="dark"] .content-detail-page a.btn-link:hover,
        body.dark-mode .content-detail-page a.btn-link:hover {
            color: #bfdbfe !important;
            text-decoration: underline !important;
        }

        body[data-bs-theme="dark"] .view-content-info-card,
        body.dark-mode .view-content-info-card {
            background: rgba(17, 24, 39, .82) !important;
            border: 1px solid rgba(148, 163, 184, .14) !important;
            color: #e5e7eb;
        }

        body[data-bs-theme="dark"] .view-content-info-card .text-dark,
        body.dark-mode .view-content-info-card .text-dark {
            color: #f8fafc !important;
        }

        body[data-bs-theme="dark"] .view-content-info-card .text-muted,
        body.dark-mode .view-content-info-card .text-muted {
            color: #94a3b8 !important;
        }

        body[data-bs-theme="dark"] .view-content-info-card .btn-outline-secondary,
        body.dark-mode .view-content-info-card .btn-outline-secondary {
            color: #94a3b8 !important;
            border-color: rgba(148, 163, 184, .25) !important;
            background: transparent;
        }

        body[data-bs-theme="dark"] .view-content-info-card .btn-outline-secondary:hover,
        body.dark-mode .view-content-info-card .btn-outline-secondary:hover {
            color: #ffffff !important;
            border-color: #3b82f6 !important;
            background: rgba(59, 130, 246, 0.15) !important;
        }

        body[data-bs-theme="dark"] .content-overview-box,
        body.dark-mode .content-overview-box {
            background-color: rgba(15, 23, 42, .75) !important;
            border: 1px solid rgba(148, 163, 184, .16) !important;
        }

        body[data-bs-theme="dark"] .content-overview-box .text-dark,
        body.dark-mode .content-overview-box .text-dark,
        body[data-bs-theme="dark"] .content-overview-text,
        body.dark-mode .content-overview-text {
            color: #e2e8f0 !important;
        }

        body[data-bs-theme="dark"] .content-overview-box .text-muted,
        body.dark-mode .content-overview-box .text-muted,
        body[data-bs-theme="dark"] .content-overview-heading,
        body.dark-mode .content-overview-heading {
            color: #94a3b8 !important;
        }

        body[data-bs-theme="dark"] .overview-meta-button,
        body.dark-mode .overview-meta-button {
            color: #94a3b8 !important;
        }

        body[data-bs-theme="dark"] .overview-meta-button:hover,
        body.dark-mode .overview-meta-button:hover {
            color: #60a5fa !important;
        }

        body[data-bs-theme="dark"] .overview-meta-separator,
        body.dark-mode .overview-meta-separator {
            color: #64748b !important;
        }

        body[data-bs-theme="dark"] .content-learning-main > .card,
        body.dark-mode .content-learning-main > .card {
            background: rgba(17, 24, 39, .82) !important;
            border: 1px solid rgba(148, 163, 184, .14) !important;
        }

        /* =====================================================
           RIGHT SIDEBAR - DARK MODE
           ===================================================== */
        body[data-bs-theme="dark"] .content-sidebar-card,
        body.dark-mode .content-sidebar-card {
            background: rgba(17, 24, 39, .82) !important;
            border: 1px solid rgba(148, 163, 184, .14) !important;
            color: #e2e8f0;
        }

        body[data-bs-theme="dark"] .content-sidebar-card .card-title,
        body.dark-mode .content-sidebar-card .card-title {
            color: #f8fafc !important;
            font-weight: 700;
        }

        body[data-bs-theme="dark"] .content-sidebar-card .card-title .text-primary,
        body[data-bs-theme="dark"] .content-sidebar-card .card-title i,
        body.dark-mode .content-sidebar-card .card-title .text-primary,
        body.dark-mode .content-sidebar-card .card-title i {
            color: #60a5fa !important;
        }

        /* A. Your Progress Card */
        body[data-bs-theme="dark"] #contentProgressValue,
        body.dark-mode #contentProgressValue {
            color: #60a5fa !important;
        }

        body[data-bs-theme="dark"] .content-sidebar-card .text-muted,
        body.dark-mode .content-sidebar-card .text-muted {
            color: #94a3b8 !important;
        }

        body[data-bs-theme="dark"] .content-sidebar-card .text-muted i,
        body.dark-mode .content-sidebar-card .text-muted i {
            color: #94a3b8 !important;
        }

        body[data-bs-theme="dark"] .content-sidebar-card .text-dark,
        body.dark-mode .content-sidebar-card .text-dark {
            color: #f8fafc !important;
        }

        body[data-bs-theme="dark"] .content-sidebar-card .text-dark i,
        body.dark-mode .content-sidebar-card .text-dark i {
            color: #60a5fa !important;
        }

        body[data-bs-theme="dark"] .content-sidebar-card .progress,
        body.dark-mode .content-sidebar-card .progress {
            background-color: rgba(255, 255, 255, 0.12) !important;
        }

        body[data-bs-theme="dark"] .content-sidebar-card .progress-bar,
        body.dark-mode .content-sidebar-card .progress-bar {
            background: linear-gradient(90deg, #2563eb, #60a5fa) !important;
        }

        /* Status & Completed Badges */
        body[data-bs-theme="dark"] .badge-learning-completed,
        body.dark-mode .badge-learning-completed {
            color: #4ade80 !important;
            background-color: rgba(34, 197, 94, 0.18) !important;
            border: 1px solid rgba(74, 222, 128, 0.3) !important;
        }

        body[data-bs-theme="dark"] .badge-learning-completed i,
        body.dark-mode .badge-learning-completed i {
            color: #4ade80 !important;
        }

        body[data-bs-theme="dark"] .content-sidebar-card .badge.bg-primary-lt,
        body.dark-mode .content-sidebar-card .badge.bg-primary-lt {
            color: #93c5fd !important;
            background-color: rgba(59, 130, 246, 0.18) !important;
            border: 1px solid rgba(147, 197, 253, 0.25) !important;
        }

        /* B. Assessment Card */
        body[data-bs-theme="dark"] .content-sidebar-card strong,
        body.dark-mode .content-sidebar-card strong {
            color: #f8fafc !important;
        }

        body[data-bs-theme="dark"] .btn-remedial:hover,
        body.dark-mode .btn-remedial:hover {
            background-color: #ea6c00 !important;
            border-color: #ea6c00 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(255, 122, 0, 0.35) !important;
        }

        body[data-bs-theme="dark"] .btn-continue-test:hover,
        body.dark-mode .btn-continue-test:hover {
            background-color: #0f6ebd !important;
            border-color: #0f6ebd !important;
            color: #ffffff !important;
            box-shadow: 0 4px 12px rgba(12, 91, 158, 0.4) !important;
        }

        body[data-bs-theme="dark"] .content-sidebar-card .btn-success.disabled,
        body.dark-mode .content-sidebar-card .btn-success.disabled {
            background-color: rgba(34, 197, 94, 0.2) !important;
            border-color: rgba(34, 197, 94, 0.4) !important;
            color: #4ade80 !important;
            opacity: 1 !important;
        }

        body[data-bs-theme="dark"] .content-sidebar-card .btn-secondary.disabled,
        body.dark-mode .content-sidebar-card .btn-secondary.disabled {
            background-color: rgba(148, 163, 184, 0.2) !important;
            border-color: rgba(148, 163, 184, 0.3) !important;
            color: #94a3b8 !important;
            opacity: 1 !important;
        }

        /* C. Content Stats Card */
        body[data-bs-theme="dark"] .sidebar-stat-card,
        body.dark-mode .sidebar-stat-card {
            background-color: rgba(15, 23, 42, .85) !important;
            border: 1px solid rgba(148, 163, 184, .18) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .25) !important;
        }

        body[data-bs-theme="dark"] .sidebar-stat-card:hover,
        body.dark-mode .sidebar-stat-card:hover {
            border-color: rgba(96, 165, 250, .35) !important;
            box-shadow: 0 8px 20px rgba(0, 0, 0, .35) !important;
        }

        body[data-bs-theme="dark"] .sidebar-stat-card .text-dark,
        body.dark-mode .sidebar-stat-card .text-dark {
            color: #f8fafc !important;
        }

        body[data-bs-theme="dark"] .sidebar-stat-card .text-muted,
        body.dark-mode .sidebar-stat-card .text-muted {
            color: #94a3b8 !important;
        }

        /* D. AI Assistant Card */
        body[data-bs-theme="dark"] .ai-assistant-card .badge.bg-success-lt,
        body.dark-mode .ai-assistant-card .badge.bg-success-lt {
            color: #4ade80 !important;
            background-color: rgba(34, 197, 94, 0.18) !important;
        }

        body[data-bs-theme="dark"] .ai-chat-input,
        body.dark-mode .ai-chat-input {
            background: rgba(15, 23, 42, .85) !important;
            color: #f8fafc !important;
            border-color: rgba(148, 163, 184, .25) !important;
        }

        body[data-bs-theme="dark"] .ai-chat-input::placeholder,
        body.dark-mode .ai-chat-input::placeholder {
            color: #94a3b8 !important;
        }

        body[data-bs-theme="dark"] .ai-assistant-card .btn-primary,
        body.dark-mode .ai-assistant-card .btn-primary {
            background: #2563eb !important;
            border-color: #2563eb !important;
            color: #ffffff !important;
        }

        body[data-bs-theme="dark"] .ai-assistant-card .btn-primary:hover,
        body.dark-mode .ai-assistant-card .btn-primary:hover {
            background: #1d4ed8 !important;
            border-color: #1d4ed8 !important;
        }

        /* E. Content Comments Modal */
        body[data-bs-theme="dark"] .content-comment-modal .modal-content,
        body.dark-mode .content-comment-modal .modal-content {
            background: #162235 !important;
            color: #e5e7eb !important;
            border: 1px solid rgba(148, 163, 184, .18) !important;
        }

        body[data-bs-theme="dark"] .content-comment-modal .modal-header,
        body.dark-mode .content-comment-modal .modal-header {
            border-bottom: 1px solid rgba(148, 163, 184, .18) !important;
            color: #e5e7eb !important;
        }

        body[data-bs-theme="dark"] .content-comment-modal .modal-title,
        body.dark-mode .content-comment-modal .modal-title {
            color: #f8fafc !important;
        }

        body[data-bs-theme="dark"] .content-comment-modal .modal-body,
        body.dark-mode .content-comment-modal .modal-body {
            background: #0f172a !important;
        }

        body[data-bs-theme="dark"] .content-comment-modal .btn-close,
        body.dark-mode .content-comment-modal .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        @media (max-width: 768px) {
            .content-detail-title {
                font-size: 1.45rem;
            }

            .content-hero-actions {
                min-width: 100%;
            }

            .comment-thread-root {
                padding: .9rem .75rem;
            }

            .comment-card {
                padding: .95rem .9rem;
            }

            .comment-thread-reply {
                padding-left: .9rem;
            }

            .comment-empty-icon {
                font-size: 42px;
            }

            .content-comment-modal .modal-dialog {
                width: calc(100% - 1rem);
                max-width: none;
                height: calc(100% - 1rem);
                margin: .5rem;
            }

            .content-comment-modal .view-comment-wrapper {
                padding: 8px;
            }
        }
    </style>
@endsection

@section('script')
    <script src="{{ asset('js/learning-progress.js') }}"></script>

    <script>
        async function shareContent(title) {
            const shareTitle = title || @json($content->title ?? '') || document.title;
            const shareData = {
                title: shareTitle,
                text: 'Lihat materi pembelajaran ini',
                url: window.location.href
            };

            try {
                if (navigator.share) {
                    await navigator.share(shareData);
                } else {
                    await navigator.clipboard.writeText(window.location.href);

                    if (window.Toastify) {
                        Toastify({
                            text: "Link berhasil disalin",
                            duration: 3000,
                            gravity: "top",
                            position: "right"
                        }).showToast();
                    } else if (typeof toastr !== 'undefined') {
                        toastr.success("Link berhasil disalin");
                    } else {
                        alert('Link berhasil disalin');
                    }
                }
            } catch (err) {
                if (err && err.name !== 'AbortError') {
                    console.log(err);
                }
            }
        }
        window.shareContent = shareContent;

        document.addEventListener('click', function (event) {
            var button = event.target.closest('.overview-toggle');
            if (!button) return;

            var overview = document.querySelector(button.dataset.overviewTarget || '#contentOverviewText');
            if (!overview) return;

            overview.classList.toggle('is-collapsed');
            var expanded = !overview.classList.contains('is-collapsed');
            button.textContent = expanded
                ? button.dataset.readLess
                : button.dataset.readMore;
        });
    </script>

    @if ($content->type == 'Document' || $content->type == 'SOP/Regulation')
        <script src="{{ asset('js/pdf.min.js') }}"></script>
        <script src="{{ asset('js/pdfjs-viewer.min.js') }}"></script>
        <script src="{{ asset('js/owl-pdf-reader.js') }}"></script>
        @if ($isContentScrollMode)
            <script src="{{ asset('js/document-scroll-mode.js') }}"></script>
        @endif
        <script src="{{ asset('js/document-reading-tracker.js') }}"></script>

        <script>
            function hideContentPlayerSkeleton() {
                $('.player-skeleton').addClass('d-none');
            }

            if (window.contentDocumentReadingTracker && typeof window.contentDocumentReadingTracker.destroy === 'function') {
                window.contentDocumentReadingTracker.destroy();
            }

            const documentReadingTracker = new DocumentReadingTracker({
                url: "{{ route('content.user.progress.update') }}",
                csrf: "{{ csrf_token() }}",
                contentId: {{ (int) $content->id }},
                maxActualWatchSeconds: {{ $content->max_actual_watch_seconds !== null ? (int) $content->max_actual_watch_seconds : 'null' }},
                initialActualWatchSeconds: {{ (int) ($content_user->actual_watch_seconds ?? 0) }},
                heartbeatIntervalMs: 10000,
                onUpdate: window.owlUpdateLearningUi
            });

            window.contentDocumentReadingTracker = documentReadingTracker;

            let fileName = "{{ $getcontentfile }}";

            let fileNameFixed = fileName
                .replaceAll(' ', '%20')
                .replaceAll('&amp;', '&');

            let fileLocation =
                "{{ asset('storage/files/content/' . $content->id . '/' . ':fileContent') }}";

            let pdfFile = fileLocation.replace(':fileContent', fileNameFixed);

            var pdfjsLib = window["pdfjs-dist/build/pdf"];

            pdfjsLib.GlobalWorkerOptions.workerSrc =
                "{{ asset('js/pdf.worker.min.js') }}";

            const progressTracker = new OwlLearningProgress({
                url: "{{ route('content.user.progress.update') }}",
                csrf: "{{ csrf_token() }}",
                contentId: {{ (int) $content->id }},
                type: 'document',
                onUpdate: window.owlUpdateLearningUi
            });

            function updateDocumentProgressUi(readPage, totalPages) {
                var progress = totalPages > 0
                    ? Math.min(100, Math.round((readPage / totalPages) * 100))
                    : 0;

                $('.pages-read-text').text(readPage + '/' + totalPages + ' pages read');
                $('#contentProgressValue').text(progress + '%');
                $('#contentProgressBar').css('width', progress + '%');
            }

            var isDocument = @json($content->type === 'Document');
            var rawDuration = @json($content->page_duration ?? ($content->duration_page ?? null));
            var rawPageDuration = rawDuration !== null && rawDuration !== undefined ? Number(rawDuration) : 30;
            var isScrollMode = isDocument && rawPageDuration === 0;

            if (isScrollMode) {
                var scrollReader = new DocumentScrollMode({
                    shellId: 'pdfviewer-shell',
                    viewerSelector: '.content-pdfjs-viewer',
                    completedPages: {{ (int) ($content_user->last_page ?? 0) }},
                    contentCompleted: {{ (int) ($content_user->finish ?? 0) }} === 1,
                    totalPages: {{ (int) ($content->total_page ?? 0) }},
                    onPageProgress: function(readPage, totalPages) {
                        updateDocumentProgressUi(readPage, totalPages);
                        progressTracker.sendDebounced({
                            last_page: readPage,
                            total_pages: totalPages,
                            status: 'in_progress'
                        });
                    }
                });

                var pdfViewer = window.pdfViewer = new PDFjsViewer(
                    $(".content-pdfjs-viewer"),
                    scrollReader.getViewerOptions({
                        onDocumentReady: function () {
                            var totalPages = pdfViewer.getPageCount();
                            var lastPage = {{ (int) ($content_user->last_page ?? 0) }};

                            if (!lastPage) {
                                var progression = {{ (int) $content_user->progress }};
                                lastPage = parseInt((progression * totalPages) / 100);
                            }

                            var initialActivePage = lastPage > 1 ? Math.min(lastPage, totalPages) : 1;
                            if (initialActivePage > 1) {
                                scrollReader.scrollToPage(initialActivePage);
                            }

                            progressTracker.send({ total_pages: totalPages }, true);
                            hideContentPlayerSkeleton();
                        }
                    })
                );

                scrollReader.attach(pdfViewer, pdfFile);

                window.pdfRotateLeft = function () {
                    return scrollReader.rotate(-90);
                };
                window.pdfRotateRight = function () {
                    return scrollReader.rotate(90);
                };
                window.pdfToggleReadingMode = function () {
                    return Promise.resolve();
                };
                window.pdfDoPrint = function () {
                    return scrollReader.print();
                };
                window.pdfToggleFullscreen = function () {
                    return scrollReader.toggleFullscreen();
                };

                if (fileName) {
                    pdfViewer.loadDocument(pdfFile).then(function () {
                        scrollReader.scheduleAutoFit(true);
                        hideContentPlayerSkeleton();
                    });
                }
            } else {
                // EXISTING TIMED PAGINATION / SOP LOGIC (100% UNCHANGED)
                var isTimedDocument = isDocument;
                var documentPageDuration = Math.max(1, rawPageDuration || 30);

                var contentPdfReader = new OwlPdfReader({
                    shellId: 'pdfviewer-shell',
                    viewerSelector: '.content-pdfjs-viewer',
                    pageTimingEnabled: isTimedDocument,
                    pageDuration: documentPageDuration,
                    completedPages: {{ (int) ($content_user->last_page ?? 0) }},
                    contentCompleted: {{ (int) ($content_user->finish ?? 0) }} === 1,
                    onPageStarted: function(page, totalPages) {
                        return progressTracker.send({
                            active_page: page,
                            total_pages: totalPages,
                            status: 'in_progress'
                        }, true);
                    },
                    onPageCompleted: function(page, totalPages, elapsedSeconds) {
                        return progressTracker.send({
                            active_page: page,
                            completed_page: page,
                            page_elapsed_seconds: elapsedSeconds,
                            total_pages: totalPages,
                            status: 'in_progress'
                        }, true).then(function(result) {
                            if (result && result.page_completed) {
                                updateDocumentProgressUi(result.last_page || page, totalPages);
                            }

                            return result;
                        });
                    }
                });

                let maxPagesRead = isTimedDocument
                    ? {{ (int) ($content_user->last_page ?? 0) }}
                    : Math.max(1, {{ (int) ($content_user->last_page ?? 0) }});

                function updatePdfPageButtons(activePage, totalPages) {
                    var currentPage = Math.max(1, parseInt(activePage, 10) || 1);
                    contentPdfReader.totalPages = Math.max(0, parseInt(totalPages, 10) || 0);
                    contentPdfReader.updateNavigationButtons(currentPage);
                }

                var pdfViewer = window.pdfViewer = new PDFjsViewer(
                    $(".content-pdfjs-viewer"),
                    contentPdfReader.getViewerOptions({
                        onDocumentReady: function () {
                            var totalPages = pdfViewer.getPageCount();

                            var lastPage = {{ (int) ($content_user->last_page ?? 0) }};

                            if (!lastPage) {
                                var progression = {{ (int) $content_user->progress }};
                                lastPage = parseInt((progression * totalPages) / 100);
                            }

                            var initialActivePage = 1;

                            if (isTimedDocument) {
                                maxPagesRead = Math.min(lastPage, totalPages);
                                initialActivePage = {{ (int) ($content_user->finish ?? 0) }} === 1
                                    ? totalPages
                                    : Math.min(totalPages, maxPagesRead + 1);
                            } else {
                                maxPagesRead = Math.max(maxPagesRead, lastPage || 1);
                                initialActivePage = lastPage > 1 && lastPage < totalPages ? lastPage : 1;
                            }

                            if (initialActivePage > 1) {
                                pdfViewer.scrollToPage(initialActivePage);
                            }

                            if (!isTimedDocument
                                || {{ (int) ($content_user->finish ?? 0) }} === 1
                                || lastPage >= totalPages) {
                                progressTracker.send({ total_pages: totalPages }, true);
                            }

                            updatePdfPageButtons(initialActivePage, totalPages);

                            hideContentPlayerSkeleton();
                        },

                        onActivePageChanged: function (page, pageno) {
                            var totalPages = this.getPageCount();
                            var activePage = parseInt(pageno, 10) || 1;

                            // The top label represents the page currently visible.
                            contentPdfReader.updatePageLabel(activePage, totalPages);
                            updatePdfPageButtons(activePage, totalPages);

                            if (isTimedDocument) return;

                            // Sidebar progress is monotonic: going backward must
                            // never reduce the highest page already reached.
                            if (activePage <= maxPagesRead) return;

                            maxPagesRead = activePage;
                            updateDocumentProgressUi(maxPagesRead, totalPages);

                            progressTracker.sendDebounced({
                                last_page: maxPagesRead,
                                total_pages: totalPages
                            });
                        }
                    }));

                contentPdfReader.attach(pdfViewer, pdfFile);

                window.pdfRotateLeft = function () {
                    return contentPdfReader.rotate(-90);
                };
                window.pdfRotateRight = function () {
                    return contentPdfReader.rotate(90);
                };
                window.pdfToggleReadingMode = function () {
                    return contentPdfReader.toggleReadingMode();
                };
                window.pdfDoPrint = function () {
                    return contentPdfReader.print();
                };
                window.pdfToggleFullscreen = function () {
                    return contentPdfReader.toggleFullscreen();
                };

                if (fileName) {
                    pdfViewer.loadDocument(pdfFile).then(function () {
                        contentPdfReader.scheduleAutoFit(true);
                        hideContentPlayerSkeleton();
                    });
                }
            }
        </script>
    @endif

    @if (($content->type == 'Video') || ($content->type == 'YouTube'))
        <script src="{{ asset('js/video-watch-time.js') }}"></script>
        <script src="{{ $url_ytplayer_js }}"></script>

        <script>
            $(document).ready(function() {
                function hideContentPlayerSkeleton() {
                    $('.player-skeleton').addClass('d-none');
                }

                @if ($content_user->progress >= 100)
                    const player = new Plyr('#player');
                @else
                    const player = new Plyr('#player', {
                        listeners: {
                            seek(e) {
                                e.preventDefault();
                                return false;
                            }
                        }
                    });
                @endif

                const progressTracker = new OwlLearningProgress({
                    url: "{{ route('content.user.progress.update') }}",
                    csrf: "{{ csrf_token() }}",
                    contentId: {{ (int) $content->id }},
                    type: 'video',
                    onUpdate: window.owlUpdateLearningUi
                });

                if (window.contentWatchTimeTracker && typeof window.contentWatchTimeTracker.destroy === 'function') {
                    window.contentWatchTimeTracker.destroy();
                }

                const watchTimeTracker = new VideoWatchTimeTracker({
                    player: player,
                    url: "{{ route('content.user.progress.update') }}",
                    csrf: "{{ csrf_token() }}",
                    contentId: {{ (int) $content->id }},
                    officialDuration: {{ (int) ($content->duration_in_seconds ?? 0) }},
                    maxActualWatchSeconds: {{ $content->max_actual_watch_seconds !== null ? (int) $content->max_actual_watch_seconds : 'null' }},
                    initialActualWatchSeconds: {{ (int) (($content_user->finish == '1' || ($content_user->progress ?? 0) >= 100) ? max((int) ($content_user->actual_watch_seconds ?? 0), (int) ($content->duration_in_seconds ?? 0)) : (int) ($content_user->actual_watch_seconds ?? 0)) }},
                    heartbeatIntervalMs: 10000,
                    onUpdate: window.owlUpdateLearningUi
                });

                window.contentWatchTimeTracker = watchTimeTracker;

                player.on('loadeddata', onDataLoaded);
                player.on('ready', function() {
                    hideContentPlayerSkeleton();
                });

                function onDataLoaded() {
                    var resumeSeconds = {{ (int) ($content_user->current_time ?? 0) }};

                    if (!resumeSeconds) {
                        var progression = $('#player_progress').val();
                        var durasi = $('#player_duration').val();
                        resumeSeconds = (progression * durasi) / 100;
                    }

                    if (resumeSeconds > 0 && resumeSeconds < player.duration) {
                        player.currentTime = resumeSeconds;
                    }

                    player.off('loadeddata', onDataLoaded);
                    hideContentPlayerSkeleton();
                }

                player.on('loadedmetadata', function() {
                    var officialDuration = {{ (int) ($content->duration_in_seconds ?? 0) }};
                    var playerDuration = (player && player.duration && isFinite(player.duration)) ? Math.round(player.duration) : 0;
                    var durationToSend = playerDuration;
                    if (officialDuration > 5 && (playerDuration <= 5 || !playerDuration)) {
                        durationToSend = officialDuration;
                    }
                    if (durationToSend > 0) {
                        progressTracker.send({ duration_seconds: durationToSend }, true);
                    }
                });
            });
        </script>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            function showContentToast(message) {
                let container = document.querySelector('.content-toast-container');

                if (!container) {
                    container = document.createElement('div');
                    container.className = 'content-toast-container position-fixed top-0 end-0 p-3';
                    container.style.zIndex = '1080';
                    document.body.appendChild(container);
                }

                const toast = document.createElement('div');
                toast.className = 'alert alert-success shadow-lg mb-2 content-toast-item';
                toast.style.transition = 'opacity .2s ease';
                toast.innerHTML = '<div class="d-flex align-items-center gap-2"><i class="ti ti-check"></i><span>' + message + '</span></div>';

                container.appendChild(toast);

                setTimeout(function () {
                    toast.style.opacity = '0';
                    setTimeout(function () {
                        toast.remove();
                    }, 220);
                }, 1600);
            }

            document.querySelectorAll('[data-copy-content-url]').forEach(function (button) {
                button.addEventListener('click', async function () {
                    try {
                        if (navigator.clipboard && window.isSecureContext) {
                            await navigator.clipboard.writeText(window.location.href);
                        } else {
                            // Fallback jika dikunjungi lewat non-HTTPS / HTTP IP lokal
                            const textArea = document.createElement('textarea');
                            textArea.value = window.location.href;
                            document.body.appendChild(textArea);
                            textArea.select();
                            document.execCommand('copy');
                            document.body.removeChild(textArea);
                        }
                        showContentToast('Link copied!');
                    } catch (error) {
                        showContentToast('Copy failed');
                    }
                });
            });
        });
    </script>
    @if (auth()->user()?->canAccessManageModule('playlist_debug_tools') && \App\Services\EngagementTime\EngagementTimeService::isTargetContent($content))
        <script src="{{ asset('js/engagement-time-tracker.js') }}"></script>
    @endif
@endsection
