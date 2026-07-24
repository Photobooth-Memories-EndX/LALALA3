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

    .view-hero-bg {
        min-height: 230px;
        background:
            radial-gradient(circle at 82% 18%, rgba(255, 255, 255, .16), transparent 30%),
            linear-gradient(90deg, rgba(7, 23, 57, .96) 0%, rgba(18, 72, 167, .9) 50%, rgba(42, 114, 202, .76) 100%);
        background-size: cover;
        background-position: center;
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
    }

    .view-hero-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 7px 12px;
        border-radius: 999px;
        color: #ffffff;
        background: rgba(255, 255, 255, .15);
        border: 1px solid rgba(255, 255, 255, .18);
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
        padding: 12px;
        border-radius: 14px;
        color: #fff;
        text-decoration: none;
        background: rgba(15, 23, 42, .28);
        border: 1px solid rgba(255, 255, 255, .18);
        backdrop-filter: blur(10px);
        text-align: center;
    }

    .view-hero-continue:hover {
        color: #fff;
        background: rgba(37, 99, 235, .85);
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
            overflow: visible !important;
            transform: none;
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
</style>
