<style>
    .learning-view-page {
        background: #f5f7fb;
        min-height: 100vh;
    }

    .rounded-4 {
        border-radius: 18px !important;
    }

    .view-hero-card {
        height: 260px;
    }

    .view-hero-bg {
        height: 260px;
        background-size: cover;
        background-position: center;
        background-color: #1e293b;
    }

    .view-hero-overlay {
        height: 100%;
        display: flex;
        align-items: flex-end;
        background: linear-gradient(
            180deg,
            rgba(0, 0, 0, 0.15) 0%,
            rgba(0, 0, 0, 0.75) 100%
        );
    }

    .learning-player video,
    .learning-player .plyr {
        width: 100%;
        height: auto;
        aspect-ratio: 16 / 9;
        background: #000;
    }

    .playlist-list .list-group-item {
        color: inherit;
        transition: .2s ease;
    }

    .playlist-list .list-group-item:hover {
        background: #f1f5ff;
    }

    .playlist-list .active-lesson {
        background: #eaf1ff;
        border: 1px solid #3b82f6 !important;
    }

    .chapter-description-box {
        background: #f8fafc;
        border: 1px solid #edf2f7;
        border-radius: 16px;
        padding: 18px;
    }

    .learn-item {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        font-size: 13px;
        color: #334155;
    }

    .learn-item i {
        margin-top: 2px;
    }

    .btn-icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .ai-message-box {
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 12px;
        font-size: 13px;
        color: #475569;
    }

    .ai-suggestion-btn {
        border-radius: 12px;
        font-size: 13px;
        padding: 10px 12px;
    }

    .ai-input-group .form-control {
        border-radius: 12px 0 0 12px;
        font-size: 13px;
    }

    .ai-input-group .btn {
        border-radius: 0 12px 12px 0;
    }

    /* Playlist sidebar */
    .view-playlist-card {
        max-height: calc(100vh - 110px);
    }

    .playlist-scroll-area {
        max-height: 640px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .playlist-scroll-area::-webkit-scrollbar {
        width: 5px;
    }

    .playlist-scroll-area::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }

    .playlist-lesson-item {
        display: flex;
        gap: 10px;
        text-decoration: none;
        color: #0f172a;
        padding: 8px;
        border-radius: 14px;
        margin-bottom: 8px;
        border: 1px solid transparent;
        transition: .2s ease;
    }

    .playlist-lesson-item:hover {
        background: #f1f5ff;
        color: #0f172a;
    }

    .playlist-lesson-item.is-active {
        background: #eaf1ff;
        border-color: #3b82f6;
    }

    .lesson-thumb {
        width: 62px;
        height: 46px;
        border-radius: 12px;
        background-size: cover;
        background-position: center;
        flex-shrink: 0;
        position: relative;
        overflow: hidden;
    }

    .lesson-play-icon,
    .lesson-done-icon {
        position: absolute;
        inset: 0;
        margin: auto;
        width: 24px;
        height: 24px;
        border-radius: 999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
    }

    .lesson-play-icon {
        background: rgba(37, 99, 235, .92);
        color: #fff;
    }

    .lesson-done-icon {
        background: rgba(22, 163, 74, .92);
        color: #fff;
    }

    .lesson-info {
        min-width: 0;
        flex: 1;
    }

    .lesson-title {
        font-size: 12px;
        font-weight: 700;
        line-height: 1.25;
        color: #0f172a;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .lesson-meta {
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
    }

    .lesson-progress {
        height: 4px;
        margin-top: 5px;
        background: #e5e7eb;
    }

    /* AI card */
    .view-ai-card,
    .ai-card {
        display: block !important;
        width: 100%;
        min-height: 520px;
    }

    @media (max-width: 1199px) {
        .view-ai-card,
        .ai-card {
            min-height: auto;
        }
    }

    @media (max-width: 991px) {
        .sticky-top {
            position: static !important;
        }

        .view-ai-card,
        .ai-card {
            min-height: auto;
        }
    }

    /* ===== Playlist Learning Layout (3-column grid) ===== */

    .playlist-learning-layout {
        position: relative;
        display: grid;
        grid-template-columns: 280px minmax(0, 1fr) 300px;
        gap: 16px;
        align-items: start;
        transition: grid-template-columns .3s ease;
    }

    .learning-left-panel,
    .learning-right-panel {
        min-width: 0;
        overflow: hidden;
        transition: opacity .25s ease;
    }

    .learning-main-panel {
        min-width: 0;
    }

    .playlist-learning-layout.left-collapsed {
        grid-template-columns: 0 minmax(0, 1fr) 300px;
    }

    .playlist-learning-layout.right-collapsed {
        grid-template-columns: 280px minmax(0, 1fr) 0;
    }

    .playlist-learning-layout.left-collapsed.right-collapsed {
        grid-template-columns: 0 minmax(0, 1fr) 0;
    }

    .playlist-learning-layout.left-collapsed .learning-left-panel,
    .playlist-learning-layout.right-collapsed .learning-right-panel {
        opacity: 0;
        pointer-events: none;
    }

    /* ---- Tombol di dalam header card (collapse panel) ---- */
    .panel-header-toggle {
        flex-shrink: 0;
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        background: #f1f5f9;
        color: #64748b;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        padding: 0;
        transition: background .2s ease, border-color .2s ease, color .2s ease;
    }

    .panel-header-toggle:hover {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }

    body[data-bs-theme="dark"] .panel-header-toggle,
    body.dark-mode .panel-header-toggle {
        background: #1e293b;
        border-color: #334155;
        color: #94a3b8;
    }

    body[data-bs-theme="dark"] .panel-header-toggle:hover,
    body.dark-mode .panel-header-toggle:hover {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }

    /* ---- Tombol restore (muncul saat panel di-collapse) ---- */
    .restore-toggle-btn {
        display: none;
        position: absolute;
        z-index: 40;
        top: 16px;
        width: 32px;
        height: 32px;
        border-radius: 10px;
        border: 1px solid #d1d5db;
        background: #f3f4f6;
        color: #6b7280;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .12);
        cursor: pointer;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        padding: 0;
        transition: background .2s ease, border-color .2s ease, color .2s ease;
    }

    .restore-toggle-btn:hover {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
        box-shadow: 0 4px 14px rgba(37, 99, 235, .30);
    }

    .restore-left  { left: 8px; }
    .restore-right { right: 8px; }

    /* Tampilkan restore button hanya saat panel collapsed */
    .playlist-learning-layout.left-collapsed  .restore-left  { display: inline-flex; }
    .playlist-learning-layout.right-collapsed .restore-right { display: inline-flex; }

    body[data-bs-theme="dark"] .restore-toggle-btn,
    body.dark-mode .restore-toggle-btn {
        background: #1e293b;
        border-color: #334155;
        color: #94a3b8;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .30);
    }

    body[data-bs-theme="dark"] .restore-toggle-btn:hover,
    body.dark-mode .restore-toggle-btn:hover {
        background: #2563eb;
        border-color: #2563eb;
        color: #ffffff;
    }

    @media (max-width: 991px) {
        .playlist-learning-layout {
            display: block;
        }

        .learning-left-panel,
        .learning-right-panel {
            overflow: visible;
            opacity: 1 !important;
            pointer-events: auto !important;
            margin-bottom: 16px;
        }

        .playlist-learning-layout.left-collapsed .learning-left-panel,
        .playlist-learning-layout.right-collapsed .learning-right-panel {
            display: none;
        }

        /* Restore buttons tidak relevan di mobile — panel toggle via panel-header-toggle */
        .restore-toggle-btn {
            display: none !important;
        }
    }
</style>
