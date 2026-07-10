<style>

/* =====================================================
   HOME MODERN — prefix: .home-modern-*
   Scoped to homepage only. Do not share globally.
===================================================== */

/* Page background */
.page-body {
    background: #f1f5f9;
    min-height: 100vh;
}

.container-fluid {
    max-width: 1200px;
    margin-left: auto;
    margin-right: auto;
}

a { text-decoration: none; }

/* =====================================================
   HERO
===================================================== */

.home-modern-hero {
    background: linear-gradient(135deg, #0f2247 0%, #1e3a8a 100%);
    border-radius: 24px;
    padding: 36px 40px;
    position: relative;
    overflow: hidden;
}

.home-modern-hero::before {
    content: '';
    position: absolute;
    width: 380px;
    height: 380px;
    border-radius: 50%;
    background: rgba(255,255,255,.04);
    top: -160px;
    right: -100px;
    pointer-events: none;
}

.home-modern-hero::after {
    content: '';
    position: absolute;
    width: 220px;
    height: 220px;
    border-radius: 50%;
    background: rgba(255,255,255,.03);
    bottom: -90px;
    left: 35%;
    pointer-events: none;
}

.home-modern-hero-label {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(255,255,255,.12);
    color: rgba(255,255,255,.9);
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .3px;
    margin-bottom: 16px;
}

.home-modern-hero-greeting {
    font-size: 20px;
    font-weight: 400;
    color: rgba(255,255,255,.72);
    margin-bottom: 2px;
    line-height: 1.2;
}

.home-modern-hero-name {
    font-size: 34px;
    font-weight: 700;
    color: #fff;
    margin-bottom: 8px;
    line-height: 1.15;
}

.home-modern-hero-subtitle {
    font-size: 15px;
    color: rgba(255,255,255,.72);
    margin-bottom: 0;
}

.home-modern-hero-stats {
    display: flex;
    gap: 10px;
    margin-top: 16px;
    flex-wrap: wrap;
}

.home-modern-hero-stat-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 7px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 500;
    color: rgba(255,255,255,.85);
}

.home-modern-hero-stat-pill strong {
    font-size: 15px;
    font-weight: 700;
    color: #fff;
}

.home-modern-hero-stat-blue { background: rgba(56,189,248,.15); }
.home-modern-hero-stat-green { background: rgba(34,197,94,.15); }

.home-modern-hero-badges {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.home-modern-badge-streak {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(251,191,36,.15);
    color: #fde68a;
    padding: 7px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
}

.home-modern-badge-progress {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: rgba(34,197,94,.15);
    color: #86efac;
    padding: 7px 14px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
}

/* Donut */
.home-modern-donut-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.home-modern-donut-outer {
    width: 184px;
    height: 184px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    box-shadow: 0 10px 28px rgba(0,0,0,.28), inset 0 0 0 1px rgba(255,255,255,.06);
    transition: transform .25s ease, box-shadow .25s ease;
}

.home-modern-donut-outer:hover {
    transform: scale(1.03);
    box-shadow: 0 16px 40px rgba(0,0,0,.4), inset 0 0 0 1px rgba(255,255,255,.08);
}

.home-modern-donut-hit {
    position: absolute;
    inset: 0;
    border-radius: 50%;
    background: transparent;
    cursor: pointer;
    transition: background .2s ease;
}

.home-modern-donut-hit-completed:hover { background: rgba(34,197,94,.28); }
.home-modern-donut-hit-ongoing:hover { background: rgba(56,189,248,.28); }

.home-modern-donut-inner {
    width: 132px;
    height: 132px;
    border-radius: 50%;
    background: #0f2247;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    position: relative;
}

.home-modern-donut-inner strong {
    font-size: 30px;
    font-weight: 700;
    line-height: 1;
    color: #fff;
    display: block;
}

.home-modern-donut-inner small {
    font-size: 11px;
    color: rgba(255,255,255,.7);
    margin-top: 5px;
    display: block;
}

.home-modern-donut-legend {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    margin-top: 12px;
}

.home-modern-donut-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: rgba(255,255,255,.75);
    font-size: 12px;
    padding: 2px 8px;
    border-radius: 6px;
    cursor: pointer;
    transition: background .2s ease, color .2s ease;
}

.home-modern-donut-legend-item:hover {
    background: rgba(255,255,255,.08);
    color: #fff;
}

.home-modern-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}

.home-modern-dot.completed { background: #22c55e; }
.home-modern-dot.ongoing { background: #38bdf8; }

.home-modern-donut-wrap:has(.home-modern-donut-legend-item.completed:hover) .home-modern-donut-hit-completed {
    background: rgba(34,197,94,.28);
}
.home-modern-donut-wrap:has(.home-modern-donut-legend-item.ongoing:hover) .home-modern-donut-hit-ongoing {
    background: rgba(56,189,248,.28);
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

    background: linear-gradient(135deg, #2563eb, #1d4ed8);
    color: #fff;

    font-size: 15px;
    font-weight: 600;
    text-decoration: none;

    box-shadow: 0 4px 14px rgba(37,99,235,.20);

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
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 8px 22px rgba(37,99,235,.30);
}

.home-modern-btn-training-plan:active {
    transform: translateY(0);
}

[data-bs-theme="dark"] .home-modern-btn-training-plan {
    box-shadow: 0 4px 14px rgba(37,99,235,.15);
}

[data-bs-theme="dark"] .home-modern-btn-training-plan:hover {
    box-shadow: 0 8px 22px rgba(37,99,235,.25);
}

/* =====================================================
   STAT CARDS
===================================================== */

.home-modern-stat {
    background: #fff;
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
    background: #eff6ff;
    color: #2563eb;
}

.home-modern-stat-green .home-modern-stat-icon {
    background: #f0fdf4;
    color: #16a34a;
}

.home-modern-stat-red .home-modern-stat-icon {
    background: #fff1f2;
    color: #dc2626;
}

.home-modern-stat-number {
    font-size: 30px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1;
    margin-bottom: 4px;
}

.home-modern-stat-label {
    font-size: 13px;
    color: #64748b;
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
    border-radius: 16px;
    padding: 16px 10px;
    text-decoration: none;
    color: #0f172a;
    font-size: 12px;
    font-weight: 600;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,.05);
    transition: all .2s;
    height: 100%;
}

.home-modern-quick-btn:hover {
    color: #2563eb;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37,99,235,.12);
}

.home-modern-quick-btn i {
    font-size: 22px;
    color: #2563eb;
}

/* =====================================================
   CARD BASE
===================================================== */

.home-modern-card {
    background: #fff;
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
    color: #0f172a;
    margin: 0;
}

.home-modern-view-all {
    font-size: 13px;
    font-weight: 600;
    color: #2563eb;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 2px;
}

.home-modern-view-all:hover {
    color: #1d4ed8;
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
    color: #0f172a;
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
    color: #64748b;
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
    background: linear-gradient(90deg, #2563eb, #38bdf8);
    transition: width .3s;
}

.home-modern-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    background: #2563eb;
    color: #fff;
    padding: 8px 18px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: background .2s, transform .15s;
}

.home-modern-btn-primary:hover {
    background: #1d4ed8;
    color: #fff;
    transform: translateY(-1px);
}

.home-modern-btn-outline {
    display: inline-flex;
    align-items: center;
    border: 1.5px solid #2563eb;
    color: #2563eb;
    padding: 7px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    text-decoration: none;
    transition: all .2s;
}

.home-modern-btn-outline:hover {
    background: #2563eb;
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
    color: #2563eb;
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
    background: #eff6ff;
    color: #2563eb;
    font-size: 10px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 999px;
    margin-bottom: 8px;
    text-transform: uppercase;
    letter-spacing: .4px;
}

.home-modern-category-green {
    background: #f0fdf4;
    color: #16a34a;
}

.home-modern-video-title {
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
    line-height: 1.45;
    margin-bottom: 8px;
}

.home-modern-video-meta {
    font-size: 12px;
    color: #64748b;
    display: flex;
    align-items: center;
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
    color: #0f172a;
    transition: color .15s;
}

.home-modern-module-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.home-modern-module-row:hover {
    color: #2563eb;
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
    background: #eff6ff;
    color: #2563eb;
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
    border-color: #c7d2fe;
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
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    color: #fff;
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
    background: #f5f3ff;
    color: #7c3aed;
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
    border-color: rgba(96,165,250,.4);
}
[data-bs-theme="dark"] .home-modern-category-purple {
    background: rgba(124,58,237,.2);
    color: #a78bfa;
}

/* =====================================================
   UPCOMING DEADLINES
===================================================== */

.home-modern-deadline-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 14px;
    border-radius: 14px;
    border: 1px solid #f1f5f9;
    background: #f8fafc;
    margin-bottom: 10px;
    text-decoration: none;
    color: #0f172a;
    transition: all .2s;
}

.home-modern-deadline-row:last-child {
    margin-bottom: 0;
}

.home-modern-deadline-row:hover {
    border-color: #bfdbfe;
    background: #eff6ff;
    color: #0f172a;
}

.home-modern-deadline-left {
    display: flex;
    align-items: center;
    gap: 12px;
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

.home-modern-deadline-danger  { background: #fff1f2; color: #dc2626; }
.home-modern-deadline-warning { background: #fffbeb; color: #d97706; }
.home-modern-deadline-primary { background: #eff6ff; color: #2563eb; }

.home-modern-deadline-title {
    font-size: 14px;
    font-weight: 600;
    color: #0f172a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 300px;
}

.home-modern-deadline-meta {
    font-size: 12px;
    color: #64748b;
    margin-top: 3px;
    display: flex;
    align-items: center;
}

.home-modern-priority-badge {
    display: inline-block;
    font-size: 10px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 999px;
    text-transform: uppercase;
    letter-spacing: .3px;
}

.home-modern-priority-danger  { background: #fee2e2; color: #dc2626; }
.home-modern-priority-warning { background: #fef3c7; color: #d97706; }
.home-modern-priority-primary { background: #dbeafe; color: #1d4ed8; }

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
    color: #0f172a;
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
    color: #64748b;
}

.home-modern-readmore {
    color: #2563eb;
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
    .home-modern-donut-outer {
        width: 160px;
        height: 160px;
    }
    .home-modern-donut-inner {
        width: 116px;
        height: 116px;
    }
    .home-modern-donut-inner strong {
        font-size: 26px;
    }
}

@media (max-width: 767px) {
    .home-modern-hero {
        padding: 22px 18px;
    }
    .home-modern-hero-name {
        font-size: 24px;
    }
    .home-modern-hero-greeting {
        font-size: 16px;
    }
    .home-modern-donut-outer {
        width: 136px;
        height: 136px;
    }
    .home-modern-donut-inner {
        width: 98px;
        height: 98px;
    }
    .home-modern-donut-inner strong {
        font-size: 22px;
    }
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
    background: #0b1629;
}

/* Hero stays dark navy — no change needed */
[data-bs-theme="dark"] .home-modern-hero {
    background: linear-gradient(135deg, #0c1e38 0%, #132e6a 100%);
}

[data-bs-theme="dark"] .home-modern-donut-inner {
    background: #0c1e38;
}

/* Stat cards */
[data-bs-theme="dark"] .home-modern-stat {
    background: #152235;
    box-shadow: none;
    border: 1px solid rgba(255,255,255,.07);
}

[data-bs-theme="dark"] .home-modern-stat-number { color: #f1f5f9; }
[data-bs-theme="dark"] .home-modern-stat-label  { color: #94a3b8; }

[data-bs-theme="dark"] .home-modern-stat-blue .home-modern-stat-icon {
    background: rgba(37,99,235,.2); color: #60a5fa;
}
[data-bs-theme="dark"] .home-modern-stat-green .home-modern-stat-icon {
    background: rgba(22,163,74,.2); color: #4ade80;
}
[data-bs-theme="dark"] .home-modern-stat-red .home-modern-stat-icon {
    background: rgba(220,38,38,.2); color: #f87171;
}

/* Quick actions */
[data-bs-theme="dark"] .home-modern-quick-btn {
    background: #152235;
    color: #f1f5f9;
    border: 1px solid rgba(255,255,255,.07);
    box-shadow: none;
}
[data-bs-theme="dark"] .home-modern-quick-btn:hover {
    color: #60a5fa;
    border-color: rgba(96,165,250,.3);
    box-shadow: 0 4px 16px rgba(37,99,235,.15);
}
[data-bs-theme="dark"] .home-modern-quick-btn i { color: #60a5fa; }

/* Cards */
[data-bs-theme="dark"] .home-modern-card {
    background: #152235;
    border: 1px solid rgba(255,255,255,.07);
    box-shadow: none;
}
[data-bs-theme="dark"] .home-modern-section-title { color: #f1f5f9; }
[data-bs-theme="dark"] .home-modern-card-header {
    border-bottom: 1px solid rgba(255,255,255,.06);
}

/* Continue learning */
[data-bs-theme="dark"] .home-modern-continue-title { color: #f1f5f9; }
[data-bs-theme="dark"] .home-modern-continue-period { color: #94a3b8; }
[data-bs-theme="dark"] .home-modern-progress       { background: #1a3356; }
[data-bs-theme="dark"] .home-modern-progress-wrap small { color: #94a3b8; }

/* Modules */
[data-bs-theme="dark"] .home-modern-module-row {
    color: #e2e8f0;
    border-bottom-color: rgba(255,255,255,.06);
}
[data-bs-theme="dark"] .home-modern-module-row:hover { color: #60a5fa; }
[data-bs-theme="dark"] .home-modern-module-icon {
    background: rgba(37,99,235,.2); color: #60a5fa;
}
[data-bs-theme="dark"] .home-modern-module-sub  { color: #4b6680; }
[data-bs-theme="dark"] .home-modern-module-arrow { color: #2a4a6a; }

/* Deadlines */
[data-bs-theme="dark"] .home-modern-deadline-row {
    background: #192d45;
    border-color: rgba(255,255,255,.07);
    color: #e2e8f0;
}
[data-bs-theme="dark"] .home-modern-deadline-row:hover {
    background: #1c3456;
    border-color: rgba(96,165,250,.25);
    color: #e2e8f0;
}
[data-bs-theme="dark"] .home-modern-deadline-title { color: #f1f5f9; }
[data-bs-theme="dark"] .home-modern-deadline-meta  { color: #64748b; }

[data-bs-theme="dark"] .home-modern-deadline-danger  { background: rgba(220,38,38,.2);  color: #f87171; }
[data-bs-theme="dark"] .home-modern-deadline-warning { background: rgba(217,119,6,.2);  color: #fbbf24; }
[data-bs-theme="dark"] .home-modern-deadline-primary { background: rgba(37,99,235,.2);  color: #60a5fa; }

[data-bs-theme="dark"] .home-modern-priority-danger  { background: rgba(220,38,38,.25);  color: #f87171; }
[data-bs-theme="dark"] .home-modern-priority-warning { background: rgba(217,119,6,.25);  color: #fbbf24; }
[data-bs-theme="dark"] .home-modern-priority-primary { background: rgba(37,99,235,.25);  color: #93c5fd; }

/* News & Gallery & Video cards */
[data-bs-theme="dark"] .home-modern-news-card,
[data-bs-theme="dark"] .home-modern-gallery-card,
[data-bs-theme="dark"] .home-modern-video-card {
    background: #192d45;
    border: 1px solid rgba(255,255,255,.07);
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
[data-bs-theme="dark"] .home-modern-video-meta { color: #4b6680; }

[data-bs-theme="dark"] .home-modern-video-play {
    background: rgba(255,255,255,.9);
    color: #1d4ed8;
}

/* Category badges */
[data-bs-theme="dark"] .home-modern-category-badge {
    background: rgba(37,99,235,.2); color: #60a5fa;
}
[data-bs-theme="dark"] .home-modern-category-green {
    background: rgba(22,163,74,.2); color: #4ade80;
}

/* Empty state */
[data-bs-theme="dark"] .home-modern-empty { color: #2a4a6a; }
[data-bs-theme="dark"] .home-modern-empty p { color: #4b6680; }

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
    scrollbar-color: #1a3356 transparent;
}
[data-bs-theme="dark"] .home-modern-hscroll::-webkit-scrollbar-thumb {
    background: #1a3356;
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
    transition: transform .4s ease;
}

.home-modern-slide-item {
    flex: 0 0 33.3333%;
    max-width: 33.3333%;
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
.home-modern-slide-nav:hover { background: rgba(37,99,235,.85); color: #fff; }
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
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid rgba(220, 38, 38, .12);
}

.deadline-status.success {
    background: #dcfce7;
    color: #15803d;
    border-color: rgba(21, 128, 61, .18);
}

[data-bs-theme="dark"] .home-modern-slide-nav {
    background: rgba(21,34,53,.85);
}
[data-bs-theme="dark"] .home-modern-slide-nav:hover {
    background: rgba(37,99,235,.85);
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
    color: #64748b;
    font-size: 13px;
    font-weight: 600;
    padding: 10px 16px;
    transition: color .15s, border-color .15s;
    background: transparent;
}

.home-modern-nav-tabs .nav-link:hover {
    color: #2563eb;
    border-bottom-color: #bfdbfe;
}

.home-modern-nav-tabs .nav-link.active {
    color: #2563eb;
    border-bottom-color: #2563eb;
}

[data-bs-theme="dark"] .home-modern-nav-tabs .nav-link {
    color: #4b6680;
}
[data-bs-theme="dark"] .home-modern-nav-tabs .nav-link:hover {
    color: #60a5fa;
    border-bottom-color: rgba(96,165,250,.35);
}
[data-bs-theme="dark"] .home-modern-nav-tabs .nav-link.active {
    color: #60a5fa;
    border-bottom-color: #3b82f6;
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
    color: #0f172a;
    transition: color .15s;
}

.home-modern-gallery-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.home-modern-gallery-row:hover { color: #2563eb; }

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
[data-bs-theme="dark"] .home-modern-gallery-row:hover { color: #60a5fa; }
[data-bs-theme="dark"] .home-modern-gallery-row-date { color: #4b6680; }

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
.home-modern-gallery-nav:hover { background: rgba(37,99,235,.85); color: #fff; }
.home-modern-gallery-nav-prev { left: 10px; }
.home-modern-gallery-nav-next { right: 10px; }

.home-modern-gallery-slide {
    display: block;
    text-decoration: none;
    color: #0f172a;
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

.home-modern-gallery-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.home-modern-gallery-placeholder i {
    font-size: 42px;
    color: #2563eb;
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
[data-bs-theme="dark"] .home-modern-gallery-placeholder {
    background: #111827;
}
[data-bs-theme="dark"] .home-modern-gallery-slide-date {
    color: #4b6680;
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
    color: #0f172a;
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

.home-modern-news-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #eff6ff, #dbeafe);
}

.home-modern-news-placeholder i {
    font-size: 42px;
    color: #2563eb;
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
    background: #2563eb;
}

[data-bs-theme="dark"] .home-modern-news-slide { color: #e2e8f0; }
[data-bs-theme="dark"] .home-modern-news-slide-thumb { background: #111827; }
[data-bs-theme="dark"] .home-modern-news-placeholder {
    background: linear-gradient(135deg, #10233d, #132e5a);
}
[data-bs-theme="dark"] .home-modern-news-placeholder i { color: #60a5fa; }
[data-bs-theme="dark"] .home-modern-news-slide-date { color: #4b6680; }
[data-bs-theme="dark"] .home-modern-news-dots [data-bs-target] { background: #2a4a6a; }
[data-bs-theme="dark"] .home-modern-news-dots .active { background: #60a5fa; }

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
    color: #0f172a;
    margin: 0 0 4px;
    line-height: 1.2;
}

.home-modern-db-subtitle {
    font-size: 14px;
    color: #64748b;
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
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #fff;
    border: none;
    padding: 10px 22px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 14px rgba(217,119,6,.3);
    transition: transform .15s, box-shadow .15s;
}

.home-modern-assessment-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(217,119,6,.4);
    color: #fff;
}

[data-bs-theme="dark"] .home-modern-assessment-btn {
    box-shadow: 0 4px 14px rgba(217,119,6,.2);
}

</style>
