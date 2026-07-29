<style>

:root {
    --kp-primary: #05507D;
    --kp-primary-rgb: 5, 80, 125;
    --kp-primary-dark: #05507D;
    --kp-primary-deep: #05507D;
    --kp-light-blue: #6AAED6;
    --kp-light-blue-rgb: 106, 174, 214;
    --kp-light-blue-soft: #D9D9D6;
    --kp-light-blue-muted: #D9D9D6;
    --kp-light-gray: #D9D9D6;
    --kp-gray: #BBBCBC;
    --kp-dark-green: #05507D;
    --kp-golden-orange: #FFB81C;
    --kp-golden-orange-hover: #85754E;
    --kp-dark-page: #53565A;
    --kp-dark-surface: #53565A;
    --kp-dark-surface-raised: #53565A;
}

/* =====================================================
   HOME MODERN — prefix: .home-modern-*
   Scoped to homepage only. Do not share globally.
===================================================== */

/* Page background */
.page-body {
    background: #fff;
    min-height: 100vh;
}

[data-bs-theme="dark"] .page-body,
body.dark-mode .page-body,
body.theme-dark .page-body {
    background: var(--tblr-body-bg, #111827);
}

.container-fluid {
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
}

a { text-decoration: none; }

.home-modern-text-primary {
    color: var(--kp-primary) !important;
}

.page-body .bg-primary-lt {
    background: var(--kp-light-blue-soft) !important;
    color: var(--kp-primary) !important;
}

.page-body .btn-primary {
    border-color: var(--kp-primary);
    background: var(--kp-primary);
    color: #fff;
}

.page-body .btn-primary:hover,
.page-body .btn-primary:focus {
    border-color: var(--kp-light-blue);
    background: var(--kp-light-blue);
    color: var(--kp-primary-deep);
}

.page-body .btn-outline-primary {
    border-color: var(--kp-primary);
    color: var(--kp-primary);
}

.page-body .btn-outline-primary:hover,
.page-body .btn-outline-primary:focus {
    border-color: var(--kp-primary);
    background: var(--kp-primary);
    color: #fff;
}

.page-body .progress-bar {
    background-color: var(--kp-primary);
}

[data-bs-theme="dark"] .home-modern-text-primary,
body.dark-mode .home-modern-text-primary {
    color: var(--kp-light-blue) !important;
}

/* =====================================================
   TRAINING PLAN CARD — sidebar (right column)
===================================================== */

/* =====================================================
   TRAINING PLAN CARD
===================================================== */

.home-modern-trainingplan-card-body {
    padding: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.home-modern-btn-training-plan {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;

    width: 100%;
    max-width: 100%;

    padding: 14px 20px;

    border-radius: 12px;

    background: var(--kp-primary);
    color: #fff;

    font-size: 15px;
    font-weight: 600;
    text-decoration: none;

    box-shadow: 0 4px 14px rgba(var(--kp-primary-rgb),.20);

    transition: all .25s ease;
}

.home-modern-btn-training-plan i {
    font-size: 20px;
    flex-shrink: 0;
}

.home-modern-btn-training-plan span {
    white-space: nowrap;
}

.home-modern-btn-training-plan:hover {
    background: var(--kp-light-blue);
    color: var(--kp-primary-deep);
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(var(--kp-primary-rgb),.30);
}

.home-modern-btn-training-plan:active {
    transform: translateY(0);
}

[data-bs-theme="dark"] .home-modern-btn-training-plan {
    box-shadow: 0 4px 14px rgba(var(--kp-primary-rgb),.15);
}

[data-bs-theme="dark"] .home-modern-btn-training-plan:hover {
    box-shadow: 0 8px 22px rgba(var(--kp-primary-rgb),.25);
}

/* =====================================================
   STAT CARDS
===================================================== */

.home-modern-stat {
    background: #fff;
    border: 1px solid rgba(var(--kp-primary-rgb), .08);
    border-radius: 18px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
    transition: transform .2s, box-shadow .2s;
    height: 100%;
}

.home-modern-stat:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,.09);
}

.home-modern-stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.home-modern-stat-blue .home-modern-stat-icon {
    background: var(--kp-light-blue-soft);
    color: var(--kp-primary);
}

.home-modern-stat-green .home-modern-stat-icon {
    background: rgba(25, 135, 84, .12);
    color: #198754;
}

.home-modern-stat-green .home-modern-stat-number,
.home-modern-stat-green .home-modern-stat-label {
    color: #198754;
}

.home-modern-stat-red .home-modern-stat-icon {
    background: var(--kp-light-blue-soft);
    color: var(--kp-primary-dark);
}

.home-modern-stat-number {
    font-size: 30px;
    font-weight: 700;
    color: #53565A;
    line-height: 1;
    margin-bottom: 4px;
}

.home-modern-stat-label {
    font-size: 13px;
    color: #8A8D8F;
    font-weight: 500;
}

/* =====================================================
   QUICK ACTIONS
===================================================== */

.home-modern-quick-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    background: #fff;
    border: 1px solid rgba(var(--kp-primary-rgb), .08);
    border-radius: 16px;
    padding: 16px 10px;
    text-decoration: none;
    color: #53565A;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
    transition: all .2s;
    height: 100%;
}

.home-modern-quick-btn:hover {
    color: var(--kp-primary);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(var(--kp-primary-rgb),.12);
}

.home-modern-quick-btn i {
    font-size: 22px;
    color: var(--kp-primary);
}

/* =====================================================
   CARD BASE
===================================================== */

.home-modern-card {
    background: #fff;
    border: 1px solid rgba(var(--kp-primary-rgb), .08);
    border-radius: 20px;
    box-shadow: 0 2px 12px rgba(0,0,0,.05);
    overflow: hidden;
}

.home-modern-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px 14px;
}

.home-modern-card-body {
    padding: 0 24px 20px;
}

.home-modern-card-body.pt-0 {
    padding-top: 0;
}

.home-modern-section-title {
    font-size: 17px;
    font-weight: 700;
    color: #53565A;
    margin: 0;
}

.home-modern-view-all {
    font-size: 13px;
    font-weight: 600;
    color: var(--kp-primary);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 2px;
}

.home-modern-view-all:hover {
    color: var(--kp-primary-dark);
}

/* =====================================================
   EMPTY STATE
===================================================== */

.home-modern-empty {
    text-align: center;
    padding: 32px 20px;
    color: #94a3b8;
}

.home-modern-empty i {
    font-size: 38px;
    margin-bottom: 10px;
    display: block;
}

.home-modern-empty p {
    margin: 0;
    font-size: 14px;
}

/* =====================================================
   CONTINUE LEARNING
===================================================== */

.home-modern-continue-wrap {
    display: flex;
    gap: 20px;
    align-items: flex-start;
}

.home-modern-continue-thumb {
    width: 210px;
    flex-shrink: 0;
    border-radius: 14px;
    overflow: hidden;
    position: relative;
}

.home-modern-continue-thumb img {
    width: 100%;
    height: 125px;
    object-fit: cover;
    display: block;
}

.home-modern-continue-play {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,0,0,.25);
    color: #fff;
    font-size: 28px;
    opacity: 0;
    transition: opacity .2s;
}

.home-modern-continue-thumb:hover .home-modern-continue-play {
    opacity: 1;
}

.home-modern-continue-info {
    flex: 1;
    min-width: 0;
}

.home-modern-continue-title {
    font-size: 16px;
    font-weight: 700;
    color: #53565A;
    margin-bottom: 6px;
    line-height: 1.4;
    overflow-wrap: break-word;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.home-modern-continue-period {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    margin-bottom: 10px;
}

.home-modern-progress-wrap small {
    font-size: 12px;
    color: #8A8D8F;
}

.home-modern-progress {
    height: 8px;
    border-radius: 999px;
    background: #e2e8f0;
    overflow: hidden;
}

.home-modern-progress-bar {
    height: 100%;
    border-radius: 999px;
    background: var(--kp-primary);
    transition: width .3s;
}

.home-modern-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: var(--kp-primary);
    color: #fff;
    padding: 8px 18px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: background .2s, transform .15s;
}

.home-modern-btn-primary:hover {
    background: var(--kp-primary-dark);
    color: #fff;
    transform: translateY(-1px);
}

.home-modern-btn-outline {
    display: inline-flex;
    align-items: center;
    border: 1.5px solid var(--kp-primary);
    color: var(--kp-primary);
    padding: 7px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all .2s;
}

.home-modern-btn-outline:hover {
    background: var(--kp-primary);
    color: #fff;
}

/* =====================================================
   VIDEO CARDS
===================================================== */

.home-modern-video-card {
    display: block;
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
    transition: transform .2s, box-shadow .2s;
    height: 100%;
    border: 1px solid #e2e8f0;
}

.home-modern-video-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(0,0,0,.1);
    color: inherit;
}

.home-video-card {
    display: flex;
    flex-direction: column;
}

.home-modern-video-thumb {
    position: relative;
    overflow: hidden;
}

.home-video-thumbnail {
    aspect-ratio: 16 / 9;
}

.home-modern-video-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform .3s;
}

.home-modern-video-card:hover .home-modern-video-thumb img {
    transform: scale(1.04);
}

.home-modern-video-play {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255,255,255,.95);
    color: var(--kp-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    box-shadow: 0 4px 16px rgba(0,0,0,.15);
}

.home-modern-video-body {
    padding: 14px 16px 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    flex: 1;
}

.home-modern-category-badge {
    display: inline-block;
    background: var(--kp-light-blue-soft);
    color: var(--kp-primary);
    font-size: 10px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: .4px;
}

.home-modern-category-green {
    background: var(--kp-light-blue-soft);
    color: var(--kp-primary);
}

.home-modern-video-title {
    font-size: 14px;
    font-weight: 600;
    color: #53565A;
    line-height: 1.45;
    margin-bottom: 8px;
}

.home-modern-video-meta {
    font-size: 12px;
    color: #8A8D8F;
    display: flex;
    align-items: center;
}

/* =====================================================
   DOCUMENT THUMBNAILS — 16:9 Landscape
===================================================== */

.home-modern-document-thumb {
    aspect-ratio: 16 / 9;
    background: #f8fafc;
}

.home-modern-document-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform .3s;
}

/* =====================================================
   TRAINING MODULES
===================================================== */

.home-modern-module-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 0;
    border-bottom: 1px solid #f1f5f9;
    text-decoration: none;
    color: #53565A;
    transition: color .15s;
}

.home-modern-module-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.home-modern-module-row:hover {
    color: var(--kp-primary);
}

.home-modern-module-row:hover .home-modern-module-arrow {
    transform: translateX(4px);
}

.home-modern-module-left {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    flex: 1;
}

.home-modern-module-text {
    min-width: 0;
}

.home-modern-module-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: var(--kp-light-blue-soft);
    color: var(--kp-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}

.home-modern-module-title {
    font-size: 14px;
    font-weight: 600;
    color: inherit;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 340px;
}

.home-modern-module-sub {
    font-size: 12px;
    color: #94a3b8;
    margin-top: 2px;
}

.home-modern-module-arrow {
    color: #cbd5e1;
    font-size: 15px;
    flex-shrink: 0;
    margin-left: 8px;
    transition: transform .15s;
}

/* Playlist tab — horizontal thumbnail cards (mirrors the Document tab) */
.home-modern-playlist-hitem {
    flex: 0 0 200px;
}

.home-modern-playlist-hcard {
    border: 1px solid #eef2f7;
    box-shadow: 0 2px 10px rgba(0,0,0,.04);
    transition: transform .2s, box-shadow .2s, border-color .2s;
}

.home-modern-playlist-hcard:hover {
    border-color: #b8daf0;
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(15,23,42,.12);
}

.home-modern-playlist-hcard .home-modern-video-thumb img {
    height: 120px;
}

.home-modern-playlist-hthumb-fallback {
    width: 100%;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--kp-light-blue-soft);
    color: var(--kp-primary);
    font-size: 38px;
}

.home-modern-playlist-htitle {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    min-height: 2.9em; /* reserve 2 lines so card heights stay even */
}

.home-modern-category-purple {
    background: var(--kp-light-blue-soft);
    color: var(--kp-primary);
}

@media (max-width: 991.98px) {
    .home-modern-playlist-hitem { flex-basis: 180px; }
    .home-modern-playlist-hcard .home-modern-video-thumb img,
    .home-modern-playlist-hthumb-fallback { height: 110px; }
}

@media (max-width: 767.98px) {
    .home-modern-playlist-hitem { flex-basis: 160px; }
    .home-modern-playlist-hcard .home-modern-video-thumb img,
    .home-modern-playlist-hthumb-fallback { height: 100px; }
}

[data-bs-theme="dark"] .home-modern-playlist-hcard {
    border-color: rgba(255,255,255,.07);
}
[data-bs-theme="dark"] .home-modern-playlist-hcard:hover {
    border-color: rgba(var(--kp-light-blue-rgb),.4);
}
[data-bs-theme="dark"] .home-modern-category-purple {
    background: rgba(var(--kp-primary-rgb),.2);
    color: var(--kp-light-blue);
}

/* =====================================================
   UPCOMING DEADLINES
===================================================== */

.home-modern-deadline-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    padding: 11px 14px;
    border-radius: 14px;
    border: 1px solid #f1f5f9;
    background: #f8fafc;
    margin-bottom: 10px;
    text-decoration: none;
    color: #53565A;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0);
    transition: border-color .2s ease, background-color .2s ease, box-shadow .2s ease;
}

.home-modern-deadline-row:last-child {
    margin-bottom: 0;
}

.home-modern-deadline-row:hover {
    border-color: #b8daf0;
    background: var(--kp-light-blue-soft);
    color: #53565A;
    box-shadow: 0 6px 16px rgba(var(--kp-primary-rgb), .08);
}

.home-modern-deadline-left {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    flex: 1;
}

.home-modern-deadline-icon {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    flex-shrink: 0;
}

.home-modern-deadline-danger  { background: var(--kp-light-blue-soft); color: var(--kp-primary-dark); }
.home-modern-deadline-warning { background: var(--kp-light-blue-soft); color: var(--kp-primary); }
.home-modern-deadline-primary { background: var(--kp-light-blue-soft); color: var(--kp-primary); }

.home-modern-deadline-content {
    min-width: 0;
    flex: 1 1 auto;
}

.home-modern-deadline-title {
    display: block;
    width: 100%;
    max-width: 100%;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.3;
    color: #53565A;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.home-modern-deadline-meta {
    font-size: 12px;
    color: #8A8D8F;
    margin-top: 4px;
    display: flex;
    align-items: flex-start;
    flex-direction: column;
    gap: 7px;
}

.home-modern-deadline-date {
    display: inline-flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 5px;
    color: #8A8D8F;
    font-size: 12px;
    font-weight: 400;
    line-height: 1.25;
}

.home-modern-deadline-separator {
    color: var(--kp-gray);
}

.home-modern-deadline-relative {
    color: var(--kp-primary);
    font-weight: 600;
}

.home-modern-deadline-relative.is-overdue {
    color: var(--kp-golden-orange);
}

.home-modern-deadline-date i {
    flex: 0 0 auto;
    font-size: 13px;
    line-height: 1;
}

.home-modern-priority-badge {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 999px;
    line-height: 1.2;
    text-transform: uppercase;
    letter-spacing: .3px;
}

.home-modern-priority-danger  { background: var(--kp-light-blue-muted); color: var(--kp-primary-dark); }
.home-modern-priority-warning { background: var(--kp-light-blue-muted); color: var(--kp-primary); }
.home-modern-priority-primary { background: var(--kp-light-blue-muted); color: var(--kp-primary-dark); }

/* =====================================================
   NEWS CARDS
===================================================== */

.home-modern-news-card {
    display: block;
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
    transition: transform .2s, box-shadow .2s;
    height: 100%;
}

.home-modern-news-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(0,0,0,.1);
    color: inherit;
}

.home-modern-news-thumb,
.home-modern-gallery-thumb {
    overflow: hidden;
}

.home-modern-news-thumb img,
.home-modern-gallery-thumb img {
    width: 100%;
    height: 175px;
    object-fit: cover;
    display: block;
    transition: transform .3s;
}

.home-modern-news-card:hover .home-modern-news-thumb img,
.home-modern-gallery-card:hover .home-modern-gallery-thumb img {
    transform: scale(1.04);
}

.home-modern-news-body,
.home-modern-gallery-body {
    padding: 14px 15px;
}

.home-modern-news-title,
.home-modern-gallery-title {
    font-size: 14px;
    font-weight: 600;
    color: #53565A;
    line-height: 1.45;
    margin-bottom: 10px;
    min-height: 42px;
}

.home-modern-news-footer,
.home-modern-gallery-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12px;
    color: #8A8D8F;
}

.home-modern-readmore {
    color: var(--kp-primary);
    font-weight: 600;
    font-size: 12px;
}

/* =====================================================
   GALLERY CARDS
===================================================== */

.home-modern-gallery-card {
    display: block;
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    text-decoration: none;
    color: inherit;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
    transition: transform .2s, box-shadow .2s;
    height: 100%;
}

.home-modern-gallery-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(0,0,0,.1);
    color: inherit;
}

/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 991px) {
    .home-modern-continue-wrap {
        flex-direction: column;
    }
    .home-modern-continue-thumb {
        width: 100%;
    }
    .home-modern-continue-thumb img {
        height: 200px;
    }
}

@media (max-width: 767px) {
    .home-modern-module-title {
        max-width: 200px;
    }
    .home-modern-deadline-title {
        max-width: 200px;
    }
}

/* =====================================================
   DARK MODE
===================================================== */

[data-bs-theme="dark"] .page-body {
    background: var(--kp-dark-page);
}

/* Stat cards */
[data-bs-theme="dark"] .home-modern-stat {
    background: var(--kp-dark-surface);
    box-shadow: none;
    border: 1px solid rgba(var(--kp-light-blue-rgb), .12);
}

[data-bs-theme="dark"] .home-modern-stat-number { color: #f1f5f9; }
[data-bs-theme="dark"] .home-modern-stat-label  { color: #94a3b8; }

[data-bs-theme="dark"] .home-modern-stat-blue .home-modern-stat-icon {
    background: rgba(var(--kp-primary-rgb),.2); color: var(--kp-light-blue);
}
[data-bs-theme="dark"] .home-modern-stat-green .home-modern-stat-icon {
    background: rgba(25, 135, 84, .22); color: #75D69A;
}
[data-bs-theme="dark"] .home-modern-stat-green .home-modern-stat-number,
[data-bs-theme="dark"] .home-modern-stat-green .home-modern-stat-label {
    color: #75D69A;
}
[data-bs-theme="dark"] .home-modern-stat-red .home-modern-stat-icon {
    background: rgba(var(--kp-primary-rgb),.2); color: var(--kp-light-blue);
}

/* Quick actions */
[data-bs-theme="dark"] .home-modern-quick-btn {
    background: var(--kp-dark-surface);
    color: #f1f5f9;
    border: 1px solid rgba(var(--kp-light-blue-rgb), .12);
    box-shadow: none;
}
[data-bs-theme="dark"] .home-modern-quick-btn:hover {
    color: var(--kp-light-blue);
    border-color: rgba(var(--kp-light-blue-rgb),.3);
    box-shadow: 0 4px 16px rgba(var(--kp-primary-rgb),.15);
}
[data-bs-theme="dark"] .home-modern-quick-btn i { color: var(--kp-light-blue); }

/* Cards */
[data-bs-theme="dark"] .home-modern-card {
    background: var(--kp-dark-surface);
    border: 1px solid rgba(var(--kp-light-blue-rgb), .12);
    box-shadow: none;
}
[data-bs-theme="dark"] .home-modern-section-title { color: #f1f5f9; }
[data-bs-theme="dark"] .home-modern-card-header {
    border-bottom: 1px solid rgba(255,255,255,.06);
}

/* Continue learning */
[data-bs-theme="dark"] .home-modern-continue-title { color: #f1f5f9; }
[data-bs-theme="dark"] .home-modern-continue-period { color: #94a3b8; }
[data-bs-theme="dark"] .home-modern-progress       { background: #0c405f; }
[data-bs-theme="dark"] .home-modern-progress-wrap small { color: #94a3b8; }

/* Modules */
[data-bs-theme="dark"] .home-modern-module-row {
    color: #e2e8f0;
    border-bottom-color: rgba(255,255,255,.06);
}
[data-bs-theme="dark"] .home-modern-module-row:hover { color: var(--kp-light-blue); }
[data-bs-theme="dark"] .home-modern-module-icon {
    background: rgba(var(--kp-primary-rgb),.2); color: var(--kp-light-blue);
}
[data-bs-theme="dark"] .home-modern-module-sub  { color: #7894a5; }
[data-bs-theme="dark"] .home-modern-module-arrow { color: #376f90; }

/* Deadlines */
[data-bs-theme="dark"] .home-modern-deadline-row {
    background: var(--kp-dark-surface-raised);
    border-color: rgba(255,255,255,.07);
    color: #e2e8f0;
}
[data-bs-theme="dark"] .home-modern-deadline-row:hover {
    background: #0d4668;
    border-color: rgba(var(--kp-light-blue-rgb),.25);
    color: #e2e8f0;
}
[data-bs-theme="dark"] .home-modern-deadline-title { color: #f1f5f9; }
[data-bs-theme="dark"] .home-modern-deadline-meta  { color: #8A8D8F; }

[data-bs-theme="dark"] .home-modern-deadline-danger  { background: rgba(var(--kp-primary-rgb),.2);  color: var(--kp-light-blue); }
[data-bs-theme="dark"] .home-modern-deadline-warning { background: rgba(var(--kp-primary-rgb),.2);  color: var(--kp-light-blue); }
[data-bs-theme="dark"] .home-modern-deadline-primary { background: rgba(var(--kp-primary-rgb),.2);  color: var(--kp-light-blue); }

[data-bs-theme="dark"] .home-modern-priority-danger  { background: rgba(var(--kp-primary-rgb),.25);  color: var(--kp-light-blue); }
[data-bs-theme="dark"] .home-modern-priority-warning { background: rgba(var(--kp-primary-rgb),.25);  color: var(--kp-light-blue); }
[data-bs-theme="dark"] .home-modern-priority-primary { background: rgba(var(--kp-primary-rgb),.25);  color: var(--kp-light-blue); }

/* News & Gallery & Video cards */
[data-bs-theme="dark"] .home-modern-news-card,
[data-bs-theme="dark"] .home-modern-gallery-card,
[data-bs-theme="dark"] .home-modern-video-card {
    background: var(--kp-dark-surface-raised);
    border: 1px solid rgba(var(--kp-light-blue-rgb), .12);
    box-shadow: none;
}
[data-bs-theme="dark"] .home-modern-news-card:hover,
[data-bs-theme="dark"] .home-modern-gallery-card:hover,
[data-bs-theme="dark"] .home-modern-video-card:hover {
    box-shadow: 0 8px 24px rgba(0,0,0,.3);
}
[data-bs-theme="dark"] .home-modern-news-title,
[data-bs-theme="dark"] .home-modern-gallery-title,
[data-bs-theme="dark"] .home-modern-video-title { color: #f1f5f9; }

[data-bs-theme="dark"] .home-modern-news-footer,
[data-bs-theme="dark"] .home-modern-gallery-footer,
[data-bs-theme="dark"] .home-modern-video-meta { color: #7894a5; }

[data-bs-theme="dark"] .home-modern-video-play {
    background: rgba(255,255,255,.9);
    color: var(--kp-primary-dark);
}

/* Category badges */
[data-bs-theme="dark"] .home-modern-category-badge {
    background: rgba(var(--kp-primary-rgb),.2); color: var(--kp-light-blue);
}
[data-bs-theme="dark"] .home-modern-category-green {
    background: rgba(var(--kp-primary-rgb),.2); color: var(--kp-light-blue);
}

/* Empty state */
[data-bs-theme="dark"] .home-modern-empty { color: #376f90; }
[data-bs-theme="dark"] .home-modern-empty p { color: #7894a5; }

/* =====================================================
   HORIZONTAL SCROLL ROW
===================================================== */

.home-modern-hscroll {
    display: flex;
    gap: 14px;
    overflow-x: auto;
    padding-bottom: 6px;
    scrollbar-width: thin;
    scrollbar-color: #e2e8f0 transparent;
}

.home-modern-hscroll::-webkit-scrollbar { height: 4px; }
.home-modern-hscroll::-webkit-scrollbar-track { background: transparent; }
.home-modern-hscroll::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 999px;
}

.home-modern-hscroll-item {
    flex: 0 0 210px;
    min-width: 0;
}

.home-modern-duration-badge {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: rgba(0,0,0,.72);
    color: #fff;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 6px;
    letter-spacing: .2px;
}

[data-bs-theme="dark"] .home-modern-hscroll {
    scrollbar-color: #0c405f transparent;
}
[data-bs-theme="dark"] .home-modern-hscroll::-webkit-scrollbar-thumb {
    background: #0c405f;
}

/* =====================================================
   SLIDE CAROUSEL — responsive 3/2/1 sliding window
   (Top Video + Training Modules tabs)
===================================================== */

.home-modern-slide-carousel {
    position: relative;
}

.home-modern-slide-viewport {
    overflow: hidden;
}

.home-modern-slide-track {
    display: flex;
    transition: transform .38s cubic-bezier(.22, .61, .36, 1);
    will-change: transform;
}

.home-modern-slide-item {
    flex: 0 0 25%;
    max-width: 25%;
    min-width: 0;
    padding: 0 7px;
}

.home-modern-slide-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 3;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: rgba(15,23,42,.45);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 17px;
    cursor: pointer;
    opacity: .65;
    transition: opacity .2s, background .2s;
}

.home-modern-slide-carousel:hover .home-modern-slide-nav { opacity: 1; }
.home-modern-slide-nav:hover { background: rgba(var(--kp-primary-rgb),.85); color: #fff; }
.home-modern-slide-nav-prev { left: -6px; }
.home-modern-slide-nav-next { right: -6px; }
.home-modern-slide-nav.d-none { display: none; }

@media (max-width: 991.98px) {
    .home-modern-slide-item { flex-basis: 50%; max-width: 50%; }
}

@media (max-width: 767.98px) {
    .home-modern-slide-item { flex-basis: 100%; max-width: 100%; }
}

.deadline-status {
    background: var(--kp-light-blue-muted);
    color: var(--kp-primary-dark);
    border: 1px solid rgba(var(--kp-primary-rgb), .12);
}

.deadline-status.success {
    background: var(--kp-light-blue-muted);
    color: var(--kp-primary);
    border-color: rgba(var(--kp-primary-rgb), .18);
}

.deadline-status.pending {
    background: var(--kp-light-blue-muted);
    color: var(--kp-primary-dark);
    border-color: rgba(var(--kp-primary-rgb), .16);
}

.deadline-status.overdue {
    background: var(--kp-light-blue-muted);
    color: var(--kp-primary-dark);
    border-color: rgba(var(--kp-primary-rgb), .16);
}

[data-bs-theme="dark"] .home-modern-deadline-date {
    color: #94a3b8;
}

[data-bs-theme="dark"] .deadline-status.success {
    background: rgba(var(--kp-primary-rgb), .16);
    color: var(--kp-light-blue);
    border-color: rgba(var(--kp-light-blue-rgb), .16);
}

[data-bs-theme="dark"] .deadline-status.pending {
    background: rgba(var(--kp-light-blue-rgb), .16);
    color: var(--kp-light-blue);
    border-color: rgba(var(--kp-light-blue-rgb), .16);
}

[data-bs-theme="dark"] .deadline-status.overdue {
    background: rgba(var(--kp-primary-rgb), .16);
    color: var(--kp-light-blue);
    border-color: rgba(var(--kp-light-blue-rgb), .16);
}

[data-bs-theme="dark"] .home-modern-slide-nav {
    background: rgba(21,34,53,.85);
}
[data-bs-theme="dark"] .home-modern-slide-nav:hover {
    background: rgba(var(--kp-primary-rgb),.85);
}

/* =====================================================
   NAV TABS — Training Modules
===================================================== */

.home-modern-nav-tabs {
    display: flex;
    gap: 2px;
    border-bottom: none;
    padding: 0;
    margin-bottom: 0;
}

.home-modern-nav-tabs .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    border-radius: 0;
    color: #8A8D8F;
    font-size: 13px;
    font-weight: 600;
    padding: 10px 16px;
    transition: color .15s, border-color .15s;
    background: transparent;
}

.home-modern-nav-tabs .nav-link:hover {
    color: var(--kp-primary);
    border-bottom-color: #b8daf0;
}

.home-modern-nav-tabs .nav-link.active {
    color: var(--kp-primary);
    border-bottom-color: var(--kp-primary);
}

[data-bs-theme="dark"] .home-modern-nav-tabs .nav-link {
    color: #7894a5;
}
[data-bs-theme="dark"] .home-modern-nav-tabs .nav-link:hover {
    color: var(--kp-light-blue);
    border-bottom-color: rgba(var(--kp-light-blue-rgb),.35);
}
[data-bs-theme="dark"] .home-modern-nav-tabs .nav-link.active {
    color: var(--kp-light-blue);
    border-bottom-color: var(--kp-light-blue);
}

/* =====================================================
   GALLERY ROW — Sidebar list style
===================================================== */

.home-modern-gallery-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid #f1f5f9;
    text-decoration: none;
    color: #53565A;
    transition: color .15s;
}

.home-modern-gallery-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.home-modern-gallery-row:hover { color: var(--kp-primary); }

.home-modern-gallery-row-thumb {
    width: 68px;
    height: 48px;
    border-radius: 10px;
    overflow: hidden;
    flex-shrink: 0;
}

.home-modern-gallery-row-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.home-modern-gallery-row-title {
    font-size: 13px;
    font-weight: 600;
    line-height: 1.35;
    color: inherit;
}

.home-modern-gallery-row-date {
    font-size: 11px;
    color: #94a3b8;
    margin-top: 3px;
    display: flex;
    align-items: center;
}

[data-bs-theme="dark"] .home-modern-gallery-row {
    color: #e2e8f0;
    border-bottom-color: rgba(255,255,255,.06);
}
[data-bs-theme="dark"] .home-modern-gallery-row:hover { color: var(--kp-light-blue); }
[data-bs-theme="dark"] .home-modern-gallery-row-date { color: #7894a5; }

/* =====================================================
   GALLERY CAROUSEL — Sidebar auto-slide card
===================================================== */

.home-modern-gallery-carousel {
    position: relative;
    overflow: hidden;
}

/* Prev / next arrows over the thumbnail */
.home-modern-gallery-nav {
    position: absolute;
    top: 75px; /* vertical center of the 150px thumbnail */
    transform: translateY(-50%);
    z-index: 3;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: none;
    background: rgba(15,23,42,.45);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    cursor: pointer;
    opacity: .65;
    transition: opacity .2s, background .2s;
}

.home-modern-gallery-carousel:hover .home-modern-gallery-nav { opacity: 1; }
.home-modern-gallery-nav:hover { background: rgba(var(--kp-primary-rgb),.85); color: #fff; }
.home-modern-gallery-nav-prev { left: 10px; }
.home-modern-gallery-nav-next { right: 10px; }

.home-modern-gallery-slide {
    display: block;
    text-decoration: none;
    color: #53565A;
}

.home-modern-gallery-slide-thumb {
    position: relative;
    width: 100%;
    height: 150px;
    border-radius: 14px;
    overflow: hidden;
    background: #f3f6fb;
}

.home-modern-gallery-slide-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.home-modern-gallery-placeholder,
.home-modern-news-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #53565A;
}

.home-modern-gallery-placeholder i {
    font-size: 42px;
    color: var(--kp-primary);
}

.home-modern-gallery-slide-body {
    padding: 12px 2px 2px;
}

.home-modern-gallery-slide-title {
    font-size: 14px;
    font-weight: 700;
    line-height: 1.35;
    color: inherit;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.home-modern-gallery-slide-date {
    font-size: 12px;
    color: #94a3b8;
    display: flex;
    align-items: center;
}

[data-bs-theme="dark"] .home-modern-gallery-slide {
    color: #e2e8f0;
}
[data-bs-theme="dark"] .home-modern-gallery-slide-date {
    color: #7894a5;
}

/* =====================================================
   NEWS CAROUSEL — Sidebar auto-slide card
===================================================== */

.home-modern-news-carousel {
    position: relative;
    overflow: hidden;
}

.home-modern-news-slide {
    display: block;
    text-decoration: none;
    color: #53565A;
}

.home-modern-news-slide-thumb {
    position: relative;
    width: 100%;
    height: 150px;
    border-radius: 14px;
    overflow: hidden;
    background: #f3f6fb;
}

.home-modern-news-slide-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.home-modern-news-placeholder i {
    font-size: 42px;
    color: var(--kp-primary);
}

.home-modern-news-slide-body {
    padding: 12px 2px 2px;
}

.home-modern-news-slide-title {
    font-size: 14px;
    font-weight: 700;
    line-height: 1.35;
    color: inherit;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.home-modern-news-slide-date {
    font-size: 12px;
    color: #94a3b8;
    display: flex;
    align-items: center;
}

/* Prev / next arrows over the thumbnail */
.home-modern-news-nav {
    position: absolute;
    top: 75px; /* vertical center of the 150px thumbnail */
    transform: translateY(-50%);
    z-index: 3;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: none;
    background: rgba(15,23,42,.45);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    cursor: pointer;
    opacity: .65;
    transition: opacity .2s, background .2s;
}

.home-modern-news-carousel:hover .home-modern-news-nav { opacity: 1; }
.home-modern-news-nav:hover { background: rgba(15,23,42,.75); color: #fff; }
.home-modern-news-nav-prev { left: 10px; }
.home-modern-news-nav-next { right: 10px; }

/* Dots — keep .carousel-indicators (Bootstrap syncs .active on it)
   but pull them out of the overlay into normal flow below the text */
.home-modern-news-dots {
    position: static;
    margin: 10px 0 0;
    justify-content: center;
    gap: 5px;
}

.home-modern-news-dots [data-bs-target] {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    border: none;
    background: #cbd5e1;
    opacity: 1;
    margin: 0;
    padding: 0;
    transition: background .2s, width .2s;
}

.home-modern-news-dots .active {
    width: 20px;
    border-radius: 99px;
    background: var(--kp-primary);
}

[data-bs-theme="dark"] .home-modern-news-slide { color: #e2e8f0; }
[data-bs-theme="dark"] .home-modern-news-slide-thumb { background: #05283c; }
[data-bs-theme="dark"] .home-modern-news-placeholder i { color: var(--kp-light-blue); }
[data-bs-theme="dark"] .home-modern-news-slide-date { color: #7894a5; }
[data-bs-theme="dark"] .home-modern-news-dots [data-bs-target] { background: #376f90; }
[data-bs-theme="dark"] .home-modern-news-dots .active { background: var(--kp-light-blue); }

/* =====================================================
   DASHBOARD HEADER
===================================================== */

.home-modern-db-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 24px;
}

.home-modern-db-title {
    font-size: 22px;
    font-weight: 700;
    color: #53565A;
    margin: 0 0 4px;
    line-height: 1.2;
}

.home-modern-db-subtitle {
    font-size: 14px;
    color: #8A8D8F;
    margin: 0;
}

[data-bs-theme="dark"] .home-modern-db-title    { color: #f1f5f9; }
[data-bs-theme="dark"] .home-modern-db-subtitle { color: #94a3b8; }

/* =====================================================
   ASSESSMENT BUTTON — top of main column
===================================================== */

.home-modern-assessment-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    position: relative;
    overflow: hidden;
    background: #D4A51F;
    color: #FFFFFF;
    border: none;
    padding: 10px 22px;
    border-radius: 14px;
    font-size: 14px;
    font-weight: 600;
    line-height: 1.4;
    cursor: pointer;
    box-shadow: none;
    transition: background-color .2s ease, border-color .2s ease,
        box-shadow .2s ease, transform .2s ease;
}

.home-modern-assessment-btn i {
    color: #FFFFFF;
    font-size: 18px;
}

.home-modern-assessment-btn:hover,
.home-modern-assessment-btn:focus-visible {
    background: #D4A51F;
    box-shadow: none;
    color: #FFFFFF;
}

[data-bs-theme="dark"] .home-modern-assessment-btn,
body.dark .home-modern-assessment-btn,
body.dark-mode .home-modern-assessment-btn {
    background: #D4A51F;
    border: none;
    color: #FFFFFF;
    box-shadow: none;
}

[data-bs-theme="dark"] .home-modern-assessment-btn i,
body.dark .home-modern-assessment-btn i,
body.dark-mode .home-modern-assessment-btn i {
    color: #FFFFFF;
}

[data-bs-theme="dark"] .home-modern-assessment-btn:hover,
[data-bs-theme="dark"] .home-modern-assessment-btn:focus-visible,
body.dark .home-modern-assessment-btn:hover,
body.dark .home-modern-assessment-btn:focus-visible,
body.dark-mode .home-modern-assessment-btn:hover,
body.dark-mode .home-modern-assessment-btn:focus-visible {
    background: #D4A51F;
    color: #FFFFFF;
    box-shadow: none;
}

/* =====================================================
   ASSESSMENT ACCESS — schedule information
===================================================== */

.home-modern-assessment-schedules {
    display: grid;
    gap: 10px;
}

.home-modern-assessment-schedule {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 14px;
    border: 1px solid rgba(0, 0, 0, .06);
    border-radius: 14px;
    background: rgba(255, 255, 255, .82);
    box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
    transition: background-color .2s ease, border-color .2s ease,
        box-shadow .2s ease, transform .2s ease;
}

.home-modern-assessment-schedule:hover {
    transform: translateY(-1px);
    border-color: rgba(0, 0, 0, .08);
    box-shadow: 0 4px 10px rgba(0, 0, 0, .10);
}

.home-modern-assessment-schedule-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    height: 38px;
    flex: 0 0 38px;
    border-radius: 11px;
    border: 1px solid rgba(199, 154, 43, .16);
    background: rgba(199, 154, 43, .10);
    color: #9A7117;
    font-size: 19px;
}

.home-modern-assessment-schedule-content {
    min-width: 0;
    flex: 1 1 auto;
}

.home-modern-assessment-schedule-title {
    margin-bottom: 7px;
    color: #334155;
    font-size: 14px;
    font-weight: 700;
    line-height: 1.35;
    overflow-wrap: anywhere;
}

.home-modern-assessment-schedule-meta {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 7px 10px;
}

.home-modern-assessment-status {
    display: inline-flex;
    align-items: center;
    padding: 4px 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
    border: 1px solid rgba(199, 154, 43, .14);
    background: rgba(199, 154, 43, .10);
    color: #806018;
}

.home-modern-assessment-status.available,
.home-modern-assessment-status.upcoming,
.home-modern-assessment-status.overdue,
.home-modern-assessment-status.no-deadline {
    background: rgba(199, 154, 43, .10);
    color: #806018;
}

.home-modern-assessment-due {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #64748b;
    font-size: 12px;
    font-weight: 600;
}

.home-modern-assessment-due.overdue,
.home-modern-assessment-date.overdue {
    color: #806018;
}

.home-modern-assessment-date {
    margin-top: 5px;
    color: #64748b;
    font-size: 11px;
    line-height: 1.35;
}

.home-modern-assessment-schedule-empty {
    align-items: center;
}

.home-modern-assessment-schedule-empty .home-modern-assessment-schedule-title {
    margin-bottom: 2px;
}

.home-modern-assessment-schedule-empty .home-modern-assessment-date {
    margin-top: 0;
}

#modal-assessment-access .modal-content {
    overflow: hidden;
    border: 1px solid rgba(0, 0, 0, .06);
    border-radius: 14px;
    background: rgba(255, 255, 255, .96);
    box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
    color: #334155;
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}

#modal-assessment-access .modal-header,
#modal-assessment-access .modal-footer {
    border-color: rgba(0, 0, 0, .06);
    background: transparent;
}

#modal-assessment-access .modal-title {
    color: #334155;
    font-size: 17px;
    font-weight: 700;
}

#modal-assessment-access .modal-title i,
#modal-assessment-access .alert-info i {
    color: #9A7117;
}

#modal-assessment-access .alert-info {
    border-color: rgba(199, 154, 43, .14);
    background: rgba(199, 154, 43, .08);
    color: #475569;
}

#modal-assessment-access .btn-primary {
    border-color: #B88920;
    background: #C79A2B;
    color: #FFFFFF;
    box-shadow: 0 2px 6px rgba(0, 0, 0, .10);
    transition: background-color .2s ease, border-color .2s ease,
        box-shadow .2s ease, transform .2s ease;
}

#modal-assessment-access .btn-primary:hover,
#modal-assessment-access .btn-primary:focus {
    border-color: #A67B1B;
    background: #B88920;
    color: #FFFFFF;
    box-shadow: 0 4px 10px rgba(0, 0, 0, .14);
    transform: translateY(-1px);
}

[data-bs-theme="dark"] #modal-assessment-access .modal-content,
body.dark-mode #modal-assessment-access .modal-content {
    border-color: rgba(255, 255, 255, .08);
    background: rgba(24, 36, 50, .96);
    box-shadow: 0 2px 8px rgba(0, 0, 0, .25);
    color: #F1F5F9;
}

[data-bs-theme="dark"] #modal-assessment-access .modal-header,
[data-bs-theme="dark"] #modal-assessment-access .modal-footer,
body.dark-mode #modal-assessment-access .modal-header,
body.dark-mode #modal-assessment-access .modal-footer {
    border-color: rgba(255, 255, 255, .08);
    background: transparent;
}

[data-bs-theme="dark"] #modal-assessment-access .modal-title,
body.dark-mode #modal-assessment-access .modal-title {
    color: #F1F5F9;
}

[data-bs-theme="dark"] #modal-assessment-access .modal-title i,
[data-bs-theme="dark"] #modal-assessment-access .alert-info i,
body.dark-mode #modal-assessment-access .modal-title i,
body.dark-mode #modal-assessment-access .alert-info i {
    color: #D4AA3A;
}

[data-bs-theme="dark"] #modal-assessment-access .alert-info,
body.dark-mode #modal-assessment-access .alert-info {
    border-color: rgba(255, 255, 255, .08);
    background: rgba(205, 164, 52, .10);
    color: #CBD5E1;
}

[data-bs-theme="dark"] .home-modern-assessment-schedule,
body.dark-mode .home-modern-assessment-schedule {
    border-color: rgba(255, 255, 255, .08);
    background: rgba(30, 44, 60, .90);
    box-shadow: 0 2px 8px rgba(0, 0, 0, .25);
}

[data-bs-theme="dark"] .home-modern-assessment-schedule:hover,
body.dark-mode .home-modern-assessment-schedule:hover {
    border-color: rgba(255, 255, 255, .12);
    background: rgba(34, 49, 66, .94);
    box-shadow: 0 4px 10px rgba(0, 0, 0, .30);
}

[data-bs-theme="dark"] .home-modern-assessment-schedule-icon,
body.dark-mode .home-modern-assessment-schedule-icon {
    border-color: rgba(205, 164, 52, .18);
    background: rgba(205, 164, 52, .12);
    color: #D4AA3A;
}

[data-bs-theme="dark"] .home-modern-assessment-schedule-title,
body.dark-mode .home-modern-assessment-schedule-title {
    color: #F1F5F9;
}

[data-bs-theme="dark"] .home-modern-assessment-due,
[data-bs-theme="dark"] .home-modern-assessment-date,
body.dark-mode .home-modern-assessment-due,
body.dark-mode .home-modern-assessment-date {
    color: #CBD5E1;
}

[data-bs-theme="dark"] .home-modern-assessment-due.overdue,
[data-bs-theme="dark"] .home-modern-assessment-date.overdue,
body.dark-mode .home-modern-assessment-due.overdue,
body.dark-mode .home-modern-assessment-date.overdue {
    color: #D4AA3A;
}

[data-bs-theme="dark"] .home-modern-assessment-status.available,
[data-bs-theme="dark"] .home-modern-assessment-status.upcoming,
[data-bs-theme="dark"] .home-modern-assessment-status.overdue,
[data-bs-theme="dark"] .home-modern-assessment-status.no-deadline,
body.dark-mode .home-modern-assessment-status.available,
body.dark-mode .home-modern-assessment-status.upcoming,
body.dark-mode .home-modern-assessment-status.overdue,
body.dark-mode .home-modern-assessment-status.no-deadline {
    border-color: rgba(205, 164, 52, .18);
    background: rgba(205, 164, 52, .12);
    color: #E5C76B;
}

[data-bs-theme="dark"] #modal-assessment-access .btn-primary,
body.dark-mode #modal-assessment-access .btn-primary {
    border-color: rgba(255, 255, 255, .12);
    background: linear-gradient(135deg, #CDA434, #B98B22);
    color: #FFFFFF;
    box-shadow: 0 2px 6px rgba(0, 0, 0, .25);
}

[data-bs-theme="dark"] #modal-assessment-access .btn-primary:hover,
[data-bs-theme="dark"] #modal-assessment-access .btn-primary:focus,
body.dark-mode #modal-assessment-access .btn-primary:hover,
body.dark-mode #modal-assessment-access .btn-primary:focus {
    border-color: rgba(255, 255, 255, .14);
    background: #B88920;
    color: #FFFFFF;
    box-shadow: 0 4px 10px rgba(0, 0, 0, .30);
}

@media (max-width: 575.98px) {
    .home-modern-assessment-schedule {
        padding: 12px;
    }

    .home-modern-assessment-schedule-icon {
        width: 34px;
        height: 34px;
        flex-basis: 34px;
        font-size: 17px;
    }

    .home-modern-assessment-schedule-meta {
        align-items: flex-start;
        flex-direction: column;
        gap: 7px;
    }
}

</style>
