{{--
    Shared Tom Select + SortableJS styles untuk Training Plan create/edit.
--}}
@once
<style>
    /* ======================================================
       DROPDOWN PANEL
    ====================================================== */
    .ts-dropdown {
        z-index: 9999 !important;
        border-radius: 10px;
        overflow: hidden;
    }
    .ts-dropdown .ts-dropdown-content {
        max-height: 280px !important;
        overflow-y: auto !important;
        overflow-x: hidden;
        overscroll-behavior: contain;
        scroll-behavior: smooth;
    }
    .ts-dropdown .option {
        padding: 9px 12px;
        font-size: 13px;
        cursor: pointer;
        transition: background .1s ease;
    }
    .ts-dropdown .option.active,
    .ts-dropdown .option:hover {
        background: var(--tblr-primary-lt, #e8f0fd);
        color: var(--tblr-primary, #206bc4);
    }
    .ts-dropdown .no-results {
        padding: 10px 14px;
        color: var(--tblr-muted, #6c757d);
        font-size: 13px;
        font-style: italic;
    }
    .playlist-option-item {
        padding: 9px 12px;
        font-size: 13px;
        line-height: 1.4;
    }

    /* ======================================================
       WRAPPER
    ====================================================== */
    .playlist-content-select-wrapper {
        position: relative;
        width: 100%;
    }
    .playlist-content-select-wrapper .ts-wrapper {
        width: 100%;
    }

    /* ======================================================
       CONTROL (the visible box containing items + search)
    ====================================================== */
    .playlist-ts-wrapper .ts-control {
        display: flex !important;
        flex-direction: column !important;
        flex-wrap: nowrap !important;
        align-items: stretch !important;
        padding: 8px 10px !important;
        gap: 5px;
        min-height: 48px;
        max-height: 420px;
        overflow-y: auto;
        cursor: text;
        border-radius: 10px !important;
    }

    /* Search input - always at top */
    .playlist-ts-wrapper .ts-control > input {
        order: -1 !important;
        width: 100% !important;
        min-width: 0 !important;
        margin: 0 0 4px 0 !important;
        padding: 5px 6px !important;
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
        font-size: 13px;
        flex: 0 0 auto;
    }

    /* ======================================================
       SELECTED ITEM ROW - flex row so .remove stays right
    ====================================================== */
    .playlist-ts-wrapper .ts-control .item {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        margin: 0 !important;
        padding: 0 !important;
        border-radius: 10px !important;
        background: var(--tblr-primary-lt, #dbe7f7) !important;
        color: var(--tblr-primary, #206bc4) !important;
        border: 1px solid rgba(32, 107, 196, .2) !important;
        cursor: default !important;
        user-select: none;
        overflow: hidden;
        min-width: 0;
    }
    .playlist-ts-wrapper .ts-control .item:hover {
        background: #c7dcf7 !important;
    }

    /* Remove button */
    .playlist-ts-wrapper .ts-control .item .remove {
        flex: 0 0 auto;
        align-self: center;
        color: var(--tblr-primary, #206bc4);
        opacity: .5;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
        border: none;
        background: none;
        padding: 9px 10px;
        margin: 0;
        transition: opacity .1s, color .1s;
    }
    .playlist-ts-wrapper .ts-control .item .remove:hover {
        opacity: 1;
        color: var(--tblr-danger, #d63939);
    }

    /* ======================================================
       INNER FLEX ROW - takes all space except .remove
    ====================================================== */
    .playlist-section-item-inner {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 10px;
        min-height: 34px;
        box-sizing: border-box;
        flex: 1;
        min-width: 0;
        overflow: hidden;
    }

    .playlist-drag-handle {
        width: 22px;
        height: 22px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: grab !important;
        opacity: .6;
        flex: 0 0 auto;
        font-size: 15px;
        transition: opacity .1s;
    }
    .playlist-drag-handle:hover { opacity: 1; }
    .playlist-drag-handle:active { cursor: grabbing !important; }

    .playlist-section-badge {
        flex: 0 0 auto;
        min-width: 72px;
        text-align: center;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .5px;
        text-transform: uppercase;
        padding: 3px 8px;
        border-radius: 999px;
        background: rgba(32, 107, 196, .15);
        color: #206bc4;
        white-space: nowrap;
    }

    .playlist-section-title {
        flex: 1;
        min-width: 0;
        font-size: 13px;
        font-weight: 500;
        line-height: 1.4;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    /* ======================================================
       SORTABLEJS STATES
    ====================================================== */
    .playlist-sortable-ghost {
        opacity: .35 !important;
        border: 2px dashed var(--tblr-primary, #206bc4) !important;
    }
    .playlist-sortable-chosen {
        background: rgba(32, 107, 196, .06) !important;
    }
    .playlist-sortable-drag { opacity: .9; }

    /* ======================================================
       COUNT LABEL
    ====================================================== */
    .content-select-count {
        font-size: 12px;
        color: var(--tblr-muted);
    }

    /* ======================================================
       DARK MODE
    ====================================================== */
    [data-bs-theme="dark"] .ts-dropdown,
    [data-theme="dark"] .ts-dropdown,
    body.theme-dark .ts-dropdown {
        background: #182433 !important;
        border-color: #25384d !important;
        color: #dce6f1 !important;
        box-shadow: 0 8px 24px rgba(0,0,0,.35) !important;
    }
    [data-bs-theme="dark"] .ts-dropdown .option.active,
    [data-bs-theme="dark"] .ts-dropdown .option:hover,
    [data-theme="dark"] .ts-dropdown .option.active,
    [data-theme="dark"] .ts-dropdown .option:hover,
    body.theme-dark .ts-dropdown .option.active,
    body.theme-dark .ts-dropdown .option:hover {
        background: #223247 !important;
        color: #fff !important;
    }
    [data-bs-theme="dark"] .ts-wrapper .ts-control,
    [data-theme="dark"] .ts-wrapper .ts-control,
    body.theme-dark .ts-wrapper .ts-control {
        background: #1f2937 !important;
        border-color: rgba(255,255,255,.14) !important;
        color: #e5e7eb !important;
    }
    [data-bs-theme="dark"] .ts-wrapper.focus .ts-control,
    [data-theme="dark"] .ts-wrapper.focus .ts-control,
    body.theme-dark .ts-wrapper.focus .ts-control {
        border-color: var(--tblr-primary, #206bc4) !important;
        box-shadow: 0 0 0 0.25rem rgba(32, 107, 196, .25) !important;
    }
    [data-bs-theme="dark"] .ts-control input,
    [data-theme="dark"] .ts-control input,
    body.theme-dark .ts-control input { color: #e5e7eb !important; caret-color: #e5e7eb; }
    [data-bs-theme="dark"] .playlist-ts-wrapper .ts-control .item,
    [data-theme="dark"] .playlist-ts-wrapper .ts-control .item,
    body.theme-dark .playlist-ts-wrapper .ts-control .item {
        background: #1a3a5c !important;
        color: #a8c8ea !important;
        border-color: #25384d !important;
    }
    [data-bs-theme="dark"] .playlist-ts-wrapper .ts-control .item:hover,
    [data-theme="dark"] .playlist-ts-wrapper .ts-control .item:hover,
    body.theme-dark .playlist-ts-wrapper .ts-control .item:hover {
        background: #223a60 !important;
    }
    [data-bs-theme="dark"] .playlist-section-badge,
    [data-theme="dark"] .playlist-section-badge,
    body.theme-dark .playlist-section-badge {
        background: rgba(32, 107, 196, .28);
        color: #8ab7ff;
    }
    [data-bs-theme="dark"] .playlist-drag-handle,
    [data-theme="dark"] .playlist-drag-handle,
    body.theme-dark .playlist-drag-handle { color: #5a7fa0; }
    [data-bs-theme="dark"] .playlist-ts-wrapper .ts-control .item .remove,
    [data-theme="dark"] .playlist-ts-wrapper .ts-control .item .remove,
    body.theme-dark .playlist-ts-wrapper .ts-control .item .remove { color: #8fa3b8 !important; }
    [data-bs-theme="dark"] .playlist-ts-wrapper .ts-control .item .remove:hover,
    [data-theme="dark"] .playlist-ts-wrapper .ts-control .item .remove:hover,
    body.theme-dark .playlist-ts-wrapper .ts-control .item .remove:hover { color: #ff6b6b !important; }
    [data-bs-theme="dark"] .playlist-sortable-ghost,
    [data-theme="dark"] .playlist-sortable-ghost,
    body.theme-dark .playlist-sortable-ghost {
        background: #1a3a5c !important;
        border-color: #5592d4 !important;
    }
    [data-bs-theme="dark"] .ts-dropdown .no-results,
    [data-theme="dark"] .ts-dropdown .no-results,
    body.theme-dark .ts-dropdown .no-results { color: #8fa3b8 !important; }
    [data-bs-theme="dark"] .ts-dropdown-content::-webkit-scrollbar,
    [data-theme="dark"] .ts-dropdown-content::-webkit-scrollbar,
    body.theme-dark .ts-dropdown-content::-webkit-scrollbar { width: 6px; }
    [data-bs-theme="dark"] .ts-dropdown-content::-webkit-scrollbar-track,
    [data-theme="dark"] .ts-dropdown-content::-webkit-scrollbar-track,
    body.theme-dark .ts-dropdown-content::-webkit-scrollbar-track { background: #182433; }
    [data-bs-theme="dark"] .ts-dropdown-content::-webkit-scrollbar-thumb,
    [data-theme="dark"] .ts-dropdown-content::-webkit-scrollbar-thumb,
    body.theme-dark .ts-dropdown-content::-webkit-scrollbar-thumb {
        background: #33465f;
        border-radius: 3px;
    }
</style>
@endonce