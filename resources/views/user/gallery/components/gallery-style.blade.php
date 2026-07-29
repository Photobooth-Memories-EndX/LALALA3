<style>
/* ============================================================
   HERO
   ============================================================ */
.content-hero-card {
    border-radius: 22px !important;
}

.content-hero-surface {
    min-height: 170px;
    padding: 28px 34px !important;
    display: flex;
    align-items: center;
    position: relative;
    overflow: hidden;
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

.content-hero-surface::before {
    content: "" !important;
    position: absolute !important;
    inset: 0 !important;
    display: block !important;
    background: radial-gradient(
        circle at 85% 20%,
        rgba(255, 255, 255, 0.22) 0%,
        transparent 28%
    ) !important;
    pointer-events: none !important;
    z-index: 1 !important;
}

.content-hero-surface > .row {
    width: 100%;
    position: relative;
    z-index: 2;
}

.gallery-hero-media {
    width: 220px;
    height: 130px;
    border-radius: 14px;
    overflow: hidden;
    position: relative;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.25);
    background: rgba(255, 255, 255, 0.12);
    border: 1px solid rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(6px);
}

.gallery-hero-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.kp-hero-media-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 42px;
    color: rgba(255, 255, 255, 0.85);
    background: rgba(255, 255, 255, 0.1);
}

.content-hero-copy .btn-white {
    background: #ffffff !important;
    color: #002b66 !important;
    border: none !important;
    transition: all 0.2s ease;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.content-hero-copy .btn-white:hover {
    background: #f8fafc !important;
    color: #001b44 !important;
    transform: translateY(-1px);
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.15);
}

/* ============================================================
   FILTERS
   ============================================================ */
.gallery-filter-card {
    background: var(--tblr-bg-surface, #fff);
    border: 1px solid var(--tblr-border-color, rgba(98, 105, 118, .12));
    border-radius: 16px;
    padding: 20px 24px;
}

.gallery-filter-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.gallery-filter-options {
    display: grid;
    grid-template-columns: repeat(3, minmax(112px, 1fr));
    flex: 1 1 auto;
    gap: 8px;
    min-width: 0;
}

.gallery-filter-option {
    min-width: 0;
}

.gallery-filter-actions {
    display: flex;
    flex: 0 1 500px;
    align-items: center;
    gap: 8px;
    min-width: 0;
}

.gallery-filter-option .form-select,
.gallery-filter-search .form-control,
.gallery-filter-search .input-group-text {
    min-height: 38px;
}

.gallery-filter-search {
    flex: 1 1 360px;
    min-width: 0;
}

.gallery-date-picker {
    position: relative;
    flex: 0 0 auto;
}

.gallery-date-trigger,
.gallery-filter-submit {
    width: 38px;
    height: 38px;
    padding: 0;
    border: 1px solid var(--tblr-border-color, rgba(98, 105, 118, .2));
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    transition: background-color .2s ease, border-color .2s ease, color .2s ease;
}

.gallery-date-trigger {
    position: relative;
    background: var(--tblr-bg-surface, #fff);
    color: var(--tblr-body-color, #182433);
}

.gallery-date-trigger:hover,
.gallery-date-trigger:focus {
    border-color: #008a43;
    color: #008a43;
}

.gallery-date-trigger.is-active {
    border-color: rgba(0, 138, 67, .55);
    background: rgba(0, 138, 67, .1);
    color: #008a43;
}

.gallery-date-trigger.is-active::after {
    content: "";
    position: absolute;
    top: 5px;
    right: 5px;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #008a43;
}

.gallery-date-input {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
    opacity: 0;
    pointer-events: none;
}

.gallery-filter-submit {
    flex: 0 0 auto;
    background: #008a43;
    border-color: #008a43;
    color: #fff;
}

.gallery-filter-submit:hover,
.gallery-filter-submit:focus {
    background: #00783a;
    border-color: #00783a;
    color: #fff;
}

/* ============================================================
   GALLERY CARDS
   ============================================================ */
.gallery-card {
    border: 1px solid var(--tblr-border-color, rgba(98, 105, 118, .12));
    border-radius: 18px;
    overflow: hidden;
    transition: transform .25s ease, box-shadow .25s ease;
    background: var(--tblr-bg-surface, #fff);
}

.gallery-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 28px rgba(83, 86, 90, .10);
}

.gallery-thumb {
    position: relative;
    height: 200px;
    background: var(--tblr-bg-surface-secondary, #f6f8fb);
    overflow: hidden;
    flex-shrink: 0;
}

.gallery-thumb-placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 48px;
    color: var(--tblr-muted, #9ba9b4);
}

.gallery-thumb-img {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.gallery-badge {
    position: absolute;
    top: 12px;
    left: 12px;
    background: #008a43;
    color: #fff;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 999px;
    text-transform: uppercase;
}

.gallery-photo-count {
    position: absolute;
    right: 12px;
    bottom: 12px;
    padding: 4px 10px;
    border-radius: 999px;
    background: rgba(0, 0, 0, .65);
    color: #fff;
    font-size: 11px;
}

.gallery-card-title {
    min-height: 45px;
    margin-bottom: 16px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    color: var(--tblr-body-color);
    font-size: 16px;
    font-weight: 700;
    line-height: 1.4;
}

.gallery-event-uploader {
    min-width: 0;
}

.gallery-event-meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 14px;
    color: var(--tblr-muted, #667382);
    font-size: 12px;
}

.gallery-event-meta span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
}

.gallery-empty-state {
    padding: 48px 20px;
    border: 1px solid var(--tblr-border-color, rgba(98, 105, 118, .12));
    border-radius: 16px;
    background: var(--tblr-bg-surface, #fff);
    text-align: center;
}

.gallery-empty-icon {
    color: var(--tblr-muted, #9ba9b4);
    font-size: 56px;
}

/* ============================================================
   DARK MODE
   ============================================================ */
[data-bs-theme="dark"] .content-hero-surface,
[data-theme="dark"] .content-hero-surface,
body.theme-dark .content-hero-surface,
body.dark-mode .content-hero-surface,
body.dark .content-hero-surface {
    background: linear-gradient(
        135deg,
        #00112c 0%,
        #001b44 20%,
        #003366 45%,
        #1d5996 70%,
        #4e82be 88%,
        #82b8f5 100%
    ) !important;
}

[data-bs-theme="dark"] .gallery-filter-card,
[data-theme="dark"] .gallery-filter-card,
body.theme-dark .gallery-filter-card,
body.dark-mode .gallery-filter-card,
body.dark .gallery-filter-card,
[data-bs-theme="dark"] .gallery-card,
[data-theme="dark"] .gallery-card,
body.theme-dark .gallery-card,
body.dark-mode .gallery-card,
body.dark .gallery-card,
[data-bs-theme="dark"] .gallery-empty-state,
[data-theme="dark"] .gallery-empty-state,
body.theme-dark .gallery-empty-state,
body.dark-mode .gallery-empty-state,
body.dark .gallery-empty-state {
    background: var(--tblr-bg-surface, #182433);
    border-color: var(--tblr-border-color, #25384d);
}

[data-bs-theme="dark"] .gallery-filter-option .form-select,
[data-bs-theme="dark"] .gallery-filter-search .form-control,
[data-bs-theme="dark"] .gallery-filter-search .input-group-text,
[data-bs-theme="dark"] .gallery-date-trigger,
[data-theme="dark"] .gallery-filter-option .form-select,
[data-theme="dark"] .gallery-filter-search .form-control,
[data-theme="dark"] .gallery-filter-search .input-group-text,
[data-theme="dark"] .gallery-date-trigger,
body.theme-dark .gallery-filter-option .form-select,
body.theme-dark .gallery-filter-search .form-control,
body.theme-dark .gallery-filter-search .input-group-text,
body.theme-dark .gallery-date-trigger,
body.dark-mode .gallery-filter-option .form-select,
body.dark-mode .gallery-filter-search .form-control,
body.dark-mode .gallery-filter-search .input-group-text,
body.dark-mode .gallery-date-trigger,
body.dark .gallery-filter-option .form-select,
body.dark .gallery-filter-search .form-control,
body.dark .gallery-filter-search .input-group-text,
body.dark .gallery-date-trigger {
    background-color: var(--tblr-bg-surface-secondary, #223247);
    border-color: var(--tblr-border-color, #25384d);
    color: var(--tblr-body-color, #f8fafc);
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (min-width: 768px) and (max-width: 991.98px) {
    .gallery-filter-toolbar {
        flex-wrap: wrap;
    }

    .gallery-filter-options {
        flex-basis: 100%;
    }

    .gallery-filter-actions {
        flex-basis: min(100%, 500px);
        margin-left: auto;
    }
}

@media (max-width: 767.98px) {
    .gallery-filter-card {
        padding: 16px;
    }

    .gallery-filter-toolbar {
        flex-direction: column;
        align-items: stretch;
    }

    .gallery-filter-options {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .gallery-filter-option:last-child {
        grid-column: span 2;
    }

    .gallery-filter-actions {
        flex-basis: auto;
        width: 100%;
    }
}

@media (max-width: 991.98px) {
    .content-hero-surface {
        padding: 20px !important;
    }

    .gallery-hero-media {
        width: 100%;
        max-width: 280px;
        height: 150px;
        margin-top: 8px;
    }
}
</style>
