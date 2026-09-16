<style>
    .learning-view-page {
        background: #f5f7fb;
        min-height: 100vh;
    }

    .rounded-4 {
        border-radius: 18px !important;
    }

    .view-hero-card {
        min-height: 230px;
    }

    /* ============================================================
       VIEW HERO BACKGROUND (MATCHED WITH NEWS HERO GRADIENT)
       ============================================================ */
    .view-hero-bg {
        min-height: 230px;
        position: relative;
        overflow: hidden;
        border-radius: 18px;

        /* Combination Radial Light + Multi-stop Blue Linear Gradient */
        background: 
            radial-gradient(circle at 85% 20%, rgba(255, 255, 255, 0.22) 0%, transparent 28%),
            linear-gradient(135deg, #001b44 0%, #002b66 20%, #004b99 45%, #2a7bd1 70%, #82b8f5 88%, #dbeeff 100%) !important;
        background-size: cover;
        background-position: center;
    }

    /* Support Dark Mode */
    [data-bs-theme="dark"] .view-hero-bg,
    body.dark-mode .view-hero-bg {
        background: 
            radial-gradient(circle at 85% 20%, rgba(255, 255, 255, 0.15) 0%, transparent 28%),
            linear-gradient(135deg, #00112c 0%, #001b44 20%, #003366 45%, #1d5996 70%, #4e82be 88%, #82b8f5 100%) !important;
    }

    .view-hero-overlay {
        height: 100%;
        display: flex;
        align-items: stretch;
        background: transparent;
    }

    .view-hero-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 28px;
        padding: 28px 32px;
    }

    .view-hero-copy {
        min-width: 0;
        max-width: 820px;
    }

    .view-hero-title {
        font-size: 2rem;
        line-height: 1.15;
        font-weight: 800;
        margin: 0 0 14px;
        overflow-wrap: anywhere;
        color: #ffffff;
    }

    .view-hero-meta,
    .view-hero-bottom {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px 16px;
    }

    .view-hero-meta {
        font-size: 13px;
        opacity: .94;
        margin-bottom: 14px;
        color: #ffffff;
    }

    .view-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 7px 12px;
        border-radius: 999px;
        color: #ffffff;
        background: rgba(255, 255, 255, .18);
        border: 1px solid rgba(255, 255, 255, .25);
        font-size: 12px;
        font-weight: 700;
        backdrop-filter: blur(8px);
    }

    .view-hero-continue {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
        width: min(280px, 32%);
        min-width: 230px;
        padding: 14px;
        border-radius: 16px;
        color: #ffffff !important;
        text-decoration: none;
        background-color: #0a4b7c !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
        backdrop-filter: none;
        -webkit-backdrop-filter: none;
        text-align: center;
        transition: all 0.25s ease-in-out;
    }

    .view-hero-continue:hover {
        color: #ffffff !important;
        background-color: #0d5a94 !important;
        border-color: rgba(255, 255, 255, 0.35) !important;
        transform: translateY(-2px);
    }

    .view-hero-continue-thumb {
        width: 100%;
        aspect-ratio: 16 / 9;
        height: auto;
        border-radius: 10px;
        background-size: cover;
        background-position: center;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        box-shadow: inset 0 0 0 999px rgba(0, 0, 0, .26);
    }

    .view-hero-continue-thumb::before,
    .view-hero-continue-thumb::after,
    .view-hero-continue-thumb i,
    .continue-learning-media::before,
    .continue-learning-media .top-arrow-icon {
        display: none !important;
    }

    .view-hero-continue strong {
        font-size: 14px;
        line-height: 1.2;
    }

    .learning-player video,
    .learning-player .plyr {
        width: 100%;
        height: auto;
        aspect-ratio: 16 / 9;
        background: #000;
    }

    .learning-player {
        position: relative;
    }

    .fullscreen-player-overlays {
        display: none;
        pointer-events: none;
        position: absolute;
        inset: 10px 12px auto 12px;
        z-index: 1002;
        justify-content: space-between;
        gap: 10px;
        width: auto;
        overflow: visible;
    }

    .fullscreen-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        min-height: 28px;
        width: fit-content;
        max-width: none;
        padding: 5px 10px;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, .16);
        background: rgba(15, 23, 42, .46);
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.2;
        white-space: nowrap;
        overflow: visible;
        text-indent: 0;
        backdrop-filter: blur(10px);
    }

    .fullscreen-pill i {
        flex: 0 0 auto;
        font-size: 12px;
        line-height: 1;
    }

    .learning-player:fullscreen .fullscreen-player-overlays,
    .learning-player:-webkit-full-screen .fullscreen-player-overlays,
    :fullscreen > .fullscreen-player-overlays,
    :-webkit-full-screen > .fullscreen-player-overlays,
    .plyr--fullscreen .fullscreen-player-overlays,
    .learning-player.is-fullscreen .fullscreen-player-overlays {
        display: flex;
    }

    @media (max-width: 576px) {
        .fullscreen-player-overlays {
            inset: 8px 8px auto 8px;
            gap: 8px;
        }

        .fullscreen-pill {
            min-height: 26px;
            width: fit-content;
            max-width: none;
            padding: 5px 8px;
            font-size: 10px;
            gap: 5px;
            overflow: visible;
        }

        .fullscreen-pill i {
            font-size: 11px;
        }
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
        border: 1px solid #6AAED6 !important;
    }

    .chapter-description-box {
        background: #f8fafc;
        border: 1px solid #edf2f7;
        border-radius: 14px;
        padding: 16px;
    }

    .overview-inline-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 7px;
        font-size: 13px;
    }

    .overview-meta-button {
        display: inline-flex;
        align-items: center;
        padding: 0;
        border: 0;
        background: transparent;
        color: inherit;
        font: inherit;
        cursor: pointer;
    }

    .overview-meta-button:hover {
        color: #05507D;
    }

    .overview-meta-separator {
        color: #94a3b8;
    }

    .overview-text {
        font-size: 14px;
        line-height: 1.65;
    }

    .overview-text.is-collapsed {
        display: -webkit-box;
        -webkit-line-clamp: 4;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .overview-toggle {
        font-weight: 700;
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
        display: flex;
        flex-direction: column;
        max-height: calc(100vh - 2rem);
        min-height: 0;
        overflow: hidden;
    }

    .view-playlist-card > .card-body {
        display: flex;
        flex-direction: column;
        flex: 1 1 auto;
        min-height: 0;
        overflow: hidden;
    }

    .view-playlist-card > .card-body > div:not(.playlist-scroll-area) {
        flex: 0 0 auto;
    }

    .playlist-scroll-area {
        flex: 1 1 auto;
        min-height: 0;
        max-height: none;
        overflow-y: auto;
        overflow-x: hidden;
        padding-right: 4px;
        scrollbar-width: thin;
    }

    .playlist-scroll-area::-webkit-scrollbar {
        width: 5px;
    }

    .playlist-scroll-area::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 999px;
    }

    .playlist-lesson-item {
        display: block;
        color: #0f172a;
        padding: 10px;
        border-radius: 14px;
        margin-bottom: 8px;
        border: 1px solid transparent;
        transition: background-color .2s ease, border-color .2s ease, box-shadow .2s ease;
    }

    .playlist-lesson-main {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        color: inherit;
        text-decoration: none;
    }

    .playlist-lesson-main:hover {
        color: inherit;
        text-decoration: none;
    }

    .playlist-lesson-item:hover {
        background: #f1f5ff;
        color: #0f172a;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .06);
    }

    .playlist-lesson-item.is-active {
        background: #eaf1ff;
        border-color: #6AAED6;
        box-shadow: 0 6px 18px rgba(37, 99, 235, .10);
    }

    .lesson-type-icon {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .10);
        overflow: hidden;
    }

    .lesson-thumbnail-image {
        display: block;
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: inherit;
    }

    .lesson-thumbnail-fallback {
        width: 100%;
        height: 100%;
        align-items: center;
        justify-content: center;
        background: #dbeafe;
        color: #05507D;
    }

    .lesson-thumbnail-fallback:not(.d-none) {
        display: inline-flex;
    }

    .lesson-thumbnail-fallback i {
        font-size: 22px;
        line-height: 1;
    }

    .lesson-info {
        min-width: 0;
        flex: 1;
    }

    .lesson-heading {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        min-width: 0;
    }

    .lesson-title {
        min-width: 0;
        flex: 1;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.25;
        color: #0f172a;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .content-title-two-lines {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        word-break: break-word;
    }

    .lesson-play-icon,
    .lesson-done-icon {
        flex: 0 0 auto;
    }

    .lesson-status-icon {
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: -2px;
        font-size: 18px;
        line-height: 1;
    }

    .lesson-status--not-started {
        color: #94a3b8;
    }

    .lesson-status--in-progress {
        color: #05507D;
    }

    .lesson-status--completed,
    .lesson-done-icon {
        color: #16a34a;
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
        border-radius: 999px;
        overflow: hidden;
    }

    .lesson-progress .progress-bar {
        border-radius: inherit;
        transition: width .25s ease;
    }

    .lesson-assessment-action {
        margin-top: 8px;
        margin-left: 56px;
        line-height: 1;
    }

    .lesson-assessment-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: auto;
        min-height: 26px;
        max-width: 100%;
        padding: 4px 8px;
        border-radius: 8px;
        font-size: 10px;
        font-weight: 600;
        line-height: 1.1;
        white-space: nowrap;
    }

    .lesson-assessment-button.disabled,
    .lesson-assessment-button[aria-disabled="true"] {
        border-color: #cbd5e1;
        background: #f1f5f9;
        color: #64748b;
        cursor: not-allowed;
        opacity: .6;
        pointer-events: auto;
    }

    .certificate-action-wrapper.is-disabled {
        cursor: not-allowed;
    }

    .certificate-action-button:disabled {
        opacity: .6;
        pointer-events: none;
    }

    body[data-bs-theme="dark"] .playlist-scroll-area::-webkit-scrollbar-thumb,
    body.dark-mode .playlist-scroll-area::-webkit-scrollbar-thumb {
        background: #475569;
    }

    body[data-bs-theme="dark"] .playlist-lesson-item,
    body.dark-mode .playlist-lesson-item {
        color: #e2e8f0;
    }

    body[data-bs-theme="dark"] .playlist-lesson-item:hover,
    body.dark-mode .playlist-lesson-item:hover {
        background: rgba(30, 64, 175, .16);
        color: #f8fafc;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .20);
    }

    body[data-bs-theme="dark"] .playlist-lesson-item.is-active,
    body.dark-mode .playlist-lesson-item.is-active {
        background: rgba(37, 99, 235, .18);
        border-color: #60a5fa;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .22);
    }

    body[data-bs-theme="dark"] .lesson-title,
    body.dark-mode .lesson-title {
        color: #e2e8f0;
    }

    body[data-bs-theme="dark"] .lesson-meta,
    body.dark-mode .lesson-meta {
        color: #94a3b8;
    }

    body[data-bs-theme="dark"] .lesson-progress,
    body.dark-mode .lesson-progress {
        background: #334155;
    }

    body[data-bs-theme="dark"] .lesson-assessment-button.disabled,
    body[data-bs-theme="dark"] .lesson-assessment-button[aria-disabled="true"],
    body.dark-mode .lesson-assessment-button.disabled,
    body.dark-mode .lesson-assessment-button[aria-disabled="true"] {
        border-color: #475569;
        background: #1e293b;
        color: #94a3b8;
    }

    body[data-bs-theme="dark"] .lesson-thumbnail-fallback,
    body.dark-mode .lesson-thumbnail-fallback {
        background: rgba(37, 99, 235, .24);
        color: #60a5fa;
    }

    body[data-bs-theme="dark"] .lesson-status--not-started,
    body.dark-mode .lesson-status--not-started {
        color: #94a3b8;
    }

    body[data-bs-theme="dark"] .lesson-status--in-progress,
    body.dark-mode .lesson-status--in-progress {
        color: #60a5fa;
    }

    body[data-bs-theme="dark"] .lesson-status--completed,
    body[data-bs-theme="dark"] .lesson-done-icon,
    body.dark-mode .lesson-status--completed,
    body.dark-mode .lesson-done-icon {
        color: #4ade80;
    }

    @media (max-width: 1199px) {
        .playlist-lesson-main {
            gap: 10px;
        }

        .lesson-type-icon {
            width: 40px;
            height: 40px;
            border-radius: 11px;
        }

        .lesson-thumbnail-fallback i {
            font-size: 21px;
        }

        .lesson-assessment-action {
            margin-left: 50px;
        }
    }

    @media (max-width: 576px) {
        .playlist-scroll-area {
            padding-right: 2px;
        }

        .playlist-lesson-item {
            padding: 8px;
        }

        .lesson-status-icon {
            width: 20px;
            height: 20px;
            font-size: 17px;
        }
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
        .view-hero-content {
            flex-direction: column;
            align-items: stretch;
            padding: 24px;
        }

        .view-hero-title {
            font-size: 1.35rem;
        }

        .view-hero-continue {
            max-width: none;
            width: 100%;
            min-width: 0;
        }

        .sticky-top {
            position: static !important;
        }

        .view-ai-card,
        .ai-card {
            min-height: auto;
        }
    }

    /* ===== Playlist Learning Layout (3-column grid) ===== */

    .playlist-learning-shell {
        position: relative;
    }

    .playlist-learning-layout {
        display: grid;
        grid-template-columns: 280px minmax(0, 1fr) 300px;
        gap: 16px;
        align-items: start;
        transition: grid-template-columns .3s ease;
    }

    .learning-left-panel,
    .learning-right-panel {
        min-width: 0;
        align-self: stretch;
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
        background: #05507D;
        border-color: #05507D;
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
        background: #05507D;
        border-color: #05507D;
        color: #ffffff;
    }

    /* ---- Tombol restore (muncul saat panel di-collapse) ---- */
    .restore-toggle-btn {
        display: none;
        position: absolute;
        z-index: 1001;
        top: 16px;
        width: fit-content;
        min-width: max-content;
        min-height: 32px;
        border-radius: 10px;
        border: 1px solid #d1d5db;
        background: #f3f4f6;
        color: #6b7280;
        box-shadow: 0 2px 10px rgba(15, 23, 42, .12);
        cursor: pointer;
        align-items: center;
        justify-content: center;
        gap: 6px;
        font-size: 13px;
        line-height: 1.2;
        padding: 7px 10px;
        white-space: nowrap;
        overflow: visible;
        transition: background .2s ease, border-color .2s ease, color .2s ease;
    }

    .restore-toggle-btn i,
    .restore-toggle-btn span {
        flex: 0 0 auto;
    }

    .restore-toggle-btn:hover {
        background: #05507D;
        border-color: #05507D;
        color: #ffffff;
        box-shadow: 0 4px 14px rgba(37, 99, 235, .30);
    }

    .restore-left  { left: 8px; }
    .restore-right { right: 8px; }

    /* Tampilkan restore button hanya saat panel collapsed */
    .playlist-learning-shell:has(.playlist-learning-layout.left-collapsed)  > .restore-left  { display: flex; }
    .playlist-learning-shell:has(.playlist-learning-layout.right-collapsed) > .restore-right { display: flex; }

    body[data-bs-theme="dark"] .restore-toggle-btn,
    body.dark-mode .restore-toggle-btn {
        background: #1e293b;
        border-color: #334155;
        color: #94a3b8;
        box-shadow: 0 2px 8px rgba(0, 0, 0, .30);
    }

    body[data-bs-theme="dark"] .restore-toggle-btn:hover,
    body.dark-mode .restore-toggle-btn:hover {
        background: #05507D;
        border-color: #05507D;
        color: #ffffff;
    }

    @media (max-width: 991px) {
        .playlist-learning-shell {
            position: static;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            column-gap: 8px;
            row-gap: 12px;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow: visible;
        }

        .playlist-learning-layout {
            position: static;
            flex: 0 0 100%;
            display: flex;
            flex-direction: column;
            gap: 0;
            align-items: stretch;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow: visible;
        }

        .learning-left-panel,
        .learning-right-panel {
            display: none;
            position: static;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow: visible;
            opacity: 1 !important;
            pointer-events: auto !important;
            transform: none;
        }

        .learning-main-panel {
            display: block;
            position: static;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            overflow: visible;
            opacity: 1 !important;
            pointer-events: auto !important;
            transform: none;
        }

        .view-playlist-card,
        .view-ai-card,
        .ai-card {
            position: static !important;
            top: auto !important;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            max-height: none !important;
            transform: none;
        }

        .view-playlist-card .playlist-scroll-area {
            max-height: 60vh;
        }

        .learning-main-panel {
            order: 1;
            margin-bottom: 16px;
        }

        .learning-left-panel {
            order: 2;
            margin-bottom: 24px;
        }

        .learning-right-panel {
            order: 3;
            margin-top: 0;
            margin-bottom: 0;
        }

        .view-player-card {
            order: 1;
        }

        .view-content-info-card {
            order: 2;
        }

        .view-navigation-card {
            order: 3;
        }

        .playlist-learning-layout.mobile-left-open .learning-left-panel,
        .playlist-learning-layout.mobile-right-open .learning-right-panel {
            display: block;
        }

        .restore-toggle-btn {
            display: inline-flex !important;
            position: static !important;
            flex: 0 1 auto;
            z-index: auto;
            top: auto;
            right: auto;
            left: auto;
            width: auto;
            max-width: calc(50% - 4px);
            min-width: 0;
            min-height: 32px;
            gap: 6px;
            padding: 7px 10px;
            white-space: nowrap;
            overflow: visible;
            opacity: 1;
            visibility: visible;
        }

        .restore-left {
            margin-right: auto;
        }

        .restore-right {
            margin-left: auto;
        }

        .restore-toggle-btn[aria-expanded="true"] {
            background: #05507D;
            border-color: #05507D;
            color: #ffffff;
        }
    }

    body[data-bs-theme="dark"] .chapter-description-box,
    body.dark-mode .chapter-description-box {
        background: #111827;
        border-color: #334155;
    }

    body[data-bs-theme="dark"] .view-hero-bg,
    body.dark-mode .view-hero-bg {
        background:
            radial-gradient(circle at 82% 18%, rgba(96, 165, 250, .14), transparent 30%),
            linear-gradient(90deg, rgba(3, 10, 24, .98) 0%, rgba(11, 31, 68, .92) 55%, rgba(20, 74, 122, .78) 100%);
    }

    body[data-bs-theme="dark"] .overview-meta-button:hover,
    body.dark-mode .overview-meta-button:hover {
        color: #93c5fd;
    }

    body[data-bs-theme="dark"] .playlist-comment-modal .modal-content,
    body.dark-mode .playlist-comment-modal .modal-content {
        background: #162235;
        color: #e5e7eb;
    }

    body[data-bs-theme="dark"] .playlist-comment-modal .modal-header,
    body.dark-mode .playlist-comment-modal .modal-header {
        border-color: rgba(148, 163, 184, .18);
    }

    /* ============================================================
       PLAYLIST DETAIL / VIEW DARK MODE SYSTEM
       ============================================================ */

    /* 1. Page Background & Back Link */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .learning-view-page {
        background: #0f172a !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .learning-view-page a[href*="playlist"].text-primary {
        color: #60a5fa !important;
        transition: color 0.15s ease;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .learning-view-page a[href*="playlist"].text-primary:hover {
        color: #93c5fd !important;
    }

    /* 1.1 Classification Badge - Basic & Common Duties (Dark Mode Only) */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-hero-bottom .classification-badge-basic-common-duties,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-hero-bottom [data-classification="Basic & Common Duties" i],
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-hero-card .classification-badge-basic-common-duties,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-hero-card [data-classification="Basic & Common Duties" i] {
        background: rgba(34, 197, 94, 0.14) !important;
        color: #4ade80 !important;
        border: 1px solid rgba(34, 197, 94, 0.30) !important;
    }

    /* 2. Main Containers & Cards */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-playlist-card,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-player-card,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-navigation-card,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-content-info-card,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-ai-card,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .ai-card {
        background: #152235 !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: #f1f5f9 !important;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.25) !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-playlist-card .card-body,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-player-card .card-body,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-navigation-card .card-body,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-content-info-card .card-body,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-ai-card .card-body {
        background: transparent !important;
        color: #f1f5f9 !important;
    }

    /* 3. Content List Sidebar */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-playlist-card h3 {
        color: #f8fafc !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-playlist-card .text-body {
        color: #f1f5f9 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-playlist-card .text-muted {
        color: #94a3b8 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-playlist-card .progress {
        background: #334155 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-playlist-card .progress-bar {
        background: #0c5b9e !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #playlistSearchInput {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #f1f5f9 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #playlistSearchInput::placeholder {
        color: #64748b !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #playlistSearchBox .input-icon-addon {
        color: #94a3b8 !important;
    }

    /* CRITICAL: Sidebar lesson items & text-dark override */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-lesson-item {
        color: #e2e8f0 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-lesson-item .content-title-two-lines,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-lesson-item .content-title-two-lines.text-dark {
        color: #f1f5f9 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-lesson-item .content-title-two-lines.text-secondary {
        color: #cbd5e1 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-lesson-item .lesson-title span.fw-bold {
        color: #94a3b8 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-lesson-item:hover {
        background: rgba(30, 64, 175, .16) !important;
        color: #f8fafc !important;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .20) !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-lesson-item.is-active {
        background: rgba(37, 99, 235, .22) !important;
        border-color: #60a5fa !important;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .28) !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-lesson-item.is-active .content-title-two-lines {
        color: #ffffff !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-lesson-item.is-active .lesson-title span.fw-bold {
        color: #93c5fd !important;
    }

    /* Inactive lesson item override (removes bg-light white clash) */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-lesson-item.is-inactive,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-lesson-item.is-inactive.bg-light {
        background: rgba(255, 255, 255, 0.03) !important;
        border: 1px solid rgba(255, 255, 255, 0.05) !important;
        opacity: 0.65 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-lesson-item .lesson-meta {
        color: #94a3b8 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-lesson-item .lesson-progress {
        background: #334155 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-lesson-item .lesson-progress .progress-bar {
        background: #0c5b9e !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .lesson-assessment-button.btn-outline-primary {
        border-color: #60a5fa !important;
        color: #93c5fd !important;
        background: rgba(96, 165, 250, 0.08) !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .lesson-assessment-button.btn-outline-primary:hover {
        background: #0c5b9e !important;
        border-color: #0c5b9e !important;
        color: #ffffff !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .lesson-assessment-button.disabled,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .lesson-assessment-button[aria-disabled="true"],
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .lesson-assessment-button:disabled {
        background: #1e293b !important;
        border-color: #334155 !important;
        color: #94a3b8 !important;
        opacity: 1 !important;
    }

    /* 4. PDF Viewer UI Wrapper & Controls */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .owl-pdf-toolbar,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-pdf-toolbar {
        background: #152235 !important;
        border-bottom: 1px solid #334155 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .owl-pdf-toolbar .pdf-tb-btn,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-pdf-toolbar .pdf-tb-btn {
        background: rgba(255, 255, 255, 0.06) !important;
        border-color: rgba(148, 163, 184, 0.25) !important;
        color: #e2e8f0 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .owl-pdf-toolbar .pdf-tb-btn:hover,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-pdf-toolbar .pdf-tb-btn:hover {
        background: #0c5b9e !important;
        border-color: #0c5b9e !important;
        color: #ffffff !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .owl-pdf-toolbar .pdf-tb-btn:disabled,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-pdf-toolbar .pdf-tb-btn:disabled {
        background: rgba(255, 255, 255, 0.02) !important;
        border-color: rgba(255, 255, 255, 0.06) !important;
        color: #64748b !important;
        opacity: 1 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .owl-pdf-toolbar .pdf-tb-zoomval {
        background: #1e293b !important;
        border: 1px solid #334155 !important;
        color: #cbd5e1 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .owl-pdf-toolbar .pdf-tb-sep {
        background: rgba(148, 163, 184, 0.25) !important;
    }

    /* PDF Bottom Navigation Bar */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation {
        background: #152235 !important;
        border-top: 1px solid #334155 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation .pdf-tb-stat-page {
        background: rgba(34, 197, 94, 0.15) !important;
        border: 1px solid rgba(34, 197, 94, 0.35) !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation .pdf-tb-stat-page .pdf-tb-stat-label {
        color: #86efac !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation .pdf-tb-stat-page strong,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation .pdf-tb-stat-page .pdf-tb-pageno {
        color: #4ade80 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation .pdf-tb-stat-self-paced {
        background: rgba(59, 130, 246, 0.15) !important;
        border: 1px solid rgba(59, 130, 246, 0.35) !important;
        color: #93c5fd !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation .pdf-tb-stat-self-paced .pdf-tb-stat-label {
        color: #93c5fd !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation .pdf-tb-stat-self-paced strong {
        color: #60a5fa !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation .pdf-tb-stat-self-paced i {
        color: #60a5fa !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation .pdf-tb-stat-minimum,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation .pdf-tb-stat-countdown {
        background: rgba(148, 163, 184, 0.12) !important;
        border: 1px solid rgba(148, 163, 184, 0.25) !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation .pdf-tb-stat-minimum .pdf-tb-stat-label,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation .pdf-tb-stat-countdown .pdf-tb-stat-label {
        color: #cbd5e1 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation .pdf-tb-stat-minimum strong,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation .pdf-tb-stat-countdown strong {
        color: #f1f5f9 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation-btn {
        background: #0c5b9e !important;
        border-color: #0c5b9e !important;
        color: #ffffff !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation-btn:hover {
        background: #09477d !important;
        border-color: #09477d !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation-btn:disabled,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .pdf-page-navigation-btn.disabled {
        background: #1e293b !important;
        border: 1px solid #334155 !important;
        color: #64748b !important;
        opacity: 1 !important;
    }

    /* 5. Action Buttons (view-navigation-card) */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-navigation-card .btn:disabled,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-navigation-card .btn.disabled,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-navigation-card .certificate-action-button:disabled,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-navigation-card .certificate-action-wrapper.is-disabled .btn,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-navigation-card .btn-outline-secondary:disabled {
        background: #1e293b !important;
        border: 1px solid #334155 !important;
        color: #94a3b8 !important;
        opacity: 1 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-navigation-card .btn:disabled i,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-navigation-card .btn-outline-secondary:disabled i,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-navigation-card .certificate-action-button:disabled i {
        color: #94a3b8 !important;
    }

    /* 6. Content Information & Share */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-content-info-card h2 {
        color: #f8fafc !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-content-info-card .text-muted {
        color: #94a3b8 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .overview-inline-meta {
        color: #94a3b8 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .overview-inline-meta .overview-meta-button {
        color: #94a3b8 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .overview-inline-meta .overview-meta-button:hover {
        color: #60a5fa !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .overview-inline-meta .overview-meta-separator {
        color: #64748b !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-content-info-card .btn-outline-secondary {
        border-color: rgba(148, 163, 184, 0.25) !important;
        color: #94a3b8 !important;
        background: transparent !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-content-info-card .btn-outline-secondary:hover {
        border-color: #60a5fa !important;
        color: #ffffff !important;
        background: rgba(59, 130, 246, 0.15) !important;
    }

    /* 7. Overview Card */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .chapter-description-box {
        background: #111827 !important;
        border: 1px solid #334155 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .chapter-description-box h4 {
        color: #f8fafc !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .chapter-description-box .overview-text,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #playlistOverviewText {
        color: #cbd5e1 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .chapter-description-box .overview-toggle,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .chapter-description-box a.btn-link,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .chapter-description-box button.btn-link {
        color: #60a5fa !important;
        text-decoration: none;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .chapter-description-box .overview-toggle:hover,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .chapter-description-box a.btn-link:hover,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .chapter-description-box button.btn-link:hover {
        color: #93c5fd !important;
        text-decoration: underline;
    }

    /* 8. Completion Requirements */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #playlistCompletionRequirementsTitle {
        color: #f8fafc !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) section[aria-labelledby="playlistCompletionRequirementsTitle"] .border.rounded-3 {
        background: #111827 !important;
        border-color: #334155 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) section[aria-labelledby="playlistCompletionRequirementsTitle"] .small.fw-semibold {
        color: #e2e8f0 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) section[aria-labelledby="playlistCompletionRequirementsTitle"] .badge.bg-blue-lt {
        background: rgba(59, 130, 246, 0.15) !important;
        color: #93c5fd !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) section[aria-labelledby="playlistCompletionRequirementsTitle"] .badge.bg-success-lt {
        background: rgba(34, 197, 94, 0.15) !important;
        color: #86efac !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) section[aria-labelledby="playlistCompletionRequirementsTitle"] .badge.bg-danger-lt {
        background: rgba(239, 68, 68, 0.15) !important;
        color: #fca5a5 !important;
    }

    /* 9. AI Assistant Panel */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-ai-card .border-bottom,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-ai-card .border-top {
        border-color: #334155 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-ai-card .fw-bold {
        color: #f8fafc !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-ai-card .text-muted {
        color: #94a3b8 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .ai-message-box {
        background: rgba(15, 23, 42, 0.75) !important;
        border: 1px solid rgba(148, 163, 184, 0.16) !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .ai-message-box .text-dark,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .ai-message-box span.fw-semibold {
        color: #f8fafc !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .ai-message-box .text-secondary {
        color: #cbd5e1 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .ai-suggestion-btn,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-ai-card .btn-light.border.ai-suggestion-btn {
        background: rgba(255, 255, 255, 0.04) !important;
        border: 1px solid rgba(148, 163, 184, 0.22) !important;
        color: #cbd5e1 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .ai-suggestion-btn i {
        color: #60a5fa !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .ai-suggestion-btn:hover,
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .view-ai-card .btn-light.border.ai-suggestion-btn:hover {
        background: #05507d !important;
        border-color: #05507d !important;
        color: #ffffff !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .ai-suggestion-btn:hover i {
        color: #ffffff !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #aiChatInput {
        background: #111827 !important;
        border-color: rgba(148, 163, 184, 0.25) !important;
        color: #f8fafc !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) #aiChatInput::placeholder {
        color: #64748b !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .ai-input-group .btn-primary {
        background: #0c5b9e !important;
        border-color: #0c5b9e !important;
        color: #ffffff !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .ai-input-group .btn-primary:hover {
        background: #09477d !important;
        border-color: #09477d !important;
    }

    /* 10. Scrollbar */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-scroll-area {
        scrollbar-color: #475569 #152235;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-scroll-area::-webkit-scrollbar-track {
        background: #152235;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-scroll-area::-webkit-scrollbar-thumb {
        background: #475569;
        border-radius: 999px;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-scroll-area::-webkit-scrollbar-thumb:hover {
        background: #64748b;
    }

    /* 11. Panel Toggles & Modals */
    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .panel-header-toggle {
        background: #192d45 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: #cbd5e1 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .panel-header-toggle:hover {
        background: #203a57 !important;
        border-color: rgba(96, 165, 250, 0.4) !important;
        color: #f1f5f9 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .restore-toggle-btn {
        background: #192d45 !important;
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        color: #cbd5e1 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .restore-toggle-btn:hover {
        background: #203a57 !important;
        border-color: rgba(96, 165, 250, 0.4) !important;
        color: #f1f5f9 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-comment-modal .modal-content {
        background: #152235 !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        color: #f1f5f9 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-comment-modal .modal-header {
        background: #152235 !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-comment-modal .modal-title {
        color: #f1f5f9 !important;
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-comment-modal .btn-close {
        filter: invert(1) grayscale(100%) brightness(200%);
    }

    :is([data-bs-theme="dark"], [data-theme="dark"], body.theme-dark, body.dark-mode, body.dark) .playlist-comment-modal .modal-body {
        background: #152235 !important;
        color: #e2e8f0 !important;
    }
</style>

